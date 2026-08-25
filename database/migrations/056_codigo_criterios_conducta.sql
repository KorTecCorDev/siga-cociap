-- 056_codigo_criterios_conducta.sql
-- Codigo estable para cada criterio de conducta.
--
-- CONTEXTO (25/08/2026). Las grillas de conducta rotulan sus columnas C1, C2...
-- y ese codigo se calculaba A MANO en cada vista como `$i + 1`, o sea POR
-- POSICION dentro de la lista que devuelve `ConductaModel::getCriterios()`:
--
--   · admin/conducta/imprimir.php        <th class="tr-crit">C<?= $i + 1 ?></th>
--   · docente/conducta-criterios.php     <th class="conducta-th-crit">C<?= $i+1 ?></th>
--
-- EL PROBLEMA DE UN CODIGO POSICIONAL. `criterios_conducta` no tenia columna de
-- codigo: solo id, texto, nivel_id y orden. Si alguien reordena un criterio, o
-- elimina uno de en medio, TODOS los codigos siguientes se corren -- y los
-- registros ya IMPRESOS Y FIRMADOS dejan de cuadrar con lo que muestra la
-- pantalla, sin ningun error visible. Hoy no ha pasado porque nadie los ha
-- tocado, y por eso se ancla antes de que pase.
--
-- SEGUNDO MOTIVO, mas silencioso: `getCriterios($nivelId)` FILTRA por nivel
-- (`nivel_id IS NULL OR nivel_id = ?`). Hoy los 10 criterios son globales, asi
-- que la posicion coincide en primaria y en secundaria. En cuanto exista UN solo
-- criterio por nivel, la misma posicion significaria criterios distintos en cada
-- nivel y el codigo dejaria de identificar nada. La columna lo resuelve de raiz.
--
-- IMPACTO VISUAL: NINGUNO. Medido antes de escribir esto: hay 10 criterios
-- vigentes, TODOS globales, con `orden` de 1 a 10 SIN HUECOS. Por tanto
-- `C{posicion}` y `C{orden}` dan exactamente el mismo valor para los 10, y el
-- sembrado de abajo reproduce letra por letra lo que hoy se imprime. Ningun
-- documento existente cambia.
--
-- Idempotente: la columna se anade solo si no existe, y el UPDATE de sembrado
-- solo toca las filas que aun no tienen codigo.

-- 1) La columna. `codigo` admite NULL a proposito: un criterio nuevo puede
--    nacer sin el y la aplicacion cae al codigo posicional como antes.
SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'criterios_conducta'
      AND COLUMN_NAME  = 'codigo'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE criterios_conducta
        ADD COLUMN codigo VARCHAR(8) NULL DEFAULT NULL AFTER id',
    'SELECT "056: la columna codigo ya existe, no se toca" AS aviso'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2) Sembrado con el codigo que HOY se muestra. Solo las filas sin codigo, para
--    que reejecutar no pise un codigo corregido a mano mas adelante.
UPDATE criterios_conducta
   SET codigo = CONCAT('C', orden)
 WHERE codigo IS NULL
   AND eliminado_en IS NULL;

-- 3) Verificacion. Los 10 vigentes deben quedar con codigo y sin repetidos.
SELECT COUNT(*)                        AS vigentes,
       COUNT(codigo)                   AS con_codigo,
       COUNT(DISTINCT codigo)          AS codigos_distintos
  FROM criterios_conducta
 WHERE eliminado_en IS NULL;
