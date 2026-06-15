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
│   │   ├── Admin/SeccionController.php             ← NUEVO (sesión 4)
│   │   ├── Admin/BoletaPublicaController.php       ← NUEVO (sesión 6)
│   │   ├── Admin/DirectorEbrController.php         ← NUEVO (sesión 7)
│   │   ├── Auth/AuthController.php
│   │   ├── Boleta/BoletaController.php
│   │   ├── BoletaPublicaController.php             ← NUEVO (sesión 6) público sin login
│   │   ├── Docente/CalificacionController.php
│   │   ├── Director/OrdenMeritoController.php
│   │   ├── Padre/PanelController.php
│   │   ├── BaseController.php
│   │   └── DashboardController.php
│   ├── Models/
│   │   ├── BaseModel.php
│   │   ├── UsuarioModel.php
│   │   ├── SeccionModel.php                        ← NUEVO (sesión 4)
│   │   ├── BoletaPublicaModel.php                  ← NUEVO (sesión 6)
│   │   ├── DirectorEbrModel.php                    ← NUEVO (sesión 7)
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
│       ├── img/logo_cociap.png      ← logo del colegio
│       ├── img/firmas/              ← PNGs de firma/sello Director EBR (excluidos de Git)
│       ├── fonts/inter/             ← fuente Inter local
│       └── icons/                   ← SVGs locales
├── resources/
│   ├── sass/
│   │   ├── app.scss              ← archivo principal
│   │   ├── base/(_variables, _reset, _typography)
│   │   ├── components/(_buttons, _forms, _alerts, _cards, _tables, _navbar)
│   │   └── pages/(_auth, _dashboard, _boleta, _boleta-digital, _admin, _reporte-merito)
│   │              ← _boleta-digital y _admin NUEVOS (sesión 3); _reporte-merito (sesión 7)
│   └── views/
│       ├── layouts/(auth.php, app.php, print.php, digital.php)
│       │                                      ← digital.php NUEVO (sesión 3)
│       ├── auth/login.php
│       ├── admin/usuarios/(index, crear, editar).php  ← NUEVO (sesión 3)
│       ├── admin/secciones/index.php                 ← NUEVO (sesión 4)
│       ├── admin/director-ebr/index.php              ← NUEVO (sesión 7)
│       ├── dashboard/index.php
│       ├── boleta/(alumno.php, digital.php)   ← digital.php NUEVO (sesión 3)
│       ├── docente/(mis-cargas, calificaciones, resumen-competencia)
│       ├── director/(orden-merito, orden-merito-periodo, reporte-merito)
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
boletas_publicas        ← NUEVO (sesión 6)
director_ebr_historial  ← NUEVO (sesión 7)
```

## Orden de ejecución SQL (setup desde cero)
```
1. migrations/000_crear_base_de_datos.sql
2. migrations/siga_cociap.sql
3. migrations/002_criterios_calificaciones.sql
4. migrations/003_bloqueos_competencia.sql
5. migrations/004_limpiar_datos_semilla.sql
6. migrations/005_boletas_publicas.sql
7. migrations/006_soft_delete_criterios.sql   ← sesión 7
8. migrations/007_director_ebr_historial.sql  ← sesión 7
9. migrations/008_director_ebr_imagenes.sql   ← sesión 7
   (… 009-017 según database/migrations/)
10. migrations/018_criterios_descripcion.sql  ← criterios: descripción opcional
11. migrations/019_transversales_docente.sql  ← transversales por docente + cierre del tutor
12. migrations/020_bloqueos_origen.sql        ← origen del bloqueo (docente/cierre)


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
- **Umbrales literales (actualizados 10/06/2026):** AD: 18-20 · A: 14-17 · B: 11-13 · C: 00-10.
  Definidos como PUNTO ÚNICO DE VERDAD en `app/Helpers/helpers.php`:
  constantes `NOTA_MIN_AD` (18), `NOTA_MIN_A` (14), `NOTA_MIN_B` (11).
  - Conversión PHP: SIEMPRE via `nota_a_literal()` (los modelos delegan, no duplican el match).
  - Queries SQL: interpolan las constantes (`OrdenMeritoModel` x2, `ControlOperativoModel`,
    `AnioAcademicoModel`). NUNCA hardcodear el umbral en una query nueva.
  - Leyendas de boletas: `escala_rangos()` genera los rangos de texto.
  - El cambio es retroactivo automático: la BD solo guarda `nota_numerica`; el literal
    se calcula al vuelo (B1: 717 notas de 17 pasaron de AD a A).
  - Los desempates `num_alto IN (15,16)` y `num_16` del orden de mérito NO se tocaron
    (decisión del colegio pendiente sobre regenerar el ranking B1).
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
- **Pie de página:** DOS firmas — Tutor(a) de Aula y Director(a) E.B.R. (sesión 7).
  Se eliminó "Padre/Madre/Tutor(a)". Las líneas se alinean con `boleta-footer__espacio-firma`
  de 18mm fijo en ambos bloques (firma PNG anclada al fondo con `align-items: flex-end`).
- **buildBoletaData():** lógica de carga de datos extraída a método privado compartido
  entre `ver()` (imprimible) y `verDigital()` (digital). Incluye `directorEbr` con
  `firma_path` y `sello_path` del Director EBR vigente.

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
- **QR de verificación** — generado en cliente con `qrcode.min.js` (librería local, sin terceros)
- **Botón PDF** — abre diálogo de impresión del navegador con instrucción "Guardar como PDF"
- **Print A4 portrait** — `@media print` expande todos los acordeones automáticamente
- **BEM prefix `.bd-`** en todos los elementos del componente
- **Logros con color semántico:** AD=verde, A=azul, B=naranja, C=rojo con borde izquierdo
- **`beforeprint` event** expande acordeones al usar Ctrl+P nativo
- **Pie de página:** DOS firmas — Tutor(a) de Aula y Director(a) E.B.R. (sesión 7).
  Pantalla: sello PNG del director (`.bd-solo-pantalla`). Al imprimir: firma PNG + nombre
  (`.bd-solo-impresion`). Alineación con `bd-footer__img-area` de 44px/14mm fijo.
- **IMPORTANTE — orden de rutas:** la ruta literal `/boleta/digital/...` debe registrarse
  ANTES del patrón `/boleta/{matricula_id}/...` en `routes/web.php`, o el router la captura
  primero con parámetros incorrectos.

## Módulo de boletas públicas con código de acceso (sesión 6)

### Propósito
Durante el I Bimestre NO se dará acceso con login a los ~1000 padres.
En su lugar: el admin genera boletas por bimestre, cada una con un
**código de acceso único**, se imprimen con el código + QR y se entregan
físicamente. El padre consulta la **boleta digital pública** (sin login)
ingresando ese código o escaneando el QR.

### Compatibilidad con lo existente
Este módulo NO reemplaza nada. Reutiliza la infraestructura ya construida:
- **Reutiliza** `CalificacionModel::getBoletaAlumno()` (ya filtra por
  `bloqueos_competencia` — solo muestra competencias aprobadas).
- **Reutiliza** `buildBoletaData()` y la vista `resources/views/boleta/digital.php`
  ya existente de la sesión 3 — la boleta pública renderiza el mismo
  componente `.bd-` pero a través de una ruta sin autenticación.
- **No toca** las rutas `/boleta/{id}/{id}` ni `/boleta/digital/{id}/{id}`
  existentes (esas siguen siendo para usuarios autenticados / padres).
- El acceso público es una **capa nueva y paralela**, no una modificación.

### Base de datos — nueva tabla
`database/migrations/005_boletas_publicas.sql`
```sql
CREATE TABLE IF NOT EXISTS boletas_publicas (
    id               INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    matricula_id     INT UNSIGNED NOT NULL,
    periodo_id       SMALLINT UNSIGNED NOT NULL,
    codigo_acceso    VARCHAR(30) NOT NULL UNIQUE,
    veces_consultada INT UNSIGNED NOT NULL DEFAULT 0,
    ultima_consulta  DATETIME NULL,
    generada_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    generada_por     INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_matricula_periodo (matricula_id, periodo_id),
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
    FOREIGN KEY (periodo_id)   REFERENCES periodos(id),
    FOREIGN KEY (generada_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```
Formato del código: `COCIAP-2026-B1-XXXXXX` (XXXXXX = 6 alfanuméricos
mayúsculos aleatorios, sin caracteres ambiguos: sin O/0/I/1/L).
Insertar en orden de ejecución SQL después de `004_limpiar_datos_semilla.sql`.

### Modelo nuevo
`app/Models/BoletaPublicaModel.php` extiende `BaseModel`:
- `generarCodigo(int $anio, int $numBimestre): string` — código único verificado
- `generarMasivo(int $periodoId, int $usuarioId): int` — INSERT IGNORE para
  todas las matrículas `aprobada` con ≥1 calificación bloqueada en el periodo
- `getPorPeriodo(int $periodoId): array` — lista con estudiante, grado, sección
- `getPorCodigo(string $codigo): ?array` — busca por código; si existe
  incrementa `veces_consultada` y setea `ultima_consulta`; retorna
  `matricula_id` + `periodo_id` para reutilizar `getBoletaAlumno()`

### Controlador admin
`app/Controllers/Admin/BoletaPublicaController.php`
hereda `BaseController`, `requireRole(['admin','registro_academico'])`:
- `index()` — `GET /admin/boletas-publicas` — selector de periodos
- `porPeriodo($periodoId)` — `GET /admin/boletas-publicas/{periodo_id}`
- `generar($periodoId)` — `POST /admin/boletas-publicas/{periodo_id}/generar`
- `imprimir($periodoId)` — `GET /admin/boletas-publicas/{periodo_id}/imprimir`
  usa `layouts/print.php`, una boleta por página con código + QR visibles

### Controlador público (SIN login)
`app/Controllers/BoletaPublicaController.php` hereda `BaseController`
pero **NO** llama `requireAuth()` ni `requireRole()`:
- `formulario()` — `GET /boleta-publica` — campo para ingresar código
- `consultar()` — `POST /boleta-publica/consultar` — valida código,
  reutiliza `CalificacionModel::getBoletaAlumno()`, renderiza la boleta
  digital pública; código inválido → mensaje de error

### Rutas (routes/web.php)
```php
// Boletas públicas SIN login — registrar ANTES de las rutas /boleta/{id}
$router->get('/boleta-publica',           'BoletaPublicaController@formulario');
$router->post('/boleta-publica/consultar','BoletaPublicaController@consultar');
// Admin
$router->get('/admin/boletas-publicas',                       'Admin\BoletaPublicaController@index');
$router->get('/admin/boletas-publicas/{periodo_id}',          'Admin\BoletaPublicaController@porPeriodo');
$router->post('/admin/boletas-publicas/{periodo_id}/generar', 'Admin\BoletaPublicaController@generar');
$router->get('/admin/boletas-publicas/{periodo_id}/imprimir', 'Admin\BoletaPublicaController@imprimir');
```
**IMPORTANTE — orden de rutas:** igual que la lección de sesión 3 con
`/boleta/digital`, las rutas literales `/boleta-publica` deben ir ANTES
que cualquier patrón `/boleta/{matricula_id}/...` para que el router no
capture "publica" como parámetro.

### AuthMiddleware
Agregar `/boleta-publica` y `/boleta-publica/consultar` al array de rutas
públicas en `app/Middleware/AuthMiddleware.php` (junto a `/login`, etc.).

### Vistas
- `resources/views/admin/boletas-publicas/index.php` — selector periodos (layout app)
- `resources/views/admin/boletas-publicas/periodo.php` — tabla + botón generar (layout app)
- `resources/views/admin/boletas-publicas/imprimir.php` — `layouts/print.php`,
  una boleta por página, código + QR visibles, page-break-after
- `resources/views/boleta-publica/formulario.php` — `layouts/digital.php`,
  diseño institucional simple, logo COCIAP, campo código + botón
- `resources/views/boleta-publica/boleta.php` — `layouts/digital.php`,
  reutiliza el componente `.bd-` de `boleta/digital.php` (boleta completa)

### Estilos
Reutilizar `_boleta-digital.scss` (componente `.bd-`). Solo agregar lo mínimo
para el formulario de código en `_boleta-digital.scss` o un parcial nuevo
`resources/sass/pages/_boleta-publica.scss` importado en `app.scss`.
NUNCA CSS inline en PHP (convención del proyecto).

### Reglas de negocio
- Primaria: solo literal (AD/A/B/C). Secundaria: numeral + literal.
- Solo competencias con docente que aprobó/bloqueó (ya lo garantiza
  `getBoletaAlumno()` con su INNER JOIN a `bloqueos_competencia`).
- Código permanente (no se regenera). Toda la boleta visible (no resumen).
- QR vía `chart.googleapis.com` (mismo patrón que boleta digital sesión 3);
  se oculta sin internet. El QR apunta a `/boleta-publica` con el código.
- Vista pública sin sesión, sin navbar, sin datos de otros alumnos.
- CSRF con `$this->validateCsrf()` en `POST /boleta-publica/consultar`.

### Reglas especiales SIAGIE (secundaria)
Los talleres propios de COCIAP no son áreas oficiales del MINEDU: sus notas se
registran en el casillero de un área oficial que cede ese tramo de grados.
- **Taller de Razonamiento Matemático** → se registra en **Ed. Religiosa (1°-4°)**
  y en **Arte y Cultura (5°)**.
- **Taller de Pre Cálculo** (solo 5°) → se registra en **Educación para el Trabajo**.
- **Áreas oficiales que ceden su casillero** (solo registran sus notas reales en el tramo libre):
  - **Educación Religiosa:** notas reales solo en **5°** (1°-4° lo ocupa RazMat).
  - **Arte y Cultura:** notas reales solo en **1°-4°** (5° lo ocupa RazMat).
  - **Educación para el Trabajo:** notas reales solo en **1°-4°** (5° lo ocupa Pre Cálculo).
- **Alias:** únicamente **Ed. Religiosa de 5° secundaria** se muestra como "(Ética y Valores)".
  Ningún otro área lleva alias (EPT ya NO usa "(Habilidades Pedagógicas)").

## Decisiones de implementación — sesión 6 (boletas públicas)
- **Sin herencia cruzada**: `BoletaPublicaController` (público) duplica `buildAreasConBimestres()`
  y las queries privadas de `BoletaController` en lugar de extenderlo, para mantener los
  contextos de auth completamente separados.
- **Vista `boleta-publica/boleta.php`** usa `require VIEW_PATH . '/boleta/digital.php'`
  directamente: reutiliza el componente `.bd-` sin duplicar HTML.
- **`getPorCodigo` actualiza estadísticas** antes de retornar el registro; el contador
  se incrementa en cada consulta real (POST), no en escaneos de QR previos.
- **`generarMasivo` es idempotente**: verifica si ya existe `(matricula_id, periodo_id)`
  antes de insertar → se puede llamar varias veces sin duplicar.
- **Código formato** `COCIAP-{anio}-B{num}-XXXXXX` con 32 caracteres alfanuméricos
  sin ambigüedad (sin O/0/I/1/L). `random_int()` garantiza entropía criptográfica.
- **Rutas públicas** registradas antes de `/boleta/{id}/{id}` en `routes/web.php`
  y en `AuthMiddleware::$publicRoutes` para que no requieran sesión.
- **QR en imprimir**: apunta a `/boleta-publica?codigo=...` vía Google Charts API,
  igual que la boleta digital de sesión 3.

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

### Migración `019_transversales_docente.sql`
- Tabla `conclusiones_transversales` (UNIQUE matricula+competencia+periodo) —
  conclusión del TUTOR, independiente de las cargas.
- Tabla `cierres_transversales` (seccion+periodo; vigente = `anulado_en IS NULL`;
  anulación con `anulado_por`/`motivo_anulacion`).
- Sellado retroactivo B1: cierre por cada sección cuya carga transversal del tutor
  quedó totalmente bloqueada (20 de 23 secciones; las otras 3 no tenían transversales).
- Migra las conclusiones B1 del tutor (33) a la tabla nueva.
- Desactiva las cargas transversales (`estado='inactiva'`).

### Registro por docente (Variante 1 de bloqueo)
- `formulario()` añade a cada carga la sección "Competencias Transversales"
  (`CriterioModel::getCompetenciasTransversalesConCriterios`) — mismo mecanismo
  de criterios/notas; flag `es_transversal` en la vista
  (`.competencia-card--transversal`, separador `.transversales-separador`).
- **`bloquear()` — Variante 1:** al bloquear la ÚLTIMA competencia propia valida
  que TIC/GAMA estén completas (mensaje claro con el detalle) y bloquea TODO
  junto en transacción. Las transversales NO se bloquean individualmente (400).
  Exonerados de la carga quedan excluidos (mismo tratamiento que el área).
- En el resumen de una transversal: sin botón aprobar ni textareas de conclusión
  ("La registra el tutor al cierre del bimestre").

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

## Seguridad y estado REAL de producción (sesión 8 — endurecimiento)

> Esta sección refleja cómo quedó realmente el despliegue y **SUPERSEDE** lo que
> diga distinto la sección "Producción — Hostinger" de más abajo (esa describía el
> plan original con subdominio y docroot a `public/`; el real usa el dominio).

### Despliegue real (Hostinger)
- **Dominio:** `sigacociap.net` (se descartó el subdominio).
- **Ruta en servidor:** `~/domains/sigacociap.net/public_html` (es el repo; clon shallow → "grafted").
- **Document root:** `public_html` (NO `public/`). El `.htaccess` raíz reescribe todo a `public/`.
- **SSH:** `ssh -p 65002 u761410128@89.116.115.116`.
- **BD:** `u761410128_siga_cociap` / usuario `u761410128_ktcdev` (contraseña rotada).

### El auto-deploy BORRA todo lo no versionado (CRÍTICO)
El Git auto-deploy de Hostinger hace un **checkout limpio** en cada push: elimina
archivos en `.gitignore` y carpetas no rastreadas. Por eso **todo secreto o archivo
subido debe vivir FUERA del repo** (`~/...`), nunca bajo `public_html/`.

### Credenciales de BD — fuera del repo
- Viven en `~/siga_secrets/database.php` (array de conexión, `chmod 600`).
- `config/database.php` es un **cargador versionado SIN secretos**: si existe el archivo
  externo lo usa, si no cae al fallback de XAMPP local. Ya NO se gitignora.

### Firmas/sello del Director EBR — fuera del repo
- Se guardan en `~/siga_uploads/firmas/` (config `firmas_path`, env-aware por `is_dir`).
  El directorio externo debe existir ANTES de subir (si no, cae al fallback local).
- Se sirven por la ruta pública `GET /firmas/{archivo}` (`FirmaController`, valida el
  nombre contra path traversal). La subida guarda en BD `firmas/{archivo}`.
- Ya NO se usa `public/assets/img/firmas/`.

### config/app.php — valores env-aware (no fijos)
- `debug`: `true` solo en hosts locales/privados (localhost, 127.*, 192.168.*, 10.*);
  `false` en producción y ante un `Host` inyectado (default seguro OFF).
- `app_url`: `https://sigacociap.net` si el host es `sigacociap.net`; `''` en local.
- `firmas_path`: `~/siga_uploads/firmas` en prod; `storage/firmas` en local.
- El manejo de errores se cablea en `public/index.php` según `debug`:
  producción → `display_errors=0` + `log_errors=1`; local → `display_errors=1`.

### .htaccess raíz — endurecido
- **Fuerza HTTPS** (301, loop-safe con `X-Forwarded-Proto` / puerto 443).
- **Cabeceras** (en `<IfModule mod_headers.c>`, que LiteSpeed sí procesa): HSTS,
  `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`.
- **Niega** acceso directo a `.git`, carpetas internas (app/core/config/database/
  resources/storage) y extensiones sensibles (sql/md/log/lock/sh/yml/dist).

### Sesión endurecida (`core/Session.php`)
Cookie `Secure` (solo bajo HTTPS, detectado), `HttpOnly`, `SameSite=Lax`;
`session.use_strict_mode` + `use_only_cookies`. El login regenera el ID (anti-fijación).

### Vistas públicas
- **Rate limiting** en `POST /boleta-publica/consultar`: `Core\Throttle` (contadores
  en `storage/throttle/`), máx 15 intentos por IP cada 5 min → responde 429.
- **noindex** en layouts `digital` y `print` + `public/robots.txt` (`Disallow: /`).

### QR — TODO local
Todo el QR usa la librería local `qrcode.min.js` (boleta digital, footer A4, hoja de
códigos). **NO se usa `chart.googleapis.com` en ningún lado**; no se filtran códigos a
terceros. (Las menciones a Google Charts en este documento quedaron obsoletas.)

### PENDIENTES de seguridad (próximas sesiones)
- [ ] **Fase 3:** auditar interpolaciones SQL (`ORDER BY`/`LIMIT`/`LIKE`), validar
  longitud/charset de entradas públicas, reforzar subida de imágenes.
- [ ] **Fase 4:** mover `error_log` fuera del docroot, revisar permisos de archivos,
  auditoría de accesos (aprovechar `veces_consultada`/`ultima_consulta` de `boletas_publicas`).
- [ ] **CSP:** pasada dedicada — auditar estilos inline (`style="--pct:..."`) y el QR
  antes de aplicar `Content-Security-Policy`.
- [ ] **Limpieza menor:** quitar del `.gitignore` las reglas obsoletas de
  `public/assets/img/firmas/`; `AuthMiddleware` está SIN USAR (la auth es por controlador)
  → decidir si se conecta o se elimina.
- [ ] **Re-subir firma/sello** si se recrea el entorno (se pierden solo si se borra el
  directorio externo; el deploy ya no los toca).

## Producción — Hostinger

> NOTA: la sección de arriba ("Seguridad y estado REAL de producción") es la fuente
> autoritativa. Lo de abajo es el plan original previo al despliegue real.

### Proveedor y requisitos mínimos
Hostinger (plan Premium o Business). Requiere PHP 8.2+ — confirmar y fijar la versión
en hPanel antes de subir cualquier archivo. El código usa `match`, `str_starts_with`,
tipos de retorno `never` y otras funciones de PHP 8.1/8.2.

### Estructura de despliegue obligatoria (Opción B)
El document root del dominio/subdominio debe apuntar directamente a `public/`, no a la
raíz del proyecto. Si apunta a la raíz, `url()` incluirá `/public/` en todas las URLs
y los QR generarán rutas incorrectas desde el primer día.

Configuración en hPanel: crear subdominio (ej. `siga.cociap.edu.pe`), establecer su
document root en `public_html/siga-cociap/public/`.

```
public_html/
└── siga-cociap/          ← repositorio completo
    ├── app/
    ├── config/
    ├── public/           ← document root del subdominio ← apunta aquí
    └── ...
```

### `url()` — no requiere cambios de código
Con `app_url = ''` (valor actual en `config/app.php`), `url()` lee `$_SERVER['HTTP_HOST']`
en cada request y construye la base automáticamente. En Hostinger genera
`https://siga.cociap.edu.pe/boleta-publica?codigo=...` sin tocar una línea.
Solo setear `app_url` si se necesita forzar una URL fija.

### SSL — activar antes de la primera prueba
Hostinger incluye Let's Encrypt gratuito. Activarlo en hPanel desde el inicio.
Los teléfonos modernos bloquean contenido HTTP mixto y muestran advertencia en HTTP puro,
lo que arruina la experiencia de escaneo de QR para los padres.

### QR — dos tipos con comportamiento distinto
| QR | Vista | URL incrustada | Requiere login |
|---|---|---|---|
| Footer boleta A4 | `boleta/alumno.php` | `/boleta/digital/{id}/{id}` | **Sí** → redirige a login |
| Hoja de códigos admin | `/admin/boletas-publicas/{id}/imprimir` | `/boleta-publica?codigo=COCIAP-...` | **No** → acceso directo |

El único QR que un padre puede escanear desde su celular sin cuenta es el de la
**hoja de códigos**. El QR del footer de la boleta A4 es para usuarios autenticados.

Todo el QR (boleta digital, footer A4 y hoja de códigos) se genera con `qrcode.min.js`
(librería local, sin dependencia de terceros). No requiere salida a internet ni filtra
datos a servicios externos.

### Cambios en `config/app.php` para producción
```php
'debug'   => false,   // OBLIGATORIO — en true expone stack traces al público
'app_url' => '',      // mantener vacío — la auto-detección funciona correctamente
```

### Archivos fuera de Git — transferir manualmente
- `config/database.php` — crear con las credenciales MySQL de Hostinger
- `public/assets/img/firmas/` — copiar los PNG de firma y sello del Director EBR
- Permisos del directorio: `chmod 775 public/assets/img/firmas/` para que el
  controlador pueda escribir nuevas imágenes al subir desde `/admin/director-ebr`

### Base de datos en Hostinger
- Crear BD y usuario MySQL desde hPanel de Hostinger.
- Exportar el dump desde XAMPP local: estructura + datos, charset utf8mb4.
  **No usar los backups del repositorio** — pueden estar desactualizados; los datos
  reales (notas del I Bimestre) solo existen en la BD local de XAMPP.
- Importar el dump via phpMyAdmin de Hostinger.
- Las migraciones ya están aplicadas en la BD local — el dump las incluye.
  No volver a correr los archivos de `migrations/` sobre el dump importado.

### Checklist de despliegue
1. Commitear todos los cambios pendientes (incluyendo `public/css/app.css` compilado)
2. Exportar dump completo de la BD local (utf8mb4, con datos reales)
3. Subir el repositorio a Hostinger vía Git o FTP
4. Crear subdominio en hPanel y apuntar document root a `public/`
5. Activar SSL (Let's Encrypt) en hPanel
6. Crear BD MySQL en hPanel e importar el dump
7. Crear `config/database.php` con credenciales de Hostinger
8. Confirmar que `mod_rewrite` está activo (activo por defecto en planes compartidos)
9. `chmod 775 public/assets/img/firmas/`
10. Transferir los PNG de firma y sello del Director EBR
11. Probar en navegador: login, boleta imprimible, boleta digital, boleta pública con código
12. Escanear un QR de la hoja de códigos desde un celular con datos móviles (no WiFi local)
13. Imprimir boleta de prueba en la RICOH MP4054 PCL6 del colegio

### Riesgos conocidos
- **PHP `exif_imagetype()`**: no disponible en XAMPP local → se usa `getimagesize()[2]`.
  Verificar si Hostinger tiene `ext-exif` habilitada; si es así, la validación funciona
  igual porque `getimagesize()[2]` es equivalente y más portable.
- **Versión PHP**: confirmar PHP 8.2 en hPanel — algunos planes Hostinger tienen 8.1
  como versión por defecto.
- **`mod_rewrite`**: los planes compartidos de Hostinger lo incluyen, pero si se usa
  un plan VPS hay que habilitarlo manualmente (`a2enmod rewrite`).

## Notas importantes
- `config/database.php` SÍ está en Git pero es un cargador SIN secretos: en prod lee
  las credenciales de `~/siga_secrets/database.php` (fuera del repo); en local usa el
  fallback de XAMPP. Ver "Seguridad y estado REAL de producción".
- Logo del colegio: `public/assets/img/logo_cociap.png` (con guión bajo)
- URL base dinámica via meta tag: `<meta name="base-url" content="...">`
- BrowserSync corre en puerto 3000, proxy a `:3000/`
- Alias Git Bash: `local3000` para iniciar el entorno
- `hash.php` en raíz: archivo temporal para generar hashes bcrypt — eliminar
  tras usarlo, NO commitear ni dejar en el servidor
- **`exif_imagetype()` NO disponible en XAMPP local** — usar `\getimagesize($path)[2]`
  para validar tipo de imagen en controllers con namespace.
- **Firma/sello PNG del Director EBR** — almacenados FUERA del repo en `~/siga_uploads/firmas/`
  (prod) o `storage/firmas/` (local), con nombres `firma_{historial_id}_{timestamp}.png`.
  Se sirven por `GET /firmas/{archivo}` (`FirmaController`). Subir desde `/admin/director-ebr`
  — se validan por contenido real (no solo extensión). Ya NO se usa `public/assets/img/firmas/`.

## Listado de áreas o áreas-curso con sus respectivas subáreas o competencias.
# Áreas Curriculares y Competencias - Modelo SIAGIE - NIVEL PRIMARIA
* IMPORTANTE.
  Solo desde el primer grado al tercer grado todas las areas son manejadas por un solo docente (UNIDOCENTE), todas las areas se convierten en area-curso.
  Las compentencias transversales son llenadas solo por el TUTOR de la sección.
* Personal Social (area-curso)
- Competencias
  1. Construye su identidad
  2. Convive y participa democráticamente en la búsqueda del bien común.
  3. Construye interpretaciones históricas.
  4. Gestiona responsablemente el espacio y el ambiente.
  5. Gestiona responsablemente los recursos económicos.
* Educación Física (area-curso)
- Competencias
  1. Se desenvuelve de manera autónoma a través de su motricidad.
  2. Asume una vida saludable.
  3. Interactúa a través de sus habilidades sociomotrices.
* Arte y Cultura (area-curso)
- Competencias
  Aprecia de manera crítica manifestaciones artístico-culturales
  Crea proyectos desde los lenguajes artísticos
* Comunicación (area)
- Competencias
  1. Se comunica oralmente en su lengua materna (subarea -> Gramática-Competencia Lingüística)
  2. Lee diversos tipos de textos escritos en su lengua materna (subarea -> Plan lector)
  3. Escribe diversos tipos de textos en su lengua materna (subarea -> Razonamiento verbal)
* Inglés como lengua extranjera (area-curso)
- Competencias
  1. Se comunica oralmente en inglés como lengua extranjera.
  2. Lee diversos tipos de textos escritos en inglés como lengua extranjera.
  3. Escribe diversos tipos de textos eninglés como lengua extranjera.
* Matemática (area)
- Competencias
  1. Resuelve problemas de cantidad. (subarea -> Aritmética)
  2. Resuelve problemas de regularidad, equivalencia y cambio. (subarea -> Álgebra)
  3. Resuelve problemas de forma,movimiento y localización. (subarea -> Geometría)
  4. Resuelve problemas de gestión de datos e incertidumbre. (subarea -> Razonamiento Matemático)
* Ciencia y Tecnología (area)
- Competencias
  1. Indaga mediante métodos científicos para construir sus conocimientos. (subarea -> Química)
  2. Explica el mundo físico basándoseen conocimientos sobre los seresvivos; materia y energía; biodiversidad, Tierra y Universo. (subarea -> Biología)
  3. Diseña y construye soluciones tecnológicas para resolver problemas de su entorno. (subarea -> Física)
* Educación Religiosa (area-curso)
- Competencias
  1. Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas.
  2. Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa.
* Competencias Transversales (caso especial) - Calificaciones registradas por el tutor
- Competencia TIC
  1. Se desenvuelve en entornos virtuales generados por las TIC
- Competencia GAMA
  1. Gestiona su Aprendizaje de manera autónoma
### Áreas Curriculares y Competencias - Modelo SIAGIE - NIVEL SECUNDARIA
-IMPORTANTE. Un curso puede ser equivalente a un area-curso con varias competencias o una subarea vinculada a una sola competencia.
* Desarrollo Personal, Ciudadanía y Cívica (area-curso)
- Competencias
    1. Construye su identidad.
    2. Convive y participa democráticamente en la búsqueda del bien común.
* Ciencias Sociales (area)
  Se maneja a Geografía/Economía como una sola subárea con dos competencias en las cargas.
- Competencias
    1. Construye interpretaciones históricas. (subarea -> Historia)
    2. Gestiona responsablemente el espacio y el ambiente. (subarea -> Geografía)
    3. Gestiona responsablemente los recursos económicos. (subarea -> Economía)
* Educación Física (area-curso)
- Competencias
    1. Se desenvuelve de manera autónoma a través de su motricidad
    2. Asume una vida saludable
    3. Interactúa a través de sus habilidades sociomotrices
* Arte y Cultura (area-curso) (Para SIAGIE: Solo del 1ero al 4to, en el 5to se registran las notas del area-curso de Taller de Razonamiento matemático)
- Competencias
    1. Aprecia de manera crítica manifestaciones artístico-culturales
    2. Crea proyectos desde los lenguajes artísticos
* Comunicación (area)
- Competencias
    1. Se comunica oralmente en su lengua materna. (subarea -> Razonamiento Verbal)
    2. Lee diversos tipos de textos escritos en su lengua materna. (subarea -> Literatura)
    3. Escribe diversos tipos de textos en su lengua materna. (subarea -> Lenguaje)
* Inglés (area-curso)
- Competencias
    1. Se comunica oralmente en Inglés.
    2. Lee diversos tipos de textos escritos en Inglés.
    3. Escribe diversos tipos de textos en Inglés.
* Matemática (area)
- Competencias
    1. Resuelve problemas de cantidad. (subarea -> Aritmética)
    2. Resuelve problemas de regularidad, equivalencia y cambio. (subarea -> Álgebra)
    3. Resuelve problemas de forma, movimiento y localización. (subarea -> Geometría)
    4. Resuelve problemas de gestión de datos e incertidumbre. (subarea -> Trigonometría)
* Taller de Razonamiento Matemático (area-curso) (Para SIAGIE: se registran desde el 1er al 4to grado en el area-curso de Educación Religiosa, para 5to se registran las notas en el area-curso de Arte y Cultura)
- Competencias
    1. Resuelve problemas de cantidad.
    2. Resuelve problemas de regularidad, equivalencia y cambio.
* Taller de Pre Cálculo (area-curso) (Para SIAGIE: las notas se registran en el area-curso de Educación para el trabajo)
- Competencias
    1. Resuelve problemas de regularidad, equivalencia y cambio.
* Ciencia y Tecnología (area)
- Competencias
  1. Indaga mediante métodos científicos para construir sus conocimientos (subarea -> Química)
  2. Explica el mundo físico basándose en conocimientos sobre los seres vivos, materia y energía, biodiversidad, Tierra y Universo (subarea -> Biología)
  3. Diseña y construye soluciones tecnológicas para resolver problemas de su entorno (subarea -> Física)
* Educación Religiosa (area-curso) (Para SIAGIE: del 1ero al 4to registra las notas del Taller de Razonamiento Matemático; en 5to registra sus propias notas con el alias "(Ética y Valores)")
- Competencias
  1. Construye su identidad como persona humana, amada por Dios, digna, libre y trascendente, comprendiendo la doctrina de su propia religión, abierto al diálogo con las que le son cercanas.
  2. Asume la experiencia del encuentro personal y comunitario con Dios en su proyecto de vida en coherencia con su creencia religiosa.
* Educación para el Trabajo (area-curso) (Para SIAGIE: Desde el 1ero al 4to, en el 5to grado se llenan las notas de Taller de Pre Cálculo)
- Competencias
  1. Gestiona proyectos de emprendimiento económico o social.
* Competencias Transversales (caso especial) - Calificaciones registradas por el tutor
- Competencias Transversales / No Asociadas a Áreas
  Competencia TIC
  1. Se desenvuelve en entornos virtuales generados por las TIC
  Competencia GAMA
  1. Gestiona su aprendizaje de manera autónoma