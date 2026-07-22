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

## Red de documentación (LEER ANTES DE TOCAR UN MÓDULO)

La documentación detallada vive en `docs/` y se lee BAJO DEMANDA. **Antes de
modificar código de un módulo, lee SU archivo** — contiene reglas de negocio,
decisiones de diseño y gotchas que NO son visibles en el código:

| Si el cambio toca… | Lee primero… |
|---|---|
| Boletas (imprimible, digital, token, pública dormida, firmas) | `docs/modulos/boletas.md` |
| Calificaciones, criterios, bloqueos, transversales, consulta de notas | `docs/modulos/calificaciones.md` |
| Matrículas, apoderados, estados, alta provisional, retorno/traslado | `docs/modulos/matriculas.md` |
| Horarios, cargas académicas, solapes, Tutoría TOE | `docs/modulos/horarios.md` |
| Orden de mérito, snapshot, desempates, rectificaciones | `docs/modulos/orden-merito.md` |
| Usuarios, secciones/tutores, Director EBR, panel de bloqueos, conducta | `docs/modulos/admin.md` |
| Exportación de notas al SIAGIE (llenado de Excel oficiales) | `docs/modulos/export-siagie.md` |
| UI: wayfinding, dashboard docente, botón Cerrar, tablas sticky | `docs/modulos/ui.md` |
| Producción, seguridad, despliegue, secretos, setup SQL desde cero | `docs/infraestructura.md` |
| Decisiones diferidas (suspensiones, compuerta de publicación, capacitación) | `docs/decisiones-diferidas.md` |
| **Estado vivo: pendientes, migraciones, planes con fecha** | `docs/ESTADO.md` |

### Reglas de mantenimiento de la red
- Al terminar un cambio de módulo, actualiza SU archivo en `docs/` (no CLAUDE.md).
- CLAUDE.md solo cambia si nace un invariante global o un módulo nuevo (fila en la tabla).
- Pendientes, migraciones y planes con fecha se registran SOLO en `docs/ESTADO.md`.
- NUNCA usar la sintaxis de import `@ruta` en CLAUDE.md — auto-carga el archivo
  en cada sesión y anula el ahorro de contexto. Referenciar siempre por ruta simple.

## Invariantes críticos (NO romper)

Versión de una línea; el porqué completo está en el doc del módulo.

- **Boleta = solo competencias bloqueadas** (`getBoletaAlumno` INNER JOIN `bloqueos_competencia`).
- **Boletas al público SIEMPRE por token** — jamás reintroducir rutas anónimas por id
  (eran enumerables). Toda boleta se arma con `BoletaModel::armar()`; el QR sale solo
  de `urlBoletaToken()`.
- **CERRAR UN BIMESTRE NO PUBLICA NADA.** Publicar las boletas a las familias es un
  acto separado, por NIVEL y con fecha/hora (`periodos_publicacion`, migración 044).
  Punto único: `PublicacionBoletaModel`. El umbral `'oficial'` de `BoletaModel::armar()`
  respeta la compuerta (acceso en línea de familias); `'archivo'` la ignora a propósito
  (documento impreso por staff: salida masiva y trasladado).
- **Escala de notas: punto único de verdad en `app/Helpers/helpers.php`**
  (`NOTA_MIN_AD/A/B`, `nota_a_literal()`, `escala_rangos()`). NUNCA hardcodear umbrales.
- **Rutas literales ANTES que patrones `{param}`** en `routes/web.php` (el router
  ancla por orden de registro).
- **`criterios.confirmado_en` es la única verdad de "oficial"**: cualquier mutación
  del criterio desconfirma; `marcarConfirmado` va ANTES de `recalcularPromedioSeccion`.
- **Fila en `calificaciones` existe ⟺ el alumno tiene nota viva** (el DELETE de
  huérfanos mira `eliminado_en`, NO `confirmado_en`).
- **Estados de matrícula: SOLO `pendiente`/`aprobada`/`desactivado`** (`'activo'` se
  eliminó del enum; otras columnas `estado` de otras tablas son independientes).
- **Rosters de evaluación (calificaciones, conducta, transversales, tutoría)
  excluyen matrículas `tipo IN ('trasladado','retirado')`** — el resto (incl.
  `desactivado` por deuda y `pendiente`) SÍ se califica. `retirado` = ya no asiste
  sin traslado oficial (migración 045); reversible vía `tipo_anterior`. NO extender
  a los usos de `trasladado` en boleta (un retirado es desactivado no-trasladado →
  BORRADOR). Ver `docs/modulos/matriculas.md`.
- **Orden de mérito excluye áreas `tipo IN ('transversal','tutoria')`** — permanente.
- **PDO preparado siempre**; `cargas_academicas` y `criterios` NO tienen UNIQUE KEY →
  proteger duplicados con `WHERE NOT EXISTS`.
- **NUNCA CSS inline en PHP** — todo en SASS bajo `resources/sass/` + `gulp build`.
- **El auto-deploy de Hostinger borra TODO lo no versionado** en cada push →
  secretos y archivos subidos viven fuera del repo (`~/siga_secrets/`, `~/siga_uploads/`).
- **Git: `dev` = trabajo, `main` = producción (auto-deploy). PREGUNTAR antes de
  mergear `dev` → `main`.**

## Arquitectura de carpetas
```
siga-cociap/
├── app/
│   ├── Controllers/        ← por dominio: Admin/, Auth/, Boleta/, Consulta/,
│   │                          Director/, Docente/, Matricula/, Padre/,
│   │                          Rectificacion/ + BaseController, DashboardController
│   ├── Models/             ← BaseModel + un modelo por dominio (UsuarioModel,
│   │                          CalificacionModel, BoletaModel, MatriculaModel, …)
│   ├── Middleware/AuthMiddleware.php   (SIN USAR — la auth es por controlador)
│   └── Helpers/helpers.php ← funciones globales + constantes de escala
├── core/                   ← Router, Database, Session, View, Throttle
├── config/
│   ├── app.php             ← valores env-aware (debug, app_url, firmas_path)
│   └── database.php        ← cargador SIN secretos (prod lee ~/siga_secrets/)
├── database/
│   ├── migrations/         ← orden de ejecución en docs/infraestructura.md
│   └── seeds/
├── docs/                   ← RED DE DOCUMENTACIÓN (ver tabla de arriba)
├── public/                 ← document root lógico: index.php (front controller),
│   ├── css/app.css         ← compilado por Gulp (no editar a mano)
│   ├── js/                 ← compilados desde resources/js/
│   └── assets/             ← img, fonts/inter, icons (SVGs locales), qrcode.min.js
├── resources/
│   ├── sass/               ← base/, components/, pages/ — importados en app.scss
│   ├── js/                 ← fuentes JS (gulp los copia a public/js/)
│   └── views/              ← layouts/(auth, app, print, digital) + carpeta por módulo
└── routes/web.php          ← única tabla de rutas
```

## Base de datos — tablas principales
```
roles, personas, usuarios
niveles, grados, areas, subareas, competencias
reglas_especiales
anios_academicos, periodos, secciones
cargas_academicas, sesiones_horario, bloques_horario, configuracion_horario
estudiantes, apoderados, vinculo_familiar
matriculas, alertas, documentos_matricula, notas_externas, retornos_grado
criterios, calificaciones_criterio, calificaciones, omisiones_criterio
bloqueos_competencia, conclusiones_transversales
boletas_publicas (dormida), director_ebr_historial
orden_merito_snapshot, rectificaciones_calificacion
```
Migraciones aplicadas y orden de setup desde cero: ver `docs/infraestructura.md`
y `docs/ESTADO.md`.

## Estructura curricular
- **Área con subáreas:** cada subárea tiene 1 competencia y 1 docente
- **Área-curso:** sin subáreas, 1 docente dicta todas las competencias
- **Unidocente:** primaria 1°-3°, flag `es_unidocente` en tabla secciones
  -Todas las áreas son un área-curso, todas las cargas que deberían ser subáreas se integran al área y se evalúa como un área-curso.
- **Competencias transversales:** las registran cada docente por carga académica y las conclusiones descriptivas, aprobación y bloqueo de notas queda a cargo del tutor de sección.
- **Tutoría (TOE):** área `tipo='tutoria'` sin competencias — visible en horario,
  invisible a notas (por datos, no por código). Ver `docs/modulos/horarios.md`.

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
8. Padre accede a la boleta desde /padre/notas SIEMPRE por token:
   - "Ver boleta digital"  → /boleta/digital/{token}  (mobile-first)
   - "🖨 Imprimir"          → /boleta/ver/{token}      (A4 landscape)
```

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
- **Comillas ASCII** en vistas PHP — jamás comillas tipográficas U+201C/U+201D en
  atributos HTML (rompen los `data-*` y el JS en silencio).
- **`exif_imagetype()` NO disponible en XAMPP local** — usar `\getimagesize($path)[2]`.

## Notas de entorno
- Logo del colegio: `public/assets/img/logo_cociap.png` (con guión bajo)
- URL base dinámica via meta tag: `<meta name="base-url" content="...">`
- BrowserSync corre en puerto 3000; alias Git Bash `local3000` para iniciar el entorno
- Todo QR se genera con `qrcode.min.js` local — sin servicios de terceros
- `hash.php` en raíz: archivo temporal para hashes bcrypt — eliminar tras usarlo,
  NO commitear ni dejar en el servidor
- Firma/sello PNG del Director EBR: FUERA del repo (`~/siga_uploads/firmas/` prod,
  `storage/firmas/` local), servidos por `GET /firmas/{archivo}`. Detalle en
  `docs/infraestructura.md`.
