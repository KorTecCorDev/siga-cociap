# Módulo: Horarios y cargas académicas

> Extraído VERBATIM de CLAUDE.md el 03/07/2026 (fase 1 de la red de documentación).
> Los invariantes globales y la tabla de enrutamiento viven en CLAUDE.md.

## Módulo de horarios — múltiples bloques por día + solapes (30/06/2026)

> Una carga académica puede tener varios bloques NO consecutivos el MISMO día
> (p. ej. una materia que se dicta dos veces el lunes con un hueco). La BD ya lo
> soportaba (`sesiones_horario` admite N filas por carga; `bloques_horario` tiene
> varios bloques por día vía `numero_bloque`); la limitación era de la aplicación,
> que asumía "un bloque por día". Commit `55f3918` (en `dev` y `main`).

### Formulario (`crear.php` / `editar.php`, `cargas.js`)
- Inputs como arreglos por día: `hora_inicio[dia][]` / `hora_fin[dia][]`. Cada día
  tiene una lista de `.bloque-rango` con "+ Agregar bloque" y quitar por fila.
- `cargas.js`: `agregarBloque`/`quitarBloque` (delegación de clic en `.horario-grid`),
  `toggleDia` habilita/inhabilita TODOS los inputs del día + el botón (al desmarcar
  colapsa a un solo rango), `refrescarQuitar` muestra el quitar solo si hay >1 rango.
- `editar.php`: `sesionesMap[$dia]` ahora es **LISTA** de rangos (pre-rellena todos).
  Esto **corrige el riesgo de pérdida** del 2.º bloque que tenía el mapa antiguo
  indexado por día (sobrescribía).
- SASS en `pages/_cargas.scss`: `.dia-row__bloques` (columna), `.bloque-rango` (fila),
  `.bloque-agregar`/`.bloque-quitar`. Reemplazó a `.dia-row__times`.

### Detección de solapes — `CargaAcademicaModel::verificarSolapes()`
- **Reemplazó a `verificarConflictos`** (que solo detectaba bloque EXACTO; eliminado).
- Solape ESTRICTO por día: `propInicio < bh.hora_fin AND bh.hora_inicio < propFin`,
  acotado al `config_id` del año. Los **contiguos** (fin == inicio del otro) **NO** chocan.
- Firma: `verificarSolapes(array $sesiones, ?int $excluirCargaId = null, array $tipos = ['seccion','docente'])`.
  Cada sesión trae `dia/hora_inicio/hora_fin/seccion_id/docente_id/config_id`.
- **Tres reglas, todas rechazan** (confirmadas por el usuario): (a) rangos del mismo
  envío que se pisan → `CargaAcademicaController::solapeInterno`; (b) otra carga del
  mismo DOCENTE ese día/hora; (c) otra carga de la misma SECCIÓN ese día/hora → (b/c)
  vía `verificarSolapes`.
- `procesarFormulario` itera N rangos/día, ignora filas vacías, valida cada uno
  (`fin > inicio`), rechaza solape interno, suma `horas_semanales` de TODOS los bloques.
- `getSesionesDeCarga` ahora expone `seccion_id` y `config_id` (para el chequeo por tiempo).
- **Reemplazo de docente** (`ReemplazoDocenteController`) migró a
  `verificarSolapes(..., ['docente'])` (el entrante hereda el horario; solo se valida
  que él no se solape; la sección conserva sus mismos slots).
- **Efecto retroactivo ACEPTADO:** editar una carga antigua que ya se solapaba se
  **bloquea** hasta resolverlo (decisión del usuario: datos correctos > comodidad).
- **Sin migración** (la BD ya soportaba N bloques/día).

## Horario imprimible — grilla alineada por rowspan + horas académicas reales (01/07/2026)

> Dos correcciones en `GET /docente/horario/imprimir` (`Docente\PanelController::horarioImprimir`
> + `resources/views/docente/horario-imprimir.php`). Solo esa vista/método (+ un
> ajuste SASS); no toca el panel `/docente/inicio` (que ya listaba bien por día) ni
> el guardado de horarios. Sin migración.

### 1. Desalineación de la tabla de doble entrada → eje por puntos de corte + rowspan
- **Bug:** las filas se armaban por **franja exacta** (`hora_inicio|hora_fin`) y se
  etiquetaban "Nª hora". Con días heterogéneos (multi-bloque o mezcla primaria/
  secundaria, que tienen fronteras de bloque distintas) un bloque largo de un día
  (p. ej. 90 min) NO se alineaba con dos bloques cortos de otro día → filas sueltas
  y numeración sin sentido. Solo pasa **entre días** (en el mismo día un bloque
  contenido en otro sería solape, ya rechazado por `verificarSolapes`).
- **Fix:** eje de tiempo por **puntos de corte** — se reúnen todos los `hora_inicio`
  y `hora_fin` distintos de las sesiones, se ordenan (`sort SORT_STRING`), y cada par
  consecutivo es un **segmento** (fila mínima). Cada bloque se ancla en la fila de su
  inicio con `rowspan = índice(fin) − índice(inicio)`. Estructuras nuevas que pasan a
  la vista: `$segmentos` (filas), `$startAt[dia][fila]` (celda que arranca ahí, con
  `rowspan`) y `$covered[dia][fila]` (filas ocupadas → en las continuadas NO se dibuja
  `<td>`). Reemplazaron a `$franjas`/`$matriz`/`$bloques`.
- La columna **Hora** ahora muestra el **rango real del segmento** (`13:10–14:40`) en
  vez de "Nª hora". SASS: `&__hora-col` de `56px → 72px` en `pages/_docente-panel.scss`.
- **Se eliminó la leyenda "Bloques horarios"** (mapeaba "Nª hora → horario"): quedó
  redundante al mostrar la hora directa en la tabla. Se conservan "Cargas y secciones"
  y "Niveles". Los huecos entre bloques (futuros recreos) salen como filas vacías.

### 2. Horas/sem por duración real (no por conteo de bloques)
- **Bug:** la columna "Horas/sem" de la leyenda "Cargas y secciones" y el total hacían
  `$grupos[key]['horas']++` por sesión → **1 bloque = 1 hora**, aunque un doble de
  90 min son 2 horas pedagógicas.
- **Fix:** cada bloque cuenta `round(duración_min / hora_académica)` horas
  (`strtotime(fin) − strtotime(inicio)`). Con hora de 45 min: 45→1, 90→2, 180→4;
  un doble atípico de 95 min → 2 (**redondeo normal, confirmado por el usuario**).
  Se acumula en `$grupos[key]['horas']` y `$totalHoras`. **(01/07/2026)** la
  duración ya NO se hardcodea: se lee de `configuracion_horario.duracion_hora_min`
  del año activo (hoy 45), fallback 45.
- **Resuelto (01/07/2026):** los "~200 bloques de 1 minuto" NO eran data de prueba:
  eran el workaround para registrar subáreas que comparten el horario del área
  (el sistema exigía horario por carga y prohibía compartirlo). La migración `030`
  los eliminó; ver "Cargas sin horario propio" más abajo.

### Recreos — PENDIENTE (diferido por el usuario)
El recreo NO está modelado (no hay `tipo`/`es_recreo` en `bloques_horario`; hoy es solo
el hueco entre bloques). Primaria tiene 2 recreos y secundaria 1, en horas distintas;
el caso "docente en ambos niveles" choca con el eje de fila única (un recreo tendría que
ser por columna/día, no una fila uniforme). Se analizará al final.

## Cargas sin horario propio + limpieza de bloques falsos + vista unidocente (01/07/2026)

> El horario real del colegio se define por ÁREA, pero el sistema exigía bloques
> propios por CARGA (subárea) y prohibía compartirlos (solape por sección Y
> docente). Cuando un docente dicta varias subáreas de la misma área en la misma
> sección (unidocente 1°-3°, especialistas de primaria 4°-6°, CCSS de secundaria)
> la única salida era inventar **bloques falsos de 1 minuto a medianoche** (~189
> en toda la BD, escalonados 00:06, 00:08… para esquivar `verificarSolapes`).

### Regla general: una carga puede existir SIN horario propio
- Checkbox **"Sin horario propio"** (`name="sin_horario"`) en `crear.php`/`editar.php`
  de cargas; en editar viene marcado si la carga no tiene sesiones.
- `CargaAcademicaController::procesarFormulario()`: con `sin_horario=1` retorna
  `sesiones=[]` + `horas_semanales=0` y salta la validación de días (los días del
  POST se ignoran; el JS los deshabilita — `initSinHorario`/`aplicarSinHorario`
  en `cargas.js`, clase `.horario-grid--deshabilitado`).
- `verificarSolapes([])`, `crearConHorario(.., [])` y `actualizarConHorario`
  toleran el arreglo vacío sin cambios (editar con el checkbox borra las sesiones).

### Migración `030_limpieza_bloques_falsos.sql` (aplicada en LOCAL y PROD)
- Borra las sesiones cuyo bloque dura ≤1 min y los bloques ≤1 min huérfanos.
- Recalcula `horas_semanales` de TODAS las cargas en **horas académicas**:
  `SUM(ROUND(min/duracion_hora_min))` con redondeo POR BLOQUE (igual que el
  imprimible). Cargas sin sesiones → 0. Idempotente.

### horas_semanales = HORAS ACADÉMICAS (antes horas reloj `round(min/60)`)
- Al guardar: `procesarFormulario` acumula `round(min/duracion)` por bloque con
  `CargaAcademicaModel::getDuracionHoraMin(configId)` (fallback 45).
- El imprimible (`horarioImprimir`) lee la duración de `configuracion_horario`
  del año activo. OJO: `getOrCreateConfiguracion` inserta 50 por defecto en años
  nuevos; el año 2026 tiene 45.

### Vista `/director/cargas/seccion/{id}`
- **Unidocente:** filas agrupadas por ÁREA — cabecera `.fila-area-grupo` (solo si
  el área tiene >1 carga) con horario consolidado (unión de `horario_resumen` de
  sus cargas) y suma de horas; debajo las subáreas (`.carga-subarea--indent`) con
  sus acciones intactas. Header con badge "Unidocente" + Tutor(a) de aula.
  Etiqueta `.carga-especialista` cuando `docente_id != tutor_id`.
- **Ambos casos:** carga sin bloques → "Sin horario propio" (`.carga-sin-horario`)
  y Hrs "—". Polidocente sigue con filas planas.
- `listarPorSeccion` expone `docente_id`, `area_real_id` y `subarea_orden`
  (ORDER BY área, orden de subárea); `findSeccion` trae el nombre del tutor.
  El agrupado `$grupos` se arma en `porSeccion()`. SASS en `pages/_cargas.scss`.
- `mis-cargas.php` (docente): "—" en vez de "0 hrs/semana".

### Reglas de negocio (decididas por el usuario)
- El horario del área se registra hacia adelante en la carga "dueña" (subárea de
  menor orden, misma convención que las TIC/GAMA), pero la vista muestra la unión
  de los bloques estén en la carga que estén — los existentes NO se movieron
  (en 1A el bloque real de CyT vive en Biología, orden 2).
- Las áreas que quedaron sin ningún bloque real (CyT/Matemática 4°-6°, etc.)
  permanecen "sin horario": los horarios reales se registrarán en producción.

### Migración `031_reparar_sesiones_cruzadas.sql` (01/07/2026)
- 11 sesiones antiguas apuntaban a cargas de **1°A secundaria** pero su
  `seccion_id` desnormalizado decía **3°B** (era el horario REAL de 3°B colgado
  de las cargas equivocadas). Bloqueaba registrar el horario de 3°B
  (`verificarSolapes` las encontraba por sección y por docente → "docente
  ocupado") y el imprimible del docente las mostraba como 3°B.
- La migración las mueve DINÁMICAMENTE a su carga gemela (misma sección declarada
  + mismo docente + misma área/subárea + activa; detecta el estado roto por
  `sh.seccion_id != ca.seccion_id`, sin IDs hardcodeados) y recalcula
  `horas_semanales` (mismo UPDATE de 030). Idempotente.
- Resultado: 3°B quedó con su horario completo; 11 cargas de 1°A secundaria
  quedaron "sin horario propio" — **PENDIENTE digitar el horario real de 1°A**.
- **Solape real preexistente conocido (NO tocado):** CLEMENTE ANGELES, lunes,
  1°C (14:40-16:10) vs 5°B (15:45-17:20) — debe resolverlo el colegio.

### Verificación de las migraciones 030/031 (aplicadas en LOCAL y PROD)
Query de sanidad — debe dar 0 sesiones cruzadas:
`SELECT COUNT(*) FROM sesiones_horario sh INNER JOIN cargas_academicas ca ON
ca.id=sh.carga_id WHERE sh.seccion_id != ca.seccion_id;`
Tampoco deben quedar bloques ≤1 min.

> Los pendientes vivos de horarios (digitación de 1°A secundaria y áreas sin
> bloques, solape de CLEMENTE ANGELES, hallazgo de "Reemplazar docente" en
> unidocente, recreos) se rastrean en `docs/ESTADO.md`.

## Tutoría (TOE) — horario sin calificaciones, calificable a futuro (02/07/2026)

> Cada sección tiene su hora de **Tutoría** (TOE): un bloque de 45 min del tutor
> con su sección, de coordinación/reflexión, **sin calificaciones hoy**. Debe verse
> en el **horario del docente tutor**. Modelado future-proof (Opción A): el día que
> se quiera calificar y mostrar en boleta, basta agregar competencias al área — la
> tubería de notas/boleta la recoge sola. Migración `032`. Sin front nuevo.

### Modelo — área `tipo='tutoria'` SIN competencias
- **Migración `032_area_tutoria.sql`**: amplía `areas.tipo` a
  `enum('area_curso','con_subareas','transversal','tutoria')` e inserta el área
  **"Tutoría (TOE)" por nivel** (primaria id 23, secundaria id 24 en local; idempotente
  con `NOT EXISTS`). **Aplicada y verificada en LOCAL y PROD (02/07/2026).**
- **Los dos ejes del sistema** (ver también horarios): "aparecer en horario" = ser
  carga con bloques (`getHorario` NO filtra por tipo); "tener notas" = que el área
  tenga **competencias**. Tutoría = carga con bloque + área sin competencias →
  visible en horario, invisible a notas **por datos, no por un `if` de tipo**.

### Creación (manual) y regla del tutor
- Se crea como **una carga más** desde `/director/cargas/crear` (o editar): se elige
  el área "Tutoría (TOE)" (aparece en el dropdown; `listarAreas` solo excluye `transversal`),
  el docente y se digita el bloque de 45 min. El JS trata `tutoria` como área-curso
  (sin subáreas) sin cambios.
- **Solo el tutor de la sección** puede dictarla: `procesarFormulario` valida
  `docente_id == secciones.tutor_id` cuando `area.tipo='tutoria'` (rechaza si la
  sección no tiene tutor o el docente no es el tutor).
- **Participa del control de solapes y suma su hora académica** como cualquier bloque
  real (sin código extra: `verificarSolapes` y `horas_semanales` operan por sesiones).

### Invisibilidad a notas HOY, reversible por datos
- **Registro de notas**: `CalificacionController::getCargas` y
  `PanelController::getCargasResumen` ocultan la carga de Tutoría **mientras su área
  no tenga competencias** (predicado NULL-safe
  `a.tipo != 'tutoria' OR EXISTS(competencias del área)`). Al agregar la primera
  competencia, la card **aparece sola** — future-proof por datos.
- **Boleta**: `getBoletaAlumno()` se engancha a competencias → hoy no muestra nada;
  con competencias+bloqueo a futuro la recoge como área normal (literal en primaria,
  numeral+literal en secundaria). **Sin cambios necesarios.**
- **Orden de mérito**: `OrdenMeritoModel` excluye `a.tipo NOT IN ('transversal','tutoria')`
  (líneas 112 y 184) — exclusión **permanente por tipo**, para que aun con notas a
  futuro Tutoría NO pese en el ranking (mismo criterio que transversal).

### Para HABILITAR calificaciones a futuro (checklist)
1. `INSERT` de 1+ competencias en el área Tutoría del nivel (ids 23/24 en local).
2. Nada más de código: registro de notas y boleta la muestran solas.
3. Decidir presentación en boleta si TOE debe verse distinto a un `area_curso`
   (hoy heredaría ese formato). El mérito ya la excluye.
