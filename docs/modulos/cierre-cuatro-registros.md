# Plan — Los cuatro registros del bimestre y el contrato del cierre

> **Estado: PLAN APROBADO EN SUS DECISIONES, SIN IMPLEMENTAR.** Redactado el
> **04/08/2026**. Se ejecuta **después de cerrar y publicar el II Bimestre**, para que
> el primer bimestre bajo las reglas nuevas sea el **III**.
> Estado vivo: `docs/ESTADO.md`. Runbook del cierre: `docs/runbooks/cierre-de-bimestre.md`.

## 1. De dónde sale este plan

El usuario planteó la regla del colegio:

> *"El registro de asistencia tiene el mismo rango que el registro de competencias
> transversales, competencias académicas y conducta; estos 4 registros deben estar
> correctamente aprobados y bloqueados para luego recién proceder a cerrar el bimestre."*

Al verificarla contra el código aparecieron tres cosas:

1. La premisa de partida estaba **invertida**: el registro de asistencia no exige el
   bimestre cerrado, exige que esté **abierto** y dentro de `limite_notas`
   (`AsistenciaModel::periodoEditable`). Cerrar lo deja en solo lectura para siempre.
2. Lo que realmente ocurría: **`limite_notas` de B2 venció el 04/08 a las 04:00**, así
   que la captura se cerró sola con el bimestre todavía `activo`.
3. **El hallazgo grave:** B2 tenía **0 filas en `inasistencias` y 0 secciones
   bloqueadas** (B1: 528 y 23). Cerrar y publicar así habría mandado a las familias
   asistencia en ceros — un dato **falso**, no ausente.

> ⚠️ **La corrección de B2 NO es parte de este plan.** Se resuelve a mano ahora
> (ampliar `limite_notas` → registrar las 23 secciones → bloquearlas), antes de la
> Fase 4 del runbook. Este documento es para que no vuelva a pasar.

## 2. Estado actual medido (04/08/2026)

La política que el colegio describe como **una** hoy está implementada **cuatro veces,
en tres regímenes distintos**:

| Registro | Compuerta temporal | ¿El cierre la exige? | ¿El cierre la fuerza? |
|---|---|---|---|
| Competencias académicas | `CalificacionModel::periodoEstaBloqueado` — cerrado **o** `limite_notas` vencido | no | **sí** (`bloquearCompetenciasPendientes`) |
| Competencias transversales | **ninguna** — `TutoriaController` no consulta fecha ni estado | no | **sí** (`crearCierresTransversalesPendientes`) |
| Conducta | `ConductaModel` (≈`:703`) — `activo` **y** dentro de `limite_notas` | **no** | **no** |
| Asistencia | `AsistenciaModel::periodoEditable` (`:419`) — `activo` **y** dentro de `limite_notas` | **no** | **no** |

Dos consecuencias:

- **Conducta y asistencia están fuera del contrato del cierre**: pueden quedarse sin
  bloquear indefinidamente y el bimestre cierra igual.
- Los dos regímenes con fecha **no coinciden entre sí**: ante un periodo `pendiente`,
  `periodoEstaBloqueado` responde "no bloqueado" y `periodoEditable` responde
  "no editable".

## 3. Decisiones cerradas (usuario, 04/08/2026 — no re-preguntar)

| # | Decisión | Elegido |
|---|---|---|
| D1 | Cierre con conducta/asistencia sin bloquear | **EXIGIR: abortar el cierre** y listar lo que falta |
| D2 | Fecha límite | **ÚNICA para los 4 registros**, lectura unificada en un punto único. **Sin migración** |
| D3 | Transversales | **Sí, igualarla**: pasa a respetar la compuerta temporal |
| D4 | Bloquear una sección de asistencia sin ninguna fila | **Rechazar con aviso** |

Notas de alcance derivadas:

- D1 aplica **solo a conducta y asistencia**. Académicas y transversales **siguen
  forzándose** en el cierre — es la válvula de escape para el docente que nunca bloquea,
  y no se toca.
- D4 se pide **solo para asistencia**. Conducta queda como está (su vacío no produce un
  cero engañoso, sino una casilla vacía).

## 4. Diseño

### F1 — Punto único de la compuerta temporal

Hoy la regla "¿se puede editar este periodo?" está escrita **cuatro veces**: dos en PHP
(`CalificacionModel:288`, `AsistenciaModel:419`, `ConductaModel:703`) y dos como columna
calculada en SQL (`AsistenciaModel:50`, `ConductaModel:53`).

**Propuesta:** un modelo dedicado, siguiendo el idioma ya establecido por
`PublicacionBoletaModel` (punto único de la compuerta 044):

```
app/Models/EdicionPeriodoModel.php
  esEditable(int $periodoId): bool
  motivoNoEditable(int $periodoId): ?string   // 'cerrado' | 'plazo_vencido' | 'no_iniciado'
```

- Los 4 modelos pasan a delegar. **No se replica la regla en SQL**: las columnas
  calculadas `editable` de `listarPeriodosActivos` se sustituyen por post-proceso en PHP
  sobre el mismo resultado, para que exista **una sola implementación**.
- `motivoNoEditable` existe para que la UI diga *por qué* no se puede escribir. El
  incidente de B2 fue difícil de diagnosticar precisamente porque la pantalla no
  distinguía "bimestre cerrado" de "plazo vencido".

> ⚠️ **Riesgo a validar antes de tocar académicas:** unificar significa elegir una
> semántica para los periodos `pendiente`. La correcta es **no editable** (aún no
> empieza), que es lo que ya hacen conducta y asistencia; adoptarla cambia el
> comportamiento actual de **académicas**. Antes de implementar, comprobar que ningún
> flujo docente dependa de escribir en un bimestre `pendiente`.

### F2 — Guard del cierre (D1)

En `Director\PeriodoController::cerrar`, un **tercer guard** junto a los de empates y
evaluación incompleta, y **antes** de abrir la transacción:

- Fuente de datos: `ConductaModel::getResumenSeccionesPorPeriodo($id)` y
  `AsistenciaModel::getResumenSeccionesPorPeriodo($id)` — **ya existen y son simétricos**;
  ambos devuelven `cierre_id` (null = sin bloquear). No hay que escribir SQL nuevo.
- Si alguna sección tiene `cierre_id IS NULL`, abortar nombrando **registro + secciones**
  (no un conteo suelto: el mensaje tiene que decir a dónde ir).

> ⚠️ **Asimetría del universo de secciones — resolver antes de implementar.** Los dos
> resúmenes no miran el mismo conjunto: conducta exige `s.tutor_id IS NOT NULL`;
> asistencia exige `s.estado_nomina = 'aprobada'`. Una sección sin tutor sería
> **invisible** para el guard de conducta. Hay que decidir cuál es el universo canónico
> del cierre y aplicarlo a ambos.

> ❓ **Pregunta abierta para el usuario:** conducta tiene **dos etapas**
> (`ra_bloqueado_en` y `tutor_cerrado_en`). ¿El guard exige solo que exista el cierre, o
> exige además la etapa del tutor? No se asume: se pregunta al implementar.

### F3 — Guard de sección vacía (D4)

En `AsistenciaModel::bloquearRA`, antes del INSERT: si la sección no tiene ninguna fila
en `inasistencias` para ese periodo, rechazar con
*"Esta sección no tiene ninguna incidencia registrada. Si de verdad no hubo faltas ni
tardanzas, guarda las filas en cero y vuelve a bloquear."*

El progreso ya se calcula (`getProgresoPorSeccion` → `registrados`), así que el guard
reusa esa cuenta.

**Este es el guard que habría evitado el incidente de B2.**

### F4 — Transversales bajo la compuerta (D3)

`Docente\TutoriaController` pasa a consultar `EdicionPeriodoModel` en sus endpoints de
escritura, igual que los otros tres.

> ⚠️ **Requiere aviso previo a los tutores.** Hoy registran transversales sin límite de
> fecha; ponerles uno sin avisar los sorprende a mitad de bimestre. **Coordinar la
> comunicación antes de desplegar esta fase** — es la única del plan con impacto directo
> en el trabajo diario de un docente.

## 5. Orden de ejecución

`F1` → `F3` → `F2` → `F4`.

- **F1 primero**: las demás se apoyan en el punto único.
- **F3 antes que F2**: sin el guard de sección vacía, el guard del cierre se puede
  satisfacer bloqueando secciones vacías — se cumpliría la forma y no el fondo.
- **F4 al final**: es la única que depende de una comunicación externa.

## 6. Qué NO hace este plan

- **No cambia `limite_notas` a varias fechas** (D2): sigue siendo una sola. Sin migración.
- **No convierte académicas/transversales en "exigir"**: se siguen forzando.
- **No valida retroactivamente.** B1 y B2 cerraron (o cerrarán) bajo las reglas viejas;
  el primer bimestre con el contrato nuevo es **B3**. No se reabre nada.
- **No toca el orden de mérito, la boleta ni la compuerta de publicación.**

## 7. Verificación

- Script en `database/verificaciones/` que, para un periodo dado, liste las secciones sin
  bloqueo de cada uno de los 4 registros — el mismo dato que verá el guard. Solo lectura.
- Prueba del guard del cierre en local con una sección desbloqueada a propósito, dentro
  de **transacción con ROLLBACK** (regla del repo: ningún script de `database/` borra lo
  que no creó).
- Comprobar que `EdicionPeriodoModel` devuelve lo mismo que las 4 implementaciones
  actuales para los periodos 1-4 **antes** de borrarlas — es un refactor de
  equivalencia, igual que el de la card de empates.

## 8. Por qué importa más allá del síntoma

Es el **mismo patrón** que produjo los empates fantasma del Centro de Control: una regla
de negocio copiada en varios módulos que se desincroniza en silencio. Ahí el resultado
fue una card que no se limpiaba nunca; aquí, un registro oficial vacío que iba a llegar
a las familias como ceros. En los dos casos el síntoma no es un error visible sino **un
dato creíble y falso**, que es el modo de fallo más caro de este sistema.

Ver `docs/modulos/orden-merito.md` §"PUNTO ÚNICO de qué empates faltan" y
`docs/modulos/admin.md`.
