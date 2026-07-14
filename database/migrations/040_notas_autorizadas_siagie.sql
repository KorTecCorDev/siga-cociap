-- ════════════════════════════════════════════════════════════════════
-- Migración 040: Notas autorizadas por dirección para el SIAGIE
-- ════════════════════════════════════════════════════════════════════
-- OBJETIVO: registrar, como un "informe aparte" auditable, las notas que
--   dirección (director EBR) ordena consignar para un alumno que NO fue
--   evaluado por causa de fuerza mayor justificada (salud, accidente, viaje),
--   VÁLIDAS SOLO PARA EL SIAGIE.
--
--   El SIAGIE no distingue permisos ni faltas justificadas: exige la nota de
--   cada competencia evaluada para TODA la sección y no deja cerrar con celdas
--   vacías. En SIGA ese alumno queda correctamente como omisión justificada
--   (sin nota); esta tabla guarda la nota que dirección autoriza para llenar
--   SOLO la celda del SIAGIE.
--
-- ALCANCE (lo que esta nota NO hace):
--   - NO entra a `calificaciones` ni a `bloqueos_competencia`.
--   - NO aparece en la boleta de SIGA (la familia sigue viendo "ausencia
--     justificada").
--   - NO cuenta para el orden de mérito.
--   - Precedencia: si el alumno luego SÍ es evaluado, la nota real gana; esta
--     autorizada solo rellena la celda en blanco del export.
--
-- CANDADO DE ELEGIBILIDAD (aplicado en el código): solo se autoriza una
--   competencia donde el alumno tiene una omisión REGISTRADA (cualquier motivo,
--   basta que el docente la haya marcado en omisiones_criterio), la competencia
--   está bloqueada y el alumno no tiene calificación viva.
--
-- Idempotente: CREATE TABLE IF NOT EXISTS. Ejecutar DESPUÉS de 039.
-- Tipos de FK verificados contra el esquema real: matriculas.id / usuarios.id
--   son INT UNSIGNED; competencias.id / periodos.id son SMALLINT UNSIGNED.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS notas_autorizadas_siagie (
    id                     INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    matricula_id           INT UNSIGNED NOT NULL,
    competencia_id         SMALLINT UNSIGNED NOT NULL,
    periodo_id             SMALLINT UNSIGNED NOT NULL,
    nota_literal           ENUM('AD','A','B','C') NOT NULL,
    conclusion_descriptiva TEXT NULL,
    resolucion             TEXT NOT NULL,                 -- autorización de dirección (documento/motivo)
    registrado_por         INT UNSIGNED NOT NULL,
    registrado_en          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nota_autorizada (matricula_id, competencia_id, periodo_id),
    KEY idx_matricula (matricula_id),
    KEY idx_periodo (periodo_id),
    FOREIGN KEY (matricula_id)   REFERENCES matriculas(id),
    FOREIGN KEY (competencia_id) REFERENCES competencias(id),
    FOREIGN KEY (periodo_id)     REFERENCES periodos(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
