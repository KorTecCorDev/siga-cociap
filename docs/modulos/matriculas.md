# Módulo: Matrículas y apoderados

> Extraído VERBATIM de CLAUDE.md el 03/07/2026 (fase 1 de la red de documentación).
> Los invariantes globales y la tabla de enrutamiento viven en CLAUDE.md.

## Módulo de matrículas (sesión 9)

### Propósito
Registro y gestión integral de matrículas: wizard de 3 pasos (estudiante →
apoderado → documentos), detalle, activación/desactivación, notas externas
para traslados de entrada y retorno de grado (caso especial).

### Migración — `database/migrations/012_modulo_matriculas.sql`
> El spec pedía `009`, pero `009` ya estaba ocupado dos veces
> (`009_inasistencias`, `009_token_acceso_matriculas`). Se usó el siguiente libre.
> Es idempotente (MariaDB 10.4 soporta `ADD COLUMN IF NOT EXISTS` y se usan
> bloques condicionales por `information_schema`).

Cambios sobre el esquema REAL (verificado contra la BD, difiere del spec):
- **roles:** `secretaria` → renombrado a `secretaria_academica` (nombre/descripcion
  actualizados); **nuevo** rol `secretaria_administrativa`. Los FK son por `id`,
  así que renombrar el `codigo` no rompe `usuarios`.
- **matriculas:** se agregaron `tipo ENUM('continuador','nuevo','trasladado')`
  (DEFAULT 'continuador' → las 528 filas existentes quedaron como 'continuador'),
  `serie_recibo VARCHAR(30)`. `anio_id` YA EXISTÍA (no se recreó).
- **matriculas.estado:** el enum real era
  `('registrada','pendiente_documentos','observada','aprobada','retirada')`.
  Se **AMPLIÓ** agregando `'pendiente','activo','desactivado'` que usa este módulo.
  La demo del I Bimestre sigue en `'aprobada'` (intacta). Mapa conceptual del
  módulo: nace `pendiente` → `activo` (al aprobar) / `desactivado` (al trasladar).
- **vinculo_familiar.tipo_vinculo:** el enum real era `('padre','madre','apoderado')`.
  Se amplió a los **14 tipos** (sin tildes en BD: `tio`,`tia`,…; la vista los
  muestra con tilde). Conserva el UNIQUE `(estudiante_id, tipo_vinculo)`.
- **boletas_publicas:** se agregó `activa TINYINT(1) DEFAULT 1` (para 7.3).
- **matriculas — UNIQUE relajado:** `uq_estudiante_anio (estudiante_id, anio_id)`
  se reemplazó por un índice NO único `idx_estudiante_anio`. **Por qué:** el
  retorno de grado exige DOS matrículas del mismo estudiante/año (oficial +
  operativa). La protección anti-duplicados del flujo normal se mantiene a nivel
  de aplicación (`MatriculaModel::existeMatricula()` + validación en el controlador).

Tablas nuevas: `documentos_matricula` (UNIQUE matricula+tipo_documento),
`notas_externas` (UNIQUE matricula+periodo+competencia), `retornos_grado`.

### Modelos
- **`app/Models/MatriculaModel.php`** — `listar(filtros)`/`contar(filtros)` (con
  paginación por `limit`/`offset`), `findById`, `existeMatricula`, `crear` (siempre
  `estado='pendiente'`), `cambiarEstado` (deja traza en `observaciones`; si `activo`
  setea `aprobado_por`/`fecha_aprobacion`), `sugerirSeccion` (sección con MENOS
  matrículas activas del grado), `seccionAnioAnterior` (para continuador),
  `crearEstudianteConPersona` (reutiliza persona/estudiante si el DNI ya existe),
  documentos (`getDocumentos`, `registrarDocumento` idempotente con ON DUPLICATE),
  notas externas (`getNotasExternas`, `registrarNotaExterna`), y auxiliares de
  filtros (`listarAnios`, `listarGrados`, `listarSecciones`).
- **`app/Models/ApoderadoModel.php`** — `buscarPorDni` (incluye sus vínculos),
  `findById`, `crear` (persona+apoderado en transacción; reutiliza por DNI),
  `vincularEstudiante` (idempotente por el UNIQUE), `getHijos(apoderadoId, anioId)`,
  `contarHijosActivos` (regla máx 3), `getVinculos(estudianteId)`,
  `desactivarUsuarioDeEstudiante` (apaga el login del apoderado al trasladar).

### Controladores (namespace `Matricula\`)
- **`MatriculaController`** — `requireRole(['admin','registro_academico',
  'secretaria_academica','secretaria_administrativa'])`. Métodos: `index`, `create`,
  `store`, `apoderado`, `storeApoderado`, `documentos`, `storeDocumentos`, `show`,
  `activar`, `desactivar`, `notasExternas`, `storeNotasExternas`.
  - `activar`/`desactivar` hacen `requireRole(['admin','registro_academico'])`
    EXTRA dentro del método (las secretarías NO pueden cambiar estado).
  - `activar` valida ≥1 apoderado vinculado.
  - `desactivar` (transacción): `estado='desactivado'` + `tipo='trasladado'`,
    desactiva el usuario del apoderado y pone `boletas_publicas.activa=0` del
    periodo activo.
  - `store` crea estudiante si es nuevo, evita duplicado por año, resuelve sección
    (sugerida o posteada), serie de recibo OBLIGATORIA, y redirige al paso 2.
  - Tipos de vínculo y catálogo de documentos viven como constantes del controlador.
- **`RetornoGradoController`** — `requireRole(['admin','registro_academico',
  'director_ebr'])`. `create`/`store`. Crea matrícula operativa en grado inferior
  (`estado='activo'`), inserta en `retornos_grado` y **transfiere las calificaciones**
  de la oficial a la operativa con `INSERT IGNORE` (preserva competencia/periodo).

### Rutas (`routes/web.php`)
Las literales (`/matriculas/crear`) y los sub-recursos (`/{id}/apoderado`,
`/{id}/documentos`, `/{id}/activar`, `/{id}/desactivar`, `/{id}/notas-externas`,
`/{id}/retorno`) se registran ANTES del patrón genérico `GET /matriculas/{id}`
(que va al FINAL) para que el router no capture `crear` como `{id}`.

> Las rutas antiguas `/secretaria/matriculas` y `/director/matriculas` apuntaban a
> controladores que NUNCA existieron (placeholders). Este módulo NO las usa; el
> dashboard ahora enlaza a `/matriculas`.

### Vistas (`resources/views/matriculas/`, layout `app`)
`index` (filtros: año/grado/sección/estado/tipo/búsqueda + paginación de 25),
`crear` (wizard paso 1), `apoderado` (paso 2), `documentos` (paso 3), `show`
(detalle con cards), `notas-externas`, `retorno`. SASS: `pages/_matriculas.scss`
(importado en `app.scss`) con `.wizard-steps`, `.matricula-badge`, `.apoderado-card`,
`.documento-checklist`, `.busqueda-dni`, `.mat-filtros`, `.mat-paginacion`,
`.form-check`. Reutiliza `.card`, `.info-grid`, `.tabla-notas`, `.form-grid`, `.badge`.

### Integraciones con módulos existentes
- **7.1 Orden de mérito** (`Director\OrdenMeritoController`): en los métodos de
  ranking/conteo, `m.estado='aprobada'` pasó a `m.estado IN ('aprobada','activo')`
  y se EXCLUYE la matrícula oficial de un retorno activo
  (`m.id NOT IN (SELECT matricula_oficial_id FROM retornos_grado WHERE estado='activo')`).
  Así el estudiante compite en su grado OPERATIVO. Las queries de descubrimiento de
  grados (otro espaciado) NO se tocaron.
- **7.2 Calificaciones** (`Docente\CalificacionController::getAlumnosSeccion`):
  `m.estado='aprobada'` → `m.estado IN ('aprobada','activo') AND m.tipo != 'trasladado'`.
  Incluye estudiantes en retorno (operativa = 'activo' en esa sección) y excluye
  trasladados. **Nota:** el spec tenía una contradicción (regla "trasladado SIEMPRE
  aparece en calificaciones" vs. 7.2 "excluir trasladado"); se siguió la instrucción
  explícita 7.2. Sin impacto en la demo (todas las filas son 'aprobada'/'continuador').
- **7.3 Boletas públicas:** `desactivar()` hace `UPDATE boletas_publicas SET activa=0`
  del periodo activo (columna `activa` añadida en la migración).
- **7.4 Dashboard:** la card "Matrículas" ahora apunta a `/matriculas` y es visible
  para `admin`, `registro_academico`, `secretaria_academica`, `secretaria_administrativa`.
  `DashboardController` envía a esas secretarías al dashboard (ven sus cards).

### Reglas de negocio aplicadas
- Toda matrícula nace `pendiente`; solo admin/registro_academico activan/desactivan.
- Serie de recibo obligatoria siempre. Sección sugerida pero confirmable.
- Máx 3 apoderados por estudiante; máx 3 estudiantes activos por apoderado/año.
- Continuador → solo `recibo_pago`; nuevo → todos los documentos.
- Notas externas solo para `tipo='nuevo'`.

## Consolidación de estados de matrícula (IMPLEMENTADO)

> Antes el enum tenía `'aprobada'` y `'activo'` como SINÓNIMOS de "matrícula
> vigente" (bug latente: el módulo activaba a `'activo'`, pero boleta, boleta
> pública, asistencia, conducta, panel del padre, año académico y parte de orden
> de mérito filtraban solo por `'aprobada'` → un alumno `'activo'` quedaba
> INVISIBLE). Se eliminó `'activo'`; la activación pasa a `'aprobada'`.

### Estados finales: SOLO TRES
| Estado | Significado | Reglas |
|--------|-------------|--------|
| `aprobada` ("Aprobado") | Estudiante correctamente matriculado, sin pendientes | Cuenta para TODO (boleta, orden de mérito, notas) |
| `pendiente` | Documentos/observaciones pendientes | Matrícula incompleta; el motivo lista los faltantes |
| `desactivado` | No matriculado por algún motivo (**motivo obligatorio**) | Apaga login del apoderado; SIN orden de mérito; SIN boleta |

El motivo visible vive en `matriculas.motivo_estado` (**TEXT**); `observaciones`
queda como traza de auditoría histórica. Se muestra junto al badge en `index` y `show`.

### Migración — `017_estados_matricula_consolidacion.sql`
> El plan original pedía `013`, pero `013`–`016` ya estaban ocupados (reaperturas,
> limpieza_estados, desempates, traslados). Se usó `017`. Idempotente.
- `ADD COLUMN IF NOT EXISTS motivo_estado TEXT NULL AFTER estado`.
- `UPDATE matriculas SET estado='aprobada' WHERE estado='activo'` (0 filas, sin pérdida).
- `MODIFY estado ENUM('pendiente','aprobada','desactivado') NOT NULL DEFAULT 'pendiente'`.

### Cambios de código
- **`MatriculaModel::cambiarEstado($id, $estado, $usuarioId, ?string $motivo = null)`**:
  guarda `motivo_estado` (`null` lo limpia); traza en `observaciones`; setea
  `aprobado_por`/`fecha_aprobacion` cuando el estado es `'aprobada'`. `listar()`
  ahora selecciona `m.motivo_estado` (`findById` ya usa `m.*`).
- **`MatriculaController`**: `activar()` → `'aprobada'` + motivo `null`;
  `desactivar()` → **motivo OBLIGATORIO** desde el POST (rechaza vacío) y lo pasa a
  `cambiarEstado`; el caso de re-pendiente anota los requisitos faltantes como motivo.
- **`'activo'` eliminado** de todas las queries de matrícula: `MatriculaModel`
  (sugerirSeccion), `ApoderadoModel`, `CalificacionModel`, `OrdenMeritoModel`,
  `ControlOperativoModel`, `OrdenMeritoController`, `Docente\CalificacionController`,
  `RetornoGradoController` (la operativa nace `'aprobada'`), `TrasladoController`
  (valida `=== 'aprobada'` y pasa el motivo del traslado a la baja).
  > OJO: `retornos_grado.estado='activo'`, `periodos.estado IN('activo','cerrado')`,
  > `anios/usuarios/areas.estado='activo'` son OTRAS columnas — NO se tocaron.
- **Vistas** `matriculas/index.php` y `show.php`: label `'aprobada' => 'Aprobado'`,
  filtro de estado actualizado, `<textarea name="motivo" required>` en el form de
  desactivar, y `.matricula-motivo` bajo el badge. SASS en `_matriculas.scss`
  (`.matricula-motivo`, `.mat-desactivar-form`; enum de badges reducido a 3 estados).

## Alta provisional sin DNI — estudiante en trámite (02/07/2026)

> Un padre matricula a su hijo pero no dejó el DNI ni los documentos de traslado,
> y el docente ya necesita calificarlo. Se permite dar de alta al estudiante SIN
> DNI real con un **código provisional**; la matrícula nace `pendiente` (ya
> calificable) y se regulariza antes de activar. Commit `24099b4` en `dev`.
> **Sin migración** (el código cabe en `personas.dni`).

### La premisa que lo hace de bajo riesgo (dos hechos del sistema)
- **Calificar NO exige `aprobada`:** `Docente\CalificacionController::getAlumnosSeccion`
  trae a TODOS los matriculados de la sección (`aprobada`, `pendiente` e incluso
  `desactivado`); el único excluido es `tipo='trasladado'` + casos de retorno. Una
  matrícula `pendiente` YA aparece en la grilla del docente.
- **`pendiente` no contamina documentos oficiales:** boleta exige `aprobada`+bloqueo
  y orden de mérito exige `aprobada`. El provisional queda fuera hasta regularizar.

### Código provisional — formato y punto único de verdad
- Formato **`P` + 7 dígitos** (`P0000001`, `P0000002`…). Cabe en `personas.dni`
  (`varchar(8) NOT NULL UNIQUE`) e es inconfundible con un DNI real (8 dígitos
  numéricos). El primero será `P0000001` (0 provisionales en BD al implementar).
- **`es_dni_provisional(?string $dni): bool`** en `app/Helpers/helpers.php` —
  verdadero si empieza con `P`. Lo usan controlador y vistas para distinguirlo.
- **`MatriculaModel::generarDniProvisional()`** — `MAX(dni) WHERE dni LIKE 'P%'` +1
  (ancho fijo → el MAX lexicográfico == máximo numérico).
- **`MatriculaModel::crearEstudianteProvisional(array $datos)`** — persona +
  estudiante en transacción, con **reintento ante colisión** del código por
  concurrencia (el UNIQUE de `personas.dni` rechaza el duplicado, SQLSTATE 23000 →
  regenera, máx 5). Los datos NO deben incluir `dni` (lo asigna el método).

### Alta (`MatriculaController::store`, rama `provisional=1`)
- Ignora el DNI; exige **apellido paterno + nombres** (materno/fecha/sexo opcionales).
- **Serie de recibo OPCIONAL** en el alta provisional (el padre no dejó recibo); se
  sigue exigiendo antes de activar (la reclama `pendientesParaActivar`).
- Crea la matrícula `pendiente` con `motivo_estado = "Registro provisional —
  pendiente de DNI y documentos"`. Sección sugerida igual que el alta normal.
- La rama redirige y termina ANTES de la validación normal (con DNI), que queda
  intacta. Roles: los 4 del módulo pueden crear provisional.

### Candado de aprobación (integridad)
- `pendientesParaActivar()` agrega, si `es_dni_provisional($matricula['dni'])`:
  *"Reemplazar el DNI provisional por el DNI real"*. Como `activar()` bloquea con
  la lista no vacía, **un DNI falso NUNCA llega a `aprobada`** (ni a boleta/mérito).
  Aparece como requisito faltante en `show.php`.

### Regularización (cuando llega el DNI real)
- Se reemplaza el código por el DNI real desde **"Editar datos"** del detalle
  (`actualizarEstudiante`, que ya valida 8 dígitos). Al hacerlo, el candado se
  levanta solo.
- **Colisión:** si el DNI real ya pertenece a OTRA persona (el alumno ya existía),
  `dniEnUsoPorOtra` **rechaza y avisa** para resolución manual (mensaje mejorado;
  la fusión de registros es manual, NO automática).

### Frontend
- `crear.php`: toggle **"El estudiante aún no tiene DNI (registro provisional)"**
  (checkbox `name="provisional"`) + `data-dni-group`. `create()` inyecta
  `page_scripts=['matriculas']`.
- `resources/js/matriculas.js` (→ `gulp build`): al marcar, oculta el campo DNI
  (`disabled` → no se envía) y relaja la obligatoriedad de DNI y serie. El servidor
  es la autoridad (el form es `novalidate`); el JS es solo UX.
- Badge **"Provisional"** en `show.php` (junto al DNI) y **"prov."** en `index.php`;
  estilo `.badge-provisional` en `_matriculas.scss` (ámbar de estado, no wayfinding).

### Pendiente menor (no implementado)
- La búsqueda del index por texto que empiece con `P…` cae en la rama de nombre y
  no matchea el código provisional. Ajuste chico en `construirFiltros` si se pide.

## Exoneraciones en el detalle de matrícula (07/07/2026)

> `/matriculas/{id}` ahora MUESTRA las exoneraciones vigentes del alumno y
> permite REGISTRAR nuevas desde ahí (antes solo existía `/admin/exoneraciones`
> por sección, y el detalle no las mencionaba — caso real: PEÑA PILLACA 3°A
> primaria, exonerada de Ed. Religiosa desde el 24/05, invisible en su detalle).

- **Card "Exoneraciones"** en `show.php` (entre Notas externas y Constancia de
  traslado): SOLO consulta — lista vigentes (área/subárea — motivo — fecha —
  registrador) via `ExoneracionModel::getVigentesPorMatricula()`. Visible para
  todos los roles con acceso al detalle.
- **Registro — REUBICADO (09/07/2026):** el formulario ya NO vive en la card del
  grid (columnas de ~300px: el `form-grid--2` colapsaba, el select desbordaba y
  la card quedaba desproporcionada). Ahora es la fila **"Exonerar de área"** en
  la card "Gestión de la matrícula" (`.mat-accion` + formulario desplegable
  `.mat-exonerar-form`, mismo patrón disclosure que Desactivar/Editar datos:
  `data-exonerar-{form,control,toggle,cancel}` en `matriculas.js`; sin JS el
  form se ve abierto). Solo `puedeGestionar` (= admin/RA, la card entera está
  gated) y con `opcionesExoneracion` no vacías. El select de
  `getOpcionesParaSeccion()` postea a `POST /matriculas/{id}/exonerar` →
  `Admin\ExoneracionController::registrarDesdeMatricula()` — reusa parseo,
  **candado de notas vivas** (`tieneNotasVivas`, 07/07) y `registrar()`; usa el
  `anio_id` de la matrícula y vuelve al detalle. Las secretarías no ven el form
  y el controlador les rechaza el POST por rol.
- **Revocar** sigue SOLO en `/admin/exoneraciones/{seccion}` (el form desplegable
  enlaza "Revocar en Exoneraciones").

## Notas autorizadas por dirección para SIAGIE (14/07/2026)

> El SIAGIE exige la nota de cada competencia evaluada para TODA la sección y no
> deja cerrar con celdas vacías. Un alumno con ausencia justificada (salud,
> accidente, viaje) queda correctamente SIN nota en SIGA (omisión con motivo
> `ausencia_justificada`) → su celda del SIAGIE queda en blanco. Dirección ordena
> consignar una nota para poder cerrar en el SIAGIE. Se resuelve con un **"informe
> aparte"** que alimenta SOLO el export (no toca boleta ni orden de mérito).

- **Tabla** `notas_autorizadas_siagie` (migración 040): `matricula+competencia+periodo
  → nota_literal + conclusión + resolución`, UNIQUE. Modelo `NotaAutorizadaSiagieModel`.
- **Pantalla** `/matriculas/{id}/notas-siagie` (solo admin/RA; card resumen en `show`).
  Muestra, por bimestre, las competencias AUTORIZABLES y las ya registradas.
- **Candado de elegibilidad** (`esElegible`, validado en servidor): la competencia
  debe tener una **omisión registrada** del alumno (CUALQUIER motivo — basta que el
  docente la haya marcado, no se exige `ausencia_justificada`), estar **bloqueada** y
  **sin calificación viva**. `competenciasElegibles` además oculta las ya autorizadas.
- **Conclusión** obligatoria según la escala vigente (`conclusion_es_obligatoria`:
  primaria B/C, secundaria C). La resolución de dirección es obligatoria.
- **Export:** `LlenadorSiagie` rellena con ella SOLO la celda en blanco de una
  competencia bloqueada; la nota real de `calificaciones` siempre tiene precedencia.
  Se reporta aparte y suma `resumen['autorizadas']`. Ver `docs/modulos/export-siagie.md`.
- **Informe imprimible** `/matriculas/{id}/notas-siagie/informe` (layout `print`):
  respaldo físico con firma del director EBR vigente. SASS `pages/_notas-siagie-informe`.
- **Rutas** literales/sub-recursos ANTES del patrón `{id}` (invariante del router).
- **Retorno de grado:** la evaluación (omisiones, elegibles, nota autorizada) vive
  en la matrícula OPERATIVA. El controlador resuelve la "matrícula de evaluación"
  (`matriculaEvaluacion`: operativa si hay retorno) y opera ahí; la card del detalle
  (que se ve en la OFICIAL) apunta a la operativa. El export une fuentes con
  `boletaContexto`, así procesa la oficial y encuentra la nota de la operativa.
- **NO toca:** `calificaciones`, `bloqueos_competencia`, boleta, orden de mérito.
- OJO: en secundaria, la exoneración de Ética y Valores se registra contra el
  área **Tutoría (TOE)** (id 24) — así aparece rotulada la opción en el select
  (nombre interno; la boleta la muestra como "Ética y Valores"). Ver
  `docs/modulos/calificaciones.md`.

## Tipo `retirado` — estudiante que ya no asiste (22/07/2026)

> Un estudiante deja de asistir pero la familia NO tramita el traslado oficial
> (no hay constancia ni IE destino; guarda la esperanza de que regrese). Hasta la
> migración 045 el único marcador de "abandono" era `tipo='trasladado'`, que exige
> el trámite oficial → un desactivado por otro motivo seguía apareciendo en las
> grillas del docente/tutor. `retirado` cubre el hueco: excluir de evaluación sin
> constancia.

- **Semántica de `matriculas.tipo`** (los "no calificables" son siempre `desactivado`):

  | estado | tipo | ¿se califica? |
  |---|---|---|
  | aprobada / pendiente | continuador·nuevo | ✅ Sí |
  | desactivado (deuda u otro motivo, **sigue asistiendo**) | continuador·nuevo | ✅ Sí |
  | desactivado, **ya no asiste** | `retirado` | ❌ No |
  | desactivado, traslado oficial | `trasladado` | ❌ No |

- **Exclusión de los 9 rosters de evaluación** (`!= 'trasladado'` →
  `NOT IN ('trasladado','retirado')`): `Docente\CalificacionController::getAlumnosSeccion`,
  `ConductaModel` (×5), `CalificacionModel` (resumen/validación de bloqueo),
  `TransversalModel`, `Docente\TutoriaController`.
- **NO se tocan** los usos de `trasladado` en boleta (`BoletaController`): un retirado
  es `desactivado` no-trasladado → cae en **BORRADOR forzado** (sin QR/firma), NO en
  el trato OFICIAL del trasladado. Tampoco la nómina de *ver* boletas del docente
  (`PanelController:713/801`) — verla no es calificar. Orden de mérito y SIAGIE ya
  excluyen `desactivado`.
- **Acción reversible** en "Gestión de la matrícula" (`show.php`, rol admin/RA):
  **"Marcar como retirado"** (`POST /matriculas/{id}/retirar` → `retirar()`), solo
  sobre `desactivado` de tipo continuador/nuevo; guarda el tipo real en
  `tipo_anterior`. Inversa **"Revertir"** (`/revertir-retiro` → `revertirRetiro()`)
  restaura `tipo_anterior` (el estado sigue `desactivado`). `activar()` también
  restaura el tipo al reactivar por completo (condición extendida a
  `IN ('trasladado','retirado')`).
- **Migración 045** (`MODIFY tipo ENUM(...,'retirado')`, idempotente). `tipo_anterior`
  NO incluye `retirado` (nunca se revierte hacia él). Badge `matricula-badge--retirado`.
