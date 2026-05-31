-- 013_reaperturas_periodo.sql
-- Auditoria de reaperturas de bimestre.
--
-- Reabrir un bimestre cerrado es una accion excepcional: vuelve a permitir el
-- ingreso de notas y libera los bloqueos sin notas (auto-generados por el cierre
-- forzado). Para que quede traza de POR QUE se reabrio, cada reapertura exige un
-- motivo y se registra aqui junto a quien la hizo y cuantos bloqueos se liberaron.
--
-- Idempotente: CREATE TABLE IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS reaperturas_periodo (
    id                 INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    periodo_id         SMALLINT UNSIGNED NOT NULL,
    motivo             VARCHAR(500) NOT NULL,
    bloqueos_liberados INT UNSIGNED NOT NULL DEFAULT 0,
    reabierto_por      INT UNSIGNED NOT NULL,
    reabierto_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_periodo (periodo_id),
    KEY idx_reabierto_por (reabierto_por),
    CONSTRAINT fk_reapertura_periodo  FOREIGN KEY (periodo_id)    REFERENCES periodos(id),
    CONSTRAINT fk_reapertura_usuario  FOREIGN KEY (reabierto_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
