<?php

namespace App\Models;

/**
 * NotaExternaModel — PUNTO ÚNICO de las notas del COLEGIO DE ORIGEN.
 *
 * REGLA DEL COLEGIO (decidida el 10/09/2026): el Informe de Progreso que emite
 * el COCIAP tras un traslado lleva **solo** las calificaciones cursadas AQUÍ.
 * Lo que el estudiante trae de su colegio anterior es INFORMATIVO, y su público
 * son los DOCENTES con carga en su sección, que necesitan saber con qué llega.
 *
 * ⚠️ POR ESO ESTA TABLA NO LA LEE `BoletaModel`, Y NO DEBE LEERLA. No es un
 * mecanismo muerto: que no llegue a la boleta ES el comportamiento pedido. El
 * caso contrario —notas NUESTRAS que no se registraron a tiempo— va por otro
 * camino distinto: la calificación extraordinaria, que sí entra a boleta y
 * SIAGIE. Son dos mecanismos con destinos opuestos; no unificarlos.
 *
 * Los nombres de área, competencia y periodo van en TEXTO LIBRE porque el plan
 * curricular del colegio de origen no tiene por qué coincidir con el nuestro.
 * `area_id` (migración 057) es un mapeo OPCIONAL que solo sirve para resaltarle
 * a cada docente las filas de su propia carga.
 */
class NotaExternaModel extends BaseModel
{
    protected string $table = 'notas_externas';

    public const LITERALES = ['AD', 'A', 'B', 'C'];

    /** Notas de origen de una matrícula, con el nombre del área mapeada. */
    public function getDeMatricula(int $matriculaId): array
    {
        return $this->query("
            SELECT
                ne.*,
                a.nombre        AS area_mapeada,
                a.nombre_boleta AS area_mapeada_boleta
            FROM notas_externas ne
            LEFT JOIN areas a ON a.id = ne.area_id
            WHERE ne.matricula_id = ?
            ORDER BY ne.periodo_nombre, ne.area_nombre, ne.competencia_nombre
        ", [$matriculaId]);
    }

    /**
     * Alta o actualización de UNA nota de origen. Idempotente por el UNIQUE
     * (matricula_id, periodo_nombre, competencia_nombre).
     */
    public function registrar(array $datos): bool
    {
        return $this->execute("
            INSERT INTO notas_externas
                (matricula_id, periodo_nombre, competencia_nombre, area_id,
                 area_nombre, nota_literal, colegio_origen, registrado_por)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                area_id        = VALUES(area_id),
                area_nombre    = VALUES(area_nombre),
                nota_literal   = VALUES(nota_literal),
                colegio_origen = VALUES(colegio_origen),
                registrado_por = VALUES(registrado_por),
                registrado_en  = CURRENT_TIMESTAMP
        ", [
            (int) $datos['matricula_id'],
            $datos['periodo_nombre'],
            $datos['competencia_nombre'],
            isset($datos['area_id']) && (int) $datos['area_id'] > 0 ? (int) $datos['area_id'] : null,
            $datos['area_nombre'],
            $datos['nota_literal'],
            $datos['colegio_origen'] ?? null,
            (int) $datos['registrado_por'],
        ]);
    }

    /**
     * Alta de VARIAS notas de una vez. El informe de origen llega como un
     * documento entero, así que se captura entero: registrarlas de una en una
     * son 25+ pasadas por el formulario, el mismo problema que ya tenía la
     * calificación extraordinaria.
     *
     * ⚠️ NO abre transacción: la owna quien llama. PDO no anida transacciones,
     * y abrirla aquí impedía envolver el lote desde fuera —empezando por los
     * verificadores, que escriben y hacen rollback—. Mismo criterio que
     * RectificacionController::escribirExtraordinaria.
     *
     * @param array $filas cada una con periodo_nombre, competencia_nombre,
     *                     area_nombre, nota_literal y area_id opcional
     * @return int Cuántas quedaron registradas.
     */
    public function registrarLote(
        int $matriculaId,
        array $filas,
        ?string $colegioOrigen,
        int $usuarioId
    ): int {
        foreach ($filas as $f) {
            $this->registrar([
                'matricula_id'       => $matriculaId,
                'periodo_nombre'     => $f['periodo_nombre'],
                'competencia_nombre' => $f['competencia_nombre'],
                'area_id'            => $f['area_id'] ?? null,
                'area_nombre'        => $f['area_nombre'],
                'nota_literal'       => $f['nota_literal'],
                'colegio_origen'     => $colegioOrigen,
                'registrado_por'     => $usuarioId,
            ]);
        }

        return count($filas);
    }

    /** ¿Esta matrícula tiene alguna nota de origen registrada? */
    public function tiene(int $matriculaId): bool
    {
        $fila = $this->queryOne(
            "SELECT 1 AS x FROM notas_externas WHERE matricula_id = ? LIMIT 1",
            [$matriculaId]
        );
        return $fila !== null;
    }

    /**
     * GUARDA de acceso del docente: ¿tiene carga ACTIVA en la sección de esta
     * matrícula, en el año de la matrícula? Si no, no ve nada (404).
     *
     * ⚠️ `cargas_academicas.docente_id` apunta directo a `usuarios.id`.
     */
    public function docenteTieneAcceso(int $matriculaId, int $docenteId): bool
    {
        $fila = $this->queryOne("
            SELECT 1 AS x
            FROM matriculas m
            INNER JOIN cargas_academicas ca
                    ON ca.seccion_id = m.seccion_id
                   AND ca.anio_id    = m.anio_id
                   AND ca.estado     = 'activa'
            WHERE m.id = ? AND ca.docente_id = ?
            LIMIT 1
        ", [$matriculaId, $docenteId]);

        return $fila !== null;
    }

    /**
     * Áreas en las que ESE docente tiene carga en la sección de la matrícula.
     * Alimenta el RESALTADO: el docente ve el informe completo, y las filas de
     * su(s) área(s) marcadas.
     *
     * @return int[] ids de área
     */
    public function areasDelDocenteEnSeccion(int $matriculaId, int $docenteId): array
    {
        $filas = $this->query("
            SELECT DISTINCT COALESCE(ca.area_id, sa.area_id) AS area_id
            FROM matriculas m
            INNER JOIN cargas_academicas ca
                    ON ca.seccion_id = m.seccion_id
                   AND ca.anio_id    = m.anio_id
                   AND ca.estado     = 'activa'
            LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
            WHERE m.id = ? AND ca.docente_id = ?
        ", [$matriculaId, $docenteId]);

        $out = [];
        foreach ($filas as $f) {
            if ($f['area_id'] !== null) {
                $out[] = (int) $f['area_id'];
            }
        }
        return $out;
    }

    /**
     * Áreas del plan de la SECCIÓN de una matrícula, para el <select> de mapeo
     * opcional que usa Registro Académico al registrar.
     */
    public function areasDeLaSeccion(int $matriculaId): array
    {
        return $this->query("
            SELECT DISTINCT
                a.id,
                COALESCE(NULLIF(a.nombre_boleta, ''), a.nombre) AS nombre
            FROM matriculas m
            INNER JOIN cargas_academicas ca
                    ON ca.seccion_id = m.seccion_id
                   AND ca.anio_id    = m.anio_id
                   AND ca.estado     = 'activa'
            LEFT  JOIN subareas sa ON sa.id = ca.subarea_id
            INNER JOIN areas a     ON a.id  = COALESCE(ca.area_id, sa.area_id)
            WHERE m.id = ?
            ORDER BY nombre
        ", [$matriculaId]);
    }
}
