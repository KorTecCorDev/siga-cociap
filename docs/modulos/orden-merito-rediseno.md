# Rediseño del Orden de mérito y Ranking por sección — DISEÑO (plan aprobado 25/07/2026)

> Documento de DISEÑO. Plan aprobado por el usuario; **aún NO implementado**. El
> estado actual del módulo vive en `orden-merito.md`; este archivo describe hacia
> dónde vamos y con qué cambios quirúrgicos. Al implementar cada fase se actualiza
> `orden-merito.md` (estado) y `ESTADO.md` (avance con fecha).
>
> Regla transversal: **cambios mínimos y localizados**. Reutilizar lo existente
> (`calcularFilasRanking`, `aplicarDesempate`, el cierre que ya congela el snapshot,
> la compuerta `periodos_publicacion`, `omisiones_criterio`). NO romper: B1 ya
> congelado (528, caso especial), la Fase A (filtro por tipo), el candado 046, ni la
> boleta/SIAGIE.

## 1. Objetivo

Reglas de comparación más justas (solo notas bloqueadas, alerta de evaluación
incompleta con gestión de motivos), Ética y Valores en el promedio de secundaria, y
**publicación unificada de boletas + orden de mérito** por nivel. El cierre pasa a
exigir que todo esté resuelto y, como hoy, oficializa (congela) el mérito.

## 2. Decisiones aprobadas (25/07/2026)

| # | Decisión | Detalle |
|---|---|---|
| P1 | **Cascada de desempate** | `promedio_exacto → menos C → menos B → más AD → más 15-16 → más 16 → DESEMPATE MANUAL`. Se quita el apellido; orden de presentación estable por `matricula_id`. |
| P2 | **Cálculo en vivo** | Solo cuenta calificaciones **bloqueadas** (`bloqueos_competencia`, origen `docente` **o** `cierre`). |
| P3 | **Cierre + publicación** | **Cerrar OFICIALIZA** el mérito (congela snapshot, como hoy) pero exige antes: 0 desempates + 0 competencias sin bloquear + 0 alertas de N. **Publicar** (por nivel, fecha/hora) libera **boletas + mérito juntos**. Familias ven **orden de mérito (grado) + ranking por sección**. Reapertura → rectificado. |
| P4 | **Alerta de N desigual** | Alerta por alumno con menos competencias que la **moda de su sección**, tras descontar exoneraciones. Detalla **qué competencias y criterios** están en blanco; se resuelve **registrando el motivo** de cada blanco. Bloquea el **cierre**. Diferencias **entre secciones** (primaria) se **aceptan**. |
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
- El snapshot congelado no se ve afectado (guarda puestos ya resueltos).

### P2 — Cálculo en vivo = solo bloqueadas
- Añadir a `rankingGradoLive`, `rankingPorSeccionLive` y `calcularFilasRanking`
  (vía sus dos sub-queries) el join:
  ```sql
  INNER JOIN bloqueos_competencia bc
    ON bc.carga_id = cal.carga_id AND bc.competencia_id = cal.competencia_id
   AND bc.periodo_id = cal.periodo_id
  ```
  Sin filtrar `bc.origen` (cuenta docente y cierre).
- Alinear `gradosConRanking` (rama viva) y `gradosConEmpatesPendientes` al mismo universo.
- Efecto: en bimestre **activo** el ranking en vivo solo refleja lo ya bloqueado
  (provisional sobre lo confirmado). En cerrado no cambia (todo bloqueado).

### P5 — Ética y Valores en el promedio (secundaria)
- El filtro `a.tipo NOT IN ('transversal','tutoria')` excluye el área 24 (Ética, C57).
  Cambiar a: excluir transversales siempre, y excluir tutoría **salvo** el área de Ética.
  Punto único: constante/helper `AREA_ETICA_SECUNDARIA = 24` usada por las 3 queries del
  ranking + `getConteosGrado`.
- La Tutoría TOE de primaria (área 23, 0 competencias) no aporta; las transversales
  siguen fuera. Solo C57 entra.
- **Exonerados** (tabla `exoneraciones`, por área): su exoneración ya les quita esa área
  del roster de notas → promedio sobre lo evaluado, sin alerta (P4).
- **No toca** boleta ni SIAGIE (Ética ya sale ahí por su propio camino).

### P4 — Alerta de evaluación incompleta (con gestión de motivos)
- **Definición:** para cada alumno del roster, `N_alumno` = competencias con nota
  bloqueada; `N_seccion` = moda de competencias evaluadas en su **sección**;
  `exoneradas_alumno` = competencias de áreas exoneradas. Hay alerta si
  `N_alumno < N_seccion - exoneradas_alumno` (le faltan notas NO justificadas por exoneración).
- **Comparación por SECCIÓN** (respeta P4a: las secciones difieren legítimamente).
- **Detalle:** la alerta lista **qué competencias y qué criterios** están en blanco por
  alumno (no solo el conteo).
- **Resolución = registrar el motivo** de cada blanco, reutilizando **`omisiones_criterio`**
  (enum `ausencia_injustificada/justificada/abandono/no_aplico`). La alerta se satisface
  cuando todo blanco tiene motivo. *A decidir en la fase:* si una competencia entera sin
  ningún criterio evaluado se cubre marcando cada criterio, o requiere un motivo a nivel
  competencia (posible migración menor).
- **Nuevo método** `OrdenMeritoModel::alertasEvaluacionIncompleta(int $periodoId, ?int $nivelId)`
  → alumnos en alerta con su detalle de competencias/criterios en blanco sin motivo.
- **Chequeo en `ControlOperativoController::index`**: nuevo item junto a "empates" y
  "competencias sin bloquear", con enlace a la gestión de motivos.

### P3 — Cierre que oficializa + publicación unificada

**Cierre (global) — refuerzo de prerequisitos.** `PeriodoController::cerrar` **mantiene**
su `registrarRanking` (ya congela el snapshot). Se **añade** a su validación previa
(donde hoy corre `gradosConEmpatesPendientes`):
- competencias sin bloquear = 0 (reusar `ControlOperativoModel::competenciasSinBloquear`),
- alertas de N sin motivo = 0 (`alertasEvaluacionIncompleta`).
Si algo falta, aborta el cierre con el detalle. **No hay tabla ni acción de "aprobar
mérito" separada: el cierre la absorbe.** El candado 046 **no cambia** (sigue con
`fuePublicado`; reabrir tras publicar → rectificado; reabrir antes de publicar → se
regenera el oficial al re-cerrar, como hoy).

**Publicación unificada (por nivel).** La compuerta `periodos_publicacion` (044) pasa a
gobernar la visibilidad de **boletas Y orden de mérito** juntas:
- **`ControlOperativoController::publicar/programar/despublicar`**: sin cambios de
  mecánica; el mismo acto ahora también gobierna el mérito (no requiere código nuevo en
  el modelo de publicación, solo que los lectores del mérito consulten la compuerta).
- **Docente/claustro** (`Docente\OrdenMeritoController`): el gate pasa de
  `estado='cerrado'` a **"periodo publicado para el nivel"** (`periodosPublicados`).
- **Familias:** nuevas superficies `/padre/orden-merito` (ranking por **grado**) y
  `/padre/ranking-seccion` (por **sección**), visibles solo si el periodo está publicado
  para el nivel del hijo. Reusan `rankingGrado`/`rankingPorSeccion` + `periodosPublicados`.
  Enlace desde `/padre/notas`.

**El snapshot se genera GLOBAL al cerrar** (como hoy); la compuerta **por nivel** filtra
solo la *visibilidad*. No hace falta parametrizar `generarSnapshot` por nivel.

## 4. Detalles finos — RESUELTOS (25/07/2026)

1. **Alerta de N:** detalla competencias/criterios en blanco + **gestión de motivos** vía
   `omisiones_criterio`. (Reemplaza el "aceptar con motivo" a nivel alumno.)
2. **Punto de bloqueo:** las alertas (y desempates y bloqueos) se resuelven **antes de
   CERRAR**; el cierre aborta si algo falta. Cerrar oficializa el mérito.
3. **Vista de familias:** **orden de mérito (grado) + ranking por sección**, páginas
   propias bajo `/padre`, enlazadas desde `/padre/notas`, bajo la compuerta de publicación.
4. **Ética técnica:** excepción por área (`AREA_ETICA_SECUNDARIA=24`), no por `tipo`.
   Candado 046 sin cambios.

## 5. Plan de fases (cada una verificable y aislada)

- **Fase 1 — Cascada + cálculo en vivo (P1, P2).** Solo `OrdenMeritoModel` (ORDER BY +
  join bloqueos). Verificar con scripts en `database/verificaciones/` (B1 sin cambios de
  puestos; en vivo cuenta solo bloqueadas). Bajo riesgo.
- **Fase 2 — Ética en el mérito (P5).** Excepción de filtro (área 24/C57) en las 3 queries
  + `getConteosGrado`. Verificar N y promedios de secundaria; exonerados sin alerta.
- **Fase 3 — Alerta de N + gestión de motivos (P4).** `alertasEvaluacionIncompleta` con
  detalle competencia/criterio + UI de motivos (`omisiones_criterio`) + chequeo en Centro
  de control. Decidir aquí si hace falta motivo a nivel competencia (posible migración menor).
- **Fase 4 — Cierre reforzado (P3, parte 1).** `PeriodoController::cerrar` valida alertas
  de N + competencias sin bloquear (además de desempates) antes de congelar. Sin migración.
- **Fase 5 — Publicación unificada (P3, parte 2).** El mérito se vuelve visible bajo la
  compuerta 044; gate del claustro pasa a "publicado".
- **Fase 6 — Vista de familias (P3, parte 3).** `/padre/orden-merito` + `/padre/ranking-seccion`
  (ranking del grado y por sección) bajo la compuerta.

Dependencias: F4 depende de F3 (la alerta es prerrequisito del cierre); F5 y F6 usan el
snapshot ya existente y la compuerta. F1, F2 son independientes y de menor riesgo → primero.

## 6. Invariantes nuevos (a agregar a CLAUDE.md al implementar)

- **Cerrar exige orden de mérito íntegro:** 0 desempates + 0 competencias sin bloquear +
  0 alertas de N. El cierre **oficializa** (congela) el snapshot; no hay acto separado.
- **Publicar libera boletas Y orden de mérito juntos**, por nivel, bajo la compuerta 044.
  Claustro y familias ven el mérito solo cuando está publicado para su nivel.
- **Mérito en vivo cuenta solo competencias bloqueadas.** Ética (C57/área 24) cuenta en
  secundaria; exonerados compiten con su N reducido sin alerta.
- **Alerta de N = evaluación incompleta por sección** (menos competencias que la moda,
  descontando exoneradas), resuelta registrando el motivo de cada blanco (`omisiones_criterio`).

## 7. Riesgos y compatibilidad
- **B1 congelado (528):** el cierre no se re-ejecuta sobre B1; su snapshot no se toca. NO
  correr `backfill` en prod (revertiría a 519).
- **Candado 046 intacto:** al no separar la aprobación, no hay que re-anclarlo; sigue con
  `fuePublicado`. Menos superficie de cambio, menos riesgo.
- **Orden de bloqueo vs alerta:** el cierre fuerza el bloqueo de competencias pendientes;
  por eso la alerta de N debe evaluarse sobre lo que el docente ya trabajó y la resolución
  (motivos) ocurre en el Centro de control **antes** de cerrar. Precisar la UX en la Fase 3.
- **Sin migración mayor:** se eliminó la tabla `orden_merito_aprobacion`. Solo una posible
  migración menor en la Fase 3 si se opta por motivo a nivel competencia.
