-- 015_desempates_merito.sql
-- Resolucion manual de empates irreducibles del orden de merito.
--
-- La cascada automatica de desempate (promedio exacto -> menos C -> menos B ->
-- mas AD) puede agotarse sin separar a dos o mas alumnos: misma distribucion
-- literal exacta, o numero de competencias distinto (exoneraciones). En ese caso
-- el puesto en disputa lo decide una persona (Registro Academico / Admin, con
-- Admin por encima de Registro). Para que la decision sea auditable y NO arbitraria,
-- cada resolucion exige un motivo y se registra aqui junto a quien la tomo.
--
-- La resolucion se ancla al CONJUNTO de matriculas empatadas + periodo (grupo_clave),
-- no a la vista, para que una misma decision se propague al ranking general y al
-- ranking por seccion sin resolverse dos veces con resultados contradictorios.
--
-- Idempotente: CREATE TABLE IF NOT EXISTS. Re-resolver el mismo grupo reemplaza el
-- detalle y actualiza quien/cuando/motivo (override de Admin sobre Registro).

CREATE TABLE IF NOT EXISTS desempates_merito (
    id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    periodo_id   SMALLINT UNSIGNED NOT NULL,
    grado_id     TINYINT UNSIGNED NOT NULL,    -- coincide con grados.id (TINYINT UNSIGNED)
    grupo_clave  VARCHAR(255) NOT NULL,        -- CSV ordenado de matricula_id del grupo empatado
    motivo       VARCHAR(500) NOT NULL,
    resuelto_por INT UNSIGNED NOT NULL,
    resuelto_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_periodo_grupo (periodo_id, grupo_clave),
    KEY idx_periodo (periodo_id),
    KEY idx_grado (grado_id),
    KEY idx_resuelto_por (resuelto_por),
    CONSTRAINT fk_desempate_periodo FOREIGN KEY (periodo_id)   REFERENCES periodos(id),
    CONSTRAINT fk_desempate_grado   FOREIGN KEY (grado_id)     REFERENCES grados(id),
    CONSTRAINT fk_desempate_usuario FOREIGN KEY (resuelto_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS desempates_merito_orden (
    id           INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    desempate_id INT UNSIGNED NOT NULL,
    matricula_id INT UNSIGNED NOT NULL,
    orden_manual SMALLINT UNSIGNED NOT NULL,   -- 1 = primer puesto del grupo empatado
    UNIQUE KEY uq_desempate_matricula (desempate_id, matricula_id),
    KEY idx_matricula (matricula_id),
    CONSTRAINT fk_desorden_desempate FOREIGN KEY (desempate_id) REFERENCES desempates_merito(id) ON DELETE CASCADE,
    CONSTRAINT fk_desorden_matricula FOREIGN KEY (matricula_id) REFERENCES matriculas(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
