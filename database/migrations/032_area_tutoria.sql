-- 032_area_tutoria.sql
-- Tutoria (TOE) como espacio con HORARIO pero SIN calificaciones.
--
-- Modelo (Opcion A, future-proof): un area de tipo 'tutoria' SIN competencias.
--   * Aparece en el horario del docente porque la carga tiene bloques
--     (getHorario no filtra por tipo) y participa del control de solapes.
--   * Es invisible a notas/boleta/merito porque el area NO tiene competencias
--     (toda la tuberia de notas se engancha a competencias, no al tipo).
--   * A FUTURO, para habilitar calificaciones: basta con INSERTAR competencias
--     en esta area; el registro de notas y la boleta la recogen solos por la
--     tuberia existente. El orden de merito la excluye por tipo (permanente,
--     como transversal) para que aun con notas no pese en el ranking.
--
-- Idempotente. Ejecutar despues de 031_reparar_sesiones_cruzadas.sql.
-- IMPORTANTE: el nombre lleva tilde (Tutoría). Ejecutar con conexion utf8mb4
-- (phpMyAdmin ya lo hace; por CLI usar `mysql --default-character-set=utf8mb4`).

SET NAMES utf8mb4;

-- 1. Ampliar el enum de tipos de area con 'tutoria'.
ALTER TABLE areas
    MODIFY tipo ENUM('area_curso','con_subareas','transversal','tutoria') NOT NULL;

-- 2. Crear el area "Tutoría (TOE)" por nivel (no hay UNIQUE por (nivel_id,tipo),
--    asi que se usa NOT EXISTS para no duplicar al re-ejecutar).
INSERT INTO areas (nivel_id, nombre, nombre_boleta, nombre_siagie, tipo, orden, activa)
SELECT 1, 'Tutoría (TOE)', 'Tutoría (TOE)', 'Tutoría (TOE)', 'tutoria', 90, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM areas WHERE tipo = 'tutoria' AND nivel_id = 1);

INSERT INTO areas (nivel_id, nombre, nombre_boleta, nombre_siagie, tipo, orden, activa)
SELECT 2, 'Tutoría (TOE)', 'Tutoría (TOE)', 'Tutoría (TOE)', 'tutoria', 90, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM areas WHERE tipo = 'tutoria' AND nivel_id = 2);
