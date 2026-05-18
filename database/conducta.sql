-- =============================================================================
-- Módulo de Calificaciones de Conducta
-- Ejecutar sobre la BD activa (backup_18_05_2026 o posterior).
-- =============================================================================

CREATE TABLE IF NOT EXISTS `calificaciones_conducta` (
  `id`            int(10) unsigned     NOT NULL AUTO_INCREMENT,
  `matricula_id`  int(10) unsigned     NOT NULL,
  `periodo_id`    smallint(5) unsigned NOT NULL,
  `literal`       enum('AD','A','B','C') NOT NULL,
  `registrado_por` int(10) unsigned    NOT NULL,
  `registrado_en` datetime             NOT NULL DEFAULT current_timestamp(),
  `modificado_en` datetime             DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_conducta` (`matricula_id`,`periodo_id`),
  KEY `idx_periodo` (`periodo_id`),
  KEY `idx_registrado_por` (`registrado_por`),
  CONSTRAINT `conducta_ibfk_1` FOREIGN KEY (`matricula_id`)   REFERENCES `matriculas` (`id`),
  CONSTRAINT `conducta_ibfk_2` FOREIGN KEY (`periodo_id`)     REFERENCES `periodos`   (`id`),
  CONSTRAINT `conducta_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios`   (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
