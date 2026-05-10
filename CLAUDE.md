# SIGA-COCIAP — Contexto del proyecto

## Descripción
Sistema Integrado de Gestión Académica del Colegio de Aplicación
"Víctor Valenzuela Guardia" — UNASAM, Huaraz, Ancash, Perú.
Proyecto de tesis para obtener el título de Ingeniero de Sistemas e Informática.

## Stack tecnológico
- **Backend:** PHP 8.2 — framework MVC propio (sin Laravel aún)
- **Frontend:** HTML + SASS + JavaScript vanilla
- **Base de datos:** MySQL (XAMPP local)
- **Build tool:** Gulp (SASS → CSS, BrowserSync)
- **Control de versiones:** Git + GitHub
- **Objetivo futuro:** Migrar a Laravel

## Arquitectura de carpetas
```
siga-cociap/
├── app/
│   ├── Controllers/
│   │   ├── Auth/AuthController.php
│   │   ├── Boleta/BoletaController.php       ← NUEVO (sesión 2)
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
│   ├── migrations/
│   │   ├── 000_crear_base_de_datos.sql       ← NUEVO (sesión 2)
│   │   ├── siga_cociap.sql                   ← schema completo + seeds base
│   │   ├── 002_criterios_calificaciones.sql
│   │   └── 003_bloqueos_competencia.sql      ← corregido (era .sql.sql)
│   └── seeds/
│       ├── 001_datos_prueba.sql
│       ├── 002_completar_sistema.sql          ← NUEVO (sesión 2)
│       ├── 003_cargas_prueba_adicionales.sql  ← NUEVO (sesión 2, solo testing)
│       └── 004_boleta_completa_test.sql       ← NUEVO (sesión 2, solo testing)
├── public/
│   ├── index.php    ← front controller único
│   ├── .htaccess
│   ├── css/app.css  ← compilado por Gulp
│   ├── js/
│   │   ├── auth.js
│   │   ├── calificaciones.js
│   │   └── resumen.js
│   └── assets/
│       ├── img/logo_cociap.png   ← logo del colegio
│       ├── fonts/inter/          ← fuente Inter local
│       └── icons/                ← SVGs locales
├── resources/
│   ├── sass/
│   │   ├── app.scss              ← archivo principal
│   │   ├── base/(_variables, _reset, _typography)
│   │   ├── components/(_buttons, _forms, _alerts, _cards, _tables, _navbar)
│   │   └── pages/(_auth, _dashboard, _boleta)  ← _boleta.scss NUEVO
│   └── views/
│       ├── layouts/(auth.php, app.php, print.php)  ← print.php NUEVO
│       ├── auth/login.php
│       ├── dashboard/index.php
│       ├── boleta/alumno.php                       ← NUEVO (sesión 2)
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
8. Padre imprime boleta desde /padre/notas → "Ver boleta"  ← NUEVO
```

## Módulo de boleta de calificaciones (sesión 2)
- **Ruta:** `GET /boleta/{matricula_id}/{periodo_id}`
- **Roles con acceso:** admin, director_general, director_ebr, registro_academico, secretaria, padre
- **Restricción padre:** solo puede ver la boleta de su propio hijo (403 en otro caso)
- **Layout:** `resources/views/layouts/print.php` — sin navbar, sin flash, solo `app.css`
- **Vista:** `resources/views/boleta/alumno.php`
- **Estilos:** `resources/sass/pages/_boleta.scss`
- **Impresora objetivo:** RICOH MP4054 PCL6 — margen `@page: 0.5cm` por todos los lados

### Decisiones de diseño de la boleta
- **Conclusión descriptiva:** columna integrada de 60mm en la misma fila de la
  competencia (estilo SIAGIE). CSS `line-clamp: 3` con puntos suspensivos nativos.
  El texto completo se guarda en BD; el truncado es solo presentación CSS.
- **Subárea:** se antepone al nombre de la competencia para áreas `con_subareas`
  (ej: `Aritmética — C23. Resuelve problemas...`). Las áreas-curso no llevan prefijo.
- **Primaria:** muestra solo literal (AD/A/B/C); **Secundaria:** nota numérica + literal.
- **Pie de página:** tres líneas de firma — Director(a) General, Registro Académico,
  Padre/Madre/Tutor(a).

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

## Fixes importantes aplicados (sesión 2)
- `periodos.nombre_display` es la columna correcta (no `nombre`). Si ves
  `Unknown column 'p.nombre'` en queries de periodos, verificar esto.
- `guardarConclusionAlumno` en CalificacionController envuelto en try-catch para
  garantizar siempre respuesta JSON (antes devolvía HTML en excepciones).
- Seed `002_completar_sistema.sql` agrega el usuario padre (DNI 99999999) que
  faltaba en `usuarios` — sin él el padre no puede loguear.
- Competencias completas para primaria y secundaria en seed 002.

## Pendientes al 7 de mayo 2026
- [x] Boleta de calificaciones imprimible A4 ← completado sesión 2
- [ ] Tests T3-T6 de la boleta pendientes de completar
  - T3: conclusión truncada con boleta completa (en progreso)
  - T4: control de acceso padre → 403 en boleta ajena
  - T5: botón "Ver boleta" en panel del padre
  - T6: previsualización de impresión A4 final
- [ ] Gestión de usuarios (CRUD admin)
- [ ] Parámetros del director (año académico, periodos, secciones)
- [ ] Cargar datos reales del COCIAP
- [ ] Pruebas con datos reales
- [ ] Video tutorial para docentes
- [ ] Deploy en servidor del colegio

## Meta: sistema listo para el 15 de mayo 2026
Los docentes subirán notas del I Bimestre el 16-17 de mayo 2026.

## Notas importantes
- `config/database.php` NO está en Git — crear manualmente en cada equipo
- Logo del colegio: `public/assets/img/logo_cociap.png` (con guión bajo)
- URL base dinámica via meta tag: `<meta name="base-url" content="...">`
- BrowserSync corre en puerto 3000, proxy a `localhost/siga-cociap/public`
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
- Competencias Transversales (caso especial)
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
* Inglés (area-curso)
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
* Competencias Transversales (caso especial)
  Competencias
  Se desenvuelve en entornos virtuales generados por las TIC
  Gestiona su aprendizaje de manera autónoma
