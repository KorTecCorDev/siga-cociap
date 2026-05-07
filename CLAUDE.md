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
│   └── migrations/
│       ├── siga_cociap.sql         ← schema completo + seeds
│       ├── 002_criterios_calificaciones.sql
│       └── 003_bloqueos_competencia.sql
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
│   │   └── pages/(_auth, _dashboard)
│   └── views/
│       ├── layouts/(auth.php, app.php)
│       ├── auth/login.php
│       ├── dashboard/index.php
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
```

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

## Pendientes al 7 de mayo 2026
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
