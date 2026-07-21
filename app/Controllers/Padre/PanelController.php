<?php

namespace App\Controllers\Padre;

use App\Controllers\BaseController;
use App\Models\BoletaPublicaModel;
use App\Models\CalificacionModel;
use App\Models\ConductaModel;
use App\Models\PublicacionBoletaModel;
use Core\Session;

/**
 * PanelController
 * Panel del padre de familia.
 */
class PanelController extends BaseController
{
    private CalificacionModel      $calModel;
    private ConductaModel          $conductaModel;
    private BoletaPublicaModel     $bpModel;
    private PublicacionBoletaModel $publicacionModel;

    public function __construct()
    {
        $this->requireRole(['padre', 'admin', 'registro_academico']);
        $this->calModel         = new CalificacionModel();
        $this->conductaModel    = new ConductaModel();
        $this->bpModel          = new BoletaPublicaModel();
        $this->publicacionModel = new PublicacionBoletaModel();
    }

    /**
     * GET /padre/inicio
     * Panel principal del padre.
     */
    public function index(): void
    {
        $user     = Session::user();
        $hijo     = $this->getHijo($user['id']);
        $periodo  = $this->getPeriodoActivo();
        $alertas  = $hijo ? $this->getAlertas($hijo['matricula_id']) : [];

        $this->view('padre/inicio', [
            'titulo'  => 'Panel del padre',
            'hijo'    => $hijo,
            'periodo' => $periodo,
            'alertas' => $alertas,
        ]);
    }

    /**
     * GET /padre/notas
     * Ver notas del hijo en el periodo activo.
     */
    public function notas(): void
    {
        $user = Session::user();
        $hijo = $this->getHijo($user['id']);

        if (!$hijo) {
            $this->redirectWithError(
                url('padre/inicio'),
                'No se encontró información del estudiante.'
            );
        }

        // F4 — El padre solo ve hasta el ULTIMO bimestre CERRADO (oficial). El
        // bimestre activo (registro/borrador) no se expone: el borrador del cierre
        // forzado bloquea todas las competencias y se filtraria como definitivo.
        // COMPUERTA DE PUBLICACION (044): ademas exige que el bimestre este
        // PUBLICADO al nivel del hijo — cerrar ya no basta. Por eso se resuelve
        // despues de conocer al hijo (la publicacion es por nivel).
        $periodo = $this->getPeriodoVigentePadre((int) $hijo['nivel_id']);

        if (!$periodo) {
            $this->redirectWithError(
                url('padre/inicio'),
                'Aún no hay notas publicadas. Las boletas se habilitan cuando el colegio las publica, en la fecha de entrega.'
            );
        }

        // Retorno de grado: durante la nivelación las notas del periodo viven en
        // la matrícula operativa; se leen por unión bajo la identidad oficial.
        $fuentes = $this->calModel->boletaContexto((int) $hijo['matricula_id'])['fuentes'];

        $notas = [];
        foreach ($fuentes as $mid) {
            $notas = array_merge(
                $notas,
                $this->calModel->getBoletaAlumno((int) $mid, (int) $periodo['id'])
            );
        }

        // Agrupar notas por área
        $areas = [];
        foreach ($notas as $nota) {
            $areaNombre = $nota['nombre_boleta'] ?? $nota['area_nombre'];
            if ($nota['alias_boleta']) {
                $areaNombre .= ' ' . $nota['alias_boleta'];
            }
            $areas[$areaNombre][] = $nota;
        }

        // Conducta del periodo: la que tenga la fuente con cierre vigente.
        $conducta = null;
        foreach ($fuentes as $mid) {
            $conducta = $this->conductaModel->getParaPeriodo((int) $mid, (int) $periodo['id']);
            if ($conducta !== null) {
                break;
            }
        }

        // QR/enlace permanente por token (identidad oficial): mismo enlace que el
        // padre escanea de la boleta impresa, estable todo el año.
        $tokenBoleta = $this->bpModel->getOCrearToken((int) $hijo['matricula_id']);

        $this->view('padre/notas', [
            'titulo'      => 'Notas de ' . $hijo['nombres'],
            'hijo'        => $hijo,
            'periodo'     => $periodo,
            'areas'       => $areas,
            'conducta'    => $conducta,
            'tokenBoleta' => $tokenBoleta,
        ]);
    }

    /**
     * GET /padre/alertas
     * Ver alertas del tutor.
     */
    public function alertas(): void
    {
        $user    = Session::user();
        $hijo    = $this->getHijo($user['id']);
        $alertas = $hijo ? $this->getAlertas($hijo['matricula_id']) : [];

        $this->view('padre/alertas', [
            'titulo'  => 'Alertas',
            'hijo'    => $hijo,
            'alertas' => $alertas,
        ]);
    }

    // ── Métodos privados ─────────────────────────────────────

    private function getPeriodoActivo(): ?array
    {
        return $this->calModel->queryOne("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE p.estado = 'activo'
            LIMIT 1
        ");
    }

    /**
     * F4 — Periodo "vigente" para el padre: el ultimo bimestre CERRADO (oficial)
     * del anio activo. El borrador (Hito A, periodo aun 'activo' con boletas
     * aprobadas) NUNCA se expone a las familias; solo lo oficial.
     *
     * COMPUERTA DE PUBLICACION (044): ademas debe estar PUBLICADO al nivel del
     * hijo. Cerrar un bimestre ya no lo muestra aqui; publicarlo si. Los
     * periodos publicados se piden a PublicacionBoletaModel (punto unico de
     * verdad) y se cruzan en PHP para no consultar la tabla desde aqui.
     */
    private function getPeriodoVigentePadre(int $nivelId): ?array
    {
        $cerrados = $this->calModel->query("
            SELECT p.*, a.anio
            FROM periodos p
            INNER JOIN anios_academicos a ON a.id = p.anio_id
            WHERE a.estado = 'activo' AND p.estado = 'cerrado'
            ORDER BY p.numero DESC
        ");

        if (!$cerrados) {
            return null;
        }

        $publicados = $this->publicacionModel->periodosPublicados(
            (int) $cerrados[0]['anio_id'],
            $nivelId
        );

        foreach ($cerrados as $p) {
            if (isset($publicados[(int) $p['id']])) {
                return $p;
            }
        }
        return null;
    }

    private function getHijo(int $usuarioId): ?array
    {
        return $this->calModel->queryOne("
            SELECT
                e.id            AS estudiante_id,
                m.id            AS matricula_id,
                p.nombres,
                p.apellido_paterno,
                p.apellido_materno,
                p.dni,
                CONCAT(
                    p.apellido_paterno, ' ',
                    p.apellido_materno, ', ',
                    p.nombres
                )               AS nombre_completo,
                g.nombre_display AS grado_nombre,
                s.nombre        AS seccion_nombre,
                n.id            AS nivel_id,
                n.nombre        AS nivel_nombre,
                n.codigo        AS nivel_codigo,
                n.escala_boleta,
                m.estado        AS estado_matricula
            FROM usuarios u
            INNER JOIN personas pa      ON pa.id = u.persona_id
            INNER JOIN apoderados ap    ON ap.persona_id = pa.id
            INNER JOIN vinculo_familiar vf ON vf.apoderado_id = ap.id
            INNER JOIN estudiantes e    ON e.id = vf.estudiante_id
            INNER JOIN personas p       ON p.id = e.persona_id
            INNER JOIN matriculas m     ON m.estudiante_id = e.id
            INNER JOIN secciones s      ON s.id = m.seccion_id
            INNER JOIN grados g         ON g.id = s.grado_id
            INNER JOIN niveles n        ON n.id = g.nivel_id
            INNER JOIN anios_academicos a ON a.id = m.anio_id
            WHERE u.id      = ?
              AND a.estado  = 'activo'
              AND m.estado  = 'aprobada'
              -- Retorno de grado: el padre siempre ve la matrícula OFICIAL
              -- (grado/sección SIAGIE), nunca la operativa del grado inferior.
              AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'activo')
            LIMIT 1
        ", [$usuarioId]);
    }

    private function getAlertas(int $matriculaId): array
    {
        return $this->calModel->query("
            SELECT
                al.id,
                al.tipo,
                al.mensaje,
                al.leida,
                al.created_at,
                CONCAT(pt.nombres, ' ', pt.apellido_paterno) AS tutor_nombre
            FROM alertas al
            INNER JOIN usuarios ut  ON ut.id  = al.tutor_id
            INNER JOIN personas pt  ON pt.id  = ut.persona_id
            WHERE al.matricula_id = ?
            ORDER BY al.created_at DESC
        ", [$matriculaId]);
    }
}