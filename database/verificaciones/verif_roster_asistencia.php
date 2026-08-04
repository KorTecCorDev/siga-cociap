<?php

/**
 * Verificación — el roster de asistencia es el mismo que el de la grilla de notas.
 * Uso: php database/verificaciones/verif_roster_asistencia.php
 *
 * SOLO LECTURA: no escribe nada, no abre transacciones. Se puede correr en
 * PRODUCCIÓN (por eso NO lleva el guard de secretos que sí tienen los que escriben).
 *
 * QUÉ COMPRUEBA
 *   1. Para cada sección del año activo, `AsistenciaModel::getEstudiantesConIncidencias`
 *      devuelve EXACTAMENTE las mismas matrículas que la grilla del docente
 *      (`Docente\CalificacionController::getAlumnosSeccion`, replicada aquí porque
 *      es privada — mismo criterio: tipo + las dos exclusiones de retorno de grado).
 *   2. Los `esperados` de `getProgresoPorSeccion` (barra del índice y de la card de
 *      bloqueos) cuadran con el tamaño de esa grilla. Si no, el avance miente.
 *   3. IMPACTO del cambio del 04/08/2026 frente a la regla vieja (`m.estado='aprobada'`,
 *      sin filtro de tipo ni retornos): quién entra, quién sale, y cuántas filas de
 *      `inasistencias` quedan fuera del roster. Correrlo ANTES de desplegar en prod
 *      dice exactamente qué documentos cambian.
 *
 * CONTEXTO: hasta el 04/08/2026 la asistencia filtraba por `m.estado='aprobada'` e
 * ignoraba `tipo` y el retorno de grado. Dejaba fuera a los 'pendiente' y
 * 'desactivado' —que sí asisten— y su boleta salía con 0 inasistencias: un dato
 * falso, no ausente. Y metía la matrícula oficial de un retorno activo, o sea el
 * grado donde la estudiante ya no está.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

spl_autoload_register(function (string $class): void {
    $map = ['Core\\' => CORE_PATH . '/', 'App\\Models\\' => APP_PATH . '/Models/'];
    foreach ($map as $prefix => $base) {
        if (str_starts_with($class, $prefix)) {
            $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (file_exists($file)) { require_once $file; return; }
        }
    }
});
require_once CONFIG_PATH . '/app.php';
require_once APP_PATH . '/Helpers/helpers.php';

$pdo   = Core\Database::connect();
$model = new App\Models\AsistenciaModel();

$fallos = 0;

/** Roster canónico: copia literal de Docente\CalificacionController::getAlumnosSeccion. */
$rosterNotas = static function (int $seccionId) use ($pdo): array {
    $st = $pdo->prepare("
        SELECT m.id
        FROM matriculas m
        INNER JOIN estudiantes e ON e.id = m.estudiante_id
        INNER JOIN personas p    ON p.id = e.persona_id
        WHERE m.seccion_id = ?
          AND m.tipo NOT IN ('trasladado', 'retirado')
          AND m.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
          AND m.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
    ");
    $st->execute([$seccionId]);
    $ids = array_map('intval', array_column($st->fetchAll(), 'id'));
    sort($ids);
    return $ids;
};

$anio = $pdo->query("SELECT id, anio FROM anios_academicos WHERE estado = 'activo' LIMIT 1")->fetch();
if (!$anio) {
    fwrite(STDERR, "ABORTA: no hay año académico activo.\n");
    exit(1);
}

$secciones = $pdo->prepare("
    SELECT s.id, g.nombre_display AS grado, s.nombre AS seccion, n.nombre AS nivel
    FROM secciones s
    INNER JOIN grados g  ON g.id = s.grado_id
    INNER JOIN niveles n ON n.id = g.nivel_id
    WHERE s.anio_id = ?
    ORDER BY n.id, g.numero, s.nombre
");
$secciones->execute([(int) $anio['id']]);
$secciones = $secciones->fetchAll();

$periodo = $pdo->query("
    SELECT id, numero FROM periodos
    WHERE anio_id = (SELECT id FROM anios_academicos WHERE estado = 'activo' LIMIT 1)
      AND estado = 'activo'
    LIMIT 1
")->fetch();
$periodoId = $periodo ? (int) $periodo['id'] : 0;

echo "=== Año {$anio['anio']} · {$periodoId} = periodo activo · " . count($secciones) . " secciones ===\n\n";

echo "=== 1. Grilla de asistencia == grilla de notas ===\n";
foreach ($secciones as $s) {
    $sid       = (int) $s['id'];
    $esperado  = $rosterNotas($sid);
    $obtenido  = array_map('intval', array_column(
        $model->getEstudiantesConIncidencias($sid, $periodoId), 'matricula_id'
    ));
    sort($obtenido);

    if ($esperado === $obtenido) {
        printf("  OK   %-10s %-3s  %2d alumnos\n", $s['grado'], $s['seccion'], count($obtenido));
        continue;
    }
    $fallos++;
    printf("  *** DIFIERE %s %s — notas=%d asistencia=%d\n",
        $s['grado'], $s['seccion'], count($esperado), count($obtenido));
    echo "        sobran en asistencia: " . json_encode(array_values(array_diff($obtenido, $esperado))) . "\n";
    echo "        faltan en asistencia: " . json_encode(array_values(array_diff($esperado, $obtenido))) . "\n";
}

echo "\n=== 2. 'esperados' del progreso == tamaño de la grilla ===\n";
$progreso = $model->getProgresoPorSeccion($periodoId);
foreach ($secciones as $s) {
    $sid      = (int) $s['id'];
    $enGrilla = count($model->getEstudiantesConIncidencias($sid, $periodoId));
    $esp      = (int) ($progreso[$sid]['esperados'] ?? -1);

    if ($esp === $enGrilla) {
        continue;
    }
    // Las secciones sin nómina aprobada no entran al progreso: no es un fallo.
    if ($esp === -1) {
        printf("  nota %-10s %-3s  fuera del progreso (nómina no aprobada) · grilla=%d\n",
            $s['grado'], $s['seccion'], $enGrilla);
        continue;
    }
    $fallos++;
    printf("  *** DIFIERE %s %s — esperados=%d grilla=%d\n", $s['grado'], $s['seccion'], $esp, $enGrilla);
}
if ($fallos === 0) {
    echo "  OK   todas las secciones cuadran\n";
}

echo "\n=== 3. Impacto frente a la regla vieja (m.estado='aprobada', sin tipo ni retornos) ===\n";
$impacto = $pdo->prepare("
    SELECT CASE WHEN vieja.id IS NULL THEN 'ENTRA (antes invisible)' ELSE 'SALE (antes visible)' END AS caso,
           m.id AS matricula, m.estado, m.tipo,
           CONCAT(g.nombre_display, ' ', s.nombre) AS seccion,
           CONCAT(p.apellido_paterno, ' ', p.apellido_materno, ', ', p.nombres) AS alumno,
           (SELECT COUNT(*) FROM inasistencias i WHERE i.matricula_id = m.id) AS filas_asistencia
    FROM matriculas m
    INNER JOIN secciones s     ON s.id = m.seccion_id AND s.anio_id = ?
    INNER JOIN grados g        ON g.id = s.grado_id
    INNER JOIN estudiantes e   ON e.id = m.estudiante_id
    INNER JOIN personas p      ON p.id = e.persona_id
    LEFT JOIN (
        SELECT m2.id FROM matriculas m2
        INNER JOIN secciones s2 ON s2.id = m2.seccion_id AND s2.anio_id = ?
        WHERE m2.estado = 'aprobada' AND m2.anio_id = ?
    ) vieja ON vieja.id = m.id
    LEFT JOIN (
        SELECT m3.id FROM matriculas m3
        INNER JOIN secciones s3 ON s3.id = m3.seccion_id AND s3.anio_id = ?
        WHERE m3.tipo NOT IN ('trasladado', 'retirado')
          AND m3.id NOT IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
          AND m3.id NOT IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
    ) nueva ON nueva.id = m.id
    WHERE (vieja.id IS NULL) <> (nueva.id IS NULL)
    ORDER BY caso, seccion, alumno
");
$aid = (int) $anio['id'];
$impacto->execute([$aid, $aid, $aid, $aid]);
$filas = $impacto->fetchAll();

if (!$filas) {
    echo "  ninguna matrícula cambia de estado — el despliegue no altera ningún registro\n";
} else {
    printf("  %d matrícula(s) cambian:\n", count($filas));
    foreach ($filas as $f) {
        printf("    %-24s m=%-4d %-11s %-12s %-10s %-45s filas_asist=%d\n",
            $f['caso'], $f['matricula'], $f['estado'], $f['tipo'],
            $f['seccion'], $f['alumno'], $f['filas_asistencia']);
    }
    echo "  ^ las que SALEN con filas_asist>0 conservan sus datos: siguen sumando en la\n";
    echo "    boleta (getDelBimestreUnion va por matrícula), pero ya no se editan por grilla.\n";
}

echo "\n=== 4. Filas de inasistencias fuera del roster ===\n";
$huerfanas = $pdo->query("
    SELECT COUNT(*) AS n
    FROM inasistencias i
    INNER JOIN matriculas m ON m.id = i.matricula_id
    WHERE m.tipo IN ('trasladado', 'retirado')
       OR m.id IN (SELECT matricula_oficial_id   FROM retornos_grado WHERE estado = 'activo')
       OR m.id IN (SELECT matricula_operativa_id FROM retornos_grado WHERE estado = 'revertido')
")->fetch();
printf("  %d fila(s) pertenecen a matrículas fuera del roster (dato histórico, no se borra)\n",
    (int) $huerfanas['n']);

echo "\n" . ($fallos === 0
    ? "RESULTADO: OK — el roster de asistencia coincide con el de notas.\n"
    : "RESULTADO: {$fallos} FALLO(S).\n");

exit($fallos === 0 ? 0 : 1);
