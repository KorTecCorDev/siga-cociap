-- ════════════════════════════════════════════════════════════════════
-- Migración 012: Módulo de matrículas
-- ════════════════════════════════════════════════════════════════════
-- Cubre: ajuste de roles (secretarías), nuevas columnas de matriculas,
-- ampliación de tipos de vínculo familiar, bandera de boletas públicas y
-- las tablas documentos_matricula, notas_externas y retornos_grado.
--
-- NOTAS DE COMPATIBILIDAD CON EL ESQUEMA REAL (verificado contra la BD):
--  * matriculas.anio_id YA EXISTE → no se recrea.
--  * matriculas.estado real es enum('registrada','pendiente_documentos',
--    'observada','aprobada','retirada'). Se AMPLÍA agregando
--    'pendiente','activo','desactivado' que usa este módulo, sin tocar los
--    registros existentes (la demo del I Bimestre quedó en 'aprobada').
--  * vinculo_familiar.tipo_vinculo real es enum('padre','madre','apoderado').
--    Se AMPLÍA a los 14 tipos del spec (sin tildes en BD; la vista las muestra).
--  * boletas_publicas no tenía columna 'activa' → se agrega.
--
-- El nombre del archivo en el spec era 009, pero 009 ya está ocupado dos veces
-- (009_inasistencias, 009_token_acceso_matriculas). Se usa el siguiente libre.
--
-- MariaDB 10.4 soporta ADD/DROP COLUMN ... IF NOT EXISTS, por eso este script
-- es idempotente y puede re-ejecutarse sin error.
-- ════════════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────────────────
-- 1.1  ROLES — renombrar 'secretaria' y crear 'secretaria_administrativa'
-- ─────────────────────────────────────────────────────────────────────
UPDATE roles
SET codigo      = 'secretaria_academica',
    nombre      = 'Secretaria Académica',
    descripcion = 'Registro de matrículas, documentos y atención académica'
WHERE codigo = 'secretaria';

INSERT INTO roles (nombre, codigo, descripcion)
SELECT 'Secretaria Administrativa',
       'secretaria_administrativa',
       'Gestión de pagos, beneficios y trámites administrativos'
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE codigo = 'secretaria_administrativa'
);

-- ─────────────────────────────────────────────────────────────────────
-- 1.2  MATRICULAS — nuevas columnas
-- ─────────────────────────────────────────────────────────────────────
ALTER TABLE matriculas
    ADD COLUMN IF NOT EXISTS tipo ENUM('continuador','nuevo','trasladado')
        NOT NULL DEFAULT 'continuador' AFTER tipo_matricula;

ALTER TABLE matriculas
    ADD COLUMN IF NOT EXISTS serie_recibo VARCHAR(30) NULL AFTER tipo;

-- anio_id ya existe en el esquema real; se incluye condicionalmente por si
-- se ejecuta sobre una BD más antigua que no lo tuviera.
ALTER TABLE matriculas
    ADD COLUMN IF NOT EXISTS anio_id SMALLINT UNSIGNED NULL AFTER seccion_id;

-- Ampliar el enum de estado conservando los valores existentes.
ALTER TABLE matriculas
    MODIFY COLUMN estado ENUM(
        'registrada','pendiente_documentos','observada','aprobada','retirada',
        'pendiente','activo','desactivado'
    ) NOT NULL DEFAULT 'registrada';

-- ─────────────────────────────────────────────────────────────────────
-- 1.x  VINCULO_FAMILIAR — ampliar tipos de vínculo (sin tildes en BD)
-- ─────────────────────────────────────────────────────────────────────
ALTER TABLE vinculo_familiar
    MODIFY COLUMN tipo_vinculo ENUM(
        'padre','madre','apoderado','apoderada',
        'abuelo','abuela','tio','tia',
        'padrino','madrina','hermano','hermana',
        'primo','prima'
    ) NOT NULL;

-- ─────────────────────────────────────────────────────────────────────
-- 7.3  BOLETAS_PUBLICAS — bandera de activación (para desactivar al retirar)
-- ─────────────────────────────────────────────────────────────────────
ALTER TABLE boletas_publicas
    ADD COLUMN IF NOT EXISTS activa TINYINT(1) NOT NULL DEFAULT 1 AFTER generada_por;

-- ─────────────────────────────────────────────────────────────────────
-- 1.3  DOCUMENTOS_MATRICULA
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS documentos_matricula (
    id             INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    matricula_id   INT UNSIGNED NOT NULL,
    tipo_documento ENUM(
        'recibo_pago',
        'certificado_estudios',
        'boleta_siagie',
        'ficha_matricula_siagie',
        'dni_estudiante',
        'dni_padre',
        'dni_madre',
        'dni_apoderado'
    ) NOT NULL,
    entregado      TINYINT(1) NOT NULL DEFAULT 0,
    observacion    VARCHAR(300) NULL,
    registrado_por INT UNSIGNED NOT NULL,
    registrado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_matricula_documento (matricula_id, tipo_documento),
    KEY idx_matricula (matricula_id),
    FOREIGN KEY (matricula_id)   REFERENCES matriculas(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- 1.4  NOTAS_EXTERNAS (traslados de entrada — promedios del colegio origen)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notas_externas (
    id                 INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    matricula_id       INT UNSIGNED NOT NULL,
    periodo_nombre     VARCHAR(30) NOT NULL,
    competencia_nombre VARCHAR(120) NOT NULL,
    area_nombre        VARCHAR(120) NOT NULL,
    nota_literal       ENUM('AD','A','B','C') NOT NULL,
    colegio_origen     VARCHAR(200) NULL,
    registrado_por     INT UNSIGNED NOT NULL,
    registrado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nota_externa (matricula_id, periodo_nombre, competencia_nombre),
    KEY idx_matricula (matricula_id),
    FOREIGN KEY (matricula_id)   REFERENCES matriculas(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- 1.5a  Relajar UNIQUE(estudiante_id, anio_id) → permitir RETORNO de grado
-- ─────────────────────────────────────────────────────────────────────
-- El retorno de grado requiere DOS matrículas del mismo estudiante en el
-- mismo año (oficial SIAGIE + operativa en grado inferior). Eso choca con el
-- UNIQUE uq_estudiante_anio. Se reemplaza por un índice NO único; el flujo
-- normal sigue protegido contra duplicados a nivel de aplicación
-- (MatriculaModel::existeMatricula() + validación en el controlador).
-- Idempotente: solo actúa si el UNIQUE todavía existe.
SET @hay_uq = (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'matriculas'
      AND INDEX_NAME   = 'uq_estudiante_anio'
      AND NON_UNIQUE   = 0
);
SET @sql = IF(@hay_uq > 0,
    'ALTER TABLE matriculas DROP INDEX uq_estudiante_anio, ADD INDEX idx_estudiante_anio (estudiante_id, anio_id)',
    'SELECT ''uq_estudiante_anio ya relajado, omitiendo'' AS info'
);
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────────────────────────────
-- 1.5  RETORNOS_GRADO (estudiante asiste a grado inferior al oficial SIAGIE)
-- ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS retornos_grado (
    id                     INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    matricula_oficial_id   INT UNSIGNED NOT NULL UNIQUE,
    matricula_operativa_id INT UNSIGNED NOT NULL UNIQUE,
    motivo                 TEXT NOT NULL,
    autorizado_por         INT UNSIGNED NOT NULL,
    fecha_retorno          DATE NOT NULL,
    estado                 ENUM('activo','revertido') NOT NULL DEFAULT 'activo',
    created_at             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (matricula_oficial_id)   REFERENCES matriculas(id),
    FOREIGN KEY (matricula_operativa_id) REFERENCES matriculas(id),
    FOREIGN KEY (autorizado_por)         REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
