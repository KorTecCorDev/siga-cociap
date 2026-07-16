# Módulo: Exportación de notas al SIAGIE (llenado de Excel oficiales)

> Implementado el 03/07/2026 (fase CLI) y el 12/07/2026 (módulo web para
> admin/RA). Vuelca los promedios literales por competencia + conclusiones
> descriptivas de SIGA a los Excel que el SIAGIE exporta por sección+bimestre —
> el registro oficial ante UGEL-MINEDU que Registro académico llenaba a mano.
> Primaria operativa; secundaria pendiente.

## Invariantes (lo que NO se puede romper)

- **El archivo se RE-SUBE al SIAGIE** → preservación byte-a-byte: SOLO se
  insertan valores en celdas (edición quirúrgica del XML del xlsx vía
  `ZipArchive`); JAMÁS reescribir el libro con una librería (protección con
  contraseña, hoja oculta `Parametros`, estilos y validaciones deben quedar
  intactos).
- **Jamás rellenar a ciegas:** sin match exacto único (código o nombre), la
  fila queda intacta y va al reporte para resolución manual. Nunca se
  sobreescribe una celda que ya tiene valor.
- **Solo lo oficial:** competencia BLOQUEADA + bimestre CERRADO (el archivo
  completo se rechaza si el periodo no está `cerrado` en SIGA). Transversales:
  promedio agregado con cierre vigente del tutor (vía `getBoletaAlumno`).
- **`estudiantes.codigo_estudiante` = código SIAGIE (14 dígitos, col. B):** se
  persiste tras el primer match por nombre SOLO si está vacío; un valor
  distinto es conflicto reportado, nunca se pisa.
- El original se reemplaza in-place (decisión del usuario, para que el SIAGIE
  no rebote el archivo) pero SIEMPRE tras: escribir a temporal → verificar
  celda por celda → respaldar en `scripts/siagie/backup/{fecha}/`.

## Uso

```
php scripts/siagie/llenar-siagie.php --simular <archivo.xlsx|carpeta>   # SIEMPRE primero
php scripts/siagie/llenar-siagie.php <archivo.xlsx|carpeta>             # escritura real
```
Genera `{nombre}_reporte_{fecha}.txt` junto a cada archivo: matching,
celdas escritas, blancos con motivo, advertencias.

## Piezas

- `app/Siagie/XlsxQuirurgico.php` — lector/escritor quirúrgico del xlsx (shared
  strings: reusa índices o anexa `<si>` actualizando count/uniqueCount; celdas de
  plantilla ya existen vacías con estilo y `t="s"`). Namespace `App\Siagie\`,
  autocargable (movido desde `scripts/siagie/lib/` el 12/07/2026).
- `app/Siagie/MatcherEstudiantes.php` — normalización (mayúsculas, translitera
  tildes/Ñ→N, elimina todo lo que no sea `[A-Z0-9 espacio]`) y clasificación:
  `match_codigo` → `match_nombre` (exacto y único) → `ambiguo` /
  `conflicto_codigo` / `sin_match` (con sugerencia ≥80% solo informativa).
- `app/Siagie/LlenadorSiagie.php` — **orquestación compartida** (CLI + web).
  `analizar()` decide TODO sin tocar disco ni BD (es el preview); admite
  `$resoluciones` (fila→estudiante_id) para la **resolución manual de identidad**
  → estado `match_manual` con guardas (dentro del roster, sin cruce, sin
  conflicto de código). `escribirVerificado()` + `persistirCodigos()`. Con
  `$resoluciones` vacío es byte-idéntico al comportamiento automático (CLI).
- `app/Models/SiagieExportModel.php` — datos: `resolverDestino` (desde la hoja
  `Parametros`: nivel/año/B{n}/'1A'), `estudiantesDeSeccion` (aprobadas, excluye
  operativas de retorno activo), `notasOficiales` (boletaContexto +
  getBoletaAlumno → en retorno las notas de la operativa se escriben en la fila
  de la sección OFICIAL), `competenciasExoneradas`, `competenciasDelNivel`,
  `guardarCodigoSiagie`.
- `scripts/siagie/llenar-siagie.php` — CLI, wrapper delgado del `LlenadorSiagie`
  con su política propia: backup + reemplazo in-place del original (lote por
  carpeta con `--simular`).

## Módulo web — Actas SIAGIE (admin / registro_academico)

`app/Controllers/Admin/ActasSiagieController.php` + vistas
`resources/views/admin/actas_siagie/{index,preview,resultado}.php`. Tile en el
dashboard (grupo *Evaluación y reportes*). Rutas `/admin/actas-siagie[...]`.

- **Flujo EFÍMERO en dos pasos, una sección por vez:** subir → `previsualizar`
  (analiza sin escribir, muestra reporte + selectores de identidad) → `confirmar`
  (re-analiza con resoluciones, escribe, verifica, persiste códigos) →
  `resultado` (descarga del acta llenada + reporte `.txt`).
- **El xlsx subido vive en un temporal** (config `siagie_tmp_path`; local
  `storage/tmp/siagie`, prod `~/siga_uploads/siagie_tmp`) entre ambos pasos y se
  borra al confirmar. Barrido por TTL (30 min) en cada visita al índice.
- **NO reemplaza in-place** (a diferencia del CLI): produce una copia llenada
  que se streamea para descargar y re-subir al SIAGIE.
- **Resolución de identidad segura:** el usuario solo elige un alumno EXISTENTE
  del roster por DNI; la nota siempre sale de SIGA (nunca se teclea). Guardas en
  el controlador (whitelist del roster) y en el servicio (roster + cruce +
  conflicto de código). `conflicto_codigo` NO se resuelve aquí → enlace a
  corregir en la matrícula. Notas faltantes / columnas sin mapear → enlaces a
  Consulta de notas y Currículo (arreglo durable en SIGA).
- **Cambio de sección sin tramitar:** SIGA no tiene trámite de cambio de sección
  (la matrícula fija `seccion_id` al crear). Si una fila `sin_match` es un alumno
  que SIGA tiene en OTRA sección del mismo grado, el servicio lo **detecta**
  (`estudiantesDeOtrasSecciones` + `anotarOtraSeccion`, match por nombre único) y
  lo ofrece en el selector bajo *"Otras secciones del grado"*, marcado con aviso.
  Al resolverlo, se escriben SUS notas reales de SIGA y la resolución queda como
  `match_manual` con `cruce_seccion=true` y detalle *"CAMBIO DE SECCIÓN sin
  tramitar"* (auditoría). El auto-match sigue acotado a la sección; **lo que se
  escribe en las celdas no cambia** (la detección solo agrega pistas), así el CLI
  conserva su salida de celdas — solo gana líneas informativas en el reporte.
- **Seguridad:** `requireRole(['admin','registro_academico'])`, CSRF en POST,
  token de job atado a sesión, validación de subida (extensión, tamaño, firma
  ZIP `PK`). Único efecto en BD: `guardarCodigoSiagie` (solo si estaba vacío).

## Estructura del Excel SIAGIE (réplica verificada)

- UN libro por **grado+sección+bimestre**. Hoja `Parametros` (oculta) =
  identidad máquina (nivel B0/B1…, año, periodo B1..B4, grado, sección) →
  autodetección; `Generalidades` legible; una hoja por ÁREA con código SIAGIE
  (0005-COMU, 063-MATE, …, transversales 0006-DESEN TIC y 0007-GEST AUTO).
- Hoja de área: F1-2 cabecera (competencias 01..N × columnas **NL** +
  **Conclusión**); F3+ estudiantes (A=ID SIAGIE interno — NO es DNI,
  B=código 14 dígitos, C="APELLIDOS, NOMBRES"); leyenda al pie
  (`01 = nombre de la competencia`).
- El mapeo columna→competencia SIGA es por la LEYENDA normalizada contra
  `competencias.nombre_completo` del nivel (1 candidata → mapea). Cuando queda
  **ambigua (>1)** o **sin match (0)**, entra la resolución POR ÁREA (ver
  "Resolución por área"). Áreas sin equivalente (CAST SEGNL) quedan en blanco.
- La plantilla valida conclusiones a **10–500 caracteres** (dataValidation);
  fuera de rango se escribe igual pero con advertencia fuerte en el reporte.

## Casos especiales (reglas del 03/07/2026)

- EXO → celdas omitidas (el SIAGIE las bloquea de todos modos), reportado.
- "No se evaluó" (bloqueo sin notas) → en blanco, reportado.
- **Nota autorizada por dirección (14/07/2026):** si la celda quedaría en blanco por
  ausencia justificada pero dirección registró una nota autorizada para esa
  competencia (tabla `notas_autorizadas_siagie`, migración 040), el llenador la usa
  para rellenar SOLO esa celda vacía (literal + conclusión). Precedencia: la nota
  oficial de `calificaciones` SIEMPRE gana; la autorizada solo cubre el blanco. Se
  reporta aparte ("NOTAS AUTORIZADAS POR DIRECCIÓN — no evaluado") y suma la clave
  `resumen['autorizadas']`. Nunca pisa una celda con valor. `LlenadorSiagie` la
  lee vía `SiagieExportModel::notasAutorizadas()` en la rama `$nota === null`
  (une fuentes con `boletaContexto`, igual que `notasOficiales`: en RETORNO de
  grado la nota vive en la OPERATIVA y el export procesa la OFICIAL). La
  registran admin/RA desde `/matriculas/{id}/notas-siagie` (candado: omisión
  registrada de cualquier motivo + competencia bloqueada + sin nota real). Ver
  `docs/modulos/matriculas.md`.
- Retorno de grado → notas de la matrícula operativa en la fila de la sección
  oficial (viable: las competencias son por NIVEL, no por grado).
- Alumno de SIGA sin fila en el Excel (o viceversa) → reporte, sin acción.

## Resolución por área (secundaria 12/07/2026; primaria 16/07/2026)

Estructura idéntica en ambos niveles; el mismo módulo los procesa. Verificado
con nóminas reales (S1A, S5B; primaria 4°A). Puntos propios:

- **NL = literal `A,AD,B,C`** (confirmado por el `dataValidation` real, igual que
  primaria). El módulo escribe `nota_a_literal()` — no numérico.
- **Diferenciación por área (`areas.codigo_siagie`, migración 039).** El tab de
  cada hoja es `{codigo}-{ABREV}` (`063-MATE`, `057-INGL`; transversales
  `0006,0007`). El matcher resuelve la hoja→área por ese código
  (`areaPorCodigoSiagie`, `FIND_IN_SET`) y, SOLO para columnas ambiguas o sin
  match, re-mapea DENTRO de esa área:
  - **Homónimos Matemática vs Talleres:** "Resuelve problemas de cantidad" existe
    en Matemática (C44) y en el Taller Raz. Mat. (C54) con el MISMO nombre. La
    resolución por área se queda con la de la hoja (Matemática) e **ignora la del
    taller**. NO se renombran competencias (curricularmente son la misma; renombrar
    rompería el export futuro del taller y las boletas de SIGA).
  - **Inglés (leyenda abreviada):** el SIAGIE dice "Se comunica oralmente"; SIGA
    "…en Inglés como lengua extranjera" → 0 match de texto. Se asigna **por
    posición** (col 01→competencia de orden 1, etc.) dentro del área Inglés.
- **Primaria poblada (16/07/2026, migración 041):** códigos tomados del RegNotas
  real de 4°A B1. NO coinciden con secundaria: Inglés `0003` (sec. `057`),
  Comunicación `0005` (sec. `017`), Personal Social `067` (sec. no existe; su
  análogo DPCC es `0010`); transversales `0006,0007`. CAST SEGNL (hoja `0002`)
  y Tutoría quedan SIN código a propósito (nunca son destino). Validado con
  `--simular` sobre el acta real de 4°A B1: reporte byte-idéntico pre/post
  (el código solo actúa cuando el match de texto falla).
  - **Lección Inglés C1 primaria (motivo del poblado):** el SIAGIE dice
    "Se comunica ORALMENTE en inglés…"; SIGA la tenía sin "oralmente" → 0 match
    de texto y, sin `codigo_siagie`, sin fallback por posición → columna 01
    "sin equivalente en SIGA" y las actas 4°A/4°B B1 salieron con Inglés en
    blanco pese a tener notas bloqueadas. Renombrada al nombre oficial CN el
    14/07 (formalizado en la 041); con el código poblado, una discrepancia
    futura se llena por posición en vez de quedar muda.
- **Talleres (Raz. Mat., Pre-Cálculo):** SIN `codigo_siagie` (no tienen hoja en el
  SIAGIE todavía) → nunca son destino. **Diferido:** cuando se aprueben, un
  **selector por nómina** (sin flag persistente) dejará que RA elija incluirlos; y
  se define cómo llegan (hoja propia = trivial por área; área anfitriona = mapeo).
- **Ética/EREL (diferido a B2):** la hoja `035-EREL` mapea a las 2 competencias del
  área 14 (Ed. Religiosa), hoy vacía; la nota real es C57 (área 24, tutoría). En B1
  no hay notas de Ética → EREL en blanco es correcto. Para B2: mapear C57 → ambas
  columnas EREL; exonerados → EXO.

## Validado (03/07/2026, local, 1°A B1)

472 celdas (440 NL + 32 conclusiones) escritas y verificadas una a una;
literales cruzados contra BD; 22 códigos persistidos; segunda corrida
idempotente (matchea por código, 0 escrituras); protección/merges intactos.

## Pendientes

Ver `docs/ESTADO.md`: piloto de re-importación al SIAGIE (1 archivo),
reprocesar las actas de primaria llenadas antes del 14/07 (Inglés en blanco:
mínimo 4°A/4°B B1), y para secundaria: selector de talleres + Ética/EREL en B2
(ambos diferidos). La discrepancia de Inglés C1 primaria y el poblado de
`codigo_siagie` de primaria quedaron RESUELTOS (14-16/07, migración 041).
