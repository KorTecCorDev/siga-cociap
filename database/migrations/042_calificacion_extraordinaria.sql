-- ════════════════════════════════════════════════════════════════════
-- Migración 042: Calificación extraordinaria (RA) — flags e identificación
-- ════════════════════════════════════════════════════════════════════
-- OBJETIVO: permitir que Registro Académico registre una calificación a un
--   alumno SIN nota en una competencia que ya salió del flujo del docente
--   (bimestre cerrado y/o competencia bloqueada), vía el módulo de
--   Rectificación. Casos: alumno con omisiones en todos los criterios
--   (declarado en blanco con motivo) o competencia entera "No se evaluó".
--
--   Reglas cerradas con el usuario (16/07/2026):
--   - La nota extraordinaria ES REAL: aparece en boleta (digital/impresa)
--     y se exporta al SIAGIE, pero NO cuenta en el orden de mérito.
--   - Mecánica: criterio único "Calificación extraordinaria" (nace
--     confirmado, atribuido a RA); su nota es el promedio final del alumno.
--     Respeta el blindaje anti-fantasma (033): toda nota de boleta conserva
--     un criterio vivo y confirmado detrás.
--
-- COLUMNAS:
--   - criterios.extraordinario: identifica el criterio especial. El docente
--     NO puede editarlo/eliminarlo/confirmarlo (guardas en controlador);
--     solo el módulo de Rectificación escribe en él.
--   - calificaciones.extraordinaria: marca el promedio insertado por RA.
--     OrdenMeritoModel filtra `AND cal.extraordinaria = 0` en sus
--     agregaciones → la nota no mueve puestos ni entra al snapshot.
--   - rectificaciones_calificacion.tipo: distingue en la auditoría una
--     corrección normal ('rectificacion') de un alta ('extraordinaria').
--     El motivo por alumno vive aquí (columna motivo, ya existente).
--
-- Idempotente (ADD COLUMN IF NOT EXISTS / MODIFY estable). Sin backfill:
-- todo lo existente es NO extraordinario (DEFAULT 0 / 'rectificacion').
-- Ejecutar DESPUÉS de 041. Conexión utf8mb4.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

ALTER TABLE criterios
    ADD COLUMN IF NOT EXISTS extraordinario TINYINT(1) NOT NULL DEFAULT 0 AFTER confirmado_por;

ALTER TABLE calificaciones
    ADD COLUMN IF NOT EXISTS extraordinaria TINYINT(1) NOT NULL DEFAULT 0 AFTER conclusion_descriptiva;

ALTER TABLE rectificaciones_calificacion
    ADD COLUMN IF NOT EXISTS tipo ENUM('rectificacion','extraordinaria') NOT NULL DEFAULT 'rectificacion' AFTER competencia_id;

-- Verificación:
--   SHOW COLUMNS FROM criterios LIKE 'extraordinario';
--   SHOW COLUMNS FROM calificaciones LIKE 'extraordinaria';
--   SHOW COLUMNS FROM rectificaciones_calificacion LIKE 'tipo';
