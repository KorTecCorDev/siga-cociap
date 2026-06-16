-- ============================================================================
-- 021_conducta_numerica.sql
-- Rediseno del modulo de Conducta a modelo NUMERICO (reemplaza el literal directo).
--
-- Flujo (mismo patron que Tutoria / Competencias Transversales, dos etapas):
--   ETAPA 1 (base) = Registro Academico responde 10 criterios Si/No por alumno;
--                    nota RA = (Si / total_criterios) * 20. Bloquea/aprueba la
--                    seccion -> la conducta se hace visible en boleta (final = RA).
--   ETAPA 2 (encima) = Tutor, solo si RA ya bloqueo: agrega su nota 00-20 opcional
--                    (final = promedio, .5 a favor del estudiante) y cierra/aprueba.
--
-- El literal SIEMPRE sale de la escala oficial nota_a_literal() (18/14/11). NO se
-- usa una tabla propia de Si->literal: 5 Si = nota 10 = C.
--
-- Idempotente (MariaDB 10.4): CREATE/ADD COLUMN IF NOT EXISTS + INSERT con guardas.
-- Tipos de FK verificados contra la BD real: niveles.id TINYINT, secciones.id y
-- periodos.id SMALLINT, matriculas.id y usuarios.id INT (todos UNSIGNED).
-- ============================================================================

-- ── 1. Catalogo de criterios (configurable + versionable por nivel) ──────────
-- nivel_id NULL = aplica a AMBOS niveles. Soft-delete (mismo patron que criterios
-- academicos) para preservar el historico cuando los criterios cambien a futuro.
CREATE TABLE IF NOT EXISTS criterios_conducta (
    id            SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    texto         VARCHAR(255)      NOT NULL,
    nivel_id      TINYINT  UNSIGNED NULL,
    orden         TINYINT  UNSIGNED NOT NULL DEFAULT 0,
    eliminado_en  DATETIME          NULL,
    eliminado_por INT      UNSIGNED NULL,
    creado_en     DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_nivel (nivel_id),
    KEY idx_vigencia (eliminado_en),
    CONSTRAINT criterios_conducta_ibfk_1 FOREIGN KEY (nivel_id)      REFERENCES niveles  (id),
    CONSTRAINT criterios_conducta_ibfk_2 FOREIGN KEY (eliminado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed de los 10 criterios iniciales (nivel_id NULL = ambos niveles).
-- Solo si la tabla esta vacia -> idempotente y no duplica al reejecutar.
INSERT INTO criterios_conducta (texto, orden)
SELECT t, o FROM (
              SELECT 'Llega a la I.E en la hora indicada portando la agenda forrada.' AS t, 1 AS o
    UNION ALL SELECT 'Cuida el patrimonio institucional.', 2
    UNION ALL SELECT 'Usa correctamente el uniforme escolar, con el cabello recogido (Mujeres con moñera) o cabello corto (Varones con corte escolar), sin maquillaje ni alhajas.', 3
    UNION ALL SELECT 'Demuestra aseo personal.', 4
    UNION ALL SELECT 'Trata con respeto a los demás, saludando respetuosamente a todos los integrantes de la comunidad.', 5
    UNION ALL SELECT 'Participa en las actividades programadas de la I.E. (formación, aula).', 6
    UNION ALL SELECT 'Contribuye con el orden y disciplina en el aula.', 7
    UNION ALL SELECT 'Muestra respeto y tolerancia hacia sus compañeros durante el recreo y en el aula.', 8
    UNION ALL SELECT 'Mantiene la higiene en el aula colaborando con la limpieza.', 9
    UNION ALL SELECT 'Demuestra proactividad académica.', 10
) src
WHERE NOT EXISTS (SELECT 1 FROM criterios_conducta);

-- ── 2. Respuestas Si/No de Registro Academico (por alumno, periodo y criterio) ─
-- respuesta: 1 = Si, 0 = No. Las 10 son OBLIGATORIAS (se valida en la aplicacion):
-- no existe "en blanco"; un alumno esta calificado solo con sus 10 respuestas.
CREATE TABLE IF NOT EXISTS conducta_respuestas (
    id             INT      UNSIGNED NOT NULL AUTO_INCREMENT,
    matricula_id   INT      UNSIGNED NOT NULL,
    periodo_id     SMALLINT UNSIGNED NOT NULL,
    criterio_id    SMALLINT UNSIGNED NOT NULL,
    respuesta      TINYINT(1)        NOT NULL,
    registrado_por INT      UNSIGNED NOT NULL,
    registrado_en  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    modificado_en  DATETIME          NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_respuesta (matricula_id, periodo_id, criterio_id),
    KEY idx_periodo (periodo_id),
    KEY idx_criterio (criterio_id),
    KEY idx_registrado_por (registrado_por),
    CONSTRAINT conducta_respuestas_ibfk_1 FOREIGN KEY (matricula_id)   REFERENCES matriculas        (id),
    CONSTRAINT conducta_respuestas_ibfk_2 FOREIGN KEY (periodo_id)     REFERENCES periodos          (id),
    CONSTRAINT conducta_respuestas_ibfk_3 FOREIGN KEY (criterio_id)    REFERENCES criterios_conducta(id),
    CONSTRAINT conducta_respuestas_ibfk_4 FOREIGN KEY (registrado_por) REFERENCES usuarios          (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Cierre por seccion (dos etapas: RA y tutor) ───────────────────────────
-- Vigente = anulado_en IS NULL (mismo criterio que cierres_transversales).
-- ra_bloqueado_* gobierna la VISIBILIDAD en boleta; tutor_cerrado_* indica que el
-- tutor aprobo (origen efectivo 'tutor' y la final usa el promedio).
CREATE TABLE IF NOT EXISTS cierres_conducta (
    id               INT      UNSIGNED NOT NULL AUTO_INCREMENT,
    seccion_id       SMALLINT UNSIGNED NOT NULL,
    periodo_id       SMALLINT UNSIGNED NOT NULL,
    ra_bloqueado_en  DATETIME          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ra_bloqueado_por INT      UNSIGNED NOT NULL,
    tutor_cerrado_en DATETIME          NULL,
    tutor_cerrado_por INT     UNSIGNED NULL,
    anulado_en       DATETIME          NULL,
    anulado_por      INT      UNSIGNED NULL,
    motivo_anulacion VARCHAR(500)      NULL,
    PRIMARY KEY (id),
    KEY idx_seccion_periodo (seccion_id, periodo_id),
    KEY idx_periodo (periodo_id),
    KEY idx_ra_por (ra_bloqueado_por),
    KEY idx_tutor_por (tutor_cerrado_por),
    KEY idx_anulado_por (anulado_por),
    CONSTRAINT cierres_conducta_ibfk_1 FOREIGN KEY (seccion_id)        REFERENCES secciones (id),
    CONSTRAINT cierres_conducta_ibfk_2 FOREIGN KEY (periodo_id)        REFERENCES periodos  (id),
    CONSTRAINT cierres_conducta_ibfk_3 FOREIGN KEY (ra_bloqueado_por)  REFERENCES usuarios  (id),
    CONSTRAINT cierres_conducta_ibfk_4 FOREIGN KEY (tutor_cerrado_por) REFERENCES usuarios  (id),
    CONSTRAINT cierres_conducta_ibfk_5 FOREIGN KEY (anulado_por)       REFERENCES usuarios  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Adaptar calificaciones_conducta ───────────────────────────────────────
-- nota_tutor: la nota directa 00-20 del tutor (por alumno). NULL = el tutor no la
-- ingreso -> la final es la nota RA sola. La nota RA NO se guarda aqui: se deriva
-- de conducta_respuestas.
ALTER TABLE calificaciones_conducta
    ADD COLUMN IF NOT EXISTS nota_tutor TINYINT UNSIGNED NULL AFTER literal;

-- literal pasa a NULLABLE: en B2+ el literal se DERIVA al vuelo de la nota final.
-- Las filas del I Bimestre conservan su literal directo (modelo legado).
ALTER TABLE calificaciones_conducta
    MODIFY COLUMN literal ENUM('AD','A','B','C') NULL;

-- ── 5. Backfill del legado (I Bimestre) ──────────────────────────────────────
-- Al activar el filtro "visible solo si esta bloqueada", las boletas B1 ya
-- entregadas desaparecerian. Marcamos como ra_bloqueado cada (seccion, periodo)
-- que YA tenga conducta registrada. ra_bloqueado_por = un registrador real de esa
-- seccion (dato de auditoria historica). Idempotente via LEFT JOIN ... IS NULL.
INSERT INTO cierres_conducta (seccion_id, periodo_id, ra_bloqueado_en, ra_bloqueado_por)
SELECT t.seccion_id, t.periodo_id, NOW(), t.uid
FROM (
    SELECT m.seccion_id AS seccion_id, cc.periodo_id AS periodo_id, MIN(cc.registrado_por) AS uid
    FROM calificaciones_conducta cc
    INNER JOIN matriculas m ON m.id = cc.matricula_id
    WHERE m.seccion_id IS NOT NULL
      AND cc.literal IS NOT NULL          -- solo filas legadas (literal directo)
    GROUP BY m.seccion_id, cc.periodo_id
) t
LEFT JOIN cierres_conducta cz
    ON cz.seccion_id = t.seccion_id AND cz.periodo_id = t.periodo_id
WHERE cz.id IS NULL;
