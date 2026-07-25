# Rediseño del Orden de mérito y Ranking por sección — DISEÑO (plan aprobado 25/07/2026)

> Documento de DISEÑO. Plan aprobado por el usuario; **aún NO implementado**. El
> estado actual del módulo vive en `orden-merito.md`; este archivo describe hacia
> dónde vamos y con qué cambios quirúrgicos. Al implementar cada fase se actualiza
> `orden-merito.md` (estado) y `ESTADO.md` (avance con fecha).
>
> Regla transversal: **cambios mínimos y localizados**. Reutilizar lo existente
> (`calcularFilasRanking`, `aplicarDesempate`, la compuerta `periodos_publicacion`).
> NO romper: B1 ya congelado (528, caso especial), la Fase A (filtro por tipo), el
> candado 046, ni la boleta/SIAGIE.

## 1. Objetivo

Convertir el orden de mérito en un documento con un **flujo de oficialización explícito**
(separado del cierre) y **publicación unificada con las boletas**, con reglas de
comparación más justas (solo notas bloqueadas, alerta de evaluación incompleta) y
Ética y Valores incorporada al promedio en secundaria.

## 2. Decisiones aprobadas (25/07/2026)

| # | Decisión | Detalle |
|---|---|---|
| P1 | **Cascada de desempate** | `promedio_exacto → menos C → menos B → más AD → más 15-16 → más 16 → DESEMPATE MANUAL`. Se quita el apellido; orden de presentación estable por `matricula_id`. |
| P2 | **Cálculo en vivo** | Solo cuenta calificaciones **bloqueadas** (`bloqueos_competencia`, origen `docente` **o** `cierre`). |
| P3 | **Flujo oficialización + publicación** | Cerrar ya NO congela mérito. Nueva acción **"Aprobar y bloquear el orden de mérito" por NIVEL** (congela snapshot). **Publicar** (por nivel) libera **boletas + mérito juntos**. Acciones separadas. Familias ven el **ranking completo del grado**. Reapertura → rectificado. |
| P4 | **Alerta de N desigual** | Alerta por alumno con menos competencias que la **moda de su sección**, tras descontar exoneraciones. Bloquea oficializar/publicar. Diferencias **entre secciones** (primaria) se **aceptan**. |
| P5 | **Ética y Valores** | Entra al promedio del mérito en **secundaria** (reemplaza a Ed. Religiosa; excepción para C57/área 24). Exonerados compiten con N reducido, sin alerta. Solo mérito. |

El **roster general NO cambia**: sigue `tipo NOT IN ('trasladado','retirado')` + anclaje
de retornos (Fase A). B1 permanece congelado como caso especial.

## 3. Diseño técnico por decisión

### P1 — Cascada de desempate (quirúrgico)
- **`OrdenMeritoModel::rankingGradoLive` / `rankingPorSeccionLive`**: en el `ORDER BY`,
  reemplazar `p.apellido_paterno, p.apellido_materno, p.nombres` por `m.id`.
- `aplicarDesempate` / `tuplaLiteral` **no cambian**: la irreducibilidad ya se decide con
  los 5 conteos (C, B, AD, alto, 16); tras `num_16` ya cae a manual. El apellido hoy solo
  ordena la presentación → pasa a `matricula_id` (neutro, determinista).
- Snapshot congelado no se ve afectado (guarda puestos ya resueltos).

### P2 — Cálculo en vivo = solo bloqueadas
- Añadir a `rankingGradoLive`, `rankingPorSeccionLive` y `calcularFilasRanking`
  (vía sus dos sub-queries) el join:
  ```sql
  INNER JOIN bloqueos_competencia bc
    ON bc.carga_id = cal.carga_id AND bc.competencia_id = cal.competencia_id
   AND bc.periodo_id = cal.periodo_id
  ```
  Sin filtrar `bc.origen` (cuenta docente y cierre).
- Revisar `gradosConRanking` (rama viva) y `gradosConEmpatesPendientes`: mismo criterio,
  para que enumerar grados y detectar empates use el mismo universo que el ranking.
- Efecto: en bimestre **activo**, el ranking en vivo solo refleja lo ya bloqueado
  (provisional sobre lo confirmado). En bimestre cerrado no cambia (todo bloqueado).

### P5 — Ética y Valores en el promedio (secundaria)
- El filtro `a.tipo NOT IN ('transversal','tutoria')` excluye el área 24 (Ética, C57).
  Cambiar a: excluir transversales siempre, y excluir tutoría **salvo** el área de Ética.
  Punto único: definir la excepción en un solo lugar (constante/helper, p. ej.
  `AREA_ETICA_SECUNDARIA = 24`) usada por las 3 queries + los conteos.
- La Tutoría TOE de primaria (área 23, 0 competencias) no aporta; las transversales
  siguen fuera. Solo C57 entra.
- **Exonerados**: su exoneración (tabla `exoneraciones`, por área) ya les quita esa área
  del roster de notas → promedio sobre lo evaluado, sin alerta (ver P4).
- **No toca** boleta ni SIAGIE (Ética ya sale ahí por su propio camino).

### P4 — Alerta de evaluación incompleta
- **Definición:** para cada alumno del roster, `N_alumno` = competencias con nota
  bloqueada; `N_seccion` = moda (o máximo) de competencias evaluadas en su **sección**;
  `exoneradas_alumno` = competencias de áreas exoneradas del alumno. Hay alerta si
  `N_alumno < N_seccion - exoneradas_alumno` (le faltan notas NO justificadas por exoneración).
- **Comparación por SECCIÓN, no por grado** (respeta P4a: las secciones difieren
  legítimamente por autonomía docente).
- **Nuevo método** `OrdenMeritoModel::alertasEvaluacionIncompleta(int $periodoId, ?int $nivelId)`
  → lista de alumnos en alerta (matricula, sección, N, faltantes). Reutilizable por el
  Centro de control.
- **Chequeo en `ControlOperativoController::index`**: nuevo item junto a "empates" y
  "competencias sin bloquear".

### P3 — Flujo de oficialización + publicación unificada (núcleo)

**Modelo de datos nuevo (migración):** tabla `orden_merito_aprobacion`, análoga a
`periodos_publicacion` (patrón probado):
```
id, periodo_id, nivel_id,
aprobado_en DATETIME NULL, aprobado_por INT NULL,
primera_aprobacion_en DATETIME NULL,   -- sello MONOTÓNICO (candado inmutabilidad)
suspendida_en DATETIME NULL,           -- reapertura suspende; re-aprobar restaura
UNIQUE (periodo_id, nivel_id)
```

**Punto único** de la tabla: nuevo `OrdenMeritoAprobacionModel` (como
`PublicacionBoletaModel`). Métodos: `aprobar(periodo,nivel,usuario)`,
`fueAprobado(periodo,nivel)` (monotónico), `estadoPorNivel(periodo)`,
`suspenderPorReapertura(periodo)`, `restaurarPorCierre(periodo)`.

**Parametrizar el snapshot por NIVEL:**
- `calcularFilasRanking(int $periodoId, ?int $nivelId = null)` — filtra grados del nivel.
- `generarSnapshot` / `generarSnapshotRectificado` / `registrarRanking` reciben `?nivelId`
  y borran/insertan solo los grados de ese nivel (con `nivelId = null` = comportamiento
  actual, para no romper el backfill ni B1).

**Cambios de flujo:**
- `PeriodoController::cerrar`: **quitar** la llamada a `registrarRanking` (cerrar ya no
  congela mérito). Mantener bloqueo de competencias, boletas, `restaurarPorCierre`.
  Añadir `OrdenMeritoAprobacionModel::restaurarPorCierre`.
- `PeriodoController::reabrir`: añadir `suspenderPorReapertura` del mérito (junto a la
  suspensión de publicación que ya hace).
- **Nueva acción** `ControlOperativoController::aprobarMerito(periodoId)` (POST, por
  nivel via `nivel_id`): valida rol (RA/admin), bimestre cerrado, y **0 incidencias**
  (empates + competencias sin bloquear + alertas de N del nivel). Llama
  `registrarRanking(periodoId, usuario, 'Aprobación de orden de mérito', nivelId)` y
  `OrdenMeritoAprobacionModel::aprobar`. El candado 046 pasa a mirar `fueAprobado`
  (por nivel) en vez de `fuePublicado`.
- **Candado 046 re-anclado:** `registrarRanking` decide oficial vs rectificado con
  `fueMeritoAprobado(periodo, nivel)` (el mérito ya oficializado no se sobrescribe;
  recálculos posteriores → rectificado).

**Publicación unificada:**
- `ControlOperativoController::publicar` / `programar`: **exigir** que el nivel tenga el
  mérito aprobado (`fueAprobado`) antes de publicar. Mantener `PublicacionBoletaModel`
  (boletas) y sumar la visibilidad del mérito bajo la misma compuerta.
- **Docente/claustro** (`Docente\OrdenMeritoController`): el gate pasa de
  `estado='cerrado'` a **"periodo publicado para el nivel"** (usar `periodosPublicados`).
- **Familias:** nueva superficie `/padre/orden-merito` que muestra el **ranking completo
  del grado** del hijo, solo si el periodo está publicado para su nivel (reusa
  `periodosPublicados` + `rankingGrado`). Enlace desde `/padre/notas`.

## 4. Detalles finos (mi recomendación — CONFIRMAR)

1. **Resolver alerta de N de un alumno que ya no asiste** (trasladado/reincorporado con
   notas parciales): el docente no puede completarle notas. **Propuesta:** permitir
   "aceptar con motivo" (marca auditada que satisface la alerta) — no lo excluye del
   mérito (compite con su N real). Los que SÍ asisten se resuelven completando notas.
2. **Punto de bloqueo de la alerta:** bloqueo **duro en "Aprobar y bloquear mérito"**
   (Fase 3, cuando todo está bloqueado) + **advertencia visible** en el Centro de control
   desde el cierre. Evita la tensión de validar N antes de que el cierre complete el bloqueo.
3. **Vista de familias:** ranking **por grado** (el que define media beca). El ranking por
   sección queda para el claustro. Página propia `/padre/orden-merito` enlazada desde
   `/padre/notas` (no dentro de la boleta).
4. **Ética técnica:** excepción por área (`AREA_ETICA_SECUNDARIA`), no por `tipo`, para no
   colar Tutoría TOE. Candado 046 re-anclado a `fueMeritoAprobado`.

## 5. Plan de fases (cada una verificable y aislada)

- **Fase 1 — Cascada + cálculo en vivo (P1, P2).** Solo `OrdenMeritoModel` (ORDER BY +
  join bloqueos). Verificar con scripts en `database/verificaciones/` (B1 sin cambios de
  puestos; en vivo cuenta solo bloqueadas). Bajo riesgo.
- **Fase 2 — Ética en el mérito (P5).** Excepción de filtro (área 24/C57) en las 3 queries
  + conteos. Verificar N y promedios de secundaria con/sin Ética; exonerados sin alerta.
- **Fase 3 — Alerta de N (P4).** `alertasEvaluacionIncompleta` + chequeo en Centro de
  control + "aceptar con motivo". Sin bloqueo aún.
- **Fase 4 — Migración + oficialización por nivel (P3 núcleo).** Tabla
  `orden_merito_aprobacion` + modelo; `calcularFilasRanking`/`registrarRanking` por nivel;
  quitar congelado del cierre; acción "Aprobar y bloquear mérito"; candado re-anclado.
- **Fase 5 — Publicación unificada (P3).** Publicar exige mérito aprobado; gate del
  claustro pasa a "publicado".
- **Fase 6 — Vista de familias (P3).** `/padre/orden-merito` (ranking completo del grado)
  bajo la compuerta de publicación.

Dependencias: F4 depende de F3 (alerta es prerrequisito de aprobar); F5 depende de F4;
F6 depende de F5. F1, F2 son independientes y de menor riesgo → primero.

## 6. Invariantes nuevos (a agregar a CLAUDE.md cuando se implemente)

- **Cerrar un bimestre NO oficializa el orden de mérito.** Oficializar es un acto
  separado, por NIVEL, en el Centro de control, que exige 0 desempates + 0 competencias
  sin bloquear + 0 alertas de N. Punto único: `OrdenMeritoAprobacionModel`.
- **Publicar libera boletas Y orden de mérito juntos**, por nivel; exige el mérito del
  nivel aprobado. El claustro y las familias ven el mérito solo cuando está publicado.
- **El candado de inmutabilidad del mérito se dispara al APROBAR/BLOQUEAR** (no al
  publicar): `fueMeritoAprobado` monotónico. Reapertura → versión rectificada.
- **Mérito en vivo cuenta solo competencias bloqueadas.** Ética (C57/área 24) cuenta en
  secundaria; exonerados compiten con su N reducido sin alerta.

## 7. Riesgos y compatibilidad
- **B1 congelado (528):** parametrizar por nivel con `nivelId=null` conserva el
  comportamiento actual; el snapshot de B1 no se toca. NO correr `backfill` en prod.
- **Candado re-anclado:** migrar de `fuePublicado` a `fueMeritoAprobado` debe cubrir el
  caso de bimestres ya publicados sin la nueva tabla (backfill de aprobación para lo ya
  oficial, o fallback a `fuePublicado`). A definir en la Fase 4.
- **Orden de bloqueo de competencias vs alerta:** la alerta se evalúa sobre lo bloqueado;
  por eso el bloqueo duro va en "aprobar mérito", no en el cierre.
