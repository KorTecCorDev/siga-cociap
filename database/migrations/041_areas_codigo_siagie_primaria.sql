-- ════════════════════════════════════════════════════════════════════
-- Migración 041: Código de hoja SIAGIE por área — PRIMARIA
-- ════════════════════════════════════════════════════════════════════
-- OBJETIVO: completar para Primaria el mapeo hoja→área que la migración 039
--   dejó solo en Secundaria. Las hojas del RegNotas de primaria se llaman
--   "{codigo}-{ABREV}" (0005-COMU, 063-MATE, 0003-INGLES EXT…). Con el código
--   por área, el matcher del exportador SIAGIE puede resolver una columna
--   AMBIGUA o SIN MATCH de texto por POSICIÓN dentro del área correcta —
--   sin depender de que `competencias.nombre_completo` coincida letra por
--   letra con la leyenda del SIAGIE.
--
--   Caso que motiva el cambio: Inglés C1 primaria. El SIAGIE la llama
--   "Se comunica ORALMENTE en inglés como lengua extranjera"; SIGA la tenía
--   sin "oralmente" → 0 match de texto y, al no haber codigo_siagie en
--   primaria, tampoco fallback por posición → la columna quedó "sin
--   equivalente en SIGA" y las actas de 4°A/4°B B1 salieron con Inglés en
--   blanco pese a tener notas bloqueadas. Con este poblado, una discrepancia
--   futura de nombre se llena igual por posición (como Inglés secundaria).
--
--   OJO: los códigos de primaria NO coinciden con los de secundaria:
--   Inglés 0003 (sec. 057), Comunicación 0005 (sec. 017), Personal Social
--   067 (sec. no existe; su análogo DPCC es 0010). Códigos tomados del
--   archivo RegNotas real de 4°A B1 (verificado el 16/07/2026).
--
--   Quedan SIN código a propósito (nunca deben ser destino de una hoja):
--   - CAST SEGNL (hoja 0002): no existe como área en SIGA → en blanco.
--   - Tutoría (TOE): sin competencias evaluables.
--
-- ADEMÁS: formaliza el rename de Inglés C1 primaria al nombre oficial del
--   Currículo Nacional (aplicado directo en BD local+prod el 14/07/2026,
--   hasta hoy sin migración — deuda). Anclado por área+orden y con guarda
--   NOT LIKE: en local/prod es un no-op; en un setup desde cero corrige.
--
-- Idempotente: UPDATEs por (nombre + nivel), area_id NO hardcodeado.
--   Transversales: 2 hojas (DESEN TIC, GEST AUTO) → una sola área → código
--   compuesto "0006,0007" (el matcher usa FIND_IN_SET).
--
-- Ejecutar DESPUÉS de 040. Conexión utf8mb4.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PREVIEW (solo lectura). Correr primero en prod: ──
--   SELECT a.id, a.nombre, a.tipo, a.codigo_siagie
--   FROM areas a JOIN niveles n ON n.id = a.nivel_id
--   WHERE n.nombre = 'Primaria' ORDER BY a.orden;

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '0001'
 WHERE n.nombre = 'Primaria' AND a.nombre = 'Arte y Cultura';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '0003'
 WHERE n.nombre = 'Primaria' AND a.nombre = 'Inglés';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '0004'
 WHERE n.nombre = 'Primaria' AND a.nombre = 'Ciencia y Tecnología';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '0005'
 WHERE n.nombre = 'Primaria' AND a.nombre = 'Comunicación';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '031'
 WHERE n.nombre = 'Primaria' AND a.nombre = 'Educación Física';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '035'
 WHERE n.nombre = 'Primaria' AND a.nombre = 'Educación Religiosa';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '063'
 WHERE n.nombre = 'Primaria' AND a.nombre = 'Matemática';

UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '067'
 WHERE n.nombre = 'Primaria' AND a.nombre = 'Personal Social';

-- Transversales: dos hojas (DESEN TIC, GEST AUTO) → una sola área SIGA.
UPDATE areas a JOIN niveles n ON n.id = a.nivel_id
   SET a.codigo_siagie = '0006,0007'
 WHERE n.nombre = 'Primaria' AND a.nombre = 'Competencias Transversales';

-- Formalización del rename de Inglés C1 primaria (aplicado a mano el
-- 14/07/2026): nombre oficial CN con "oralmente". No-op si ya está corregido.
UPDATE competencias c
  JOIN areas a   ON a.id = c.area_id
  JOIN niveles n ON n.id = a.nivel_id
   SET c.nombre_completo = 'Se comunica oralmente en inglés como lengua extranjera.'
 WHERE n.nombre = 'Primaria'
   AND a.nombre = 'Inglés'
   AND c.orden  = 1
   AND c.nombre_completo NOT LIKE '%oralmente%';

-- Verificación (CAST SEGNL no existe como área y Tutoría queda sin código):
--   SELECT a.nombre, a.tipo, a.codigo_siagie
--   FROM areas a JOIN niveles n ON n.id = a.nivel_id
--   WHERE n.nombre = 'Primaria' ORDER BY a.orden;
--   SELECT c.orden, c.nombre_completo FROM competencias c
--   JOIN areas a ON a.id = c.area_id JOIN niveles n ON n.id = a.nivel_id
--   WHERE n.nombre = 'Primaria' AND a.nombre = 'Inglés' ORDER BY c.orden;
