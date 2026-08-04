# Módulo: Orden de mérito, snapshot y rectificaciones

> Documentado el 03/07/2026 al crear la red de documentación (este módulo se
> implementó el 17/06/2026 y no tenía sección propia en CLAUDE.md).
> Los invariantes globales viven en CLAUDE.md.
>
> **Rediseño 2 (aprobado 25/07/2026, SIN implementar): ver
> `orden-merito-rediseno.md`.** Este archivo describe el estado ACTUAL; se irá
> actualizando a medida que cada fase del rediseño entre en producción.

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
  notas SÍ actualiza el ranking (regenera al re-cerrar) **mientras el bimestre no
  haya sido publicado**; si ya lo estuvo, el oficial es inmutable y la corrección va
  a `orden_merito_rectificado` (candado 046). Una reversión con el bimestre cerrado
  nunca lo toca.
- **Empates resueltos ANTES de cerrar; bimestres se abren en orden cronológico.**
- **"Vigente"** (buscador, columna puesto de nómina) = snapshot del cerrado de
  mayor número < bimestre activo (`EstudianteModel::ultimoBimestreCerrado`).
- **Excluye áreas `tipo IN ('transversal','tutoria')`**, con UNA excepción desde el
  rediseño 2 (26/07): el área **Ética y Valores** (tutoría de secundaria, C57) SÍ
  cuenta, porque reemplaza a Ed. Religiosa. Punto único:
  `AREA_ETICA_NOMBRE_BOLETA` en `helpers.php` — se identifica por `nombre_boleta`,
  NO por id (el id del área difiere entre entornos). El resto de la tutoría TOE y las
  transversales siguen fuera, de forma permanente.
- **El cálculo EN VIVO solo cuenta competencias BLOQUEADAS** (rediseño 2): join a
  `bloqueos_competencia`, sin filtrar `origen` (cuenta `docente` y `cierre`). En un
  bimestre cerrado no cambia nada (todo está bloqueado); en uno activo el ranking
  provisional refleja solo lo ya confirmado.
- **Cascada de desempate sin apellidos** (rediseño 2): tras `num_16` el desempate es
  MANUAL y el orden de presentación lo fija `m.id` (neutro y determinista).
- **PUNTO ÚNICO de "qué empates faltan" (desde el 04/08/2026):**
  `OrdenMeritoModel::gradosConEmpatesPendientesDetalle`. Lo consumen el guard del
  cierre (vía el wrapper `gradosConEmpatesPendientes`, que devuelve strings) y la card
  del Centro de Control (`ControlOperativoModel::empatesPendientes`, que ahora solo
  delega). **NUNCA reimplementar la cascada fuera de `aplicarDesempate`.**
  - **Por qué existe la regla:** el Centro de Control tuvo su propia copia desde el
    08/06/2026 (`detectarGruposIrreducibles`, hoy eliminada) que se quedó en la tupla
    de 3 conteos y nunca incorporó `num_alto`/`num_16` — añadidos al motor real ese
    mismo día, cuatro horas después (`d41c548`). Durante dos meses la card inventó
    empates que el motor deshace solo; y como la pantalla de resolución sí usa el
    motor real, esos grupos **jamás aparecían allí para resolverse**: la card no se
    limpiaba nunca. Medido antes del arreglo: B1 mostraba 7 grados "pendientes" con
    0 reales (14 desempates ya resueltos), B2 mostraba 6 con 0 reales.
  - La copia además arrastraba el roster viejo (`estado='aprobada'` en vez del filtro
    por `tipo`), no excluía notas extraordinarias ni no bloqueadas (P2) y contaba
    toda la tutoría en vez de solo Ética y Valores.
  - Regresión cubierta por `database/verificaciones/verif_empates_card_control.php`.

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
  (`debeUsarSnapshot`: tiene filas Y (cerrado **o** ya fue publicado) → lee snapshot;
  si no, cálculo vivo). La segunda condición cubre la REAPERTURA de un bimestre
  publicado: su oficial ya salió a las familias y es inmutable, así que el claustro
  y las familias siguen viendo el congelado mientras se corrige. Está memoizado por
  periodo (se llama en bucle, un grado por iteración).
  El cálculo vivo ancla por bimestre: excluye la OFICIAL si su operativa cubrió ese
  periodo e incluye la operativa revertida (desactivada) en sus periodos.
- `PeriodoController::cerrar` llama `generarSnapshot` DENTRO de su transacción
  (PDO singleton compartido → atómico). `gradosConRanking` es snapshot-aware.
- **Backfill:** `database/backfill_orden_merito.php` — idempotente; SALTA periodos
  con empates pendientes para no congelar un orden arbitrario **y, desde el 26/07,
  también los que ya tienen snapshot oficial PUBLICADO** (candado 046), salvo que se
  invoque con `--forzar`. Sin esa guarda sobrescribía en silencio el documento
  entregado a las familias: en B1 habría cambiado las 528 filas reconstruidas a mano
  por las 518 de la regla general.
- **Reconstrucción de B1:** `database/reconstruir_snapshot_b1.php` — único camino
  soportado para regenerar el oficial de B1 con la regla ESPECIAL de la Fase C (roster
  SIN filtro de tipo). Nunca corre en prod (guard por el archivo de secretos), simula
  salvo `--confirmar`, y verifica la firma del documento (528 / puestos 1-72 / 0
  empates) dentro de la transacción antes del COMMIT. Existe porque el snapshot local
  se perdió el 26/07 y `backfill` no puede reponerlo: aplica la regla general. Detalle
  y verificación de fidelidad en `docs/ESTADO.md`.
- `gradosConEmpatesPendientes` usa el cálculo EN VIVO (`rankingGradoLive`), NO el
  wrapper snapshot-aware: valida lo que se va a congelar, no lo ya congelado. Importa
  al RE-cerrar un bimestre publicado y reabierto, donde `debeUsarSnapshot` es `true` y
  leer por `rankingGrado` devolvería el snapshot viejo, sin ver los empates que
  introdujeron las rectificaciones.
- Los empates se detectan/resuelven a nivel GRADO (UI por periodo+grado en
  `Director\OrdenMeritoController::desempate`).
- Limitación menor conocida: `getConteosGrado` (header del reporte) sigue en vivo —
  solo afecta el conteo del grado operativo de un retorno revertido, no los puestos.

## Inmutabilidad del oficial + versión rectificada (migración 046, 24/07/2026)

Fase B del rediseño. Regla: **el snapshot OFICIAL (`orden_merito_snapshot`) no cambia
una vez que el periodo ESTUVO publicado** (compuerta 044). Un orden de mérito publicado
es información pública; corregirlo sin acto formal es una regresión grave.

- **Disparador (monotónico, a nivel de periodo):** `PublicacionBoletaModel::fuePublicado`
  = existe fila con `primera_publicacion_en` sellada, o `publica_en <= ahora` (cubre las
  programadas que alcanzaron su hora). El sello lo pone `publicar()` con `COALESCE` (una
  sola vez, nunca se limpia) → suspender por reapertura o despublicar a mano NO reabren el
  candado. Columna additiva `periodos_publicacion.primera_publicacion_en` (backfill en 046).
- **Punto único de escritura:** `OrdenMeritoModel::registrarRanking($periodoId, $usuarioId,
  $motivo)` → si `fuePublicado` **y** ya hay oficial, escribe la versión RECTIFICADA
  (`generarSnapshotRectificado`, tabla `orden_merito_rectificado`) y el oficial queda
  intacto; si no, (re)genera el oficial. Devuelve `'oficial'|'rectificado'`. Lo usan
  `PeriodoController::cerrar` y `RectificacionController` (antes llamaban `generarSnapshot`
  directo). **`generarSnapshot` directo NO honra el candado** — reservado para el
  backfill y la reconstrucción de B1 (registro inicial del oficial de un bimestre ya
  publicado que aún no tenía snapshot).
- **Fuente de cálculo común:** `calcularFilasRanking` (extraída) alimenta el oficial y el
  rectificado con el mismo ranking en vivo, para que solo difieran en tabla y `motivo`.
- **Vista:** el Centro de control (`/admin/control`) muestra una card cuando hay versión
  rectificada + una página de solo lectura
  (`/admin/control/{periodo_id}/orden-merito-rectificado`). El oficial se sigue viendo por
  el flujo normal del director; la rectificada es "registrada pero no mostrada" ahí.
- `orden_merito_rectificado` guarda **la última** versión no oficial por periodo (se
  sobrescribe); la traza por criterio vive en `rectificaciones_calificacion`.

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
En ranking/conteo el roster se filtra por **`m.tipo NOT IN ('trasladado','retirado')`**
(NO por `estado='aprobada'`): un alumno permanece en el orden de mérito hasta que su
tipo sea `trasladado` o `retirado` — los `desactivado` por deuda y los `pendiente` SÍ
compiten. Alineado con los rosters de evaluación. La operativa de un retorno revertido
(`continuador`) queda incluida por tipo; ya no hace falta el `OR revertido` explícito.
Se sigue EXCLUYENDO la matrícula oficial de un retorno activo (`m.id NOT IN
(SELECT matricula_oficial_id FROM retornos_grado WHERE estado='activo' …)`) — el
estudiante compite en su grado OPERATIVO (anclaje por bimestre intacto).

> Cambio del 24/07/2026 (Fase A del rediseño): el filtro pasó de `estado` a `tipo`.
> Nota histórica: la vista live de un bimestre CERRADO sin snapshot (fallback) refleja
> este roster actual; por eso el reporte oficial debe venir del snapshot congelado
> (ver Fases B y C en `docs/ESTADO.md`), no del cálculo en vivo tardío.

## Visibilidad: quién ve el mérito y cuándo (rediseño 2, 26/07/2026)

**Publicar libera boletas Y orden de mérito juntos**, por NIVEL, bajo la compuerta 044.
Cerrar el bimestre oficializa (congela) el mérito, pero no lo muestra a nadie fuera de
dirección.

- **Director:** `/director/orden-merito`, siempre (no depende de la compuerta).
- **Claustro:** `Docente\OrdenMeritoController` lista solo los periodos con **algún**
  nivel publicado (`PublicacionBoletaModel::periodosConAlgunNivelPublicado`) y, dentro
  de un periodo, solo los grados de niveles publicados (`nivelesPublicados`). El
  criterio de "publicado" no se duplica fuera de ese modelo.
- **Familias:** `/padre/orden-merito` (grado) y `/padre/ranking-seccion` (solo la
  sección del hijo), bajo la misma compuerta que las notas
  (`PanelController::getPeriodoVigentePadre`). Ven la lista completa con nombre y
  promedio, igual que el claustro (decisión del usuario, 26/07).
- **Retorno de grado:** `getHijo` devuelve la matrícula OFICIAL, pero el alumno compite
  con la OPERATIVA. `PanelController::contextoMerito` recorre las fuentes de
  `boletaContexto` y se queda con la que aparece en el ranking; **los rótulos usan ese
  grado/sección**, no el oficial (integridad: no mostrar "2°" sobre una tabla de 1°).
  Es la excepción consciente a "boletas y nómina siempre muestran el grado OFICIAL":
  aquí lo que se muestra es un ranking de un grado concreto, no un documento SIAGIE.

**El cierre exige mérito íntegro:** `PeriodoController::cerrar` aborta si hay empates
sin resolver o alumnos con evaluación incompleta
(`ControlOperativoModel::alertasEvaluacionIncompleta`: criterios que otros compañeros de
su sección sí tienen con nota y a él le faltan, sin omisión ni exoneración, en cargas
ACTIVAS). NO valida "0 competencias sin bloquear" —el propio cierre las fuerza, y ese
camino maneja las transversales—, que es una diferencia consciente con el diseño (P3).

## Estado operativo
Ver `docs/ESTADO.md`. **Rediseño 1 COMPLETADO (25/07/2026):** A = filtro por tipo (en
prod); B = inmutabilidad tras publicar + versión rectificada no oficial en Centro de
control (migración 046 en prod); C = reconstrucción de B1 EN PROD.
**Rediseño 2 COMPLETADO en `dev` (26/07/2026), pendiente de deploy:** F1-F6 + F5b y
fixes, sin migración nueva. Detalle y desviaciones del plan en
`orden-merito-rediseno.md` §8.

**B1 (periodo 1) tiene snapshot oficial de 528 filas en PROD** (reemplazó a 519 previas).
Roster por REGLA del usuario: todos los estudiantes con calificaciones bloqueadas/aprobadas
en B1, SIN filtro de tipo (reincorpora 8 `trasladado` + `541` `retirado`, continuadores con
notas B1), conservando el anclaje de retornos. Es un CASO ESPECIAL de reconstrucción: la
regla general del código sigue filtrando por tipo (Fase A) y produce 519/520, por eso NO se
debe correr `backfill_orden_merito.php` en prod (sobrescribiría el 528). El candado 046
mantiene el oficial inmutable (B1 publicado → futuras correcciones van a `orden_merito_rectificado`).
