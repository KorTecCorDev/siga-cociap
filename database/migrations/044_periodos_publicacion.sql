-- ============================================================
-- 044_periodos_publicacion.sql
-- COMPUERTA DE PUBLICACION DE BOLETAS.
--
-- Antes de esta tabla, poner un bimestre en 'cerrado' publicaba sus
-- boletas a las familias AL INSTANTE. Las boletas se entregan en
-- reuniones oficiales y primaria suele entregarse un dia antes que
-- secundaria, por lo que la publicacion es POR NIVEL y con fecha/hora.
--
-- Semantica de una fila (periodo_id, nivel_id):
--   sin fila                  -> NO publicado
--   publica_en en el futuro   -> PROGRAMADO (invisible hasta esa hora)
--   publica_en en el pasado   -> PUBLICADO
--   suspendida_en NOT NULL    -> suspendido por reapertura (REVERSIBLE)
--   despublicada_en NOT NULL  -> despublicado a mano (DEFINITIVO)
--
-- La fila NO se borra al despublicar: se marca, para conservar el motivo
-- y el autor (mismo patron anulado_en/motivo_anulacion de cierres_conducta
-- y cierres_asistencia). Volver a publicar limpia la marca.
--
-- Un solo mecanismo cubre publicar y programar SIN cron: la condicion
-- se evalua al leer. OJO: el "ahora" lo calcula PHP (config timezone
-- America/Lima) y entra como parametro preparado — NOW() de MySQL no
-- interviene en la lectura, porque el huso del servidor de produccion
-- es desconocido (Hostinger suele estar en UTC) y adelantaria las
-- publicaciones programadas.
--
-- Matriz de reapertura (la implementa PeriodoController):
--   Cerrar               -> NO publica, nunca (acto separado)
--   Reabrir              -> suspendida_en = ahora (REVERSIBLE)
--   Volver a cerrar      -> suspendida_en = NULL (restaura la publicacion)
--   Despublicar manual   -> despublicada_en = ahora + motivo (DEFINITIVO:
--                           volver a cerrar NO lo revive; solo publicar
--                           de nuevo a mano limpia la marca)
--
-- Idempotente: CREATE TABLE IF NOT EXISTS + backfill anti-duplicado por
-- LEFT JOIN (ademas del UNIQUE).
-- ============================================================

CREATE TABLE IF NOT EXISTS periodos_publicacion (
    id                    INT      UNSIGNED NOT NULL AUTO_INCREMENT,
    periodo_id            SMALLINT UNSIGNED NOT NULL,
    nivel_id              TINYINT  UNSIGNED NOT NULL,
    publica_en            DATETIME          NOT NULL,
    suspendida_en         DATETIME          NULL,
    despublicada_en       DATETIME          NULL,
    despublicada_por      INT      UNSIGNED NULL,
    motivo_despublicacion VARCHAR(500)      NULL,
    publicado_por         INT      UNSIGNED NULL,
    creado_en             DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_periodo_nivel (periodo_id, nivel_id),
    KEY idx_nivel (nivel_id),
    KEY idx_publicado_por (publicado_por),
    KEY idx_despublicada_por (despublicada_por),
    CONSTRAINT periodos_publicacion_ibfk_1 FOREIGN KEY (periodo_id)       REFERENCES periodos (id) ON DELETE CASCADE,
    CONSTRAINT periodos_publicacion_ibfk_2 FOREIGN KEY (nivel_id)         REFERENCES niveles  (id),
    CONSTRAINT periodos_publicacion_ibfk_3 FOREIGN KEY (publicado_por)    REFERENCES usuarios (id),
    CONSTRAINT periodos_publicacion_ibfk_4 FOREIGN KEY (despublicada_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- BACKFILL RETROACTIVO — OBLIGATORIO.
-- Todo bimestre ya CERRADO queda publicado en TODOS los niveles. Sin
-- esto el deploy ocultaria a las familias las boletas que hoy ven
-- (el I Bimestre), que es la regresion mas grave posible de este cambio.
--
-- publica_en = boletas_aprobadas_en (el Hito A, momento real en que la
-- boleta quedo aprobada) y NOW() como respaldo para los cierres previos
-- a la migracion 025, que lo tienen NULL (anomalia historica de B1).
-- Ambos son pasados, asi que la boleta queda visible de inmediato.
--
-- publicado_por queda NULL a proposito: no hubo un acto humano de
-- publicacion, la publicacion es derivada del cierre historico.
-- ------------------------------------------------------------

INSERT INTO periodos_publicacion (periodo_id, nivel_id, publica_en, publicado_por)
SELECT p.id, n.id, COALESCE(p.boletas_aprobadas_en, NOW()), NULL
FROM periodos p
CROSS JOIN niveles n
LEFT JOIN periodos_publicacion pp
       ON pp.periodo_id = p.id
      AND pp.nivel_id   = n.id
WHERE p.estado = 'cerrado'
  AND pp.id IS NULL;
