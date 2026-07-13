-- ════════════════════════════════════════════════════════════════════
-- Migración 039: Código de hoja SIAGIE por área (mapeo hoja→área)
-- ════════════════════════════════════════════════════════════════════
-- OBJETIVO: dar al exportador SIAGIE una clave EXACTA hoja→área para el
--   nivel Secundaria. Las hojas del RegNotas se llaman "{codigo}-{ABREV}"
--   (063-MATE, 057-INGL, 0006-DESEN TIC…). Guardando ese código por área,
--   el matcher puede resolver una columna AMBIGUA o SIN MATCH dentro de la
--   competencia correcta de ESA área — sin renombrar competencias.
--
--   Caso que resuelve: Matemática y los Talleres (Raz. Mat., Pre-Cálculo)
--   comparten el MISMO nombre de competencia ("Resuelve problemas de …").
--   Con el código, la hoja 063-MATE se resuelve dentro del área Matemática
--   (competencia única) e ignora la homónima del taller. También habilita
--   el llenado de Inglés por posición (leyenda SIAGIE abreviada).
--
-- ALCANCE: SOLO SECUNDARIA. Primaria queda con codigo_siagie NULL → mantiene
--   su matching global ya validado (cero riesgo). Se poblará cuando haya un
--   archivo modelo de primaria. Los TALLERES quedan sin código (no tienen
--   hoja en el SIAGIE todavía) → nunca son destino.
--
-- Idempotente: ADD COLUMN IF NOT EXISTS + UPDATEs por (nombre + nivel).
--   area_id NO hardcodeado. Transversales: 2 hojas (DESEN TIC, GEST AUTO)
--   → una sola área → código compuesto "0006,0007".
--
-- Además: corrige el nombre_siagie ERRÓNEO del "Taller de Razonamiento
--   Matemático" (hoy "Educación Religiosa", resabio de un plan viejo de
--   mapearlo dentro de EREL) → su propio nombre, como el otro taller.
--
-- Ejecutar DESPUÉS de 038. Conexión utf8mb4.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PREVIEW (solo lectura). Correr primero en prod: ──
--   SELECT a.id, a.nombre, a.codigo_siagie
--   FROM areas a JOIN niveles n ON n.id = a.nivel_id
--   WHERE n.nombre = 'Secundaria' ORDER BY a.orden;

ALTER TABLE areas
    ADD COLUMN IF NOT EXISTS codigo_siagie VARCHAR(20) NULL AFTER nombre_siagie;

-- Poblado por área (nivel Secundaria), resuelto por nombre. Códigos tomados de
-- las nóminas RegNotas reales (S1A / S5B).
UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '0001'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Arte y Cultura';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '0004'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Ciencia y Tecnología';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '0010'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Desarrollo Personal, Ciudadanía y Cívica';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '014'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Ciencias Sociales';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '017'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Comunicación';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '031'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Educación Física';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '032'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Educación para el Trabajo';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '035'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Educación Religiosa';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '057'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Inglés';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '063'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Matemática';

-- Transversales: dos hojas (DESEN TIC, GEST AUTO) → una sola área SIGA.
UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '0006,0007'
 WHERE n.nombre = 'Secundaria' AND a.nombre = 'Competencias Transversales';

-- Corrección del nombre_siagie erróneo del Taller de Razonamiento Matemático.
UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.nombre_siagie = 'Taller de Razonamiento Matemático'
 WHERE n.nombre = 'Secundaria'
   AND a.nombre = 'Taller de Razonamiento Matemático'
   AND a.nombre_siagie = 'Educación Religiosa';

-- Verificación (debe listar los códigos por área; talleres y CAST SEGNL sin código):
--   SELECT a.nombre, a.codigo_siagie, a.nombre_siagie
--   FROM areas a JOIN niveles n ON n.id = a.nivel_id
--   WHERE n.nombre = 'Secundaria' ORDER BY a.orden;
