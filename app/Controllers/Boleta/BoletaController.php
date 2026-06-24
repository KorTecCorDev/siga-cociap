<?php

namespace App\Controllers\Boleta;

use App\Controllers\BaseController;
use App\Models\BoletaModel;
use App\Models\BoletaPublicaModel;
use App\Models\CalificacionModel;
use Core\Session;
use Core\View;

/**
 * BoletaController
 *
 * Entry points DELGADOS de la boleta. Cada metodo solo decide QUIEN entra y
 * QUE periodo se muestra; el ensamblado del documento vive en BoletaModel y
 * el QR sale SIEMPRE del token permanente (urlBoletaToken).
 *
 * Direccionamiento:
 *  - Publico (familias): SIEMPRE por token, solo bimestres oficiales (cerrados).
 *  - Interno (docente/admin): autenticado por id+alcance, puede ver BORRADOR.
 *
 * No existen rutas anonimas por id (se retiraron `ver`/`verDigital`).
 */
class BoletaController extends BaseController
{
    private CalificacionModel  $calModel;
    private BoletaModel        $boletaModel;
    private BoletaPublicaModel $boletaPublicaModel;

    public function __construct()
    {
        $this->calModel           = new CalificacionModel();
        $this->boletaModel        = new BoletaModel();
        $this->boletaPublicaModel = new BoletaPublicaModel();
    }

    /**
     * URL del QR PERMANENTE de un estudiante: el enlace publico por token, que
     * se mantiene durante todo el anio academico (un solo QR por estudiante). Se
     * rotula con la matricula IDENTIDAD (oficial) para que coincida con el QR
     * que ven los padres, incluso en retorno de grado.
     */
    private function urlBoletaToken(int $matriculaId): string
    {
        $identidad = (int) $this->calModel->boletaContexto($matriculaId)['identidad'];
        $token     = $this->boletaPublicaModel->getOCrearToken($identidad);

        return url("boleta/digital/{$token}");
    }

    /**
     * GET /boleta/ver/{token}
     * Vista imprimible publica sin login — resuelve token -> matricula + periodo.
     */
    public function verToken(string $token): void
    {
        ['matricula_id' => $matriculaId, 'periodo_id' => $periodoId] = $this->resolveToken($token);
        $this->render($matriculaId, $periodoId, 'print', [
            'soloOficiales'   => true,
            'registrarVisita' => true,
        ]);
    }

    /**
     * GET /boleta/digital/{token}
     * Vista digital publica sin login — resuelve token -> matricula + periodo.
     * Destino del QR: aqui se registra cada visita por token.
     */
    public function verDigitalToken(string $token): void
    {
        ['matricula_id' => $matriculaId, 'periodo_id' => $periodoId] = $this->resolveToken($token);
        $this->render($matriculaId, $periodoId, 'digital', [
            'soloOficiales'   => true,
            'registrarVisita' => true,
        ]);
    }

    /**
     * GET /docente/boleta/{matricula_id}
     * Boleta DIGITAL para el docente. Validada por alcance: solo alumnos de un
     * nivel donde el docente tiene carga activa. El periodo se resuelve solo.
     */
    public function verDigitalDocente($matriculaId): void
    {
        $this->requireRole(['docente', 'admin']);
        $matriculaId = (int) $matriculaId;
        $periodoId   = $this->resolverBoletaDocente($matriculaId);

        $this->render($matriculaId, $periodoId, 'digital', [
            'vistaPrevia' => $this->estadoBoletaDePeriodo($periodoId) !== 'oficial',
        ]);
    }

    /**
     * GET /docente/boleta/{matricula_id}/imprimir
     * Boleta IMPRIMIBLE (A4) para el docente. Mismo alcance que verDigitalDocente.
     */
    public function verImprimirDocente($matriculaId): void
    {
        $this->requireRole(['docente', 'admin']);
        $matriculaId = (int) $matriculaId;
        $periodoId   = $this->resolverBoletaDocente($matriculaId);

        $this->render($matriculaId, $periodoId, 'print', [
            'vistaPrevia' => $this->estadoBoletaDePeriodo($periodoId) !== 'oficial',
        ]);
    }

    /**
     * Render UNICO de la boleta. Arma los datos via BoletaModel, fija el QR
     * SIEMPRE desde el token permanente y elige layout/vista.
     *
     * @param array $opts ['soloOficiales'=>bool, 'vistaPrevia'=>bool, 'registrarVisita'=>bool]
     */
    private function render(int $matriculaId, int $periodoId, string $layout, array $opts = []): void
    {
        $data = $this->boletaModel->armar($matriculaId, $periodoId, $opts['soloOficiales'] ?? false);

        if (!$data) {
            http_response_code(404);
            require VIEW_PATH . '/shared/404.php';
            exit;
        }

        // La boleta se rotula con la matrícula IDENTIDAD (oficial). El conteo y el
        // QR se anclan a ella para que coincidan aun en retorno de grado.
        $identidad = (int) $data['alumno']['matricula_id'];

        // Cuenta toda consulta que pase por el token (escaneo de QR o portal).
        if ($opts['registrarVisita'] ?? false) {
            $this->boletaPublicaModel->registrarVisitaToken($identidad);
        }

        $vista  = $layout === 'print' ? 'boleta/alumno' : 'boleta/digital';
        $rotulo = $layout === 'print' ? 'Boleta — ' : 'Boleta Digital — ';

        View::setLayout($layout);
        $this->view($vista, array_merge($data, [
            'titulo'      => $rotulo . $data['alumno']['nombre_completo'],
            'url_boleta'  => $this->urlBoletaToken($identidad),
            'vistaPrevia' => $opts['vistaPrevia'] ?? false,
        ]));
    }

    /**
     * Valida que el docente actual pueda ver la boleta de $matriculaId (alumno en
     * un nivel donde tiene carga activa) y devuelve el periodo a mostrar: el más
     * reciente con notas bloqueadas, con fallback al primer periodo del año.
     * Responde 403 si está fuera de alcance, 404 si no hay periodos.
     */
    private function resolverBoletaDocente(int $matriculaId): int
    {
        $docenteId = (int) Session::user()['id'];

        // Alcance: la matrícula existe, no está desactivada y su NIVEL coincide con
        // un nivel donde el docente tiene carga activa. Evita abrir boletas fuera
        // de alcance manipulando el id en la URL.
        $mat = $this->calModel->queryOne("
            SELECT m.id, m.anio_id
            FROM matriculas m
            INNER JOIN secciones s ON s.id = m.seccion_id
            INNER JOIN grados g    ON g.id = s.grado_id
            WHERE m.id = ?
              AND m.estado <> 'desactivado'
              AND g.nivel_id IN (
                  SELECT DISTINCT g2.nivel_id
                  FROM cargas_academicas ca
                  INNER JOIN secciones s2 ON s2.id = ca.seccion_id
                  INNER JOIN grados g2    ON g2.id = s2.grado_id
                  WHERE ca.docente_id = ? AND ca.estado = 'activa'
              )
            LIMIT 1
        ", [$matriculaId, $docenteId]);

        if (!$mat) {
            http_response_code(403);
            $this->view('shared/403');
            exit;
        }

        $anioId = (int) $mat['anio_id'];

        // Solo periodos PUBLICABLES: cerrado (OFICIAL) o activo con boletas
        // aprobadas (BORRADOR, Hito A). Un bimestre en registro aun NO tiene
        // boleta para el docente -> se muestra hasta el ultimo publicable.
        $periodo = $this->calModel->queryOne("
            SELECT p.id
            FROM periodos p
            WHERE p.anio_id = ?
              AND (p.estado = 'cerrado'
                   OR (p.estado = 'activo' AND p.boletas_aprobadas_en IS NOT NULL))
              AND EXISTS (
                  SELECT 1 FROM calificaciones cal
                  INNER JOIN bloqueos_competencia bc
                      ON bc.carga_id = cal.carga_id
                     AND bc.competencia_id = cal.competencia_id
                     AND bc.periodo_id = cal.periodo_id
                  WHERE cal.matricula_id = ? AND cal.periodo_id = p.id
              )
            ORDER BY p.numero DESC
            LIMIT 1
        ", [$anioId, $matriculaId]);

        if (!$periodo) {
            http_response_code(404);
            require VIEW_PATH . '/shared/404.php';
            exit;
        }

        return (int) $periodo['id'];
    }

    /** Estado de boleta ('registro'|'borrador'|'oficial') de un periodo. */
    private function estadoBoletaDePeriodo(int $periodoId): string
    {
        $p = $this->calModel->queryOne(
            "SELECT estado, boletas_aprobadas_en FROM periodos WHERE id = ? LIMIT 1",
            [$periodoId]
        );
        return boleta_estado_bimestre($p['estado'] ?? null, $p['boletas_aprobadas_en'] ?? null);
    }

    /**
     * Resuelve un token a matricula_id + periodo_id.
     * Elige el período más reciente con competencias bloqueadas;
     * si no hay ninguno, usa el primer período del año.
     * Termina con 404 si el token no existe o no hay períodos.
     */
    private function resolveToken(string $token): array
    {
        // El token es bin2hex(random_bytes(16)) = 32 hex en minúsculas. Validar
        // el formato antes de consultar rechaza basura/payloads de inmediato,
        // con el mismo 404 que un token inexistente. No afecta los QR ya
        // emitidos: todos los tokens vigentes cumplen este patrón.
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            http_response_code(404);
            require VIEW_PATH . '/shared/404.php';
            exit;
        }

        // Una matrícula 'desactivado' (baja o traslado) no expone su boleta por
        // token aunque el QR impreso siga circulando: se trata como inexistente.
        $matricula = $this->calModel->queryOne(
            "SELECT id, anio_id FROM matriculas
             WHERE token_acceso = ? AND estado <> 'desactivado' LIMIT 1",
            [$token]
        );

        if (!$matricula) {
            http_response_code(404);
            require VIEW_PATH . '/shared/404.php';
            exit;
        }

        $matriculaId = (int) $matricula['id'];
        $anioId      = (int) $matricula['anio_id'];

        $periodo = $this->calModel->queryOne("
            SELECT p.id
            FROM periodos p
            WHERE p.anio_id = ?
              AND EXISTS (
                  SELECT 1
                  FROM calificaciones cal
                  INNER JOIN bloqueos_competencia bc
                      ON bc.carga_id       = cal.carga_id
                     AND bc.competencia_id = cal.competencia_id
                     AND bc.periodo_id     = cal.periodo_id
                  WHERE cal.matricula_id = ? AND cal.periodo_id = p.id
              )
            ORDER BY p.numero DESC
            LIMIT 1
        ", [$anioId, $matriculaId]);

        if (!$periodo) {
            $periodo = $this->calModel->queryOne(
                "SELECT id FROM periodos WHERE anio_id = ? ORDER BY numero ASC LIMIT 1",
                [$anioId]
            );
        }

        if (!$periodo) {
            http_response_code(404);
            require VIEW_PATH . '/shared/404.php';
            exit;
        }

        return ['matricula_id' => $matriculaId, 'periodo_id' => (int) $periodo['id']];
    }
}
