-- ============================================================
-- SIGA-COCIAP — Schema completo de base de datos
-- Colegio de Aplicación "Víctor Valenzuela Guardia"
-- Huaraz, Ancash, Perú — 2026
-- Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;
SET time_zone = '-05:00'; -- UTC-5 Lima

-- ─── LIMPIEZA (desarrollo) ──────────────────────────────────
DROP TABLE IF EXISTS calificaciones;
DROP TABLE IF EXISTS sesiones_horario;
DROP TABLE IF EXISTS cargas_academicas;
DROP TABLE IF EXISTS bloques_horario;
DROP TABLE IF EXISTS configuracion_horario;
DROP TABLE IF EXISTS reglas_especiales;
DROP TABLE IF EXISTS alertas;
DROP TABLE IF EXISTS matriculas;
DROP TABLE IF EXISTS vinculo_familiar;
DROP TABLE IF EXISTS apoderados;
DROP TABLE IF EXISTS estudiantes;
DROP TABLE IF EXISTS secciones;
DROP TABLE IF EXISTS periodos;
DROP TABLE IF EXISTS anios_academicos;
DROP TABLE IF EXISTS competencias;
DROP TABLE IF EXISTS subareas;
DROP TABLE IF EXISTS areas;
DROP TABLE IF EXISTS grados;
DROP TABLE IF EXISTS niveles;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS personas;

-- ════════════════════════════════════════════════════════════
-- 1. ROLES
-- ════════════════════════════════════════════════════════════
CREATE TABLE roles (
    id          TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombre      VARCHAR(60)  NOT NULL,
    codigo      VARCHAR(30)  NOT NULL UNIQUE,  -- clave para lógica de negocio
    descripcion VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 2. PERSONAS (base de todos los usuarios humanos)
-- ════════════════════════════════════════════════════════════
CREATE TABLE personas (
    id                INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    dni               VARCHAR(8)   NOT NULL UNIQUE,
    apellido_paterno  VARCHAR(60)  NOT NULL,
    apellido_materno  VARCHAR(60)  NOT NULL,
    nombres           VARCHAR(100) NOT NULL,
    fecha_nacimiento  DATE,
    sexo              ENUM('M','F') DEFAULT NULL,
    telefono          VARCHAR(15),
    correo            VARCHAR(120),
    direccion         VARCHAR(255),
    created_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dni (dni),
    INDEX idx_apellidos (apellido_paterno, apellido_materno)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 3. USUARIOS (credenciales + rol)
-- ════════════════════════════════════════════════════════════
CREATE TABLE usuarios (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    persona_id      INT UNSIGNED NOT NULL UNIQUE,
    rol_id          TINYINT UNSIGNED NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    ultimo_acceso   DATETIME,
    sesion_token    VARCHAR(64),     -- token de sesión activa (control de sesión única)
    estado          ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (persona_id) REFERENCES personas(id),
    FOREIGN KEY (rol_id)     REFERENCES roles(id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 4. NIVELES EDUCATIVOS
-- ════════════════════════════════════════════════════════════
CREATE TABLE niveles (
    id              TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nombre          VARCHAR(30)  NOT NULL,  -- 'Primaria', 'Secundaria'
    codigo          VARCHAR(10)  NOT NULL UNIQUE,  -- 'prim', 'sec'
    escala_boleta   ENUM('solo_literal','ambas') NOT NULL,
    -- 'solo_literal' para primaria, 'ambas' para secundaria
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 5. GRADOS
-- ════════════════════════════════════════════════════════════
CREATE TABLE grados (
    id              TINYINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nivel_id        TINYINT UNSIGNED NOT NULL,
    numero          TINYINT UNSIGNED NOT NULL,  -- 1, 2, 3, 4, 5, 6
    nombre_display  VARCHAR(30)  NOT NULL,       -- '1° Primaria', '3° Secundaria'
    UNIQUE KEY uq_nivel_numero (nivel_id, numero),
    FOREIGN KEY (nivel_id) REFERENCES niveles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 6. ÁREAS CURRICULARES
-- tipo: 'area_curso' (un docente, sin subáreas)
--       'con_subareas' (múltiples docentes, una por subárea)
--       'transversal' (a cargo del tutor)
-- ════════════════════════════════════════════════════════════
CREATE TABLE areas (
    id              SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    nivel_id        TINYINT UNSIGNED NOT NULL,
    nombre          VARCHAR(120) NOT NULL,   -- nombre oficial MINEDU/SIAGIE
    nombre_boleta   VARCHAR(120),            -- nombre que aparece en la boleta
    alias_boleta    VARCHAR(80),             -- ej: '(Ética y valores)'
    nombre_siagie   VARCHAR(120),            -- nombre en el sistema SIAGIE
    tipo            ENUM('area_curso','con_subareas','transversal') NOT NULL,
    orden           TINYINT UNSIGNED NOT NULL DEFAULT 0,
    activa          BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (nivel_id) REFERENCES niveles(id),
    INDEX idx_nivel_tipo (nivel_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 7. SUBÁREAS (solo para areas de tipo 'con_subareas')
-- ════════════════════════════════════════════════════════════
CREATE TABLE subareas (
    id              SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    area_id         SMALLINT UNSIGNED NOT NULL,
    nombre          VARCHAR(80) NOT NULL,
    orden           TINYINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (area_id) REFERENCES areas(id),
    INDEX idx_area (area_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 8. COMPETENCIAS MINEDU
-- Vinculadas a subarea_id (para áreas con subáreas)
-- o a area_id (para áreas-curso y transversales)
-- RESTRICCIÓN: solo uno de los dos puede ser NOT NULL
-- ════════════════════════════════════════════════════════════
CREATE TABLE competencias (
    id              SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    codigo_minedu   VARCHAR(5),           -- C1, C2, C23, etc.
    nombre_completo TEXT NOT NULL,
    nombre_corto    VARCHAR(120),         -- versión resumida para la UI
    subarea_id      SMALLINT UNSIGNED NULL,
    area_id         SMALLINT UNSIGNED NULL,
    orden           TINYINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (subarea_id) REFERENCES subareas(id),
    FOREIGN KEY (area_id)    REFERENCES areas(id),
    CONSTRAINT chk_competencia_vinculo
        CHECK (
            (subarea_id IS NOT NULL AND area_id IS NULL) OR
            (subarea_id IS NULL AND area_id IS NOT NULL)
        ),
    INDEX idx_subarea (subarea_id),
    INDEX idx_area    (area_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 9. REGLAS ESPECIALES (alias y vínculos SIAGIE por grado)
-- ════════════════════════════════════════════════════════════
CREATE TABLE reglas_especiales (
    id                  SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    area_id             SMALLINT UNSIGNED NOT NULL,  -- área afectada en nuestro sistema
    nivel_id            TINYINT UNSIGNED NOT NULL,
    grado_desde         TINYINT UNSIGNED NOT NULL,
    grado_hasta         TINYINT UNSIGNED NOT NULL,
    nombre_override     VARCHAR(120),  -- reemplaza el nombre del área en la boleta
    alias_override      VARCHAR(80),   -- reemplaza el alias en la boleta
    area_siagie_id      SMALLINT UNSIGNED NULL,  -- área destino en SIAGIE (si difiere)
    descripcion         VARCHAR(255),  -- documentación de la regla
    FOREIGN KEY (area_id)        REFERENCES areas(id),
    FOREIGN KEY (nivel_id)       REFERENCES niveles(id),
    FOREIGN KEY (area_siagie_id) REFERENCES areas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 10. AÑOS ACADÉMICOS
-- ════════════════════════════════════════════════════════════
CREATE TABLE anios_academicos (
    id              SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    anio            YEAR NOT NULL UNIQUE,
    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NOT NULL,
    estado          ENUM('planificado','activo','cerrado') NOT NULL DEFAULT 'planificado',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 11. PERÍODOS (bimestres o trimestres)
-- ════════════════════════════════════════════════════════════
CREATE TABLE periodos (
    id              SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    anio_id         SMALLINT UNSIGNED NOT NULL,
    numero          TINYINT UNSIGNED NOT NULL,  -- 1, 2, 3, 4
    tipo            ENUM('bimestre','trimestre') NOT NULL DEFAULT 'bimestre',
    nombre_display  VARCHAR(30),              -- 'I Bimestre', 'II Bimestre'
    fecha_inicio    DATE NOT NULL,
    fecha_fin       DATE NOT NULL,
    limite_notas    DATETIME NULL,            -- fecha límite para subir notas (la fija el director)
    estado          ENUM('pendiente','activo','cerrado') NOT NULL DEFAULT 'pendiente',
    UNIQUE KEY uq_anio_numero (anio_id, numero),
    FOREIGN KEY (anio_id) REFERENCES anios_academicos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 12. SECCIONES
-- ════════════════════════════════════════════════════════════
CREATE TABLE secciones (
    id              SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    grado_id        TINYINT UNSIGNED NOT NULL,
    anio_id         SMALLINT UNSIGNED NOT NULL,
    nombre          VARCHAR(5) NOT NULL,          -- 'A', 'B', 'C'
    tutor_id        INT UNSIGNED NULL,             -- FK a usuarios (docente tutor)
    es_unidocente   BOOLEAN NOT NULL DEFAULT FALSE,
    estado_nomina   ENUM('borrador','aprobada') NOT NULL DEFAULT 'borrador',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_grado_anio_seccion (grado_id, anio_id, nombre),
    FOREIGN KEY (grado_id)  REFERENCES grados(id),
    FOREIGN KEY (anio_id)   REFERENCES anios_academicos(id),
    FOREIGN KEY (tutor_id)  REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 13. CONFIGURACIÓN DE HORARIO (por año académico)
-- ════════════════════════════════════════════════════════════
CREATE TABLE configuracion_horario (
    id                      SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    anio_id                 SMALLINT UNSIGNED NOT NULL UNIQUE,
    duracion_hora_min       TINYINT UNSIGNED NOT NULL DEFAULT 50,
    hora_inicio_clases      TIME NOT NULL DEFAULT '07:45:00',
    recreo_bloques          JSON,   -- ej: [{"despues_de": 3, "duracion": 15}]
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (anio_id) REFERENCES anios_academicos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 14. BLOQUES DE HORARIO
-- ════════════════════════════════════════════════════════════
CREATE TABLE bloques_horario (
    id              SMALLINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    config_id       SMALLINT UNSIGNED NOT NULL,
    dia_semana      ENUM('lunes','martes','miercoles','jueves','viernes') NOT NULL,
    numero_bloque   TINYINT UNSIGNED NOT NULL,
    hora_inicio     TIME NOT NULL,
    hora_fin        TIME NOT NULL,
    UNIQUE KEY uq_config_dia_bloque (config_id, dia_semana, numero_bloque),
    FOREIGN KEY (config_id) REFERENCES configuracion_horario(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 15. CARGAS ACADÉMICAS
-- Vincula docente + (subarea O area_curso) + seccion + año
-- ════════════════════════════════════════════════════════════
CREATE TABLE cargas_academicas (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    docente_id      INT UNSIGNED NOT NULL,        -- FK a usuarios
    seccion_id      SMALLINT UNSIGNED NOT NULL,
    anio_id         SMALLINT UNSIGNED NOT NULL,
    subarea_id      SMALLINT UNSIGNED NULL,       -- para áreas con subáreas
    area_id         SMALLINT UNSIGNED NULL,       -- para áreas-curso y transversales
    horas_semanales TINYINT UNSIGNED NOT NULL DEFAULT 0,
    estado          ENUM('activa','inactiva') NOT NULL DEFAULT 'activa',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_carga_vinculo
        CHECK (
            (subarea_id IS NOT NULL AND area_id IS NULL) OR
            (subarea_id IS NULL AND area_id IS NOT NULL)
        ),
    FOREIGN KEY (docente_id)  REFERENCES usuarios(id),
    FOREIGN KEY (seccion_id)  REFERENCES secciones(id),
    FOREIGN KEY (anio_id)     REFERENCES anios_academicos(id),
    FOREIGN KEY (subarea_id)  REFERENCES subareas(id),
    FOREIGN KEY (area_id)     REFERENCES areas(id),
    INDEX idx_docente_anio (docente_id, anio_id),
    INDEX idx_seccion_anio (seccion_id, anio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 16. SESIONES DE HORARIO
-- Reserva de bloques por sección (doble UNIQUE)
-- ════════════════════════════════════════════════════════════
CREATE TABLE sesiones_horario (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    carga_id        INT UNSIGNED NOT NULL,
    bloque_id       SMALLINT UNSIGNED NOT NULL,
    seccion_id      SMALLINT UNSIGNED NOT NULL,   -- desnormalizado para el UNIQUE
    docente_id      INT UNSIGNED NOT NULL,         -- desnormalizado para el UNIQUE
    -- Nadie puede tener dos clases en el mismo bloque
    UNIQUE KEY uq_seccion_bloque  (seccion_id, bloque_id),
    UNIQUE KEY uq_docente_bloque  (docente_id, bloque_id),
    FOREIGN KEY (carga_id)   REFERENCES cargas_academicas(id),
    FOREIGN KEY (bloque_id)  REFERENCES bloques_horario(id),
    FOREIGN KEY (seccion_id) REFERENCES secciones(id),
    FOREIGN KEY (docente_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 17. ESTUDIANTES
-- ════════════════════════════════════════════════════════════
CREATE TABLE estudiantes (
    id                  INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    persona_id          INT UNSIGNED NOT NULL UNIQUE,
    codigo_estudiante   VARCHAR(20),
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (persona_id) REFERENCES personas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 18. APODERADOS (padre, madre u otro apoderado)
-- ════════════════════════════════════════════════════════════
CREATE TABLE apoderados (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    persona_id      INT UNSIGNED NOT NULL UNIQUE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (persona_id) REFERENCES personas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 19. VÍNCULO FAMILIAR (estudiante ↔ apoderado)
-- tipo: 'padre', 'madre', 'apoderado'
-- es_responsable: quien firma el contrato de matrícula
-- ════════════════════════════════════════════════════════════
CREATE TABLE vinculo_familiar (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    estudiante_id   INT UNSIGNED NOT NULL,
    apoderado_id    INT UNSIGNED NOT NULL,
    tipo_vinculo    ENUM('padre','madre','apoderado') NOT NULL,
    es_responsable  BOOLEAN NOT NULL DEFAULT FALSE,
    UNIQUE KEY uq_estudiante_tipo (estudiante_id, tipo_vinculo),
    FOREIGN KEY (estudiante_id) REFERENCES estudiantes(id),
    FOREIGN KEY (apoderado_id)  REFERENCES apoderados(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 20. MATRÍCULAS
-- ════════════════════════════════════════════════════════════
CREATE TABLE matriculas (
    id                      INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    estudiante_id           INT UNSIGNED NOT NULL,
    seccion_id              SMALLINT UNSIGNED NULL,    -- NULL hasta que el director apruebe
    anio_id                 SMALLINT UNSIGNED NOT NULL,
    tipo_matricula          ENUM('regular','traslado_entrada') NOT NULL DEFAULT 'regular',
    estado                  ENUM(
                                'registrada',
                                'pendiente_documentos',
                                'observada',
                                'aprobada',
                                'retirada'
                            ) NOT NULL DEFAULT 'registrada',
    seccion_solicitada      VARCHAR(5),                -- sección pedida por el padre
    fecha_registro          DATE NOT NULL,
    limite_documentos       DATE NULL,                 -- para estado 'pendiente_documentos'
    fecha_aprobacion        DATE NULL,
    registrado_por          INT UNSIGNED NOT NULL,     -- usuario que registró
    aprobado_por            INT UNSIGNED NULL,         -- director que aprobó
    observaciones           TEXT NULL,
    created_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_estudiante_anio (estudiante_id, anio_id),
    FOREIGN KEY (estudiante_id)  REFERENCES estudiantes(id),
    FOREIGN KEY (seccion_id)     REFERENCES secciones(id),
    FOREIGN KEY (anio_id)        REFERENCES anios_academicos(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id),
    FOREIGN KEY (aprobado_por)   REFERENCES usuarios(id),
    INDEX idx_estado (estado),
    INDEX idx_anio   (anio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 21. CALIFICACIONES
-- Nota siempre numérica (00-20). Literal calculada al mostrar.
-- conclusión: obligatoria según nivel y literal.
-- ════════════════════════════════════════════════════════════
CREATE TABLE calificaciones (
    id                      INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    matricula_id            INT UNSIGNED NOT NULL,
    carga_id                INT UNSIGNED NOT NULL,
    periodo_id              SMALLINT UNSIGNED NOT NULL,
    competencia_id          SMALLINT UNSIGNED NOT NULL,
    nota_numerica           TINYINT UNSIGNED NOT NULL,  -- 0 a 20
    conclusion_descriptiva  TEXT NULL,
    registrado_en           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modificado_en           DATETIME NULL,
    registrado_por          INT UNSIGNED NOT NULL,      -- usuario docente
    UNIQUE KEY uq_nota (matricula_id, carga_id, periodo_id, competencia_id),
    CONSTRAINT chk_nota_rango CHECK (nota_numerica BETWEEN 0 AND 20),
    FOREIGN KEY (matricula_id)   REFERENCES matriculas(id),
    FOREIGN KEY (carga_id)       REFERENCES cargas_academicas(id),
    FOREIGN KEY (periodo_id)     REFERENCES periodos(id),
    FOREIGN KEY (competencia_id) REFERENCES competencias(id),
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id),
    INDEX idx_matricula_periodo (matricula_id, periodo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 22. ALERTAS DEL TUTOR AL PADRE
-- ════════════════════════════════════════════════════════════
CREATE TABLE alertas (
    id              INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    tutor_id        INT UNSIGNED NOT NULL,
    matricula_id    INT UNSIGNED NOT NULL,
    tipo            ENUM('academica','conductual','asistencia','general') NOT NULL DEFAULT 'general',
    mensaje         TEXT NOT NULL,
    leida           BOOLEAN NOT NULL DEFAULT FALSE,
    enviada_correo  BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_id)    REFERENCES usuarios(id),
    FOREIGN KEY (matricula_id) REFERENCES matriculas(id),
    INDEX idx_matricula (matricula_id),
    INDEX idx_leida     (leida)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ════════════════════════════════════════════════════════════
-- DATOS SEMILLA
-- ════════════════════════════════════════════════════════════

-- ─── ROLES ──────────────────────────────────────────────────
INSERT INTO roles (nombre, codigo, descripcion) VALUES
('Administrador',              'admin',             'Acceso total al sistema'),
('Registro Académico',         'registro_academico','Gestión de matrículas, traslados y documentos oficiales'),
('Director General',           'director_general',  'Supervisión de todos los niveles'),
('Director EBR',               'director_ebr',      'Supervisión de su nivel educativo'),
('Secretaria',                 'secretaria',        'Registro de matrículas y atención'),
('Docente',                    'docente',           'Registro de calificaciones de sus cargas'),
('Padre de Familia',           'padre',             'Consulta del progreso de su menor hijo');

-- ─── NIVELES ────────────────────────────────────────────────
INSERT INTO niveles (nombre, codigo, escala_boleta) VALUES
('Primaria',   'prim', 'solo_literal'),
('Secundaria', 'sec',  'ambas');

-- ─── GRADOS ─────────────────────────────────────────────────
-- Primaria: 1° al 6°
INSERT INTO grados (nivel_id, numero, nombre_display) VALUES
(1, 1, '1°'), (1, 2, '2°'), (1, 3, '3°'),
(1, 4, '4°'), (1, 5, '5°'), (1, 6, '6°');
-- Secundaria: 1° al 5°
INSERT INTO grados (nivel_id, numero, nombre_display) VALUES
(2, 1, '1°'), (2, 2, '2°'), (2, 3, '3°'),
(2, 4, '4°'), (2, 5, '5°');

-- ─── ÁREAS PRIMARIA ─────────────────────────────────────────
-- Nivel 1 = Primaria
INSERT INTO areas (nivel_id, nombre, nombre_boleta, alias_boleta, nombre_siagie, tipo, orden) VALUES
-- Áreas-curso
(1, 'Personal Social',               'Personal Social',               NULL,                  'Personal Social',                   'area_curso',    2),
(1, 'Educación Física',              'Educación Física',              NULL,                  'Educación Física',                  'area_curso',    4),
(1, 'Arte y Cultura',                'Arte y Cultura',                NULL,                  'Arte y Cultura',                    'area_curso',    6),
(1, 'Inglés',                        'Inglés como Lengua Extranjera', NULL,                  'Inglés como Lengua Extranjera',     'area_curso',    1),
(1, 'Educación Religiosa',           'Educación Religiosa',           NULL,                  'Educación Religiosa',               'area_curso',    3),
-- Áreas con subáreas
(1, 'Comunicación',                  'Comunicación',                  NULL,                  'Comunicación',                      'con_subareas',  5),
(1, 'Matemática',                    'Matemática',                    NULL,                  'Matemática',                        'con_subareas',  7),
(1, 'Ciencia y Tecnología',          'Ciencia y Tecnología',          NULL,                  'Ciencia y Tecnología',              'con_subareas',  8),
-- Transversales
(1, 'Competencias Transversales',    'Comp. Transv.',                 NULL,                  NULL,                                'transversal',   9);

-- ─── ÁREAS SECUNDARIA ───────────────────────────────────────
-- Nivel 2 = Secundaria
INSERT INTO areas (nivel_id, nombre, nombre_boleta, alias_boleta, nombre_siagie, tipo, orden) VALUES
-- Áreas-curso
(2, 'Desarrollo Personal, Ciudadanía y Cívica', 'DPCC',                    NULL,                    'Desarrollo Personal, Ciudadanía y Cívica', 'area_curso',   1),
(2, 'Educación Física',                         'Educación Física',         NULL,                    'Educación Física',                         'area_curso',   3),
(2, 'Arte y Cultura',                           'Arte y Cultura',           NULL,                    'Arte y Cultura',                           'area_curso',   4),
(2, 'Inglés',                                   'Inglés',                   NULL,                    'Inglés como Lengua Extranjera',             'area_curso',   6),
(2, 'Educación Religiosa',                      'Educación Religiosa',      '(Ética y Valores)',     'Educación Religiosa',                      'area_curso',   10),
(2, 'Educación para el Trabajo',                'EPT',                      '(Habilidades Pedagógicas)', 'Educación para el Trabajo',            'area_curso',   11),
(2, 'Taller de Razonamiento Matemático',        'Taller Raz. Matemático',   NULL,                    'Educación Religiosa',                      'area_curso',   8),
-- Áreas con subáreas
(2, 'Ciencias Sociales',                        'Ciencias Sociales',        NULL,                    'Ciencias Sociales',                        'con_subareas', 2),
(2, 'Comunicación',                             'Comunicación',             NULL,                    'Comunicación',                             'con_subareas', 5),
(2, 'Matemática',                               'Matemática',               NULL,                    'Matemática',                               'con_subareas', 7),
(2, 'Ciencia y Tecnología',                     'Ciencia y Tecnología',     NULL,                    'Ciencia y Tecnología',                     'con_subareas', 9),
-- Transversales
(2, 'Competencias Transversales',               'Comp. Transv.',            NULL,                    NULL,                                       'transversal',  12);

-- ─── SUBÁREAS PRIMARIA ──────────────────────────────────────
-- Comunicación Primaria (area_id = 6 en primaria)
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Comunicación',       1 FROM areas WHERE nivel_id=1 AND nombre='Comunicación';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Plan Lector',        2 FROM areas WHERE nivel_id=1 AND nombre='Comunicación';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Razonamiento Verbal',3 FROM areas WHERE nivel_id=1 AND nombre='Comunicación';

-- Matemática Primaria
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Aritmética',    1 FROM areas WHERE nivel_id=1 AND nombre='Matemática';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Álgebra',       2 FROM areas WHERE nivel_id=1 AND nombre='Matemática';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Geometría',     3 FROM areas WHERE nivel_id=1 AND nombre='Matemática';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Razonamiento Matemático',     4 FROM areas WHERE nivel_id=1 AND nombre='Matemática';

-- Ciencia y Tecnología Primaria
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Química',   1 FROM areas WHERE nivel_id=1 AND nombre='Ciencia y Tecnología';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Biología',  2 FROM areas WHERE nivel_id=1 AND nombre='Ciencia y Tecnología';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Física',    3 FROM areas WHERE nivel_id=1 AND nombre='Ciencia y Tecnología';

-- ─── SUBÁREAS SECUNDARIA ─────────────────────────────────────
-- Ciencias Sociales
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Historia',   1 FROM areas WHERE nivel_id=2 AND nombre='Ciencias Sociales';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Geografía',  2 FROM areas WHERE nivel_id=2 AND nombre='Ciencias Sociales';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Economía',   3 FROM areas WHERE nivel_id=2 AND nombre='Ciencias Sociales';

-- Comunicación Secundaria
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Razonamiento Verbal', 1 FROM areas WHERE nivel_id=2 AND nombre='Comunicación';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Literatura',          2 FROM areas WHERE nivel_id=2 AND nombre='Comunicación';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Lenguaje',            3 FROM areas WHERE nivel_id=2 AND nombre='Comunicación';

-- Matemática Secundaria
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Aritmética',     1 FROM areas WHERE nivel_id=2 AND nombre='Matemática';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Álgebra',        2 FROM areas WHERE nivel_id=2 AND nombre='Matemática';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Geometría',      3 FROM areas WHERE nivel_id=2 AND nombre='Matemática';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Trigonometría',  4 FROM areas WHERE nivel_id=2 AND nombre='Matemática';

-- Ciencia y Tecnología Secundaria
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Química',   1 FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Biología',  2 FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología';
INSERT INTO subareas (area_id, nombre, orden)
SELECT id, 'Física',    3 FROM areas WHERE nivel_id=2 AND nombre='Ciencia y Tecnología';

-- ─── REGLAS ESPECIALES ───────────────────────────────────────
-- Secundaria 1°-3°: Taller Raz. Matemático → vinculo SIAGIE a Ed. Religiosa
-- (ya está reflejado en nombre_siagie del área, esta regla es para documentación)

-- Secundaria 4°-5°: Arte y Cultura → Raz. Matemático en la boleta
INSERT INTO reglas_especiales
    (area_id, nivel_id, grado_desde, grado_hasta, nombre_override, alias_override, descripcion)
SELECT
    a.id, 2, 4, 5,
    'Arte y Cultura', '(Raz. Matemático)',
    'En 4° y 5° de secundaria las notas de Raz. Matemático se registran en el campo Arte y Cultura del SIAGIE'
FROM areas a WHERE a.nivel_id=2 AND a.nombre='Arte y Cultura';

-- Usuario administrador inicial (DNI: 00000000 / pass: admin1234)
-- IMPORTANTE: cambiar la contraseña en el primer acceso
INSERT INTO personas (dni, apellido_paterno, apellido_materno, nombres, correo) VALUES
('00000000', 'Sistema', 'COCIAP', 'Administrador', 'admin@cociap.edu.pe');

INSERT INTO usuarios (persona_id, rol_id, password_hash, estado)
SELECT p.id, r.id,
    '$2y$10$uYEa/sZHfN6Rj5XRdY861euNiXsccWZk0SynNzcFebqNf9V1j2Pfy',
    'activo'
FROM personas p, roles r
WHERE p.dni = '00000000' AND r.codigo = 'admin';
