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
## Filosofía de Trabajo (OBLIGATORIO)

Estas reglas son de cumplimiento obligatorio durante todo el ciclo de desarrollo. En caso de conflicto entre reglas, aplica el siguiente orden de prioridad:

1. No romper funcionalidades existentes.
2. Preguntar antes de asumir cualquier comportamiento.
3. Respetar la arquitectura MVP del proyecto.
4. Mantener la compatibilidad con el sistema.
5. Realizar únicamente los cambios mínimos necesarios.
6. Mantener la calidad, seguridad y legibilidad del código.

---

### Principio de Incertidumbre

No debes tomar decisiones de diseño, arquitectura o implementación cuando exista información insuficiente, ambigua o contradictoria.

Si existe cualquier duda, por mínima que sea, debes detener la implementación y solicitar aclaraciones al usuario.

Nunca debes asumir:

- Reglas de negocio.
- Comportamientos esperados.
- Estructuras de datos.
- Relaciones entre módulos.
- Flujo de ejecución.
- Interfaces públicas.
- Convenciones no documentadas.
- Preferencias de implementación.

---

### Obligación ante la Incertidumbre

Antes de modificar cualquier archivo debes verificar si existe alguna incertidumbre.

Si detectas cualquier duda, debes:

1. Detener inmediatamente la implementación.
2. Enumerar claramente todas las dudas.
3. Explicar por qué cada una afecta la implementación.
4. Esperar la respuesta del usuario.

No continúes hasta recibir las aclaraciones correspondientes.

---

### Decisiones Técnicas

Si existen dos o más alternativas técnicamente válidas para resolver un problema:

- No elijas una automáticamente.
- Presenta todas las alternativas relevantes.
- Explica las ventajas y desventajas de cada una.
- Recomienda una opción con su justificación técnica.
- Espera la aprobación del usuario antes de implementarla.

---

### Alcance de la Implementación

Limítate estrictamente al alcance solicitado.

No implementes mejoras adicionales, optimizaciones, refactorizaciones, cambios de arquitectura o nuevas funcionalidades que no hayan sido solicitadas explícitamente.

Si detectas oportunidades de mejora, infórmalas al finalizar como recomendaciones, pero no las implementes sin autorización del usuario.

---

### Arquitectura

Respetar estrictamente la arquitectura MVP del proyecto.

- Model: acceso a datos y lógica de negocio.
- Presenter: coordinación entre Model y View.
- View: presentación e interacción con el usuario.

Nunca mezclar responsabilidades entre capas.

Mantener la estructura actual del proyecto.

---

### Modificaciones

Toda modificación debe ser incremental y localizada.

- Nunca sobrescribas archivos completos si basta con modificar una sección.
- Nunca elimines funcionalidades existentes sin autorización explícita.
- Nunca cambies interfaces públicas sin autorización.
- Reutiliza componentes existentes antes de crear nuevos.
- Conserva el estilo y organización del proyecto.
- Realiza únicamente los cambios indispensables para cumplir el objetivo solicitado.

---

### Seguridad

Toda implementación debe respetar las siguientes prácticas:

- Consultas preparadas (PDO).
- Protección contra SQL Injection.
- Protección contra XSS.
- Validación tanto en cliente como en servidor.
- Escape correcto de la salida.
- Manejo seguro de sesiones y autenticación cuando corresponda.

Nunca sacrifiques seguridad por rapidez de implementación.

---

### Estilo del Código

Respeta siempre:

- Convenciones de nombres existentes.
- Organización de carpetas.
- Estructura del proyecto.
- Estilo de codificación.
- Patrones ya utilizados.
- Consistencia con el resto del sistema.

El código nuevo debe integrarse naturalmente con el código existente.

---

### Protocolo Obligatorio antes de Implementar

Antes de escribir cualquier línea de código debes:

1. Leer completamente el Plan de Implementación.
2. Comprender el objetivo funcional.
3. Analizar la arquitectura relacionada.
4. Identificar todos los archivos involucrados.
5. Analizar dependencias directas e indirectas.
6. Evaluar posibles riesgos de regresión.
7. Verificar que no existan ambigüedades.

Si existe cualquier duda, vuelve al apartado **Principio de Incertidumbre**.

---

### Verificación Obligatoria

Antes de finalizar una implementación debes verificar que:

- No se rompieron funcionalidades existentes.
- No existen errores de sintaxis.
- No se modificaron interfaces públicas sin autorización.
- La arquitectura MVP continúa respetándose.
- El cambio mantiene compatibilidad con el resto del sistema.
- Solo se modificaron los archivos estrictamente necesarios.
- No existen efectos secundarios conocidos derivados de la implementación.

No finalices una tarea hasta completar esta verificación.

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


## Estructura curricular
- **Área con subáreas:** cada subárea tiene 1 competencia y 1 docente
- **Área-curso:** sin subáreas, 1 docente dicta todas las competencias
- **Unidocente:** primaria 1°-3°, flag `es_unidocente` en tabla secciones
  -Todas las áreas son un área-curso, todas las cargas que deberían ser subáreas se integran al área y se evalúa como un área-curso.
- **Competencias transversales:** las registran cada docente por carga académica y las conclusiones descriptivas, aprobación y bloqueo de notas queda a cargo del tutor de sección.

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


## Boleta: documento único por token (24/06/2026)

> Consolidación de la boleta como **documento oficial único** (digital + imprimible)
> servido SIEMPRE por token. Se retiraron las rutas anónimas por id (enumerables),
> se unificó el ensamblado en un solo modelo y el código tecleado quedó dormido.

### Builder único — `app/Models/BoletaModel.php`
- `armar(int $matriculaId, int $periodoId, bool $soloOficiales = false): ?array` —
  **fusiona las 3 copias** que vivían en `Boleta\BoletaController`,
  `BoletaPublicaController` (público) y `Admin\BoletaPublicaController`. Punto ÚNICO
  de verdad del documento (agregación, transversales, exoneraciones, conducta,
  asistencia, tutor, directorEbr). Es PURO: data in → array out; la autorización
  vive en los entry points.
- **`soloOficiales=true` → solo bimestres `cerrado`** (regla de familias: el BORRADOR
  de Hito A nunca se expone al público). Lo usan las rutas por token. `false` (docente,
  salida masiva admin) muestra todos los periodos.
- **El builder duplicado del controlador público (dormido) NO se tocó** (queda con su
  propia copia para resurrección; sus rutas están comentadas).

### Render y QR únicos — `Boleta\BoletaController::render()`
- Entry points DELGADOS: cada uno decide quién + qué periodo, y delega en `render()`.
- **El QR sale SIEMPRE de `urlBoletaToken()`** (token de la matrícula IDENTIDAD) — una
  sola fuente, fin de las 6 variantes que causaban el bug del QR. La boleta se ancla a
  la identidad (`$data['alumno']['matricula_id']`) para coincidir en retorno de grado.

### Direccionamiento (invariante de seguridad)
- **Cero rutas ANÓNIMAS por id.** Se **borraron** `GET /boleta/{id}/{periodo}` (`ver`) y
  `GET /boleta/digital/{id}/{periodo}` (`verDigital`) — eran enumerables.
- **Público (familias):** SIEMPRE por token, solo oficiales:
  `GET /boleta/digital/{token}` (`verDigitalToken`) y `GET /boleta/ver/{token}` (`verToken`).
- **Interno (docente/admin):** autenticado por id + alcance (`/docente/boleta/{id}[/imprimir]`),
  puede ver BORRADOR. Por estar tras login + 403 por alcance NO es enumerable → se queda por id.
- `padre/notas` y `matriculas/show` ahora enlazan por token (sus controladores resuelven
  el token vía `getOCrearToken`).

### Tracking de visitas — `matriculas.token_consultas` (migración `028`)
- `028_boleta_token_tracking.sql`: `token_consultas INT` + `token_ultima_consulta DATETIME`,
  con **backfill** desde `boletas_publicas.veces_consultada` (preserva el histórico B1).
- `BoletaPublicaModel::registrarVisitaToken(int $matriculaId)` reescrito: `UPDATE matriculas`
  (ya NO toca `boletas_publicas`). **Cuenta TODO acceso por token** (escaneo de QR o portal
  del padre, digital o impreso). El token es por estudiante → conteo por identidad.
- `BoletaPublicaModel::getOCrearToken(int $matriculaId): string` — token hex-32 permanente,
  get-or-create idempotente.

### Salida masiva (sobrevive, re-apuntada a token)
- `Admin\BoletaPublicaController::{vistaPrevia,boletasAlumno,archivar}` iteran
  `getMatriculasAprobadasParaBoleta` (NO `getPorPeriodo`/código) y arman con
  `BoletaModel::armar` + `urlBoletaToken`. **Independientes del código.**
- Hub `porPeriodo` + vista `periodo.php`: ahora token-céntricos
  (`getEstudiantesParaPeriodo` con `token_consultas`); botones de impresión gated por
  `total_aprobables > 0`. `index.php` cuenta boletas oficiales (no códigos).

### Código tecleado — DORMIDO (conservado para reactivar)
- **Borradas** las rutas: público `/boleta-publica` + `/consultar`; admin `/{per}/generar`,
  `/{per}/actualizar`, `/{per}/imprimir` (hoja de códigos). **Quedan comentadas en
  `routes/web.php` con el snippet para reactivarlas.**
- **Conservado intacto** (sin ruta): `BoletaPublicaController` (público), métodos
  `generar`/`actualizar`/`imprimir` del admin, vistas `boleta-publica/*` e `imprimir.php`,
  tabla `boletas_publicas` y `BoletaPublicaModel::{generarMasivo,getPorCodigo,...}`.
- La generación de **token** (`generar-tokens`) sigue activa.

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

## Sistema de color "wayfinding" (16/06/2026)

> Color FIJO por concepto en todo el sistema, como ayuda de orientación: hay
> docentes que trabajan en varios colegios y se saturan con mucha información;
> el color fijo deja que ubiquen el acceso sin leer.

- **Tokens en `resources/sass/base/_variables.scss`** (bloque "Wayfinding del dashboard
  docente"). Cada concepto tiene 3 variantes: `-line` (borde vivo), `-bg` (fondo tenue),
  `-ink` (título oscuro legible):
  | Concepto | `-line` | `-bg` | `-ink` |
  |---|---|---|---|
  | **Académicas / Mis cargas** (`$card-cargas-*`) | `#1e6fa8` azul | `#eef5fb` | `#1a5a8c` |
  | **Transversales / Tutoría** (`$card-tutoria-*`) | `#0d9488` teal | `#ecfbf8` | `#0f766e` |
  | **Conducta** (`$card-conducta-*`) | `#7c3aed` púrpura | `#f5f0fe` | `#6d28d9` |
  | **Nómina** (`$card-nomina-*`) | `#e07b1a` naranja | `#fef3e2` | `#b45309` |
- **REGLA:** rojo (`$color-error`) y ámbar (`$color-warning`) quedan RESERVADOS para los
  badges de estado (error/advertencia); NUNCA se usan como identidad de un acceso.
- Combinación azul↔naranja + teal/púrpura: bien diferenciable con daltonismo.
- Aplicado en `/docente/inicio` y `/director/bloqueos`. Usar estos mismos colores en
  futuras vistas para el mismo concepto.

## Dashboard del docente — cards de acceso (16/06/2026)

- **`/docente/inicio`** (`Docente\PanelController::index`) tiene 4 cards en `.dpanel-grid`:
  **Mis cargas académicas** (azul), **Tutoría — {grado}{sección}** (teal), **Conducta —
  {grado}{sección}** (púrpura) y **Nómina de matriculados** (naranja). Tutoría y Conducta
  solo aparecen si el docente es tutor del año activo (`$tutoria`/`$conducta` no nulos).
- `PanelController` ahora inyecta `ConductaModel` y calcula `$conducta` (mismo origen que
  usaba `mis-cargas`: `ConductaModel::getCierreVigente`).
- **Se eliminaron las cards largas** (`.tutoria-card`) de Tutoría y Conducta de
  `/docente/mis-cargas`; `CalificacionController::misCargas` ya NO calcula
  `$tutoria`/`$conducta` (y se quitaron de ese controlador `TransversalModel`/`ConductaModel`
  que quedaron sin uso). La vista solo lista cargas.
- **SASS** en `pages/_docente-panel.scss`: modificadores `.dpanel-card--{cargas,tutoria,
  conducta,nomina}` (borde + título por color). El **fondo tenue (`-bg`) SOLO se pinta
  cuando la card lleva un estado activo** (`--cerrado`/`--disponible`/`--progreso`); libre
  de estados = fondo blanco (menos ruido). Como Mis cargas y Nómina nunca llevan estado,
  quedan blancas; Tutoría/Conducta siempre traen estado, así que muestran su tinte.

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

## Nómina del docente — buscador destacado + íconos de sección (22/06/2026)

`/docente/nomina` (`Docente\PanelController::nomina`, vista `resources/views/docente/nomina.php`).
- **El buscador es la acción principal y va PRIMERO**, destacado: card `.nomina-buscar`
  (borde de marca + acento lateral `$brand-accent` + fondo `$brand-light` + sombra),
  campo con ícono de lupa (patrón `.buscador__campo`/`.buscador__icono`/`.buscador__input`),
  `autofocus`. El panel **Imprimir nómina** baja al final como acción secundaria
  (`.nomina-imprimir-card`: fondo gris, borde punteado).
- **Cada sección lleva su ícono de título** (`.nomina-seccion-ico`, mask + currentColor):
  Buscar → `lupa-look.svg`; Imprimir → `printer.svg`.
- El `h1` lleva el ícono de concepto wayfinding (ver sección siguiente).
- SASS en `pages/_docente-panel.scss`. JS `public/js/nomina.js` intacto (todo por ID).

## Wayfinding en el h1 de cada vista del docente (22/06/2026)

> Continuidad card→vista: el `h1` de cada vista a la que lleva una card del
> dashboard del docente lleva el MISMO glifo que la card, pequeño (`1.05em`) y
> antes del texto, tintando SOLO el ícono con el tono `-ink` del concepto (texto
> neutro = subtil). Refuerza el [sistema wayfinding por color](#sistema-de-color-wayfinding-16062026).

- **Punto único de verdad** en `pages/_docente-panel.scss`, junto al mapa de las
  cards (`.dpanel-card--*`): clase base `.page-title--wf` (icono vía `::before`,
  mask con `var(--wf-icon)` tintado con `var(--wf-ink)`) + modificadores por concepto.
- **Mapa concepto → ícono → color** (los `h1` y las cards comparten glifo):
  | Concepto | Modificador h1 | Ícono | `-ink` |
  |---|---|---|---|
  | Mis cargas | `page-title--cargas` | `book-bookmark` | azul |
  | Tutoría | `page-title--tutoria` | `users-group-rounded` | teal |
  | Conducta | `page-title--conducta` | `smile` | púrpura |
  | Nómina | `page-title--nomina` | `childs-students` | naranja |
  | Orden de mérito | `page-title--merito` | `medal-ribbon-star` | naranja (familia Nómina) |
  | Ranking por sección | `page-title--ranking` | `ver-resumen` | naranja (familia Nómina) |
- Aplicado en `mis-cargas`, `tutoria`, `conducta`, `nomina`, `orden-merito`
  (selector compartido: el modificador se elige por `$rutaBase`) y las dos vistas
  de periodo de mérito/ranking.
- **Colisión de íconos resuelta**: `users-group-rounded` quedó EXCLUSIVO de Tutoría.
  La card Nómina y su acción "Ver nómina" usan `childs-students`; "Buscar estudiante"
  usa `lupa-look`.
- **REGLA de coexistencia de colores (opción A — por rol/forma):** el color de
  CONCEPTO (wayfinding) vive solo en el chrome (cards + `h1`); el color de SECCIÓN
  (`--sec-*`, paleta por letra A-F en `_dashboard.scss`) vive en chips/anclas
  (`.seccion-ancla`). Comparten varios hex (Conducta púrpura == Sección B,
  Tutoría teal == Sección C), pero NO se confunden porque cada sistema vive en
  una forma/posición distinta. NUNCA pintar un `h1` con color de sección ni un
  chip de sección con color de concepto.

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

## Mis cargas — ancla de sección monocroma + jerarquía de grado (24/06/2026)

En `/docente/mis-cargas` (vista `mis-cargas.php`, SASS `pages/_dashboard.scss`):
- **Ancla de sección**: la **letra es el identificador** (única dentro del grado,
  por eso va grande y sola). Pasó a **monocromo** en la familia "Mis cargas" (azul
  `$card-cargas-*`); se quitó la paleta por letra del ancla y el grado/nivel
  repetidos. Render `"Sección A"` (rótulo + recuadro de la letra, sin duplicar la
  letra). La paleta por letra `.seccion-ancla--{letra}` (`--sec-*`) se **ELIMINÓ**
  (24/06): el acordeón del ranking (`.merito-seccion-acordeon` en
  `/docente/ranking-seccion`) era su único consumidor y ahora usa el MISMO monocromo
  azul de "Mis cargas" (`$card-cargas-*`). Distinción de sección = la LETRA, nunca
  color por letra (confunde con el wayfinding por concepto).
- **Bloque de grado** (`.card--grado` + `.grado-head`): se diferencia por
  **jerarquía tipográfica** (nivel como antetítulo + grado en grande), NO por color
  (los grados son secuenciales; un tinte por grado competiría con el azul de la
  página).
- **Ranking por sección estandarizado (24/06):** `ranking-seccion-periodo.php` ya no
  aplica `seccion-ancla--{letra}`; `.merito-seccion-acordeon` (`pages/_docente-panel.scss`)
  usa `$card-cargas-*` fijo. Misma lectura que mis-cargas (letra = identificador).

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
- [x] **Fase 3 (CERRADA, en prod):** interpolaciones SQL auditadas, longitud/charset
  de entradas públicas validados, subida de imágenes reforzada.
- [x] **Fase 4 (CERRADA, en prod):** `error_log` fuera del docroot (via config
  `log_path`, verificado por SSH), permisos revisados, auditoría de accesos.
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

### Pendientes vivos de horarios (al 01/07/2026)
- **Migraciones 030 y 031:** APLICADAS en LOCAL y PROD. LOCAL verificada (0 sesiones
  cruzadas, 0 bloques ≤1 min, horas académicas recalculadas). Query de verificación:
  `SELECT COUNT(*) FROM sesiones_horario sh INNER JOIN cargas_academicas ca ON
  ca.id=sh.carga_id WHERE sh.seccion_id != ca.seccion_id;` → 0.
- **Migraciones 023/024/028 en prod:** sin confirmar — verificar antes de asumir aplicadas.
- **Digitación de horarios (la hace el usuario en prod):** 1°A secundaria (11 cursos
  "sin horario propio" tras la 031) y las áreas que quedaron sin bloques reales tras
  la 030 (CyT/Matemática de primaria 4°-6°, Arte y Cultura 1°A prim., etc.). 3°B
  secundaria YA quedó completo.
- **Por coordinar con el colegio:** el solape real de CLEMENTE ANGELES (arriba).
- **Hallazgo NO implementado (tarea futura si se pide):** "Reemplazar docente"
  en sección unidocente no actualiza `secciones.tutor_id` ni opera sobre todas
  las cargas del tutor → el entrante pierde `es_aula` (vista consolidada,
  Tutoría/Conducta). También pendiente de siempre: modelado de recreos.

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