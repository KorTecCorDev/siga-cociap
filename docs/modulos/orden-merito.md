# Módulo: Orden de mérito, snapshot y rectificaciones

> Documentado el 03/07/2026 al crear la red de documentación (este módulo se
> implementó el 17/06/2026 y no tenía sección propia en CLAUDE.md).
> Los invariantes globales viven en CLAUDE.md.

## Orden de mérito — snapshot al cerrar (17/06/2026)

El orden de mérito era 100% dinámico (se recalculaba en vivo desde `calificaciones`
+ estado actual de matrículas/secciones). Eso corrompía rankings de bimestres
cerrados ante reversión de retorno de grado, traslados o ediciones. Se convirtió
en documento oficial inmutable por snapshot.

### Reglas de negocio
- **Anclado POR BIMESTRE según dónde están las notas:** en retorno de grado activo
  el alumno compite en su sección OPERATIVA (grado inferior); tras revertir, los
  bimestres pasados quedan congelados en la operativa y desde el siguiente compite
  en su sección OFICIAL.
- **Nómina y boletas SIEMPRE muestran grado/sección OFICIAL** (nómina vía filtro en
  `PanelController::getMatriculados`; boletas vía `CalificacionModel::boletaContexto`:
  identidad = oficial, notas por unión [operativa, oficial]).
- **El snapshot solo se (re)genera al CERRAR** el bimestre. Reabrir para corregir
  notas SÍ actualiza el ranking (regenera al re-cerrar). Una reversión con el
  bimestre cerrado nunca lo toca.
- **Empates resueltos ANTES de cerrar; bimestres se abren en orden cronológico.**
- **"Vigente"** (buscador, columna puesto de nómina) = snapshot del cerrado de
  mayor número < bimestre activo (`EstudianteModel::ultimoBimestreCerrado`).
- **Excluye áreas `tipo IN ('transversal','tutoria')`** — permanente, aunque a
  futuro tengan notas.

### Implementación
- **Fase 1 — candados:** `AnioAcademicoModel::hayBimestrePrevioPendiente` (no abrir
  si un bimestre de número menor sigue pendiente) y
  `OrdenMeritoModel::gradosConEmpatesPendientes` (el cierre ABORTA si hay empates
  pendientes), ambos validados en `PeriodoController::abrir`/`cerrar`.
- **Fase 2 — snapshot:** migración `023_orden_merito_snapshot.sql` — tabla con
  `puesto_grado` Y `puesto_seccion`, grado/sección explícitos congelados y métricas
  (num_competencias, total_notas, promedios, num_c/b/ad/alto/16).
  UNIQUE(periodo, matricula).
- `OrdenMeritoModel::rankingGrado`/`rankingPorSeccion` son wrappers snapshot-aware
  (`debeUsarSnapshot`: cerrado + tiene filas → lee snapshot; si no, cálculo vivo).
  El cálculo vivo ancla por bimestre: excluye la OFICIAL si su operativa cubrió ese
  periodo e incluye la operativa revertida (desactivada) en sus periodos.
- `PeriodoController::cerrar` llama `generarSnapshot` DENTRO de su transacción
  (PDO singleton compartido → atómico). `gradosConRanking` es snapshot-aware.
- **Backfill:** `database/backfill_orden_merito.php` — idempotente; SALTA periodos
  con empates pendientes para no congelar un orden arbitrario.
- Los empates se detectan/resuelven a nivel GRADO (UI por periodo+grado en
  `Director\OrdenMeritoController::desempate`).
- Limitación menor conocida: `getConteosGrado` (header del reporte) sigue en vivo —
  solo afecta el conteo del grado operativo de un retorno revertido, no los puestos.

## Rectificación de calificaciones (17/06/2026)

Módulo GENERAL para que `admin`/`registro_academico` corrijan notas que ya
salieron del flujo normal del docente, con auditoría obligatoria.

- **Invariante:** una competencia es RECTIFICABLE solo si está BLOQUEADA y/o en
  periodo CERRADO (`RectificacionModel::esRectificable`). Si está abierta va por
  el flujo del docente. Control = rol + estado rectificable + motivo obligatorio +
  traza en `rectificaciones_calificacion`.
- **Mecánica:** edición por criterio → recálculo de UNA matrícula (reusa
  `calcularPromedio`/`guardarNotaFinal`/`actualizarConclusion`) → valida
  `conclusionObligatoria(literal, nivel)` → registra auditoría → **regenera el
  snapshot del bimestre** (`OrdenMeritoModel::generarSnapshot`).
- **Interacción con empates (cuidado):** tras regenerar, `rankingGrado` lee del
  snapshot y NO detecta empates nuevos. Por eso existe
  `OrdenMeritoModel::gradoTieneEmpateLivePendiente()` (calcula en vivo ignorando
  el snapshot); si la corrección introdujo un empate, avisa al director.
- **Piezas:** migración `024_rectificaciones_calificacion.sql`,
  `RectificacionModel`, `Rectificacion\RectificacionController`, rutas
  `/rectificaciones[...]`, vistas `resources/views/rectificaciones/`, SASS
  `pages/_rectificaciones.scss`. El buscador `buscador-estudiante.js` acepta
  `data-target-base` en `#buscadorResultados` para redirigir las tarjetas.
- **Entradas:** card en dashboard y acciones en `matriculas/show.php`.

### Calificación extraordinaria (16/07/2026, migración 042)

Alta de nota por RA a un alumno SIN calificación en una competencia que ya
salió del flujo del docente (cerrada/bloqueada). Vive dentro de Rectificación
(`GET/POST /rectificaciones/extraordinaria[...]`). Detalle completo del flujo
y sus guardas en `docs/modulos/calificaciones.md` (sección "Calificación
extraordinaria"). Lo que importa a ESTE módulo:

- **La nota extraordinaria NO cuenta en el orden de mérito** (decisión del
  usuario): `calificaciones.extraordinaria = 1` y las DOS agregaciones en vivo
  (`rankingGradoLive`, `rankingPorSeccionLive`) filtran
  `AND cal.extraordinaria = 0`. Va a boleta y SIAGIE, no mueve puestos.
- Por eso el alta **NO regenera el snapshot** (el ranking no cambia); la
  rectificación normal sí sigue regenerándolo.
- La auditoría distingue `tipo='extraordinaria'` (nota_anterior NULL) de
  `tipo='rectificacion'` en `rectificaciones_calificacion`.
- Tras el alta, la competencia pasa a ser rectificable por el flujo normal
  (corrección futura de la extraordinaria = rectificación estándar).
- Los subqueries de ANCLAJE de retorno (`c2`) NO filtran extraordinarias a
  propósito: deciden DÓNDE compite el alumno (dónde viven sus notas), no qué
  suma al promedio.

## Integración con matrículas (7.1)
En ranking/conteo: `m.estado='aprobada'` y se EXCLUYE la matrícula oficial de un
retorno activo (`m.id NOT IN (SELECT matricula_oficial_id FROM retornos_grado
WHERE estado='activo')`) — el estudiante compite en su grado OPERATIVO.

## Estado operativo
Ver `docs/ESTADO.md`: la tabla snapshot está VACÍA en LOCAL y PROD (el backfill
saltó B1/B3 por empates sin resolver); mientras tanto todo se calcula en vivo.
