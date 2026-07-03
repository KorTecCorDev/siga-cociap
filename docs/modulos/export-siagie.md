# Módulo: Exportación de notas al SIAGIE (llenado de Excel oficiales)

> Implementado el 03/07/2026 (fase CLI). Vuelca los promedios literales por
> competencia + conclusiones descriptivas de SIGA a los Excel que el SIAGIE
> exporta por sección+bimestre — el registro oficial ante UGEL-MINEDU que
> Registro académico llenaba a mano. Primaria operativa; secundaria pendiente.

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

- `scripts/siagie/llenar-siagie.php` — CLI (bootstrap tipo backfill; lote por carpeta).
- `scripts/siagie/lib/XlsxQuirurgico.php` — lector/escritor quirúrgico del xlsx
  (shared strings: reusa índices o anexa `<si>` actualizando count/uniqueCount;
  celdas de plantilla ya existen vacías con estilo y `t="s"`).
- `scripts/siagie/lib/MatcherEstudiantes.php` — normalización (mayúsculas,
  translitera tildes/Ñ→N, elimina todo lo que no sea `[A-Z0-9 espacio]`) y
  clasificación: `match_codigo` → `match_nombre` (exacto y único) → `ambiguo` /
  `conflicto_codigo` / `sin_match` (con sugerencia ≥80% solo informativa).
- `app/Models/SiagieExportModel.php` — datos: `resolverDestino` (desde la hoja
  `Parametros`: nivel/año/B{n}/'1A'), `estudiantesDeSeccion` (aprobadas, excluye
  operativas de retorno activo), `notasOficiales` (boletaContexto +
  getBoletaAlumno → en retorno las notas de la operativa se escriben en la fila
  de la sección OFICIAL), `competenciasExoneradas`, `competenciasDelNivel`,
  `guardarCodigoSiagie`.
- El futuro módulo web reutiliza Model + libs con un controlador de subida.

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
  `competencias.nombre_completo` del nivel (0 o >1 candidatas → columna en
  blanco + reporte). Áreas sin equivalente (CAST SEGNL) quedan en blanco.
- La plantilla valida conclusiones a **10–500 caracteres** (dataValidation);
  fuera de rango se escribe igual pero con advertencia fuerte en el reporte.

## Casos especiales (reglas del 03/07/2026)

- EXO → celdas omitidas (el SIAGIE las bloquea de todos modos), reportado.
- "No se evaluó" (bloqueo sin notas) → en blanco, reportado.
- Retorno de grado → notas de la matrícula operativa en la fila de la sección
  oficial (viable: las competencias son por NIVEL, no por grado).
- Alumno de SIGA sin fila en el Excel (o viceversa) → reporte, sin acción.

## Validado (03/07/2026, local, 1°A B1)

472 celdas (440 NL + 32 conclusiones) escritas y verificadas una a una;
literales cruzados contra BD; 22 códigos persistidos; segunda corrida
idempotente (matchea por código, 0 escrituras); protección/merges intactos.

## Pendientes

Ver `docs/ESTADO.md`: piloto de re-importación al SIAGIE (1 archivo),
discrepancia de nombre en Inglés C1, y la variante SECUNDARIA (numeral+literal
por confirmar con su archivo modelo).
