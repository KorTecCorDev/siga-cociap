-- ════════════════════════════════════════════════════════════════════
-- Migración 024: Rectificación de calificaciones (auditoría)
-- ════════════════════════════════════════════════════════════════════
-- Módulo GENERAL de rectificación: permite a Registro Académico corregir,
-- de forma auditada, una calificación que ya salió del flujo normal del
-- docente (competencia bloqueada y/o periodo cerrado). La rectificación
-- real se escribe sobre `calificaciones` / `calificaciones_criterio`
-- reutilizando la mecánica existente; esta tabla SOLO guarda la traza:
-- quién, cuándo, el antes/después y el porqué (motivo obligatorio).
--
-- Una fila por competencia rectificada (la unidad significativa de la
-- boleta y del orden de mérito). Es genérica: no está acoplada al caso
-- de retorno de grado, sirve a cualquier rectificación post-cierre.
--
-- MariaDB 10.4 → CREATE TABLE IF NOT EXISTS es idempotente.
-- ════════════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS rectificaciones_calificacion (
    id                   INT UNSIGNED      NOT NULL AUTO_INCREMENT,
    matricula_id         INT UNSIGNED      NOT NULL,
    carga_id             INT UNSIGNED      NOT NULL,
    periodo_id           SMALLINT UNSIGNED NOT NULL,
    competencia_id       SMALLINT UNSIGNED NOT NULL,
    nota_anterior        TINYINT UNSIGNED  NULL,
    nota_nueva           TINYINT UNSIGNED  NULL,
    conclusion_anterior  TEXT              NULL,
    conclusion_nueva     TEXT              NULL,
    motivo               TEXT              NOT NULL,
    rectificado_por      INT UNSIGNED      NOT NULL,
    rectificado_en       DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_matricula_periodo (matricula_id, periodo_id),
    KEY idx_competencia (competencia_id),
    KEY idx_rectificado_por (rectificado_por),
    CONSTRAINT fk_rect_matricula   FOREIGN KEY (matricula_id)    REFERENCES matriculas(id),
    CONSTRAINT fk_rect_carga       FOREIGN KEY (carga_id)        REFERENCES cargas_academicas(id),
    CONSTRAINT fk_rect_periodo     FOREIGN KEY (periodo_id)      REFERENCES periodos(id),
    CONSTRAINT fk_rect_competencia FOREIGN KEY (competencia_id)  REFERENCES competencias(id),
    CONSTRAINT fk_rect_usuario     FOREIGN KEY (rectificado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
