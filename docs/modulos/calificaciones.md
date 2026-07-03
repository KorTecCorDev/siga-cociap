# Módulo: Calificaciones, criterios y bloqueos

> Extraído VERBATIM de CLAUDE.md el 03/07/2026 (fase 1 de la red de documentación).
> Los invariantes globales y la tabla de enrutamiento viven en CLAUDE.md.

## Módulo soft-delete de criterios (sesión 7)
- **Migración:** `006_soft_delete_criterios.sql` — agrega `eliminado_en DATETIME NULL` y
  `eliminado_por INT UNSIGNED NULL FK→usuarios` a la tabla `criterios`.
- **Comportamiento:** eliminar un criterio con calificaciones hace soft-delete (el registro
  permanece en BD para auditoría). El promedio de la competencia se recalcula automáticamente.
- **Patrón de filtro:** todas las queries que leen criterios incluyen `AND eliminado_en IS NULL`
  (12 puntos actualizados en `CriterioModel`, `CalificacionModel` y `CalificacionController`).
- **`CriterioModel::eliminarConAuditoria(int $id, int $eliminadoPor): bool`** — método de borrado.
- **JS:** si el criterio tiene calificaciones, muestra confirm con advertencia y recarga la
  página tras el borrado para reflejar el promedio recalculado.

## Criterios — nombre máx 100 + descripción opcional (10/06/2026)
- **Migración:** `018_criterios_descripcion.sql` — `ADD COLUMN IF NOT EXISTS descripcion TEXT NULL`.
  La columna `nombre` sigue siendo VARCHAR(120): los 143 nombres existentes >100 quedan intactos.
- **Validación servidor:** `CalificacionController::CRITERIO_NOMBRE_MAX = 100`. `crearCriterio`
  y `renombrarCriterio` responden 422 con mensaje claro si el nombre excede — NUNCA truncar.
  Ambos endpoints aceptan `descripcion` opcional (vacía → NULL).
- **`CriterioModel::crear()`/`renombrar()`** reciben `?string $descripcion = null`.
- **UI docente** (`calificaciones.php` + `calificaciones.js`):
  - Contador en vivo `67/100` (`.contador-chars`, `--excedido` en rojo) en crear y editar.
  - Campo descripción opcional (textarea) en crear y editar.
  - Editor de criterio: si el nombre actual supera 100 (criterio antiguo), botón
    "↓ Mover a descripción" traslada el texto íntegro con un clic.
  - La descripción se muestra bajo el nombre (`.criterio-bloque__descripcion`).
- **Lectura:** resumen del docente → tooltip del `<th>` = nombre completo + descripción;
  vista del padre (`padre/notas.php`) → descripción como línea muted bajo el criterio
  (`.criterio-desc`). Queries actualizadas en `CalificacionModel` (resumen y padre).
- **SASS:** todo en `pages/_dashboard.scss` (OJO: `components/_dashboard.scss` NO está
  importado en `app.scss` — es código muerto, no agregar estilos ahí).

## Transversales por docente + cierre del tutor (10/06/2026)

> Desde el II Bimestre las competencias transversales (TIC/GAMA) las registra
> CADA docente en su propia carga. La carga transversal del tutor quedó
> DESACTIVADA (sus datos B1 siguen legibles). El tutor solo agrega conclusiones
> y cierra el bimestre desde `/docente/tutoria`.


### Registro por docente (Variante 1 de bloqueo)
- `formulario()` añade a cada carga la sección "Competencias Transversales"
  (`CriterioModel::getCompetenciasTransversalesConCriterios`) — mismo mecanismo
  de criterios/notas; flag `es_transversal` en la vista
  (`.competencia-card--transversal`, separador `.transversales-separador`).

### Agregación y boletas — REGLA ÚNICA
- `CalificacionModel::getBoletaAlumno()` EXCLUYE las filas crudas transversales
  (por el área de la COMPETENCIA, no de la carga) y agrega al final
  `getTransversalesAgregadas()`: **promedio de promedios por carga bloqueada**
  (cubre B1 = carga del tutor y B2+ = cargas de docentes), SOLO si existe
  cierre vigente. Conclusión desde `conclusiones_transversales`.
- Cubre automáticamente los 4 consumidores: boleta imprimible, digital,
  pública (admin y sin login) y `/padre/notas`.
- Orden de mérito y estadísticas siguen excluyendo transversales (filtran por
  el área de la competencia — las filas por docente no contaminan el ranking).

### Vista del tutor (`Docente\TutoriaController`)
- Rutas: `GET /docente/tutoria[/{periodo_id}]`,
  `POST /docente/tutoria/{periodo_id}/conclusion`, `POST .../cerrar`.
- Card "Tutoría" DESTACADA en `/docente/mis-cargas` (`.tutoria-card`, 3 estados:
  `⏳ Bloqueadas X de Y` / `✍ Disponible con N conclusiones pendientes` /
  `✅ Cerrado el {fecha}`). Solo tutores del año activo (`secciones.tutor_id`).
- Panel: selector de bimestre, tabla de promedios agregados TIC/GAMA, textarea
  de conclusión SOLO donde el literal la exige (B/C primaria, C secundaria).
- `cerrar` valida en servidor: todas las cargas activas bloqueadas + 0
  conclusiones obligatorias pendientes. JS: `resources/js/tutoria.js`.

### Integración con reaperturas
- `BloqueoController::desbloquear`: al desbloquear una competencia propia LIBERA
  en cascada las TIC/GAMA de esa misma carga (`TransversalModel::liberarTransversalesDeCarga`,
  competencias en área `tipo='transversal'`) y ANULA el cierre vigente de la
  sección con traza — todo en una transacción. Las transversales se registran
  bajo la carga del docente pero NO aparecen como filas en el panel; sin la
  cascada quedaban bloqueadas e inalcanzables. Se repite el ciclo re-bloqueo→re-cierre.
- `PeriodoController::reabrir`: YA NO libera bloqueos ni anula cierres
  automáticamente (ver "Origen del bloqueo" abajo). Solo reactiva el periodo y
  deja traza del motivo. La liberación de los bloqueos del cierre forzado es
  MANUAL desde el panel (`BloqueoController::limpiarBloqueosCierre`), que sí
  anula los cierres transversales de las secciones afectadas con traza.
- `SeccionModel::asignarTutor` YA NO crea/reactiva la carga transversal.

### Modelo `TransversalModel`
`getCompetencias(nivel)`, `getCierreVigente`, `cerrar`, `anularCierreVigente`,
`getPromediosMatricula/Seccion`, `getConclusiones*`, `guardarConclusion`,
`estadoCargasSeccion` (solo competencias PROPIAS de cargas activas),
`conclusionesObligatoriasPendientes`, `seccionesConBloqueosDeCierre`,
`liberarTransversalesDeCarga`, `anularCierresDeSecciones`, `getSeccionDelTutor`.

## Origen del bloqueo y reapertura quirúrgica (15/06/2026)

> **Bug corregido:** al cerrar B1 se bloquean TODAS las competencias de cargas
> activas (con o sin notas). Antes, al reabrir un bimestre, `reabrir()` borraba
> TODO bloqueo sin notas — incluidas las competencias finalizadas-vacías
> intencionalmente (casilleros cedidos SIAGIE / áreas no usadas) → se
> "rehabilitaban". Ahora reabrir NO borra nada; la liberación es manual y
> distingue el origen del bloqueo.

### Migración `020_bloqueos_origen.sql`
- `bloqueos_competencia.origen ENUM('docente','cierre') NOT NULL DEFAULT 'docente'`.
- Backfill por el DEFAULT: TODAS las filas existentes (incl. I Bimestre) → `'docente'`.
- A futuro solo el cierre forzado escribe `'cierre'`.

### Quién escribe cada origen
- `AnioAcademicoModel::bloquearCompetenciasPendientes` (cierre forzado) → `'cierre'`.
- `CalificacionModel::bloquearCompetencia` (aprobación del docente Variante 1 y
  bloqueo manual del director) → `'docente'`.

### Comportamiento
- `PeriodoController::reabrir`: solo reactiva el periodo + traza (no toca bloqueos).
- `BloqueoController::limpiarBloqueosCierre` (`POST /director/bloqueos/limpiar-cierre`):
  botón MANUAL en el panel de bloqueos. Borra `origen='cierre'` del periodo
  (requiere el bimestre `activo`/reabierto), anula los cierres transversales de
  las secciones afectadas con traza. Los `origen='docente'` (incl. finalizadas
  sin notas) NUNCA se tocan.
- `AnioAcademicoModel::eliminarBloqueosDeCierre` reemplaza a `eliminarBloqueosSinNotas`.
- El panel muestra el conteo `stats['cierre_forzado']` y etiqueta "por cierre
  forzado" en cada fila bloqueada por el cierre.

## Botón de competencia: "No se evaluó" / "Ver resumen" (24/06/2026)

> En `/docente/calificaciones/{id}` el botón de cabecera de cada competencia
> tiene 4 estados. Resuelve el viejo **Catch-22**: para finalizar una competencia
> SIN notas (casillero cedido SIAGIE / área no usada) había que entrar al resumen,
> pero "Ver resumen" estaba bloqueado justo por no haber notas → inalcanzable.
> Implementado, pusheado (commit `6aaaf6d`) y en prod.

### Máquina de 4 estados (vista `calificaciones.php`)
Variables calculadas por competencia justo tras `$esTransversal` (líneas ~106):

| Estado | Condición | Botón |
|--------|-----------|-------|
| Sin criterios (académica) | `!$esTransversal && !$compBloqueada && !$tieneCriterios && !$bloqueado` | **"No se evaluó"** — habilitado, acción terminal con `confirm()` |
| En progreso | ≥1 criterio y **alguno pendiente** (`!$todosConfirmados`) | **"Ver resumen"** bloqueado (`.btn-ver-resumen--bloqueado`, tooltip) |
| Listo | ≥1 criterio y **TODOS confirmados** (`$todosConfirmados`) **o** aprobada (`$resumenAccesible`) | **"Ver resumen"** habilitado |
| No evaluada | bloqueada con `alumnos_calificados == 0` (`$sinNotasBloqueada`) | badge **"No evaluada"** + mensaje propio en el cuerpo |

> **Endurecimiento (30/06/2026):** "Listo" pasó de `≥1 criterio confirmado` a
> `TODOS los criterios confirmados`. Ver "Resumen = solo confirmados" más abajo.

### Regla central — el autosave NO desbloquea (y AHORA re-bloquea)
- **"Ver resumen" se desbloquea SOLO con el clic en "Confirmar"** (el submit por
  criterio, endpoint `/guardar`). Ese handler llama
  `CriterioModel::marcarConfirmado()` que sella `criterios.confirmado_en` (solo si
  está NULL, para conservar la marca de la primera confirmación).
- **El autosave (`/autosave`, en `blur`/paste) NUNCA sella `confirmado_en`** → no
  se puede llegar al resumen salteándose el filtro de omisión ayudándose del
  autoguardado. (El filtro de omisión sigue obligando el motivo por alumno en
  blanco vía el modal "Confirmar y guardar".)
- **(30/06/2026) AHORA cualquier cambio del criterio DESCONFIRMA**
  (`CriterioModel::desconfirmar`): autosave de una nota (set o blank), omisión
  (`guardarOmisiones`) o edición de nombre/descripción (`renombrarCriterio`)
  vuelven el criterio a "pendiente". Antes solo desconfirmaba un blanco sin
  motivo. Ver "Resumen = solo confirmados".
- Al ser una **columna persistida** (no estado de sesión como antes, que el JS
  quitaba y al recargar volvía), el desbloqueo **sobrevive al recargado**.

### El candado de aprobación
> **Actualizado 30/06/2026** (ver "Resumen = solo confirmados"): se retiró la
> "puerta blanda". "Ver resumen" exige AHORA todos los criterios confirmados, y
> `errorBloqueoCompetencia` añadió la misma puerta (`competenciaListaParaResumen`)
> ANTES del chequeo por alumno. **Aprobar** sigue exigiendo además ≥1 criterio +
> todos los alumnos con nota u omisión. El camino "No se evaluó" (sin criterios)
> se evalúa antes de la puerta y no se ve afectado.

### "No se evaluó" — acción
Reusa el endpoint existente `POST /docente/calificaciones/{carga}/bloquear/{comp}`
con `sin_calificaciones=1` (mismo backend que el botón "no se trabajó" del resumen,
`errorBloqueoCompetencia` lo acepta solo si NO hay criterios). JS en
`calificaciones.js` (`.btn-no-evaluo`, con `confirm()` irreversible → recarga).
Crea un bloqueo `origen='docente'` sin notas. **NO aparece en transversales**
(no se bloquean individualmente) **ni en periodo bloqueado** (acción de escritura).

### Migración `026_criterios_confirmado.sql`
- `criterios.confirmado_en DATETIME NULL` + `confirmado_por INT UNSIGNED NULL`.
  Idempotente (`ADD COLUMN IF NOT EXISTS`).
- **Backfill**: todo criterio vivo con ≥1 nota queda confirmado (preserva el
  acceso de quien ya estaba calificando; el distingo autosave/confirmar aplica
  solo a ediciones futuras). 2308 confirmados / 15 vacíos sin confirmar al aplicar.

### SASS / lectura del estado
- `getCriterios()` hace `SELECT *` → `confirmado_en` llega solo a cada criterio
  sin tocar el modelo. La vista calcula `$todosConfirmados` recorriendo
  `$competencia['criterios']` (30/06/2026: era `$tieneConfirmado` ≥1).
- `pages/_dashboard.scss`: `.btn-no-evaluo` (discreto, borde punteado, vira a
  ámbar en hover) + `.competencia-card__acciones` (reemplazó un `style=""` inline).

## Resumen = solo confirmados — confirmación como única verdad (30/06/2026)

> El resumen (`/docente/calificaciones/{id}/resumen/{id}`) y el promedio agregado
> SOLO reflejan criterios CONFIRMADOS. Se retiró la "puerta blanda". `confirmado_en`
> (por criterio) es la única fuente de verdad de "esto es oficial". Sin migración
> (reusa 026). Commit `5d08463` en `dev`.

### Las tres reglas (alineadas)
1. **Promedio agregado = solo criterios confirmados.** `CalificacionModel::calcularPromedio`
   y la query de descubrimiento de `recalcularPromedioSeccion` filtran
   `AND cr.confirmado_en IS NOT NULL`. Un criterio pendiente no cuenta para el promedio.
   **Existencia de la fila (DELETE de huérfanos) ≠ promedio:** el DELETE borra la fila de
   `calificaciones` (incluida su `conclusion_descriptiva`) si y solo si el alumno NO tiene
   **ninguna nota viva** en la competencia — mira solo `cr.eliminado_en IS NULL`, **NO**
   `confirmado_en`. Así: (a) si el alumno conserva alguna nota, la fila y su conclusión se
   conservan aunque un criterio quede momentáneamente desconfirmado tras editarlo (retiene
   su `nota_numerica` anterior hasta re-confirmar; ese promedio transitorio no se muestra:
   el resumen exige todos confirmados y el resto de consumidores leen solo bloqueadas);
   (b) si el alumno se queda sin nota (borró la nota, omisión sin nota, o se eliminó el
   único criterio), la fila se elimina con su conclusión — una conclusión sin calificación
   NO debe persistir ni dejar un promedio fantasma en el resumen.
   > Historia: el 30/06 el DELETE filtraba `confirmado_en` → borraba la fila de un alumno
   > que SÍ tenía nota desconfirmada (data-loss de la conclusión). Un parche que conservaba
   > la fila "si tiene conclusión" produjo el bug inverso (fila fantasma de un alumno YA
   > sin nota). La regla correcta y final es **fila = existe nota viva**.
2. **Resumen (entrar + mostrar) = ≥1 criterio y TODOS confirmados.**
   `CriterioModel::competenciaListaParaResumen(cargaId, compId, periodoId)` (≥1 vivo
   y 0 pendientes; un criterio vacío cuenta como pendiente). El guard de `resumen()`
   y el `resumenAccesible` del autosave/guardar/omisiones/renombrar la usan (reemplazó
   a `existeConfirmado`, conservado sin uso en estos sitios). `getResumenCompetencia`
   recibe `soloConfirmados` (true solo desde `resumen()`; los demás callers — validación
   de `guardar`, `errorBloqueoCompetencia`, ConsultaNotas, histórico — en false).
3. **Aprobar = todos confirmados.** `errorBloqueoCompetencia` añadió la puerta
   `competenciaListaParaResumen` ANTES del chequeo por alumno (evita que un retoque
   no confirmado se pierda silenciosamente del promedio bloqueado). El branch
   "No se evaluó" (sin criterios) se resuelve antes y no se ve afectado.

### Desconfirmado en cascada — CUALQUIER cambio del criterio
Regla: cualquier mutación del criterio tras confirmarlo lo vuelve "pendiente".
- `autosave`: tras escribir/borrar la nota, llama `desconfirmar($criterioId)`
  **incondicional** y ANTES de `recalcularPromedioSeccion` (así el recalc lo excluye).
- `guardarOmisiones`: también desconfirma (registrar/cambiar omisión = cambio de
  composición) y devuelve `resumenAccesible`.
- `renombrarCriterio` (nombre **y** descripción; incluye "Mover a descripción"):
  desconfirma + recalcula + devuelve `resumenAccesible`; el JS sincroniza "Ver
  resumen" tras el éxito. **Guard:** si la competencia ya está aprobada/bloqueada,
  el renombrado se RECHAZA (criterio INMUTABLE para el docente, parejo con
  `eliminarCriterio`); para corregir un typo hay que reabrir el bimestre.
- `crearCriterio`: el nuevo criterio nace pendiente; su handler JS **recarga la
  página**, así que la vista recalcula `$todosConfirmados` sin lógica extra.
  **Guard nuevo:** rechaza agregar criterios a una competencia bloqueada.
- `guardar` (Confirmar): **orden crítico** — `marcarConfirmado` va ANTES de
  `recalcularPromedioSeccion` (con promedio solo-confirmados, el sello debe existir
  para que el criterio entre al cálculo). Devuelve `resumenAccesible`.

### Frontend
- Vista `calificaciones.php`: el estado "Listo" calcula `$todosConfirmados`
  (recorre `confirmado_en` de cada criterio; antes era `$tieneConfirmado` ≥1).
- `resources/js/calificaciones.js`: `ejecutarGuardado` (Confirmar) y el handler de
  renombrar sincronizan vía `sincronizarBotonResumen(competenciaId,
  data.resumenAccesible)`; el autosave ya lo hacía. Recompilar con `gulp build`.

### Sin impacto retroactivo
Las competencias YA bloqueadas no se recalculan (no hay más edits), y el backfill de
026 selló todo criterio con notas → ninguna boleta/orden de mérito histórico cambia.
La completitud de transversales / piso de carga cuenta notas crudas en
`calificaciones_criterio` (no el promedio), así que no se altera.

## Calificaciones — feedback en vivo del docente (30/06/2026)

> Dos detalles de UX en `/docente/calificaciones/{carga}` que antes solo se
> reflejaban al recargar. Commit `3ceb300` (en `dev` y `main`). Solo front
> (`calificaciones.js` + `_dashboard.scss`), sin BD ni endpoints.

- **Chip "X de Y" (alumnos con nota guardada)** del criterio se actualiza sin
  recargar: `actualizarProgresoCriterio(form)` cuenta los `.input-nota` con
  `data-nota-inicial` no vacío (lo PERSISTIDO, no `value` → un autosave fallido no
  infla el conteo); total = nº de inputs (alumnos no exonerados, los `EXO` no
  renderizan input). Se llama en `autoguardarCelda` y `ejecutarGuardado`.
- **Punto "pendiente de confirmar"** (`.criterio-bloque__estado--pendiente`):
  círculo ámbar al inicio del `.criterio-bloque__header`, visible SOLO si
  `confirmado_en` es null. `actualizarEstadoCriterio(bloque, pendiente)` lo enciende
  al editar/omitir/renombrar (desconfirma) y lo apaga al Confirmar. Solo se renderiza
  en competencias editables (`!$compBloqueada && !$bloqueado`). Ámbar = acción
  pendiente; no choca con `--con-cambios` (ese pinta el borde durante el tipeo).

## Módulo de consulta de calificaciones — solo lectura (22/06/2026)

> Capa de SUPERVISIÓN read-only: ver el detalle criterio-a-criterio de notas ya
> oficiales, sin editar. La edición sigue en `/rectificaciones` (que audita).

- **Eje:** periodo → sección → área/carga → grilla criterio-a-criterio.
- **Alcance:** SOLO lo oficial (competencias con bloqueo), mismo criterio que la
  boleta. Bimestres activos y cerrados.
- **Controlador:** `app/Controllers/Consulta/ConsultaNotasController.php`
  (`requireRole(['admin','registro_academico','director_general','director_ebr'])`).
  3 métodos: `index` (selector de periodo + grid de secciones), `seccion`
  (áreas/cargas de la sección), `carga` (grillas read-only por competencia).
- **Sin métodos de modelo nuevos:** navega con
  `CalificacionModel::getCompetenciasPorPeriodo()` filtrando `bloqueo_id != null`
  en PHP, y arma el detalle con `getResumenCompetencia()` (+ omisiones/exonerados,
  igual que el resumen del docente).
- **Vistas:** `resources/views/consulta-notas/{index,seccion,carga,_tabla}.php`.
  `_tabla.php` es el parcial read-only (mismo lenguaje visual que el resumen del
  docente: `.tabla-resumen`, `.nota-numeral`, `.exo-badge`, `.omision-badge`…),
  SIN inputs ni botones; la conclusión se muestra como texto. `carga.php` lo
  `require VIEW_PATH . '/consulta-notas/_tabla.php'` por cada competencia.
- **Rutas:** `/consulta-notas`,
  `/consulta-notas/{periodo_id}/seccion/{seccion_id}`,
  `/consulta-notas/{periodo_id}/carga/{carga_id}`.
- **Entradas:** botón "Consultar notas (lectura)" en `/director/bloqueos`
  (lleva el periodo actual) y en `/rectificaciones`.
- **SASS:** `pages/_consulta-notas.scss` (solo las listas de navegación; el detalle
  reusa estilos existentes). Importado en `app.scss`.
- **Filtro por nivel del Director EBR: NO aplicado**, igual que `/director/bloqueos`
  (no existe mapeo usuario→nivel en el sistema). Si se requiere, es un añadido aparte.
- `getCompetenciasPorPeriodo()` ahora incluye `pu.apellido_materno AS docente_materno`
  para mostrar el nombre completo del docente (no rompe `/director/bloqueos`).

### Histórico del docente (F2) — bimestres cerrados en solo lectura
- **Selector de bimestre en `/docente/mis-cargas`**: `<select class="form-select">`
  con layout compacto `.cargas-periodo` (en `pages/_dashboard.scss`). Default = activo
  (comportamiento de siempre). `getCargas()` ya recibe `periodoId`.
- Si el bimestre elegido NO es el activo (`$esHistorico`), las cards enlazan a la
  vista read-only y el badge del header marca "· solo lectura".
- **Ruta:** `GET /docente/calificaciones/{carga_id}/historial/{periodo_id}`
  (5 segmentos: no colisiona con el patrón base de 3, el router ancla `^…$`).
  Registrada ANTES del patrón base por orden de lectura.
- **`CalificacionController::historial()`**: valida que la carga sea del docente
  (`validarCargaDocente`, filtra por usuario → solo SUS cargas), arma sus
  competencias bloqueadas del periodo y **reutiliza `consulta-notas/_tabla.php`**
  vía la vista `resources/views/docente/historial-carga.php`.
- El `formulario` editable sigue clavado al periodo activo (`getPeriodoActivo`);
  el histórico es una ruta paralela de solo lectura.

## Guardar criterio atómico + validación de omisión (26/06/2026)

> Fix del "promedio fantasma". Documentado desde la memoria de sesión al crear
> la red (03/07/2026).

- `guardar()` (el Confirmar por criterio) es **atómico**: valida la omisión en el
  SERVIDOR — un alumno en blanco sin motivo de omisión responde **422** y no
  guarda nada. El JS hace **un solo fetch** (antes eran dos: notas + omisiones,
  con estados intermedios inconsistentes).
- `recalcularPromedioSeccion` limpia los huérfanos en `calificaciones` (DELETE de
  filas sin nota viva; ver regla "fila ⟺ nota viva" en CLAUDE.md).
- **NUNCA usar `guardarNotasMasivas` dentro de una transacción** (maneja la suya).

## Fixes importantes aplicados (sesión 2)
- `periodos.nombre_display` es la columna correcta (no `nombre`). Si ves
  `Unknown column 'p.nombre'` en queries de periodos, verificar esto.
- `guardarConclusionAlumno` en CalificacionController envuelto en try-catch para
  garantizar siempre respuesta JSON (antes devolvía HTML en excepciones).
- Seed `002_completar_sistema.sql` agrega el usuario padre (DNI 99999999) que
  faltaba en `usuarios` — sin él el padre no puede loguear.
- Competencias completas para primaria y secundaria en seed 002.
