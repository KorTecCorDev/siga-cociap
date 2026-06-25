<?php

namespace App\Models;

/**
 * OmisionCriterioModel
 * Gestiona los registros de alumnos no evaluados en un criterio.
 */
class OmisionCriterioModel extends BaseModel
{
    protected string $table = 'omisiones_criterio';

    const MOTIVOS = [
        'ausencia_injustificada' => 'Ausencia injustificada',
        'ausencia_justificada'   => 'Ausencia justificada (enfermedad, permiso)',
        'abandono'               => 'Abandono / retiro',
        'no_aplico'              => 'No aplicó para este alumno',
    ];

    /**
     * Guarda o actualiza las omisiones de varios alumnos para un criterio.
     * @param array $omisiones  [matricula_id => motivo]
     */
    public function guardarLote(int $criterioId, array $omisiones, int $usuarioId): void
    {
        foreach ($omisiones as $matriculaId => $motivo) {
            if (!array_key_exists($motivo, self::MOTIVOS)) continue;
            $this->execute("
                INSERT INTO omisiones_criterio
                    (criterio_id, matricula_id, motivo, registrado_por, registrado_en)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    motivo         = VALUES(motivo),
                    registrado_por = VALUES(registrado_por),
                    registrado_en  = NOW()
            ", [$criterioId, (int) $matriculaId, $motivo, $usuarioId]);
        }
    }

    /**
     * ¿El alumno tiene una omisión registrada (motivo) en este criterio?
     * Se usa en el autosave: borrar una nota cuyo blanco YA está justificado no
     * rompe la completitud, así que no desconfirma el criterio.
     */
    public function tieneOmision(int $criterioId, int $matriculaId): bool
    {
        return (bool) $this->queryOne(
            "SELECT 1 FROM omisiones_criterio
             WHERE criterio_id = ? AND matricula_id = ?
             LIMIT 1",
            [$criterioId, $matriculaId]
        );
    }

    /**
     * Retorna omisiones de un criterio indexadas por matricula_id.
     * @return array [matricula_id => motivo]
     */
    public function getPorCriterio(int $criterioId): array
    {
        $rows = $this->query("
            SELECT matricula_id, motivo
            FROM omisiones_criterio
            WHERE criterio_id = ?
        ", [$criterioId]);

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row['matricula_id']] = $row['motivo'];
        }
        return $result;
    }

    /**
     * Retorna los matricula_id que tienen al menos una omision en la competencia.
     * Usado en bloquear() para permitir que alumnos sin promedio puedan ser bloqueados
     * si tienen todas sus omisiones justificadas.
     */
    public function getMatriculasConOmisionEnCompetencia(
        int $cargaId,
        int $competenciaId,
        int $periodoId
    ): array {
        $rows = $this->query("
            SELECT DISTINCT oc.matricula_id
            FROM omisiones_criterio oc
            INNER JOIN criterios cr ON cr.id = oc.criterio_id
            WHERE cr.carga_id       = ?
              AND cr.competencia_id = ?
              AND cr.periodo_id     = ?
              AND cr.eliminado_en   IS NULL
        ", [$cargaId, $competenciaId, $periodoId]);

        return array_column($rows, 'matricula_id');
    }

    /**
     * Retorna omisiones de un alumno en un periodo, agrupadas por competencia_id.
     * @return array [competencia_id => string[]]  (motivos únicos)
     */
    public function getPorMatriculaPeriodo(int $matriculaId, int $periodoId): array
    {
        $rows = $this->query("
            SELECT DISTINCT cr.competencia_id, oc.motivo
            FROM omisiones_criterio oc
            INNER JOIN criterios cr ON cr.id = oc.criterio_id
            WHERE oc.matricula_id = ?
              AND cr.periodo_id   = ?
              AND cr.eliminado_en IS NULL
        ", [$matriculaId, $periodoId]);

        return $this->agruparPorCompetencia($rows);
    }

    /**
     * Retorna omisiones de un alumno en todos los periodos de un año académico.
     * Usado en buildBoletaData() para mostrar motivos en la boleta digital,
     * que muestra todos los bimestres del año a la vez.
     * @return array [competencia_id => string[]]  (motivos únicos del año)
     */
    public function getPorMatriculaAnio(int $matriculaId, int $anioId): array
    {
        $rows = $this->query("
            SELECT DISTINCT cr.competencia_id, oc.motivo
            FROM omisiones_criterio oc
            INNER JOIN criterios cr ON cr.id = oc.criterio_id
            INNER JOIN periodos p   ON p.id  = cr.periodo_id
            WHERE oc.matricula_id = ?
              AND p.anio_id       = ?
              AND cr.eliminado_en IS NULL
        ", [$matriculaId, $anioId]);

        return $this->agruparPorCompetencia($rows);
    }

    /**
     * Versión por UNIÓN para el retorno de grado: fusiona las omisiones de varias
     * matrículas del mismo estudiante (oficial + operativa) en un único mapa
     * [competencia_id => [motivos...]]. Con una sola matrícula se comporta igual
     * que getPorMatriculaAnio().
     */
    public function getPorMatriculaAnioUnion(array $matriculaIds, int $anioId): array
    {
        if (count($matriculaIds) <= 1) {
            return $this->getPorMatriculaAnio((int) ($matriculaIds[0] ?? 0), $anioId);
        }

        $out = [];
        foreach ($matriculaIds as $id) {
            foreach ($this->getPorMatriculaAnio((int) $id, $anioId) as $compId => $motivos) {
                $out[$compId] = array_values(array_unique(
                    array_merge($out[$compId] ?? [], $motivos)
                ));
            }
        }
        return $out;
    }

    private function agruparPorCompetencia(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $compId = (int) $row['competencia_id'];
            if (!isset($result[$compId])) {
                $result[$compId] = [];
            }
            $motivo = $row['motivo'];
            if (!in_array($motivo, $result[$compId], true)) {
                $result[$compId][] = $motivo;
            }
        }
        return $result;
    }

    /**
     * Devuelve la etiqueta legible de un motivo.
     */
    public static function etiqueta(string $motivo): string
    {
        return self::MOTIVOS[$motivo] ?? $motivo;
    }
}
