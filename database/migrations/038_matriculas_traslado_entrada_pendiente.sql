-- ════════════════════════════════════════════════════════════════════
-- Migración 038: Corrección de matrículas del registro masivo → pendiente
-- ════════════════════════════════════════════════════════════════════
-- CONTEXTO: durante el registro masivo del año, seis estudiantes que en
--   realidad llegaron ESTE AÑO al colegio (traslado de entrada) o que tienen
--   documentación pendiente quedaron mal registrados como matrícula APROBADA
--   (y algunos con tipo 'continuador' cuando debían ser 'nuevo'). Se corrigen
--   para que entren al flujo de matrícula PENDIENTE, que exige el checklist de
--   documentos antes de reactivar.
--
-- CAMBIOS (por DNI, año ACTIVO):
--   A) Traslado de entrada mal registrado → tipo='nuevo' + estado='pendiente':
--        91478026  MIRANDA MAMANI, Catalina Sofia        (1°B)
--        78660487  MIRANDA MAMANI, Luhana Victoria        (2°B)
--        79923689  ÑIQUEN PAJUELO, Xoana Antonella        (4°A)  [ya era 'nuevo']
--   B) Continuador con documentación pendiente → solo estado='pendiente'
--      (se MANTIENE tipo='continuador'):
--        90704063  VALDEZ ALVA, Luhana Rosa               (3°B)
--
-- NO se tocan (ya estaban correctos, nuevo+pendiente):
--        62542262  RIMAC CIRIACO, Azahi Fernanda          (5°B)
--        63013825  SANTAMARIA RODRIGUEZ, Jakeline         (3°A)
--
-- NO se escribe motivo_estado (decisión: solo el cambio de estado/tipo).
-- Las notas ya ingresadas NO se tocan (van por matrícula/carga/competencia,
--   independientes del tipo). Ninguna de estas matrículas figura en un
--   snapshot de orden de mérito ni tiene respuestas de conducta.
--
-- SEGURIDAD / IDEMPOTENCIA:
--   * Ancla por DNI (estable entre entornos), NO por id auto-incremental.
--   * Acotada al AÑO ACTIVO (no toca matrículas de años anteriores del mismo
--     alumno).
--   * Guarda estado='aprobada': una segunda corrida no encuentra filas (no-op),
--     y NUNCA reactiva una matrícula ya 'desactivado' (baja/traslado de salida).
--
-- ⚠️ ANTES DE APLICAR EN PRODUCCIÓN: correr el PREVIEW de abajo para ver el
--   estado actual de los seis DNIs. Ejecutar con conexión utf8mb4.
-- Ejecutar DESPUÉS de 037_consolidar_docentes_duplicados.sql.
-- ════════════════════════════════════════════════════════════════════

SET NAMES utf8mb4;

-- ── PREVIEW (solo lectura; NO modifica nada). Correr esto primero en prod: ──
--   SELECT p.dni,
--          CONCAT(p.apellido_paterno,' ',p.apellido_materno,', ',p.nombres) AS estudiante,
--          m.tipo, m.estado, a.anio
--   FROM matriculas m
--   INNER JOIN estudiantes e ON e.id = m.estudiante_id
--   INNER JOIN personas    p ON p.id = e.persona_id
--   INNER JOIN anios_academicos a ON a.id = m.anio_id AND a.estado = 'activo'
--   WHERE p.dni IN ('91478026','78660487','79923689','62542262','63013825','90704063')
--   ORDER BY p.dni;

-- A. Traslados de entrada → nuevo + pendiente.
UPDATE matriculas m
INNER JOIN estudiantes e ON e.id = m.estudiante_id
INNER JOIN personas    p ON p.id = e.persona_id
INNER JOIN anios_academicos a ON a.id = m.anio_id AND a.estado = 'activo'
SET m.tipo = 'nuevo', m.estado = 'pendiente'
WHERE p.dni IN ('91478026','78660487','79923689')
  AND m.estado = 'aprobada';

-- B. Continuador con documentación pendiente → solo pendiente (mantiene tipo).
UPDATE matriculas m
INNER JOIN estudiantes e ON e.id = m.estudiante_id
INNER JOIN personas    p ON p.id = e.persona_id
INNER JOIN anios_academicos a ON a.id = m.anio_id AND a.estado = 'activo'
SET m.estado = 'pendiente'
WHERE p.dni = '90704063'
  AND m.estado = 'aprobada';

-- Verificación (los 4 corregidos deben quedar 'pendiente'; 79923689 en 'nuevo';
-- 90704063 sigue 'continuador'; los otros dos ya estaban 'nuevo'/'pendiente'):
--   SELECT p.dni, m.tipo, m.estado
--   FROM matriculas m
--   INNER JOIN estudiantes e ON e.id = m.estudiante_id
--   INNER JOIN personas    p ON p.id = e.persona_id
--   INNER JOIN anios_academicos a ON a.id = m.anio_id AND a.estado = 'activo'
--   WHERE p.dni IN ('91478026','78660487','79923689','62542262','63013825','90704063')
--   ORDER BY p.dni;
