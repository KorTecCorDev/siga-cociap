# SIGA-COCIAP — Contexto del proyecto

## Descripción
Sistema Integrado de Gestión Académica del Colegio de Aplicación
"Víctor Valenzuela Guardia" — UNASAM, Huaraz, Ancash, Perú.
Proyecto de tesis para obtener el título de Ingeniero de Sistemas e Informática.

## Stack tecnológico
- **Backend:** PHP 8.2 — framework MVC propio (sin Laravel aún)
- **Frontend:** HTML + SASS + JavaScript vanilla
- **Base de datos:** MySQL (XAMPP local) - Versión de MariaDB 10.4.32-MariaDB // Conjunto de caracteres del servidor: UTF-8 Unicode (utf8mb4) // Apache/2.4.58 (Win64) OpenSSL/3.1.3 PHP/8.2.12 // Versión del cliente de base de datos: libmysql - mysqlnd 8.2.12 // Versión de PHP: 8.2.12
- **Build tool:** Gulp (SASS → CSS, BrowserSync)
- **Control de versiones:** Git + GitHub
- **Objetivo futuro:** Migrar a Laravel

## Arquitectura de carpetas
```
siga-cociap/
├── app/
│   ├── Controllers/
│   │   ├── Admin/UsuarioController.php            ← NUEVO (sesión 3)
│   │   ├── Auth/AuthController.php
│   │   ├── Boleta/BoletaController.php
│   │   ├── Docente/CalificacionController.php
│   │   ├── Director/OrdenMeritoController.php
│   │   ├── Padre/PanelController.php
│   │   ├── BaseController.php
│   │   └── DashboardController.php
│   ├── Models/
│   │   ├── BaseModel.php
│   │   ├── UsuarioModel.php
│   │   ├── CalificacionModel.php
│   │   └── CriterioModel.php
│   ├── Middleware/AuthMiddleware.php
│   └── Helpers/helpers.php
├── core/
│   ├── Router.php
│   ├── Database.php
│   ├── Session.php
│   └── View.php
├── config/
│   ├── app.php
│   └── database.php  ← NO está en Git (.gitignore)
├── database/
│   ├── backup_13_05_2026.sql ← Back up de la base de datos hasta la fecha 13/05/2026
│   └── seeds/
│       └── 003_demo_boletas.sql ← NUEVO (sesión 4) escenarios completos para presentación
├── public/
│   ├── index.php    ← front controller único
│   ├── .htaccess
│   ├── css/app.css  ← compilado por Gulp
│   ├── js/
│   │   ├── auth.js
│   │   ├── boleta-digital.js                 ← NUEVO (sesión 3)
│   │   ├── calificaciones.js
│   │   ├── cargas.js
│   │   ├── resumen.js
│   │   └── secciones.js                      ← NUEVO (sesión 4)
│   └── assets/
│       ├── img/logo_cociap.png   ← logo del colegio
│       ├── fonts/inter/          ← fuente Inter local
│       └── icons/                ← SVGs locales
├── resources/
│   ├── sass/
│   │   ├── app.scss              ← archivo principal
│   │   ├── base/(_variables, _reset, _typography)
│   │   ├── components/(_buttons, _forms, _alerts, _cards, _tables, _navbar)
│   │   └── pages/(_auth, _dashboard, _boleta, _boleta-digital, _admin)
│   │                                          ← _boleta-digital y _admin NUEVOS (sesión 3)
│   └── views/
│       ├── layouts/(auth.php, app.php, print.php, digital.php)
│       │                                      ← digital.php NUEVO (sesión 3)
│       ├── auth/login.php
│       ├── admin/usuarios/(index, crear, editar).php  ← NUEVO (sesión 3)
│       ├── admin/secciones/index.php                 ← NUEVO (sesión 4)
│       ├── dashboard/index.php
│       ├── boleta/(alumno.php, digital.php)   ← digital.php NUEVO (sesión 3)
│       ├── docente/(mis-cargas, calificaciones, resumen-competencia)
│       ├── director/(orden-merito, orden-merito-periodo)
│       ├── padre/(inicio, notas, alertas)
│       └── shared/(404.php, 403.php)
└── routes/web.php
```

## Base de datos — tablas principales
```
roles, personas, usuarios
niveles, grados, areas, subareas, competencias
reglas_especiales
anios_academicos, periodos, secciones
cargas_academicas, sesiones_horario, bloques_horario
estudiantes, apoderados, vinculo_familiar
matriculas, alertas
criterios, calificaciones_criterio, calificaciones
bloqueos_competencia
```

## Orden de ejecución SQL (setup desde cero)
```
1. migrations/000_crear_base_de_datos.sql
2. migrations/siga_cociap.sql
3. migrations/002_criterios_calificaciones.sql
4. migrations/003_bloqueos_competencia.sql
5. seeds/001_datos_prueba.sql
6. seeds/002_completar_sistema.sql
```
Los seeds 003 y 004 son solo para desarrollo/testing, no van en producción.

## Seed de demostración para presentación (sesión 4)
```
database/seeds/003_demo_boletas.sql
```
Ejecutar UNA SOLA VEZ sobre la BD restaurada desde **backup_18_05_2026.sql**.
Cubre tres escenarios completos (boleta imprimible + digital):
- **E1** Sec 1 (1°P A, unidocente user 21) → matriculas 1-5, periodo 1. Notas curadas AD/A/B/C.
- **E2** Sec 13 (1°S A, Taller Raz. Mat.) → matriculas 78-82, periodo 1. Notas REALES de docentes. Solo agrega Economía, Ed. Religiosa y bloqueo EPT.
- **E3** Sec 20 (4°S A, Arte=Raz.Mat.) → matriculas 106-110, periodo 1. Notas curadas AD/A/B/C.
URLs de boleta: `/boleta/{mat_id}/1` y `/boleta/digital/{mat_id}/1`
También corrige bloqueos erróneos de carga 38 (comp 54/55 → 56/57).

### Correcciones estructurales del seed (sesión 5 — backup 18/05)
- **cargas_academicas sin UNIQUE KEY** → usa `INSERT INTO ... WHERE NOT EXISTS` para todas las cargas
- **E1 docente correcto**: user 21 (no 4); carga 44 ya existe como transversal de sec 1
- **E2 cargas hardcodeadas**: usa IDs reales del backup (1-12, 17, 20, 21, 26, 28, 29, 42, 43). Solo inserta Economía (subarea 13) y Ed. Religiosa (area 14) como cargas nuevas
- **Bug carga 8 corregido**: carga 8 pertenece a sec 23 (5°S B), no a sec 13. Se eliminó toda referencia cruzada errónea
- **E2 calificaciones preservadas**: las notas reales ingresadas el 16-17/05 se mantienen via INSERT IGNORE

## Migración de limpieza (solo sobre DB existente, no en setup desde cero)
```
migrations/004_limpiar_datos_semilla.sql
```
Elimina criterios, calificaciones y bloqueos con FK inválidas generados
por seeds aplicados con FOREIGN_KEY_CHECKS=0. No afecta datos reales
(cargas 10/14, competencias 41-43, bloqueos válidos del docente SOTELO).

## Roles del sistema
| Código | Nombre | Acceso |
|--------|--------|--------|
| admin | Administrador | Todo |
| registro_academico | Registro Académico | Matrículas, traslados, documentos |
| director_general | Director General | Todos los niveles |
| director_ebr | Director EBR | Su nivel educativo |
| secretaria | Secretaria | Matrículas |
| docente | Docente | Sus cargas académicas |
| padre | Padre de Familia | Notas y alertas de su hijo |

## Usuarios de prueba (desarrollo)
- **Admin:** DNI `00000000` / pass `admin1234`
- **Docente:** DNI `12345678` / pass `admin1234`
- **Padre:** DNI `99999999` / pass `admin1234`

## Estructura curricular
- **Área con subáreas:** cada subárea tiene 1 competencia y 1 docente
- **Área-curso:** sin subáreas, 1 docente dicta todas las competencias
- **Unidocente:** primaria 1°-3°, flag `es_unidocente` en tabla secciones
- **Competencias transversales:** a cargo del tutor de sección

## Escala de calificaciones
- **Notas:** siempre numéricas 00-20 en BD
- **Primaria:** boleta solo muestra literal (AD/A/B/C)
- **Secundaria:** boleta muestra numeral + literal
- **Conclusión descriptiva:**
  - Primaria: obligatoria en B y C
  - Secundaria: obligatoria solo en C

## Flujo de calificaciones (módulo principal)
```
1. Docente entra a su carga académica
2. Define criterios de evaluación (libres, igual peso)
3. Ingresa notas por criterio para todos los alumnos
4. Sistema calcula promedio automáticamente
5. Docente ve resumen → agrega conclusiones descriptivas
6. Docente aprueba y bloquea la competencia
7. Padre puede ver notas, criterios y conclusiones
8. Padre accede a boleta desde /padre/notas:
   - "🖨 Imprimir"          → /boleta/{id}/{id}          (A4 landscape)
   - "Ver boleta digital"   → /boleta/digital/{id}/{id}  (mobile-first)
```

## Módulo de boleta imprimible (sesión 2)
- **Ruta:** `GET /boleta/{matricula_id}/{periodo_id}`
- **Roles con acceso:** admin, director_general, director_ebr, registro_academico, secretaria, padre
- **Restricción padre:** solo puede ver la boleta de su propio hijo (403 en otro caso)
- **Layout:** `resources/views/layouts/print.php` — sin navbar, sin flash, solo `app.css`
- **Vista:** `resources/views/boleta/alumno.php`
- **Estilos:** `resources/sass/pages/_boleta.scss`
- **Impresora objetivo:** RICOH MP4054 PCL6 — margen `@page: 0.5cm` por todos los lados
- **IMPORTANTE:** La boleta solo muestra competencias cuyo docente haya aprobado/bloqueado.
  `CalificacionModel::getBoletaAlumno()` hace INNER JOIN con `bloqueos_competencia`.

### Decisiones de diseño de la boleta imprimible
- **Conclusión descriptiva:** columna integrada de 60mm en la misma fila de la
  competencia (estilo SIAGIE). CSS `line-clamp: 3` con puntos suspensivos nativos.
  El texto completo se guarda en BD; el truncado es solo presentación CSS.
- **Subárea:** se antepone al nombre de la competencia para áreas `con_subareas`
  (ej: `Aritmética — C23. Resuelve problemas...`). Las áreas-curso no llevan prefijo.
- **Primaria:** muestra solo literal (AD/A/B/C); **Secundaria:** nota numérica + literal.
- **Pie de página:** tres líneas de firma — Tutor(a) de Aula, Director(a) Académico(a),
  Padre/Madre/Tutor(a).
- **buildBoletaData():** lógica de carga de datos extraída a método privado compartido
  entre `ver()` (imprimible) y `verDigital()` (digital).

## Módulo de boleta digital (sesión 3)
- **Ruta:** `GET /boleta/digital/{matricula_id}/{periodo_id}`
- **Mismos roles y restricción de padre** que la boleta imprimible.
- **Layout:** `resources/views/layouts/digital.php` — sin navbar, carga `boleta-digital.js`
- **Vista:** `resources/views/boleta/digital.php`
- **Estilos:** `resources/sass/pages/_boleta-digital.scss`
- **JS:** `public/js/boleta-digital.js` — acordeones, QR (Google Charts API), toast PDF

### Características de la boleta digital
- **Mobile-first, responsive** — 3 breakpoints: < 640px, 640-959px, ≥ 960px
- **Conclusiones descriptivas completas** — sin `line-clamp`, texto íntegro visible
- **Cards expandibles** por área curricular — colapsadas por defecto en móvil
- **QR de verificación** — imagen generada via `chart.googleapis.com`; se oculta sin internet
- **Botón PDF** — abre diálogo de impresión del navegador con instrucción "Guardar como PDF"
- **Print A4 portrait** — `@media print` expande todos los acordeones automáticamente
- **BEM prefix `.bd-`** en todos los elementos del componente
- **Logros con color semántico:** AD=verde, A=azul, B=naranja, C=rojo con borde izquierdo
- **`beforeprint` event** expande acordeones al usar Ctrl+P nativo
- **IMPORTANTE — orden de rutas:** la ruta literal `/boleta/digital/...` debe registrarse
  ANTES del patrón `/boleta/{matricula_id}/...` en `routes/web.php`, o el router la captura
  primero con parámetros incorrectos.

## Reglas especiales SIAGIE (secundaria)
- **1°-3° sec:** Taller Raz. Matemático → se registra en Ed. Religiosa en SIAGIE
- **4°-5° sec:** Raz. Matemático → se registra en Arte y Cultura en SIAGIE
- **Todos los grados:** Ed. Religiosa tiene alias "(Ética y Valores)"
- **Toda la secundaria:** EPT tiene alias "(Habilidades Pedagógicas)"

## Convenciones de código
- **Namespace:** `App\Controllers\`, `App\Models\`, `Core\`
- **Rutas:** `$router->get('/ruta', 'Namespace\Controlador@metodo')`
- **Vistas:** `$this->view('carpeta/archivo', ['variable' => $valor])`
- **JSON:** `$this->json(['success' => true, 'mensaje' => '...'])`
- **CSRF:** siempre `$this->validateCsrf()` en métodos POST
- **Commits:** Conventional Commits en español sin tildes
- **Estilos:** NUNCA CSS inline en PHP — siempre en SASS bajo `resources/sass/`
- **config():** la función NO soporta notación de puntos. Usar `config('institucion')`,
  NO `config('app.institucion')`. Las claves son las del array en `config/app.php`.

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

## Mejoras de UI/UX (sesión 3)

### Sticky columns en tablas docente
- **`/docente/calificaciones/...`** — columnas N° y Apellidos congeladas al hacer
  scroll horizontal (`.col-num` sticky left:0, `.col-nombre` sticky left:40px)
- **`/docente/calificaciones/.../resumen/...`** — mismo patrón; además:
  - `.col-criterio` min-width:80px, `.col-conclusion` min-width responsivo (200/260/320px)
  - `.fila-pendiente` conserva su background naranja en celdas sticky
  - `.conclusion-texto` reemplaza el inline `style="font-size:12px"`
- Ambas tablas viven dentro de `.tabla-notas-wrapper` (overflow-x:auto)

### Componentes SASS nuevos/extendidos
- **`_buttons.scss`** — `.btn-group { display:inline-flex; gap:$spacing-sm }` reutilizable
- **`_tables.scss`** — `.tabla-notas-wrapper`, `.tabla-resumen` con sticky columns y
  `.conclusion-texto`
- **`_admin.scss`** — `.usuario-avatar` (círculo con iniciales coloreado por rol),
  `.td-usuario`, `.td-acciones`, `.form-grid`, `.form-section-title`, `.form-actions`,
  `.select-rol`, `.text-danger`, `.text-sm`, `.fila-inactiva`
- **`_boleta-digital.scss`** — todo el sistema de diseño de la boleta digital (BEM `.bd-`)

## Fixes importantes aplicados (sesión 2)
- `periodos.nombre_display` es la columna correcta (no `nombre`). Si ves
  `Unknown column 'p.nombre'` en queries de periodos, verificar esto.
- `guardarConclusionAlumno` en CalificacionController envuelto en try-catch para
  garantizar siempre respuesta JSON (antes devolvía HTML en excepciones).
- Seed `002_completar_sistema.sql` agrega el usuario padre (DNI 99999999) que
  faltaba en `usuarios` — sin él el padre no puede loguear.
- Competencias completas para primaria y secundaria en seed 002.

## Fixes importantes aplicados (sesión 3)
- `CalificacionModel::getBoletaAlumno()` ahora hace INNER JOIN con
  `bloqueos_competencia` — la boleta solo muestra notas que el docente aprobó.
  Antes mostraba todas las notas guardadas aunque no estuvieran bloqueadas.
- Eliminado `003_bloqueos_competencia.sql.sql` (nombre con extensión duplicada).
- Orden de rutas en `routes/web.php`: `/boleta/digital/{id}/{id}` debe ir ANTES
  de `/boleta/{id}/{id}` para que el router no capture "digital" como parámetro.

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

## Fixes importantes aplicados (sesión 4)
- **Comillas tipográficas en PHP** — `resources/views/admin/secciones/index.php`
  tenía comillas U+201D (`"`) en atributos HTML del botón de asignación en lugar de
  comillas ASCII U+0022 (`"`). El parser HTML las trata como texto, no como delimitadores
  de atributo → todos los `data-*` quedaban rotos → `SyntaxError` en el browser.
  **Diagnóstico:** `node -e "const src=require('fs').readFileSync('file.php','utf8');
  for(let i=0;i<src.length;i++){const c=src.charCodeAt(i);if(c===0x201C||c===0x201D)
  console.log('linea',src.substring(0,i).split('\\n').length,src[i]);}"` 
  **Fix:** `src.replace(/[""]/g, '"')` y reescribir el archivo.
- **`data-label` con guillemets** — el atributo usaba `«»` literales que `e()` no escapa.
  Corregido a `data-label="<?= e($s['grado_numero'] . $s['seccion_nombre']) ?>"` 
  (formato `1A`, `2B`, etc. — simple y sin caracteres especiales).
- **Fuentes Inter 404** — `@font-face` usaba rutas absolutas con `/siga-cociap/public/`.
  Corregido a rutas relativas `../assets/fonts/inter/` en `_typography.scss`.

## Pendientes al 18 de mayo 2026
- [x] Boleta de calificaciones imprimible A4 ← completado sesión 2
- [x] Boleta digital mobile-first con QR ← completado sesión 3
- [x] Botón "Ver boleta digital" en panel del padre ← completado sesión 3
- [x] Gestión de usuarios (CRUD admin) ← completado sesión 3
- [x] Módulo secciones y tutores (modal asignación) ← completado sesión 4
- [x] Seed de escenarios de boleta para demo ← completado sesión 4
- [ ] Asignar tutores a secciones de primaria 1°B-6°B (actualmente sin tutor)
- [ ] Cargas académicas para primaria desde interfaz director (actualmente solo por BD)
- [ ] Verificar boleta impresa en RICOH MP4054 PCL6 (márgenes, paginación)
- [ ] Verificar acceso desde red local del colegio
- [ ] Módulo director: activar/cerrar periodos visualmente
- [ ] Vista de control: secciones con competencias aún sin bloquear
- [ ] Cargar datos reales del COCIAP
- [ ] Video tutorial para docentes
- [ ] Deploy en servidor del colegio

## Meta actualizada: presentación al comité directivo
Docentes subieron notas del I Bimestre el 16-17 de mayo 2026.
Presentación pendiente al comité directivo — seed de demo listo en `database/seeds/003_demo_boletas.sql`.

## Notas importantes
- `config/database.php` NO está en Git — crear manualmente en cada equipo
- Logo del colegio: `public/assets/img/logo_cociap.png` (con guión bajo)
- URL base dinámica via meta tag: `<meta name="base-url" content="...">`
- BrowserSync corre en puerto 3000, proxy a `:3000/`
- Alias Git Bash: `local3000` para iniciar el entorno
- `hash.php` en raíz: archivo temporal para generar hashes bcrypt — eliminar
  tras usarlo, NO commitear ni dejar en el servidor

## Listado de áreas o áreas-curso con sus respectivas subáreas o competencias.
### Áreas Curriculares y Competencias - Modelo SIAGIE - NIVEL SECUNDARIA
-IMPORTANTE. Un curso puede ser equivalente a un area-curso con varias competencias o una subarea vinculada a una sola competencia.

- Desarrollo Personal, Ciudadanía y Cívica (area-curso)
  Competencias
    Construye su identidad
    Convive y participa democráticamente en la búsqueda del bien común
- Ciencias Sociales (area)
  Competencias
    Construye interpretaciones históricas (subarea -> Historia)
    Gestiona responsablemente el espacio y el ambiente (subarea -> Geografía)
    Gestiona responsablemente los recursos económicos (subarea -> Economía)
- Educación Física (area-curso)
  Competencias
    Se desenvuelve de manera autónoma a través de su motricidad
    Asume una vida saludable
    Interactúa a través de sus habilidades sociomotrices
- Arte y Cultura (Razonamiento matemático) (area-curso) ¡RAZONAMIENTO MATEMÁTICO EN CASO DEL CUARTO Y QUINTO GRADO - cualquier sección!
  Competencias
    Aprecia de manera crítica manifestaciones artístico-culturales
    Crea proyectos desde los lenguajes artísticos
- Comunicación (area)
  Competencias
    Se comunica oralmente en su lengua materna (subarea -> Razonamiento Verbal)
    Lee diversos tipos de textos escritos en su lengua materna (subarea -> Literatura)
    Escribe diversos tipos de textos en su lengua materna (subarea -> Lenguaje)
- Inglés (area-curso)
  Competencias
    Se comunica oralmente
    Lee diversos tipos de textos escritos
    Escribe diversos tipos de textos
- Matemática (area)
  Competencias
    Resuelve problemas de cantidad (subarea -> Aritmética)
    Resuelve problemas de regularidad, equivalencia y cambio (subarea -> Álgebra)
    Resuelve problemas de forma, movimiento y localización (subarea -> Geometría)
    Resuelve problemas de gestión de datos e incertidumbre (subarea -> Trigonometría)
- Taller de Razonamiento Matemático (area-curso) ¡SOLO DEL PRIMER GRADO AL TERCER GRADO - cualquier sección!
  Competencias
  Resuelve problemas de cantidad
  Resuelve problemas de gestión de datos e incertidumbre
- Ciencia y Tecnología (area)
  Competencias
  Indaga mediante métodos científicos para construir sus conocimientos (subarea -> Química)
  Explica el mundo físico basándose en conocimientos sobre los seres vivos, materia y energía, biodiversidad, Tierra y Universo (subarea -> Biología)
  Diseña y construye soluciones tecnológicas para resolver problemas de su entorno (subarea -> Física)
- Educación Religiosa (area-curso)
  Competencias
  Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas
  Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa
- Educación para el Trabajo (area-curso)
  Competencias
  Gestiona proyectos de emprendimiento económico o social
- Competencias Transversales (caso especial) - Calificaciones registradas por el tutor
  Competencias Transversales / No Asociadas a Áreas
    Competencias
    Se desenvuelve en entornos virtuales generados por las TIC
    Gestiona su aprendizaje de manera autónoma
# Áreas Curriculares y Competencias - Modelo SIAGIE - NIVEL PRIMARIA
* IMPORTANTE.
  Solo desde el primer grado al tercer grado todas las areas son manejadas por un solo docente (UNIDOCENTE), todas las areas se convierten en area-curso.
  Las compentencias transversales son llenadas solo por el TUTOR de la sección.
* Personal Social (area-curso)
  Competencias
  Construye su identidad
  Convive y participa democráticamente en la búsqueda del bien común
  Construye interpretaciones históricas
  Gestiona responsablemente el espacio y el ambiente
  Gestiona responsablemente los recursos económicos
* Educación Física (area-curso)
  Competencias
  Se desenvuelve de manera autónoma a través de su motricidad
  Asume una vida saludable
  Interactúa a través de sus habilidades sociomotrices
* Arte y Cultura (area-curso)
  Competencias
  Aprecia de manera crítica manifestaciones artístico-culturales
  Crea proyectos desde los lenguajes artísticos
* Comunicación (area)
  Competencias
  Se comunica oralmente en su lengua materna (subarea -> Comunicación)
  Lee diversos tipos de textos escritos en su lengua materna (subarea -> Plan lector)
  Escribe diversos tipos de textos en su lengua materna (subarea -> Razonamiento verbal)
* Inglés como lengua extranjera (area-curso)
  Competencias
  Se comunica oralmente
  Lee diversos tipos de textos escritos
  Escribe diversos tipos de textos
* Matemática (area)
  Competencias
  Resuelve problemas de cantidad (subarea -> Aritmética)
  Resuelve problemas de regularidad, equivalencia y cambio (subarea -> Álgebra)
  Resuelve problemas de forma, movimiento y localización (subarea -> Geometría)
  Resuelve problemas de gestión de datos e incertidumbre (subarea -> Razonamiento matemático)
* Ciencia y Tecnología (area)
  Competencias
  Indaga mediante métodos científicos para construir sus conocimientos (subarea -> Química)
  Explica el mundo físico basándose en conocimientos sobre los seres vivos, materia y energía, biodiversidad, Tierra y Universo (subarea -> Biología)
  Diseña y construye soluciones tecnológicas para resolver problemas de su entorno (subarea -> Física)
* Educación Religiosa (area-curso)
  Competencias
  Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas
  Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa
* Competencias Transversales (caso especial) - Calificaciones registradas por el tutor
  Competencias
  Se desenvuelve en entornos virtuales generados por las TIC
  Gestiona su aprendizaje de manera autónoma
