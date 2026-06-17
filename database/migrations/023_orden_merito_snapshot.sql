-- ════════════════════════════════════════════════════════════════════
-- Migración 023: Snapshot del orden de mérito (documento oficial inmutable)
-- ════════════════════════════════════════════════════════════════════
-- El orden de mérito era 100% dinámico (se recalculaba en vivo desde
-- calificaciones + estado actual de matrículas). Eso permitía que una
-- reversión de retorno de grado, un traslado o una edición posterior
-- alteraran retroactivamente el ranking de un bimestre YA cerrado.
--
-- Esta tabla CONGELA el ranking oficial en el momento del cierre del bimestre
-- (PeriodoController::cerrar → OrdenMeritoModel::generarSnapshot). Guarda los
-- DOS puestos que usan los documentos: por GRADO (vista de periodo, buscador,
-- nómina) y por SECCIÓN (reporte A4 con firmas). Se guardan grado_id/seccion_id
-- explícitos para congelar el anclaje del alumno (p. ej. la sección OPERATIVA
-- de un retorno queda grabada aunque luego se revierta).
--
-- Reglas:
--  - Solo se (re)genera al CERRAR (reabrir→re-cerrar regenera con notas corregidas).
--  - Una reversión con el bimestre cerrado NUNCA lo toca.
--  - Lectura: periodo 'cerrado' con filas aquí → ranking congelado; si no, en vivo.
--
-- MariaDB 10.4: CREATE TABLE IF NOT EXISTS → idempotente.
-- Ejecutar DESPUÉS de 022_retorno_reversion.sql. Tras correr esta migración,
-- ejecutar el backfill de bimestres ya cerrados: database/backfill_orden_merito.php
-- ════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS orden_merito_snapshot (
    id               INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    periodo_id       SMALLINT UNSIGNED NOT NULL,
    matricula_id     INT UNSIGNED NOT NULL,
    -- Anclaje congelado del alumno en ESE bimestre (no cambia ante reversión).
    grado_id         INT UNSIGNED NOT NULL,
    seccion_id       INT UNSIGNED NULL,
    -- Puestos oficiales (sin empates pendientes: se resuelven antes de cerrar).
    puesto_grado     SMALLINT UNSIGNED NOT NULL,
    puesto_seccion   SMALLINT UNSIGNED NULL,
    -- Métricas congeladas para reproducir el documento idéntico.
    num_competencias SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    total_notas      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    promedio_general DECIMAL(5,2)  NULL,
    promedio_exacto  DECIMAL(12,8) NULL,
    num_c            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    num_b            SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    num_ad           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    num_alto         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    num_16           SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    generado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    generado_por     INT UNSIGNED NULL,
    UNIQUE KEY uq_periodo_matricula (periodo_id, matricula_id),
    INDEX idx_periodo_grado   (periodo_id, grado_id, puesto_grado),
    INDEX idx_periodo_seccion (periodo_id, seccion_id, puesto_seccion),
    CONSTRAINT fk_oms_periodo   FOREIGN KEY (periodo_id)   REFERENCES periodos(id),
    CONSTRAINT fk_oms_matricula FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
    CONSTRAINT fk_oms_generado  FOREIGN KEY (generado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
