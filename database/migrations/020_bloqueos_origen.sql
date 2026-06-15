-- 020_bloqueos_origen.sql
-- Origen del bloqueo de competencia: distingue el bloqueo que hizo el DOCENTE
-- (aprobacion manual de su carga, Variante 1, o el director desde el panel) del
-- que genero el CIERRE FORZADO del bimestre (barrido automatico de competencias
-- pendientes al cerrar).
--
-- Por que: al reabrir un bimestre ya NO se borran bloqueos automaticamente. Las
-- competencias finalizadas-vacias (origen='docente', sin notas pero aprobadas)
-- deben permanecer bloqueadas. Solo los bloqueos del cierre forzado se liberan,
-- y de forma MANUAL desde el panel de bloqueos.
--
-- Backfill: el DEFAULT 'docente' marca TODAS las filas existentes (incluido el
-- I Bimestre) como 'docente'. Es intencional: lo ya cerrado se trata como
-- aprobado por el docente. A partir de aqui, solo el cierre forzado escribe
-- 'cierre' (ver AnioAcademicoModel::bloquearCompetenciasPendientes).
--
-- Idempotente: ADD COLUMN IF NOT EXISTS (MariaDB 10.4+).

ALTER TABLE `bloqueos_competencia`
  ADD COLUMN IF NOT EXISTS `origen` ENUM('docente','cierre')
  NOT NULL DEFAULT 'docente' AFTER `bloqueado_por`;
