<?php

/**
 * Verificación — estructura anual completa en TODAS las vistas de boleta, sin
 * filtrar datos de bimestres que aún no corresponden.
 * Uso: php database/verificaciones/verif_estructura_boleta.php
 *
 * SOLO LECTURA: no escribe nada. Se puede correr en PRODUCCION.
 *
 * CONTEXTO (04/08/2026). La REGLA DE FORMATO del 09/07/2026 —las 4 columnas de
 * bimestre siempre, aunque esten vacias— se habia aplicado solo a
 * `/boleta/ver/{token}` y a la boleta del trasladado. La IMPRESION MASIVA
 * (`/admin/boletas-publicas/{id}/boletas-alumno`) y el ZIP de archivo llamaban a
 * `armar()` sin el 4º parametro, asi que colapsaban columnas: el documento que RA
 * firma y entrega salia con OTRO formato que el que la familia abre por QR, siendo
 * el MISMO componente (`boleta/alumno.php`). Ahora las 9 entradas piden estructura
 * anual completa.
 *
 * QUE COMPRUEBA, para cada umbral:
 *   1. ESTRUCTURA: siempre 4 columnas de bimestre (o tantas como periodos tenga el
 *      anio), independiente de cuantos esten cerrados.
 *   2. DATOS: abrir las columnas NO relaja el guard. Los bimestres que aportan
 *      notas siguen siendo los que corresponden al umbral:
 *        - 'oficial'  -> cerrados Y publicados al nivel del alumno;
 *        - 'archivo'  -> cerrados;
 *        - 'borrador' -> cerrados o activos con Hito A;
 *        - 'todos'    -> todos.
 *      Es la aserción de seguridad: una columna vacia es formato, no una fuga.
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

$pdo     = Core\Database::connect();
$boletas = new App\Models\BoletaModel();
$fallos  = 0;

$check = static function (string $caso, string $esperado, string $obtenido) use (&$fallos): void {
    if ($esperado === $obtenido) {
        printf("  OK    %-50s [%s]\n", $caso, $obtenido);
        return;
    }
    $fallos++;
    printf("  ***   %-50s esperado [%s] · obtenido [%s]\n", $caso, $esperado, $obtenido);
};

// Alumno con notas reales, para que "aporta datos" signifique algo.
$mat = $pdo->query("
    SELECT cal.matricula_id, m.seccion_id
    FROM calificaciones cal
    INNER JOIN matriculas m ON m.id = cal.matricula_id
    WHERE m.estado = 'aprobada' AND cal.nota_numerica IS NOT NULL
    GROUP BY cal.matricula_id
    HAVING COUNT(*) > 5
    ORDER BY cal.matricula_id LIMIT 1
")->fetch();
if (!$mat) {
    fwrite(STDERR, "ABORTA: no hay ninguna matricula con notas para probar.\n");
    exit(1);
}
$matriculaId = (int) $mat['matricula_id'];

$periodos = $pdo->query("
    SELECT id, numero, estado, boletas_aprobadas_en
    FROM periodos
    WHERE anio_id = (SELECT id FROM anios_academicos WHERE estado = 'activo' LIMIT 1)
    ORDER BY numero
")->fetchAll();
$totalPeriodos = count($periodos);

// Periodos PUBLICADOS para el nivel de este alumno. Se pregunta al MISMO punto
// que usa la boleta (`BoletaModel` línea ~99) en vez de replicar la regla aquí.
//
// 🔴 POR QUÉ SE DELEGA, Y NO SE COPIA (22/08/2026): este bloque tenía su propia
// copia de la compuerta —`primera_publicacion_en IS NOT NULL`— que NO es la
// regla que aplica la boleta. `periodosPublicados()` corta por `publica_en <=
// ahora` y ni siquiera mira ese sello. La divergencia estuvo LATENTE mientras
// todas las publicaciones fueron INMEDIATAS (con ellas ambas ramas coinciden) y
// se activó en cuanto venció la primera publicación PROGRAMADA —B2, el 13 y 14
// de agosto—, cuyas filas conservan `primera_publicacion_en` en NULL: el
// verificador daba por NO publicado un bimestre que las familias ya veían, y
// acusaba de fuga a un guard que funcionaba.
//
// Que el esperado salga del mismo modelo NO debilita esta verificación: aquí se
// prueba el GUARD DE DATOS de la boleta (que 'oficial' deje pasar solo cerrados
// Y publicados), no la compuerta en sí. La compuerta tiene su propio verificador
// con escenarios forzados: `verif_merito_nomina_compuerta.php`.
$ctx = $pdo->prepare("
    SELECT g.nivel_id, m.anio_id
    FROM matriculas m
    INNER JOIN secciones s ON s.id = m.seccion_id
    INNER JOIN grados    g ON g.id = s.grado_id
    WHERE m.id = ?
");
$ctx->execute([$matriculaId]);
$ctxAlumno = $ctx->fetch();
if (!$ctxAlumno) {
    fwrite(STDERR, "ABORTA: no se pudo resolver el nivel del alumno de prueba.\n");
    exit(1);
}
$publicados = array_keys((new App\Models\PublicacionBoletaModel())->periodosPublicados(
    (int) $ctxAlumno['anio_id'],
    (int) $ctxAlumno['nivel_id']
));

// Bimestres que REALMENTE tienen notas de este alumno en la BD. Sin esto, el
// umbral 'todos' esperaria los 4 y fallaria por los que nadie califico todavia:
// el guard puede DEJAR PASAR un bimestre y aun asi no haber nada que mostrar.
$stNotas = $pdo->prepare("
    SELECT DISTINCT p.numero
    FROM calificaciones cal
    INNER JOIN periodos p ON p.id = cal.periodo_id
    WHERE cal.matricula_id = ? AND cal.nota_numerica IS NOT NULL
    ORDER BY p.numero
");
$stNotas->execute([$matriculaId]);
$numsConNotas = array_map('intval', array_column($stNotas->fetchAll(), 'numero'));

/** Numeros de bimestre que DEBEN aportar notas: los que el umbral deja pasar Y tienen notas. */
$esperaDatos = static function (string $modo) use ($periodos, $publicados, $numsConNotas): array {
    $out = [];
    foreach ($periodos as $p) {
        $estado = boleta_estado_bimestre($p['estado'], $p['boletas_aprobadas_en']);
        $ok = match ($modo) {
            'oficial'  => $estado === 'oficial' && in_array((int) $p['id'], $publicados, true),
            'archivo'  => $estado === 'oficial',
            'borrador' => $estado !== 'registro',
            'todos'    => true,
        };
        if ($ok && in_array((int) $p['numero'], $numsConNotas, true)) {
            $out[] = (int) $p['numero'];
        }
    }
    return $out;
};

/** Numeros de bimestre que REALMENTE traen alguna nota en la boleta armada. */
$conDatos = static function (array $data): array {
    $porId = [];
    foreach ($data['periodos'] as $p) { $porId[(int) $p['id']] = (int) $p['numero']; }
    $nums = [];
    foreach ($data['areas'] as $area) {
        foreach ($area as $comp) {
            foreach (($comp['bimestres'] ?? []) as $pid => $b) {
                if (!is_array($b)) { continue; }
                $tieneNota = ($b['literal'] ?? null) !== null || ($b['nota'] ?? null) !== null;
                if ($tieneNota && isset($porId[(int) $pid])) { $nums[$porId[(int) $pid]] = true; }
            }
        }
    }
    $nums = array_keys($nums);
    sort($nums);
    return $nums;
};

$verPeriodo = (int) $periodos[0]['id'];

echo "=== Escenario (matricula {$matriculaId}) ===\n";
foreach ($periodos as $p) {
    printf("   bimestre %d: estado=%-9s hito_A=%-2s publicado=%s\n",
        $p['numero'], $p['estado'], $p['boletas_aprobadas_en'] ? 'si' : 'no',
        in_array((int) $p['id'], $publicados, true) ? 'si' : 'no');
}

echo "\n=== 1. ESTRUCTURA: {$totalPeriodos} columnas en todos los umbrales ===\n";
foreach (['oficial', 'archivo', 'borrador', 'todos'] as $modo) {
    $d = $boletas->armar($matriculaId, $verPeriodo, $modo, true);
    $nums = array_map(fn($p) => (int) $p['numero'], $d['periodos']);
    $check("'{$modo}' con estructuraCompleta", implode(' ', range(1, $totalPeriodos)), implode(' ', $nums));
}

echo "\n=== 2. DATOS: abrir columnas NO relaja el guard ===\n";
foreach (['oficial', 'archivo', 'borrador', 'todos'] as $modo) {
    $d = $boletas->armar($matriculaId, $verPeriodo, $modo, true);
    $check("'{$modo}' aporta notas solo de", implode(' ', $esperaDatos($modo)), implode(' ', $conDatos($d)));
}

echo "\n=== 3. Control: sin estructuraCompleta los datos son LOS MISMOS ===\n";
echo "    (si difirieran, el flag estaria filtrando datos y no solo formato)\n";
foreach (['oficial', 'archivo'] as $modo) {
    $con = $conDatos($boletas->armar($matriculaId, $verPeriodo, $modo, true));
    $sin = $conDatos($boletas->armar($matriculaId, $verPeriodo, $modo, false));
    $check("'{$modo}': mismos bimestres con datos", implode(' ', $sin), implode(' ', $con));
}

echo "\n" . ($fallos === 0
    ? "RESULTADO: OK — estructura anual completa sin fuga de datos.\n"
    : "RESULTADO: {$fallos} FALLO(S).\n");

exit($fallos === 0 ? 0 : 1);
