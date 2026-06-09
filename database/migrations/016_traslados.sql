-- ════════════════════════════════════════════════════════════════════
-- Migración 016: Constancias de traslado (salida)
-- ════════════════════════════════════════════════════════════════════
-- Crea la tabla `traslados` (libro oficial de constancias de traslado del
-- COCIAP) y agrega a `anios_academicos` los dos datos que varían por año:
-- el lema oficial ("Año de…") y el correlativo inicial de constancias.
--
-- Numeración: correlativo por año, formato N° 000-{AÑO}-CAVVG-DA. El número
-- es ÚNICO solo entre constancias 'vigente' del año (una 'anulado' LIBERA su
-- número para reutilizarse) → la unicidad se valida a nivel de aplicación
-- (TrasladoModel), por eso NO hay UNIQUE KEY sobre (anio_id, correlativo).
--
-- MariaDB 10.4 soporta ADD COLUMN IF NOT EXISTS → script idempotente.
-- ════════════════════════════════════════════════════════════════════

-- Asegura que el cliente interprete el archivo como UTF-8 (evita mojibake en
-- el lema "Año de…" al importar desde consola). Importar SIEMPRE con utf8mb4.
SET NAMES utf8mb4;

-- ─────────────────────────────────────────────────────────────────────
-- 1) ANIOS_ACADEMICOS — datos anuales del membrete / numeración
-- ─────────────────────────────────────────────────────────────────────
ALTER TABLE anios_academicos
    ADD COLUMN IF NOT EXISTS lema_oficial VARCHAR(255) NULL AFTER anio;

ALTER TABLE anios_academicos
    ADD COLUMN IF NOT EXISTS correlativo_traslado_inicial SMALLINT UNSIGNED NOT NULL DEFAULT 1
        AFTER lema_oficial;

-- Lema oficial 2026 (solo si aún no se definió).
UPDATE anios_academicos
SET lema_oficial = 'Año de la Esperanza y el Fortalecimiento de la Democracia'
WHERE anio = 2026
  AND (lema_oficial IS NULL OR lema_oficial = '');

-- ─────────────────────────────────────────────────────────────────────
-- 2) TRASLADOS — constancias de traslado de salida
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS traslados (
    id                         INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    matricula_id               INT UNSIGNED NOT NULL,
    anio_id                    SMALLINT UNSIGNED NOT NULL,
    correlativo                SMALLINT UNSIGNED NOT NULL,
    numero_constancia          VARCHAR(40) NOT NULL,

    -- Colegio destino
    ie_destino_nombre          VARCHAR(200) NOT NULL,
    ie_destino_codigo_modular  VARCHAR(30) NOT NULL,
    ie_destino_ugel            VARCHAR(150) NULL,
    ie_destino_ubicacion       VARCHAR(200) NULL,

    -- Datos del traslado
    fecha_constancia           DATE NOT NULL,
    periodo_id                 SMALLINT UNSIGNED NULL,
    motivo                     VARCHAR(40) NOT NULL,
    motivo_detalle             VARCHAR(300) NULL,

    -- Solicitante (apoderado)
    solicitante_nombre         VARCHAR(200) NULL,
    solicitante_dni            VARCHAR(8) NULL,
    solicitante_parentesco     VARCHAR(40) NULL,

    -- Situación / notas
    situacion_academica        VARCHAR(300) NULL,
    observaciones              VARCHAR(500) NULL,

    -- Estado del documento (anulado LIBERA el número)
    estado                     ENUM('vigente','anulado') NOT NULL DEFAULT 'vigente',
    anulado_motivo             VARCHAR(300) NULL,
    anulado_en                 DATETIME NULL,
    anulado_por                INT UNSIGNED NULL,

    -- Auditoría
    generada_por               INT UNSIGNED NOT NULL,
    generada_en                DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    veces_impresa              INT UNSIGNED NOT NULL DEFAULT 0,

    KEY idx_matricula    (matricula_id),
    KEY idx_anio_estado  (anio_id, estado),
    KEY idx_anio_corr    (anio_id, correlativo),
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
    FOREIGN KEY (anio_id)      REFERENCES anios_academicos(id),
    FOREIGN KEY (periodo_id)   REFERENCES periodos(id),
    FOREIGN KEY (generada_por) REFERENCES usuarios(id),
    FOREIGN KEY (anulado_por)  REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
