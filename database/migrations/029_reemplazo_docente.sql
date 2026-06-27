-- ════════════════════════════════════════════════════════════════════
-- Migración 029: Reemplazo de docente en carga activa (auditoría por snapshot)
-- ════════════════════════════════════════════════════════════════════
-- Proceso oficial para cambiar el docente de una carga que sigue ACTIVA, sin
-- perder la trazabilidad del trabajo del saliente. La carga no se desactiva ni
-- se duplica: solo cambia `docentes`; el entrante hereda y continúa en vivo.
--
-- El trabajo del saliente (criterios + notas por alumno + conclusiones +
-- bloqueos/aprobaciones) se CONGELA en un snapshot JSON al momento del
-- reemplazo. Se congelan TODOS los bimestres (no solo el activo): el sistema
-- permite reaperturas (migración 013), así que un bimestre cerrado podría
-- reabrirse y editarse DESPUÉS del reemplazo — el snapshot preserva la versión
-- del saliente aunque la tabla viva cambie luego.
--
-- Propósito: AUDITORÍA interna. NO altera boleta, orden de mérito ni cierre:
-- esos siguen leyendo las tablas vivas (que el entrante continúa). Es un archivo
-- lateral de solo lectura.
--
-- MariaDB 10.4: CREATE TABLE IF NOT EXISTS → idempotente. No toca tablas
-- existentes. Ejecutar DESPUÉS de 028_boleta_token_tracking.sql.
-- ════════════════════════════════════════════════════════════════════

-- Evento de reemplazo: un registro por cada cambio de docente en una carga.
-- Soporta varios eventos por carga/bimestre (cadena A→B→C): cada uno guarda su
-- propio saliente/entrante y su snapshot.
CREATE TABLE IF NOT EXISTS reemplazos_docente (
    id                   INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    carga_id             INT UNSIGNED NOT NULL,
    -- Bimestre activo al momento del reemplazo (contexto; NULL si no había uno).
    periodo_id           SMALLINT UNSIGNED NULL,
    docente_saliente_id  INT UNSIGNED NOT NULL,
    docente_entrante_id  INT UNSIGNED NOT NULL,
    motivo               TEXT NOT NULL,
    reasignado_por       INT UNSIGNED NOT NULL,
    reasignado_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_carga (carga_id, reasignado_en),
    CONSTRAINT fk_rd_carga     FOREIGN KEY (carga_id)            REFERENCES cargas_academicas(id),
    CONSTRAINT fk_rd_periodo   FOREIGN KEY (periodo_id)          REFERENCES periodos(id),
    CONSTRAINT fk_rd_saliente  FOREIGN KEY (docente_saliente_id) REFERENCES usuarios(id),
    CONSTRAINT fk_rd_entrante  FOREIGN KEY (docente_entrante_id) REFERENCES usuarios(id),
    CONSTRAINT fk_rd_reasig    FOREIGN KEY (reasignado_por)      REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foto solo-lectura del trabajo del saliente al momento del reemplazo. Una fila
-- por evento. `contenido` es JSON (LONGTEXT por portabilidad de import): incluye
-- criterios (con eliminados), notas por alumno, conclusiones y bloqueos de TODOS
-- los bimestres de la carga.
CREATE TABLE IF NOT EXISTS reemplazos_snapshot (
    id            INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    reemplazo_id  INT UNSIGNED NOT NULL,
    contenido     LONGTEXT NOT NULL,
    creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_reemplazo (reemplazo_id),
    CONSTRAINT fk_rs_reemplazo FOREIGN KEY (reemplazo_id) REFERENCES reemplazos_docente(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
