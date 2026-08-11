-- ============================================================================
-- VERIFICACION POST-CIERRE DE UN BIMESTRE — consulta de solo lectura
-- ============================================================================
-- Captura, EN EL ENTORNO DONDE SE CORRE, las cifras que deja el cierre de un
-- bimestre (Fase 5 del runbook `docs/runbooks/cierre-de-bimestre.md`) mas las
-- dos comprobaciones que el runbook pide despues: que la publicacion sea la
-- esperada y que el bimestre SIGUIENTE tenga `limite_notas` fijado.
--
-- POR QUE EXISTE: B2 se cerro y se publico en produccion el 10/08/2026 y las
-- cifras del snapshot NO se capturaron alli. Con el bimestre ya publicado, el
-- candado 046 dejo el snapshot oficial INMUTABLE: una discrepancia ya no se
-- puede corregir en el oficial, asi que lo unico que queda es MEDIRLA. Por eso
-- este archivo es de solo lectura y esta pensado para pegarse en phpMyAdmin.
--
-- ⚠️ REGLA DE TRAZABILIDAD (leccion de la migracion 048): la salida de estos
-- bloques es IDENTICA en local y en produccion, porque local es copia de prod.
-- El BLOQUE 0 es el unico que dice en que servidor estas. Sin el, una captura
-- no prueba nada. Correrlo SIEMPRE, y guardar su fila junto a las demas.
--
-- USO: cambiar `@num` (2 = II Bimestre) en cada bloque. Los bloques son
-- autocontenidos a proposito, para poder ejecutarlos sueltos sin arrastrar la
-- variable de otra conexion. El periodo se ancla por NUMERO + anio activo,
-- nunca por id (los ids difieren entre entornos).
--
-- SOLO LECTURA: no escribe nada. Ningun INSERT, UPDATE, DELETE ni DDL.
--
-- ⚠️ `SELECT ROW_COUNT()` y las variables NO cruzan de una pestana a otra en
-- phpMyAdmin. Si un bloque devuelve `periodo_id` NULL, es que se ejecuto sin su
-- `SET` previo: vuelve a lanzar el bloque entero.
-- ============================================================================


-- ============ 0) HUELLA DEL SERVIDOR — correr esto ANTES QUE NADA ===========
--   · LOCAL (XAMPP):    bd 'siga_cociap', root@localhost, so 'Win64'.
--   · PROD (Hostinger): otra bd, otro usuario, so Linux.
-- Si la fila dice Win64, estas en tu maquina y NADA de lo de abajo cuenta como
-- verificacion de produccion.
SELECT DATABASE() AS bd, USER() AS usuario_conexion, @@hostname AS hostname,
       @@version AS version, @@version_compile_os AS so, @@datadir AS datadir,
       NOW() AS momento_de_la_captura;


-- ============ 1) ANCLAJE + ESTADO DEL PERIODO ==============================
-- Esperado para B2 tras el cierre del 10/08/2026: estado 'cerrado' y
-- `boletas_aprobadas_en` con fecha (las boletas pasaron a OFICIAL).
SET @num := 2;
SET @periodo := (SELECT id FROM periodos
                  WHERE numero = @num
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));

SELECT @periodo AS periodo_id, p.numero, p.nombre_display, p.estado,
       p.limite_notas, p.boletas_aprobadas_en,
       CASE WHEN @periodo IS NULL     THEN '*** ABORTAR: periodo no resuelto ***'
            WHEN p.estado <> 'cerrado' THEN '*** OJO: el periodo NO esta cerrado ***'
            ELSE 'ANCLAJE OK · PERIODO CERRADO' END AS veredicto
FROM periodos p WHERE p.id = @periodo;


-- ============ 2) FIRMA DEL SNAPSHOT OFICIAL ================================
-- Es la captura que faltaba. Los `@esp_*` son la prediccion del espejo local
-- del 10/08/2026 para B2 — al reutilizar el archivo en B3/B4 hay que
-- actualizarlos (o ponerlos a NULL para que el veredicto salga 'SIN ESPERADO').
SET @num := 2;
SET @periodo := (SELECT id FROM periodos
                  WHERE numero = @num
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));
SET @esp_filas := 524, @esp_grados := 11, @esp_secciones := 23,
    @esp_mn := 1, @esp_mx := 72;

SELECT COUNT(*)                       AS filas,
       MIN(puesto_grado)              AS mn,
       MAX(puesto_grado)              AS mx,
       COUNT(DISTINCT grado_id)       AS grados,
       COUNT(DISTINCT seccion_id)     AS secciones,
       MIN(generado_en)               AS generado_en,
       CASE WHEN @esp_filas IS NULL THEN 'SIN ESPERADO — solo capturar'
            WHEN COUNT(*) = @esp_filas
             AND COUNT(DISTINCT grado_id)   = @esp_grados
             AND COUNT(DISTINCT seccion_id) = @esp_secciones
             AND MIN(puesto_grado) = @esp_mn
             AND MAX(puesto_grado) = @esp_mx
            THEN 'COINCIDE CON LO PREDICHO'
            ELSE '*** DISCREPANCIA: anotarla, el oficial ya NO se puede corregir ***'
       END AS veredicto
FROM orden_merito_snapshot WHERE periodo_id = @periodo;


-- ============ 3) NINGUN EMPATE PETRIFICADO — debe dar 0 FILAS ==============
-- La verificacion clave del runbook (5.3). Si devuelve filas, el cierre
-- congelo empates que nadie resolvio (el "hueco del guard"). Con el bimestre
-- ya PUBLICADO no se corrige en el oficial: la via es una rectificacion, que
-- va a `orden_merito_rectificado`.
SET @num := 2;
SET @periodo := (SELECT id FROM periodos
                  WHERE numero = @num
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));

SELECT grado_id, puesto_grado, COUNT(*) AS repetido
FROM orden_merito_snapshot WHERE periodo_id = @periodo
GROUP BY grado_id, puesto_grado HAVING COUNT(*) > 1;


-- ============ 4) NADIE SIN PUESTO DE SECCION — debe dar 0 ==================
SET @num := 2;
SET @periodo := (SELECT id FROM periodos
                  WHERE numero = @num
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));

SELECT COUNT(*) AS sin_puesto_seccion,
       CASE WHEN COUNT(*) = 0 THEN 'OK' ELSE '*** REVISAR ***' END AS veredicto
FROM orden_merito_snapshot WHERE periodo_id = @periodo AND puesto_seccion IS NULL;


-- ============ 5) BLOQUEOS FORZADOS POR EL CIERRE — se espera 0 =============
-- 0 significa que los docentes bloquearon todo por su cuenta y el cierre no
-- tuvo que forzar nada (era el caso en el ensayo local del 10/08). Un numero
-- distinto de 0 NO es un error, pero cambia dos cosas: reabre el hueco del
-- guard de empates (el universo validado no seria el congelado) y, si hay
-- transversales entre ellos, son los "fantasmas" que limpio la migracion 051.
SET @num := 2;
SET @periodo := (SELECT id FROM periodos
                  WHERE numero = @num
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));

SELECT bc.origen,
       CASE WHEN a.tipo = 'transversal' THEN 'transversal' ELSE 'academica' END AS clase,
       COUNT(*) AS bloqueos
FROM bloqueos_competencia bc
JOIN competencias c ON c.id = bc.competencia_id
LEFT JOIN areas a   ON a.id = c.area_id
WHERE bc.periodo_id = @periodo
GROUP BY bc.origen, clase
ORDER BY bc.origen, clase;


-- ============ 6) PUBLICACION DEL PERIODO Y CANDADO 046 =====================
-- B2 se publico el 10/08/2026 por NIVEL: se esperan 2 filas, una por nivel.
--
-- 🔴 LEER CON CUIDADO — publicar NO siempre activa el candado en el acto.
-- `fuePublicado()` (el candado de inmutabilidad del snapshot oficial) es:
--       primera_publicacion_en IS NOT NULL  OR  publica_en <= NOW()
-- El sello `primera_publicacion_en` SOLO se escribe cuando la publicacion es
-- INMEDIATA. Una publicacion PROGRAMADA a futuro deja la fila con el sello en
-- NULL y el candado se activa SOLO al llegar su `publica_en`.
--
-- CONSECUENCIA OPERATIVA: mientras las dos filas esten programadas a futuro,
-- el snapshot oficial TODAVIA es corregible (reabrir -> corregir -> re-cerrar
-- sigue escribiendo el OFICIAL) y las familias aun no ven nada. Esa ventana se
-- cierra sola, sin que nadie pulse nada, en el `publica_en` mas TEMPRANO de las
-- filas de abajo: `candado_desde`.
SET @num := 2;
SET @periodo := (SELECT id FROM periodos
                  WHERE numero = @num
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));

SELECT pp.*,
       CASE WHEN pp.despublicada_en IS NOT NULL THEN 'despublicada a mano'
            WHEN pp.suspendida_en   IS NOT NULL THEN 'suspendida por reapertura'
            WHEN pp.primera_publicacion_en IS NOT NULL THEN 'publicada (sello inmediato)'
            WHEN pp.publica_en <= NOW()                THEN 'publicada (programada, ya vencio)'
            ELSE 'PROGRAMADA a futuro — las familias aun NO la ven'
       END AS estado_publicacion
FROM periodos_publicacion pp WHERE pp.periodo_id = @periodo;

-- 6.b El candado, con la MISMA condicion que `fuePublicado()`.
SET @num := 2;
SET @periodo := (SELECT id FROM periodos
                  WHERE numero = @num
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));

SELECT
    SUM(pp.primera_publicacion_en IS NOT NULL OR pp.publica_en <= NOW()) AS niveles_publicados,
    MIN(pp.publica_en)                                                   AS candado_desde,
    CASE WHEN SUM(pp.primera_publicacion_en IS NOT NULL
                  OR pp.publica_en <= NOW()) > 0
         THEN 'CANDADO 046 ACTIVO — el snapshot oficial ya es INMUTABLE'
         ELSE 'candado 046 AUN NO activo — el oficial todavia es corregible hasta candado_desde'
    END AS veredicto
FROM periodos_publicacion pp WHERE pp.periodo_id = @periodo;


-- ============ 7) VERSION RECTIFICADA — se espera 0 =========================
-- Con el candado puesto, todo cierre o rectificacion POSTERIOR escribe aqui en
-- vez de en el oficial. Si trae filas, alguien ya toco el ranking despues de
-- publicar: mirarlo en `/admin/control` antes de sacar conclusiones.
SET @num := 2;
SET @periodo := (SELECT id FROM periodos
                  WHERE numero = @num
                    AND anio_id = (SELECT id FROM anios_academicos
                                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1));

SELECT COUNT(*) AS filas_rectificadas, MAX(generado_en) AS ultima
FROM orden_merito_rectificado WHERE periodo_id = @periodo;


-- ============ 8) EL BIMESTRE SIGUIENTE TIENE `limite_notas` ================
-- Un bimestre recien abierto lo trae en NULL, y con NULL `periodoEstaBloqueado`
-- devuelve false: los docentes SI registran, pero sin ninguna fecha limite.
-- Es el problema inverso al de B2, cuyo plazo vencido corto la captura de
-- asistencia sin que nadie lo notara. Aqui se mira B3 (numero = 3).
SET @num_sig := 3;

SELECT p.id, p.numero, p.nombre_display, p.estado, p.fecha_inicio, p.fecha_fin,
       p.limite_notas,
       CASE WHEN p.estado <> 'activo'      THEN 'no es el bimestre en curso'
            WHEN p.limite_notas IS NULL    THEN '*** FIJAR limite_notas en /director/anios/{anio} ***'
            WHEN p.limite_notas < NOW()    THEN '*** VENCIDO: los docentes NO pueden registrar ***'
            ELSE 'OK · plazo vigente' END AS veredicto
FROM periodos p
WHERE p.numero = @num_sig
  AND p.anio_id = (SELECT id FROM anios_academicos
                    WHERE estado = 'activo' ORDER BY anio DESC LIMIT 1);
