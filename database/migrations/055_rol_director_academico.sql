-- 055_rol_director_academico.sql
-- Tercer rol de direccion: Director academico.
--
-- CONTEXTO (24/08/2026). El colegio tiene TRES cargos de direccion: Director
-- General, Director EBR y Director academico. Los dos primeros ya existian como
-- roles; el tercero no. Decision del usuario: los tres tienen EXACTAMENTE las
-- mismas atribuciones -- supervision en SOLO LECTURA sobre los dos niveles --,
-- sin alcance por nivel (no existe mapeo usuario->nivel y se decidio que no lo
-- habra).
--
-- UNA SOLA DIFERENCIA entre ellos, y es la razon por la que NO se unifican en un
-- unico rol: solo el Director EBR puede ser asignado como FIRMANTE de boletas,
-- actas de desempate y reporte de orden de merito. Ese anclaje vive en dos
-- sitios de codigo (DirectorEbrModel::listarCandidatos y
-- Admin\DirectorEbrController::asignar), ambos con 'director_ebr' en singular a
-- proposito.
--
-- El control de acceso de "los directores" NO se lista a mano: sale de la
-- constante ROLES_DIRECCION en app/Helpers/helpers.php.
--
-- No toca esquema: es un INSERT de catalogo. Los permisos del rol se conceden
-- desde el codigo, no desde la base -- este INSERT por si solo no da acceso a
-- nada nuevo mientras ningun usuario tenga el rol.
--
-- Idempotente: `roles.codigo` tiene UNIQUE KEY, asi que un INSERT plano
-- reventaria al re-ejecutar; el NOT EXISTS lo hace re-ejecutable.
-- Ejecutar despues de 054_revertir_anulacion_constancia_traslado.sql.
--
-- IMPORTANTE: el nombre lleva tilde (academico). Ejecutar con conexion utf8mb4
-- (phpMyAdmin ya lo hace; por CLI usar `mysql --default-character-set=utf8mb4`).

SET NAMES utf8mb4;

INSERT INTO roles (nombre, codigo, descripcion)
SELECT 'Director académico', 'director_academico', 'Supervisión académica en solo lectura'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE codigo = 'director_academico');

-- Verificacion (debe devolver 9 roles y 1 director_academico):
--   SELECT COUNT(*) AS roles_espera_9,
--          SUM(codigo = 'director_academico') AS espera_1
--   FROM roles;
