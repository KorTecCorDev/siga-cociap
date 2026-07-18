# Módulo: Administración (usuarios, secciones, Director EBR, panel de bloqueos)

> Extraído VERBATIM de CLAUDE.md el 03/07/2026 (fase 1 de la red de documentación).
> Los invariantes globales y la tabla de enrutamiento viven en CLAUDE.md.

## Módulo de gestión de usuarios (sesión 3)
- **Rutas:** `GET/POST /admin/usuarios`, `/admin/usuarios/crear`, `/{id}/editar`, `/{id}/estado`
- **Rol requerido:** solo `admin`
- **Controlador:** `app/Controllers/Admin/UsuarioController.php`
- **Vistas:** `resources/views/admin/usuarios/` (index.php, crear.php, editar.php)
- **SASS:** `resources/sass/pages/_admin.scss`

### Operaciones del CRUD
- **index** — tabla con avatar de iniciales coloreado por rol, badges de rol/estado,
  último acceso, botones Editar y Activar/Desactivar
- **crear** — formulario en grid responsivo (1→2→3 col); secciones: datos personales + acceso
- **editar** — igual al crear con valores precargados; contraseña opcional (vacío = no cambia)
- **toggleEstado** — alterna activo/inactivo con `IF(estado='activo','inactivo','activo')`

### Protecciones del servidor
- No puedes desactivar tu propia cuenta
- No puedes cambiar tu rol si eres el único admin activo
- No puedes desactivar al último admin activo
- DNI único: `existeDni()` excluye el propio ID al editar

### Nuevos métodos en UsuarioModel
`findById`, `listarRoles`, `existeDni`, `crearConPersona`, `actualizarConPersona`,
`toggleEstado`, `contarPorRolCodigo`

### Convenciones del formulario de usuario
- Apellidos y nombres se almacenan en `mb_strtoupper()` (mayúsculas)
- Correo y teléfono se almacenan como `NULL` si el campo viene vacío
- Contraseña: bcrypt cost=12, mínimo 8 caracteres

## Módulo de secciones y tutores (sesión 4)
- **Rutas:** `GET /admin/secciones`, `POST /admin/secciones/{id}/tutor`
- **Rol requerido:** solo `admin`
- **Controlador:** `app/Controllers/Admin/SeccionController.php`
- **Modelo:** `app/Models/SeccionModel.php`
- **Vista:** `resources/views/admin/secciones/index.php`
- **JS fuente:** `resources/js/secciones.js` → compilado a `public/js/secciones.js`

### Características del módulo
- Tabla de secciones agrupada por nivel (separador de fila por nivel)
- Botón "Asignar" / "Cambiar" abre modal con select dinámico
- El select filtra docentes: solo muestra disponibles (sin tutoría en otra sección del año activo)
- La carga transversal se crea/actualiza/desactiva automáticamente al asignar/quitar tutor
- `SeccionModel::listarDocentes()` incluye subquery `tutor_seccion_id` para filtrar disponibles
- `SeccionController` serializa docentes como JSON (`$docentesJson`) para el select dinámico
- Los datos JSON se embeben en un `<div id="modalTutorData" data-docentes="...">` en la vista
- El JS lee el JSON y reconstruye el `<select>` cada vez que se abre el modal

### Fuentes Inter (@font-face)
- Las rutas en `_typography.scss` usan path relativo `../assets/fonts/inter/`
  (relativo al CSS compilado en `public/css/`). NO usar rutas absolutas con
  `/siga-cociap/public/...` — no funcionan en todos los entornos.

## Panel de bloqueos del director — hub de 3 tabs (16/06/2026)

> `/director/bloqueos` pasó de ser un scroll único a un **hub** con 3 cards-tab que
> separan los tres tipos de bloqueo. Mismo color wayfinding (académicas=azul,
> transversales=teal, conducta=púrpura).

### Estructura de la vista (`resources/views/director/bloqueos/index.php`)
- Selector de periodo (igual) + `.bloqueos-hub` con 3 `.bloqueos-tabcard` (mini-stat
  `X/Y` + barra + %). **Sin detalle hasta hacer clic.**
- Tres `<section class="bloqueos-panel" data-panel="..." hidden>`:
  - **academicas** — donut + widgets + ranking + tablas por sección (TODO lo que existía,
    preservado intacto, solo envuelto en el panel).
  - **transversales** — tabla TIC/GAMA cerrar/reabrir (lo que existía).
  - **conducta** — tabla NUEVA (ver abajo).
- **JS** (`resources/js/bloqueos.js`): tabs sin recargar; clic muestra un panel y oculta
  los demás; segundo clic colapsa; recuerda el último tab **por periodo** en
  `sessionStorage` (`bloqueos.tab.{periodoId}`) para no perder contexto tras un
  POST→redirect. El acordeón de secciones académicas se mantiene.
- **SASS** en `pages/_admin.scss`: `.bloqueos-hub`, `.bloqueos-tabcard--*` (tokens
  wayfinding; fondo tenue solo en `--activa`), `.bloqueos-panel`, `.td-acciones-conducta`.

### Conducta en el panel del director (gestión nueva)
- **Dos etapas** (igual que el flujo real): **auxiliar académico** registra/bloquea
  (etapa 1) → **tutor** cierra (etapa 2). Hoy la etapa 1 la hace el rol
  `registro_academico`, pero en la UI se etiqueta **"auxiliar académico"** (rol futuro;
  NO se creó el rol todavía).
- `$conducta[]` con `estado` ∈ `pendiente_auxiliar` (rojo) / `pendiente_tutor` (ámbar) /
  `cerrada` (verde) + columna "Calificados X/Y".
- **El director tiene control total:** forzar etapa 1, forzar etapa 2, o **reabrir**
  (anula con traza). Forzar etapa 1 RESPETA la regla de negocio (exige todos los
  estudiantes calificados; el botón se deshabilita y `bloquearRA` lo revalida en servidor).
  Reabrir es libre.
- **`ConductaModel::getResumenSeccionesPorPeriodo(int $periodoId)`** — espejo del de
  transversales (sección + tutor + estado de las 2 etapas del cierre), enriquecido con
  completitud reusando `getProgresoConductaPorSeccion`. Solo secciones del año del periodo
  CON tutor (la etapa 2 lo exige).
- **`Director\BloqueoController`**: inyecta `ConductaModel`; `index()` arma
  `$conducta`/`$conductaStats`/`$transStats` (todas inicializadas ANTES de los `if`, nunca
  indefinidas en la vista); métodos `bloquearConducta`/`cerrarConducta`/`reabrirConducta`
  (reusan `bloquearRA`/`cerrarTutor`/`anularCierre`) + helper privado `nivelIdDeSeccion`.
- **Rutas** (registradas ANTES de `/director/bloqueos/{id}/desbloquear`):
  `POST /director/bloqueos/conducta/{seccion_id}/{bloquear|cerrar|reabrir}`.

## Módulo Director EBR — historial de cargo (sesión 7)

### Tabla `director_ebr_historial`
```sql
id, usuario_id, anio_id, desde DATE, hasta DATE NULL,
asignado_por, asignado_en, firma_path VARCHAR(255), sello_path VARCHAR(255)
```
- `hasta = NULL` significa vigente. Un registro por periodo de cargo.
- `firma_path` / `sello_path`: ruta relativa a `public/` de los PNG (excluidos de Git).
- Al asignar nuevo director: cierra el registro vigente (`hasta = desde_nuevo - 1 día`)
  e inserta el nuevo. Transacción garantiza atomicidad.

### `DirectorEbrModel` — métodos clave
- `getVigenteEnFecha(int $anioId, ?string $fecha = null): ?array` — director en una fecha
  (NULL = hoy). Retorna `nombre_completo`, `firma_path`, `sello_path`.
- `asignar(...): int` — retorna ID del nuevo registro (necesario para subir imágenes).
- `actualizarImagenes(int $id, ?string $firma, ?string $sello): bool`
- `getHistorialPorAnio(int $anioId): array`

### `Admin\DirectorEbrController`
- Rutas: `GET /admin/director-ebr`, `POST /admin/director-ebr/{anio_id}/asignar`,
  `POST /admin/director-ebr/{id}/imagenes`
- Solo rol `admin`.
- Validación de PNG con `\getimagesize()` (NO `exif_imagetype()` — ext-exif deshabilitada
  en XAMPP). Límite 2 MB. Almacena en `public/assets/img/firmas/` (excluido de Git).
- Elimina el archivo anterior al reemplazar imagen.

### Uso en documentos
- **`OrdenMeritoController::imprimir()`** llama `getVigenteEnFecha($anioId)` sin fecha
  (siempre hoy — el documento se firma en el momento de impresión).
- **`Boleta\BoletaController::buildBoletaData()`** y **`Admin\BoletaPublicaController::buildBoletaData()`**
  incluyen `directorEbr` en su array de retorno.
- **`BoletaPublicaController` público** (sin login) también inyecta `DirectorEbrModel`.

### Firma y sello en vistas
| Vista | Elemento visible | CSS |
|-------|-----------------|-----|
| Boleta imprimible A4 | Firma PNG + nombre | `boleta-footer__espacio-firma` (18mm fijo) |
| Reporte orden de mérito A4 | Firma PNG + nombre | `reporte-footer__espacio-firma` (18mm fijo) |
| Boleta digital (pantalla) | Sello PNG | `bd-footer__img-area` (44px) + `.bd-solo-pantalla` |
| Boleta digital (al imprimir) | Firma PNG + nombre | `bd-footer__img-area` (14mm) + `.bd-solo-impresion` |

### Técnica de alineación de líneas de firma
Todos los bloques de firma (con y sin imagen) tienen un contenedor de **altura fija**:
- Print: `boleta-footer__espacio-firma` / `reporte-footer__espacio-firma` — 18mm
- Digital: `bd-footer__img-area` — 44px pantalla / 14mm print
- La imagen se ancla al fondo con `align-items: flex-end; justify-content: center`.
- El bloque sin imagen tiene el contenedor vacío de la misma altura → líneas al mismo nivel.
- `bd-footer__line` pasa a `height: 0` (solo dibuja el borde); el espacio lo provee `__img-area`.

### Reporte orden de mérito — footer dinámico
- Clase dedicada `.reporte-footer` en `_reporte-merito.scss` (no reutiliza `.boleta-footer`).
- `flex-wrap: wrap; justify-content: space-around; flex: 0 0 30%` por bloque → máx 3 por fila.
  4ª firma: nueva fila centrada. Soporta hasta 6 firmas (2 filas de 3).
- Firmas: Director EBR + 1 tutor por sección del grado (dinámico desde `$tutores`).
- `$infoConteos` muestra solo el número de áreas (no competencias — varían por docente).

## Nómina detallada admin/RA (22/06/2026)

> Reporte de matrículas para el comité, en 2 etapas. Documentado desde la
> memoria de sesión al crear la red (03/07/2026).

- **Etapa 1 (implementada):** nómina imprimible GLOBAL con filtros, agrupada por
  sección. NO toca la nómina del docente (`/docente/nomina`), que es otra vista.
- **Etapa 2 (pendiente):** resumen estadístico.
- **Retorno R3:** un alumno con retorno de grado activo aparece en su matrícula
  oficial Y en la operativa (comportamiento esperado en este reporte).

## Conducta: grilla de criterios en SOLO LECTURA para el tutor (07/07/2026)

El tutor puede consultar la matriz Si/No que registraron los auxiliares (RA),
ademas de la nota derivada que ya veia en su panel:

- **Ruta:** `GET /docente/conducta/{periodo_id}/criterios`
  (`Docente\ConductaTutorController::criterios`). Boton "Ver criterios
  evaluados por los auxiliares (lectura)" en `/docente/conducta` — solo
  se renderiza dentro del branch con cierre vigente.
- **Gate:** identico a la etapa 2 — visible SOLO con la conducta de la seccion
  BLOQUEADA Y APROBADA por RA (`getCierreVigente`). Sin cierre → redirect con
  mensaje. Ambos niveles (el guard `seccionTutor` es agnostico al nivel).
- **Vista:** `docente/conducta-criterios.php` — espejo de
  `admin/conducta/seccion.php` en su estado bloqueado (mismas clases
  `.conducta-grilla`/`.cc-btn` deshabilitadas), SIN formularios ni JS; la nota
  RA se calcula en servidor (Si/total x 20, `PHP_ROUND_HALF_UP`), no via JS.
- **Solo lectura por diseño:** la vista no expone ningun POST y los endpoints
  de escritura de conducta siguen gateados a admin/registro_academico.
- **B1 legado** (literal directo, sin matriz): estado vacio explicativo.

## Conducta: roster igual al del docente al calificar (09/07/2026)

El registro de conducta (RA/auxiliares y grilla del tutor) debe listar EL MISMO
roster que el docente ve al ingresar notas (`getAlumnosSeccion`): todos los
matriculados de la seccion — aprobada, **pendiente** (recien matriculado) y
**desactivado por baja administrativa/deuda** (sigue asistiendo) — con el UNICO
excluido siendo el traslado de salida (`tipo='trasladado'`), mas las exclusiones
de retorno de grado (oficial en retorno activo / operativa revertida).

- **Antes:** las 4 queries de `ConductaModel` filtraban `m.estado='aprobada'`, que
  dejaba fuera pendientes y desactivados-no-trasladados que el docente SI califica.
- **Ahora (paridad total con el docente):** `m.tipo != 'trasladado'` +
  `m.id NOT IN (retornos_grado oficiales activos / operativos revertidos)` en las
  CUATRO queries, que deben moverse juntas o la compuerta de cierre queda
  inconsistente:
  - `getEstudiantesParaRegistro` (grilla de RA),
  - `getProgresoConductaPorSeccion` (indice + panel del director),
  - `completitudSeccion` (compuerta "todos calificados" de `bloquearRA`),
  - `getEstudiantesParaTutor` (grilla del tutor).
- **Seguro contra NULL:** `matriculas.tipo` es `NOT NULL DEFAULT 'continuador'`
  (sin NULLs), asi que `tipo != 'trasladado'` no descarta filas por accidente.
- **Sin migracion.** Solo cambia el filtro SQL del roster; `getParaBoleta` y demas
  lecturas por matricula no se tocan (la boleta muestra la conducta segun el
  cierre, independiente del estado — coherente con boletas de desactivados).

## Conducta y Asistencia: historial por bimestre + imprimible oficial (17/07/2026)

Historial de lectura de los registros aprobados y bloqueados en `/admin/conducta`
y `/admin/asistencia`, con copia imprimible firmable. Migracion `043_cierres_asistencia`.

### Selector de bimestre (ambas vistas de seccion)
- `GET /admin/{conducta|asistencia}/{id}?periodo={pid}`: pestañas `.periodo-tabs`
  con los periodos del año activo **excepto los `pendiente`** (futuros, sin datos).
  Badges: "En curso" (editable) / "Aprobado" (cierre vigente) / "Sin cierre".
- Sin `?periodo=` el comportamiento es el previo (periodo editable). Un periodo
  NO editable se muestra en **solo lectura**: sin toolbar, sin botones de guardar,
  sin `page_scripts` (la nota RA de conducta se calcula en servidor, patron de
  `docente/conducta-criterios.php`); asistencia muestra los contadores como texto.

### Cierre de asistencia (nuevo — tabla `cierres_asistencia`)
- Una sola etapa (espejo parcial de `cierres_conducta`): RA "Bloquear y aprobar"
  via `POST /admin/asistencia/{id}/bloquear`. **SIN precondicion de completitud**:
  fila ausente en `inasistencias` = 0 incidencias (estado valido).
- El cierre vigente es `anulado_en IS NULL` (sin UNIQUE; `getCierreVigente` antes
  de insertar). `guardar()` rechaza edicion con cierre vigente (403), ademas del
  gate de `periodoEditable`.
- Desbloqueo SOLO desde el panel del director (con traza `anulado_por/motivo`).

### Imprimible oficial (layout print, A4 portrait)
- `GET /admin/{conducta|asistencia}/{id}/imprimir/{periodo_id}` — **gate: cierre
  vigente obligatorio** (sin cierre redirige con error). Conducta ademas exige
  matriz de respuestas (B1 legado de literal directo NO imprime; el boton se
  oculta en la vista).
- Estructura: `.boleta-header` (membrete + **fecha de impresion**), `.reporte-titulo`,
  `.tabla-registro` (criterios ✓/✗ + nota RA en conducta; 4 contadores en
  asistencia), leyenda de criterios, traza del cierre (quien/cuando) y
  `.reporte-footer` con DOS lineas de firma EN BLANCO tituladas
  **"Auxiliar Responsable"** y **"Personal de Registro Académico"** (el rol
  auxiliar_academico aun no existe como usuario; se firma a mano).
- SASS: `resources/sass/pages/_registro-cierre.scss` (pantalla + print).

### Panel del director (`/director/bloqueos`)
- 4.ª tabcard **Asistencia** (naranja `$card-nomina-*`) con su panel: tabla de
  secciones (registrados/esperados, estado, fecha) y acciones
  `POST /director/bloqueos/asistencia/{seccion_id}/{bloquear|reabrir}`
  (`AsistenciaModel::getResumenSeccionesPorPeriodo`, sin requisito de tutor).
