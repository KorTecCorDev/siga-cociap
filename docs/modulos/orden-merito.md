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
- **Excluye áreas `tipo IN ('transversal','tutoria')`, con UNA excepción: ÉTICA Y
  VALORES cuenta en TODA secundaria, 5.º incluido (05/08/2026).** Ética **no es
  tutoría**: es la Educación Religiosa de secundaria, servida por la carga TOE porque
  el área homónima de ese nivel es un cascarón (0 cargas, 0 notas). Su `tipo='tutoria'`
  es un artefacto de implementación. Se ancla por `nombre_boleta`, **nunca por id**.
  Detalle, historia e impacto medido: ver la sección de auditoría más abajo.
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
- **Staff — ranking por SECCIÓN: `/director/ranking-seccion[/{periodo}]` (10/08/2026).**
  Mismos 4 roles que el módulo (admin, RA, director general, director EBR) porque comparte
  el `requireRole` de `Director\OrdenMeritoController`. **NO aplica la compuerta 044**, a
  diferencia del flujo del docente: el staff lo necesita ANTES de publicar, que es cuando
  el dato sirve para decidir. Es el mismo criterio que ya seguía el ranking por grado.
  - **No calcula nada nuevo:** llama a `rankingPorSeccion()`, el punto único
    snapshot-aware (cerrado → congelado; activo → en vivo sobre lo ya bloqueado), que
    comparten el imprimible del director y la vista del docente.
  - **Reutiliza la vista del docente** (`docente/ranking-seccion-periodo.php`)
    parametrizando `$rutaBase`, `$rutaMerito`, `$provisional` y `$hayPendientes`, todos
    con default. Se descartó copiarla: **dos copias divergen**, y así nacieron los 130
    bloqueos fantasma y la card de empates irresolubles.
  - **Sin top-N: todos los estudiantes de cada sección** (decisión del usuario), igual que
    el reporte imprimible.
  - Avisa cuando el periodo está **abierto** (badge *Provisional*: el ranking en vivo se
    mueve) y cuando hay **empates sin resolver** (el orden aún no es oficializable).
  - **Verificación:** `verif_ranking_seccion_staff.php` (solo lectura, corre en prod).
    Contrasta la suma de las secciones contra el snapshot oficial —**B1 528=528 y B2
    524=524**— y comprueba que la compuerta no recorta al staff. Medido el 10/08: en B2 los
    **11 grados** estaban ocultos al claustro (publicación programada al 13-14/08) y
    visibles aquí, que es justo el motivo de la vista.
- **Claustro:** `Docente\OrdenMeritoController` lista solo los periodos con **algún**
  nivel publicado (`PublicacionBoletaModel::periodosConAlgunNivelPublicado`) y, dentro
  de un periodo, solo los grados de niveles publicados (`nivelesPublicados`). El
  criterio de "publicado" no se duplica fuera de ese modelo.
- 🔴 **FUGA CORREGIDA EL 10/08/2026 — la NÓMINA del docente enseñaba el mérito NO
  PUBLICADO.** `/docente/nomina` resolvía el puesto con **`ultimoBimestreCerrado()`**, sin
  preguntar por la publicación: mostraba «Orden de mérito vigente: II Bimestre» y el
  puesto de cada alumno mientras `/docente/orden-merito` —dos clics más allá— se lo
  ocultaba. **Cerrar congela el ranking; PUBLICAR es lo que lo muestra**, y entre ambos
  actos pasan días.
  - **Punto único nuevo:** `PublicacionBoletaModel::ultimoPeriodoPublicadoPorNivel()`
    devuelve, **por NIVEL**, el último bimestre cerrado **y ya publicado**. Recorre los
    cerrados de más reciente a más antiguo y **reutiliza `nivelesPublicados()`** en vez de
    reescribir el criterio de "publicado" (son 4 bimestres como mucho; una quinta copia de
    esa regla es lo que costó los 130 bloqueos fantasma).
  - **La respuesta es POR NIVEL porque la compuerta lo es:** primaria se publica un día
    antes que secundaria, así que en esa ventana la misma pantalla muestra legítimamente
    **B2 para primaria y B1 para secundaria**. El encabezado lista un rótulo por nivel
    cuando difieren; decir uno solo mentiría.
  - **No se oculta lo ya publicado** (decisión del usuario): retrocede al último bimestre
    visible en vez de borrar el puesto. Medido el 10/08: la nómina pasa de enseñar **B2**
    (cerrado, publicación programada al 13-14/08) a enseñar **B1**, publicado el 22/07.
  - Cada alumno lleva `merito_visible`, que separa **"su nivel aún no se publicó"** de
    **"está publicado pero no tiene puesto"** — mensajes distintos en la card.
  - **El buscador de `Admin\BuscadorEstudianteController` NO se toca:** sus roles son staff
    (admin, RA, secretaría, los dos directores) y para ellos rige lo mismo que en
    `/director/ranking-seccion` — ven el mérito antes de publicar porque lo necesitan para
    decidir.
  - **Revisado y descartado como caso análogo:** el panel de BOLETA de esa misma nómina usa
    el umbral `'borrador'`, que por diseño no pasa por la compuerta (son las notas que el
    propio docente registra, y salen con marca de agua). Un ranking comparativo no es lo
    mismo que la boleta de su alumno.
  - **Verificación:** `verif_merito_nomina_compuerta.php` (transacción + ROLLBACK, guard de
    prod). Cubre los 4 escenarios: cerrado-sin-publicar devuelve el anterior, publicación
    escalonada da bimestres distintos por nivel, y suspendida/despublicada dejan de contar.
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

## Las ÁREAS EXONERADAS salen del cálculo (05/08/2026)

> **Regla:** una competencia cuya área (o subárea) esté exonerada para ese alumno **no
> entra en el promedio del orden de mérito**. Filtro en las **dos** queries de
> `OrdenMeritoModel` que calculan promedio (`rankingGradoLive` y `rankingPorSeccion`).

Nació al permitir **exonerar a un alumno que ya tiene notas** (ver
`docs/modulos/matriculas.md`). Como esas notas **no se borran** —para que la exoneración
sea reversible y para no tener que reabrir un bimestre cerrado—, sin este filtro el
sistema quedaba incoherente: la boleta mostraba `EXO` y el ranking seguía promediando la
nota.

- Cubre las **dos formas de exonerar**: por **área** (`a.id` llega ya resuelta con
  `COALESCE(sa.area_id, comp.area_id)`, así que alcanza a todas sus subáreas) y por
  **subárea** suelta. Los `NULL` no generan falsos positivos: una exoneración de área
  tiene `subarea_id` NULL y `NULL = x` es NULL.
- ⚠️ **Es el cálculo EN VIVO. Los snapshots guardados NO se tocan** — el de B1 es
  inmutable (candado `046`) y sigue en 528 filas.
- **Impacto medido sobre el caso real** (NOLASCO ALVARADO, 5.º B primaria, exonerada de
  Ed. Religiosa el 05/08 con 3 notas ya puestas, 1 en B1 cerrado y 2 en B2):

```
Promedio B2:  13.38  ->  13.21     (baja: sus notas de Religión estaban por encima de su media)
Su puesto:       30  ->     30
Puestos que cambian en el grado (39 alumnos):  0
Snapshot de B1: 528 filas, su puesto congelado 34 (promedio 12.17) — intacto
```

## El motor de cálculo, auditado (04/08/2026) — DECISIONES CERRADAS, no re-preguntar

Auditoría de `rankingGradoLive` + `aplicarDesempate` a petición del usuario. **No se
cambió nada del código**: las tres preguntas abiertas se decidieron por mantener el
comportamiento actual. Se documenta para no volver a plantearlas.

### Cómo calcula, en una línea
**Promedio aritmético simple por COMPETENCIA** (`AVG(cal.nota_numerica)`), sin ponderar
por área, horas ni nivel: un área con 4 competencias pesa el cuádruple que una con 1.
`promedio_exacto` (sin redondear) ordena; `promedio_general` (2 decimales) se muestra;
el agrupamiento de empates redondea a 6 decimales.

**El denominador varía entre compañeros del mismo grado** (medido en B1: 1° primaria de
19 a 23 competencias, 5° secundaria de 18 a 20). No es un fallo —`AVG` es por alumno—
pero el promedio no mide lo mismo para todos. Por eso la cascada trata el **N desigual**
como empate irreducible: si dos alumnos empatan en promedio con distinto N, la decisión
es humana. *(En B1 esa rama no llegó a dispararse: 0 grupos con N desigual.)*

### El motor NO distingue primaria de secundaria
No hay una sola condición por `nivel_id` en el cálculo. Las diferencias entre niveles las
producen los DATOS, no el código: primaria aporta 25 competencias evaluables al mérito y
secundaria 29 (28 más Ética, ver abajo).

### Ética y Valores ENTRA al mérito en toda secundaria (05/08/2026) — DECISIÓN CERRADA

Filtro vigente en las dos queries del cálculo en vivo:

```sql
AND (a.tipo NOT IN ('transversal', 'tutoria')
     OR a.nombre_boleta = '" . AREA_ETICA_NOMBRE_BOLETA . "')
```

**Por qué.** Ética y Valores **no es tutoría**: es la nota que corresponde al área-curso
**Educación Religiosa de secundaria**, que no tiene cargas propias (el tutor la evalúa a
través de su carga TOE). Sin la excepción, **el mismo curso pesaba en el promedio en
primaria** —donde Ed. Religiosa es un área-curso normal— **y no pesaba en secundaria**.
Esa asimetría no tenía justificación pedagógica, solo técnica.

**El vínculo Ética ↔ Ed. Religiosa vive en TRES sitios y ninguno es un dato estructural.**
No existe columna, FK ni configuración que diga "el área 24 reemplaza a la 14":

| Dónde | Cómo |
|---|---|
| SIAGIE | `LlenadorSiagie::EXCEPCIONES_HOJA` → hoja `035-EREL`, `buscar` por `nombre_boleta` |
| Orden de mérito | la excepción de arriba, en las 2 queries |
| Boleta | `areas.alias_boleta` del área 24 = `(Educación Religiosa)` |

Al tocar cualquiera de los tres, revisar los otros dos.

#### Deroga la regla de 5.º del 04/08 — y por qué aquella era errónea

La decisión anterior sacaba a Ética del mérito de 5.º. Se apoyaba en una lista que
enumeraba **«Ética y Valores» y «Educación Religiosa» como áreas distintas**, siendo la
misma. Además, Arte y EPT están fuera de 5.º **por dato** (0 cargas), mientras que Ética
**sí se dicta en 5.º** (50 notas en B2, todas bloqueadas): excluirla solo de ese grado
habría exigido una excepción por grado hardcodeada en el SQL, justo lo que este módulo
evita para no duplicar el plan de estudios.

#### Impacto medido en B2 con el MOTOR REAL (cascada completa, no solo promedio)

| Grado | Alumnos | Cambian N | Cambian puesto | Salto máx |
|---|---|---|---|---|
| Primaria (los 6) | 252 | **0** | **0** | — |
| Secundaria 1° | 72 | 72 | 29 | 3 |
| Secundaria 2° | 52 | 52 | 18 | 3 |
| Secundaria 3° | 45 | 45 | 7 | 2 |
| Secundaria 4° | 53 | 52 | 9 | 2 |
| Secundaria 5° | 50 | 50 | 13 | 2 |

- **Ningún primer puesto cambia** en ningún grado → la media beca no se ve afectada.
- **Primaria: 0 cambios**, confirmación de que la excepción no se filtra de nivel (su TOE
  tiene 0 competencias y su `nombre_boleta` es «Tutoría (TOE)»).
- Condiciones duras del cierre tras el cambio: **0 empates pendientes** y **0 alumnos con
  evaluación incompleta** en B2.

> ⚠️ Una medición anterior (04/08) reportaba 76 puestos movidos, salto 9 y un primer
> puesto cambiando en 1.º. Estaba hecha ordenando **solo por promedio** y resolviendo el
> área con `comp.area_id` en vez de `COALESCE(sa.area_id, comp.area_id)`, lo que
> descartaba las áreas con subáreas. Las cifras de la tabla son las buenas.

**B1 no se ve afectado, por tres vías independientes:** tiene **0 notas de Ética**; su
snapshot está publicado y es inmutable (candado 046); y los lectores usan el snapshot
(528 filas), no el cálculo en vivo.

**Lo que se tocó en el mismo cambio:**
- `ControlOperativoModel::alertasEvaluacionIncompleta` ya incluía Ética, así que su
  filtro **convergió solo**; se reescribió el comentario, que afirmaba lo contrario.
- `database/verificaciones/verif_universo_merito.php`: Ética sale de la lista de
  prohibidas de 5.º y sus dos consultas replican ahora la excepción — antes reportaban
  «correctamente fuera» de un área que ya aportaba, o sea **mentían en vez de fallar**.
  «Educación Religiosa» se queda en la lista con otro significado: **guard
  anti-duplicado**, extendido a los 5 grados por un chequeo dedicado.
- `database/reconstruir_snapshot_b1.php` conserva su propia excepción: reproduce el
  documento oficial de B1 tal como se generó. Inocuo (B1 tiene 0 notas de Ética).

⚠️ **Refuerzo recomendado:** desactivar el área *Educación Religiosa* de secundaria desde
`/admin/curriculum`. Ahora que Ética cuenta, una carga creada sobre esa área haría que el
**mismo curso contara dos veces**. No añadir `AND a.activa = 1` al mérito (ver más abajo).

#### Este cambio NO alinea SIGA con el SIAGIE — y no pretende hacerlo

Corrige una inconsistencia **interna**. Las divergencias con el acta siguen, en ambas
direcciones (medido en 5.º, B2):

| | Cuenta en el mérito | Llega al SIAGIE |
|---|---|---|
| Ética y Valores (50) | ✅ | ✅ `035-EREL` |
| GAMA, transversal (50) | ❌ | ✅ `032-ETRA` |
| Taller de Pre-Cálculo (50) | ✅ | ❌ sin hoja |
| Taller de Raz. Matemático (50) | ✅ | ❌ sin hoja |

Si alguna vez se decide que el mérito reproduzca el promedio del SIAGIE, son tres
decisiones más, no una.

### Qué entra y qué no en el promedio — tabla de referencia (04/08/2026)

| Nivel | Tipo | Área | Subáreas | Comps. | ¿Entra? |
|---|---|---|---|---|---|
| Primaria | área-curso | Arte y Cultura | — | 2 | **Sí** |
| Primaria | área-curso | Educación Física | — | 3 | **Sí** |
| Primaria | área-curso | Educación Religiosa | — | 2 | **Sí** |
| Primaria | área-curso | Inglés | — | 3 | **Sí** |
| Primaria | área-curso | Personal Social | — | 5 | **Sí** |
| Primaria | con subáreas | Ciencia y Tecnología | Química · Biología · Física | 3 | **Sí** |
| Primaria | con subáreas | Comunicación | Gramática · Plan Lector · Razonamiento Verbal | 3 | **Sí** |
| Primaria | con subáreas | Matemática | Aritmética · Álgebra · Geometría · Raz. Matemático | 4 | **Sí** |
| Primaria | transversal | Competencias Transversales | — | 2 | No |
| Primaria | tutoría | Tutoría (TOE) | — | 0 | No |
| Secundaria | área-curso | Arte y Cultura | — | 2 | **Sí** |
| Secundaria | área-curso | DPCC | — | 2 | **Sí** |
| Secundaria | área-curso | Educación Física | — | 3 | **Sí** |
| Secundaria | área-curso | Educación para el Trabajo | — | 1 | **Sí** |
| Secundaria | área-curso | Educación Religiosa | — | 2 | **Sí** |
| Secundaria | área-curso | Inglés | — | 3 | **Sí** |
| Secundaria | área-curso | Taller de Pre-Cálculo | — | 1 | **Sí** |
| Secundaria | área-curso | Taller de Razonamiento Matemático | — | 2 | **Sí** |
| Secundaria | con subáreas | Ciencia y Tecnología | Química · Biología · Física | 3 | **Sí** |
| Secundaria | con subáreas | Ciencias Sociales | Historia · Geografía · Economía | 3 | **Sí** |
| Secundaria | con subáreas | Comunicación | Raz. Verbal · Literatura · Lenguaje | 3 | **Sí** |
| Secundaria | con subáreas | Matemática | Aritmética · Álgebra · Geometría · Trigonometría | 4 | **Sí** |
| Secundaria | transversal | Competencias Transversales | — | 2 | No |
| Secundaria | tutoría | **Ética y Valores** | — | 1 | **Sí** (desde 05/08 — es Ed. Religiosa) |

**Totales que entran: Primaria 25 competencias · Secundaria 28.**

Notas de lectura:
- **Las subáreas no son una unidad de cómputo.** El promedio es por COMPETENCIA: cada
  subárea aporta las suyas (normalmente 1) y el área con subáreas pesa la suma. Matemática
  (4) pesa el doble que Arte y Cultura (2).
- **La CONDUCTA no entra**, y no por un filtro: vive en otra tabla
  (`calificaciones_conducta`) que el mérito ni siquiera consulta. Verificado: no hay
  ninguna referencia a conducta en `OrdenMeritoModel`.
- **Las competencias transversales (TIC/GAMA) no entran** en ninguno de los dos niveles.
- Ese universo aún se filtra por: nota **bloqueada**, `extraordinaria = 0` y el roster
  (`tipo NOT IN ('trasladado','retirado')` + anclaje de retorno).

### 5.º de secundaria lleva OTRO plan — regla del colegio (revisada el 05/08/2026)

**En 5.º de secundaria no entran al mérito:** Arte y Cultura, Educación para el Trabajo
y las Competencias Transversales.

> ⚠️ **La regla del 04/08 incluía además «Ética y Valores» y «Educación Religiosa», y se
> DEROGÓ el 05/08.** Las enumeraba como dos áreas distintas siendo **la misma**: en
> secundaria Ed. Religiosa es un cascarón sin cargas y su nota la produce la carga de
> Ética. Al aclararse, el usuario decidió que **Ética cuenta en los 5 grados**. Es el
> caso testigo de que una regla registrada puede apoyarse en una premisa falsa sobre
> cómo están modelados los datos: contrastarlas con la BD antes de codificar excepciones.

| Área | Situación en 5.º |
|---|---|
| Arte y Cultura | **fuera**: 5.º no la lleva (0 cargas y 0 notas; sí en 1.º-4.º) |
| Educación para el Trabajo | **fuera**: 5.º no la lleva (0 cargas y 0 notas; sí en 1.º-4.º) |
| Competencias Transversales | **fuera** por `tipo='transversal'` |
| **Ética y Valores** | **ENTRA** (50 notas en B2, todas bloqueadas) — es la Ed. Religiosa del nivel |
| Educación Religiosa (secundaria) | 0 cargas y 0 notas: cascarón del catálogo. Vigilada como **guard anti-duplicado**, no como veto curricular |

**Plan de 5.º frente a 1.º-4.º:** no lleva Arte y Cultura ni EPT, y sí lleva **Taller de
Pre-Cálculo** (exclusivo del grado). Competencias que entran: **1.º-4.º = 27 · 5.º = 25**
(una más que antes en cada tramo, por Ética).

> **Ojo al leer el acta SIAGIE de 5.º:** `032-EPT` se nutre de **GAMA**, una competencia
> transversal que **no** cuenta para el mérito (`LlenadorSiagie:77`, solo 5.º,
> precisamente porque el grado no lleva el curso de EPT). Y en sentido contrario, los dos
> **talleres** (Raz. Matemático y Pre-Cálculo) **sí** cuentan para el mérito y **no**
> llegan al acta, porque no tienen hoja en el SIAGIE. `035-EREL` ← Ética ya no es una
> discrepancia: desde el 05/08 cuenta en ambos. El acta y el ranking **no tienen por qué
> cuadrar área por área**.

**Por qué esto NO se codificó como excepción en el SQL del mérito:** el plan de estudios
se deriva de las **cargas académicas**; hardcodear "5.º no lleva Arte" en la query
duplicaría esa fuente de verdad y quedaría desincronizado el día que el plan cambie. La
red de seguridad es `database/verificaciones/verif_universo_merito.php` (solo lectura,
corre en prod): lista qué área aporta al promedio en cada grado y **falla** si una
prohibida empieza a aportar.

**Refuerzo pendiente (dato, no código):** desactivar el área *Educación Religiosa* de
secundaria desde `/admin/curriculum`. `CargaAcademicaModel` solo ofrece áreas con
`activa = 1`, así que impide crearle una carga — sin carga no hay notas. **No** añadir
`AND a.activa = 1` al mérito: convertiría desactivar un área en una alteración
**retroactiva** de rankings ya calculados (el exportador SIAGIE ya evita esa trampa con
`WHERE a.activa = 1 OR notas > 0`).

### Decisiones del usuario (04/08/2026)
1. **Primaria se sigue ordenando por PROMEDIO NUMÉRICO**, igual que secundaria. Se evaluó
   ordenarla por conteo literal (más AD, menos C…) —que es lo que su boleta comunica, ya
   que en primaria solo se publica AD/A/B/C— y **se decidió mantener el promedio**.
   - Consecuencia asumida, medida en B1: **180 alumnos de primaria** están en grupos con
     la boleta literal idéntica (mismo N y misma distribución C/B/A/AD), y en **42** de
     esos grupos el orden lo decide el numeral, que la familia de primaria **no ve**.
     En secundaria son 81 alumnos / 33 grupos, pero allí el numeral sí se publica.
2. **`num_alto` (15,16) y `num_16` se quedan como están**, aunque sean criterios de escala
   vigesimal aplicados también a primaria. Siguen congelados desde el cambio de escala del
   10/06/2026.
3. **Los umbrales de `num_c`/`num_b` se quedan HARDCODEADOS.** No se unifican con las
   constantes pese a ser un cambio sin efecto en el resultado de hoy.

### ⚠️ Riesgo latente que dejan las decisiones 2 y 3
La cascada mezcla dos fuentes de umbral:
```sql
SUM(cal.nota_numerica <= 10)             AS num_c    -- literal, hardcodeado
SUM(cal.nota_numerica BETWEEN 11 AND 13) AS num_b    -- literal, hardcodeado
SUM(cal.nota_numerica >= " . NOTA_MIN_AD . ") AS num_ad  -- constante
```
Hoy los tres coinciden con la escala vigente (`NOTA_MIN_B=11`, `NOTA_MIN_A=14`,
`NOTA_MIN_AD=18`), así que el ranking es correcto.

**DISPARADOR:** si alguna vez se mueve `NOTA_MIN_B` o `NOTA_MIN_A`, `num_ad` se ajustará
solo y `num_c`/`num_b` se quedarán atrás **en silencio**, desincronizando el desempate.
Ante un cambio de escala hay que venir aquí y actualizar estas dos líneas a mano (y
revisar `num_alto`/`num_16`, congelados desde el cambio anterior). Las dos queries del
modelo (`rankingGradoLive` y `rankingPorSeccionLive`) llevan la misma copia.

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
