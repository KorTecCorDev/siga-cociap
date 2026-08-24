<?php

/**
 * Verificación de la FASE 1A — punto único ROLES_DIRECCION.
 * Solo lectura: no toca la base de datos ni escribe archivos.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
require ROOT_PATH . '/app/Helpers/helpers.php';

$ok  = true;
$chk = function (string $t, bool $c) use (&$ok) {
    printf("  [%s] %s\n", $c ? 'OK ' : 'FAIL', $t);
    $ok = $ok && $c;
};
$leer = fn(string $r): string => file_get_contents(ROOT_PATH . $r);

echo "1) La constante\n";
$chk(
    'ROLES_DIRECCION trae los 3 codigos, en orden',
    ROLES_DIRECCION === ['director_general', 'director_ebr', 'director_academico']
);

echo "\n2) Constantes de clase — el spread resuelve\n";
$esperado = ['admin', 'registro_academico', 'director_general', 'director_ebr', 'director_academico'];
foreach (['AnioAcademico', 'Periodo', 'CargaAcademica'] as $c) {
    preg_match('/private const ROLES = (\[.*?\]);/s', $leer("/app/Controllers/Director/{$c}Controller.php"), $m);
    $chk("{$c}Controller::ROLES = admin + RA + los 3 directores", eval("return {$m[1]};") === $esperado);
}

echo "\n3) Los DOS anclajes de firma siguen en SINGULAR\n";
foreach ([
    'DirectorEbrModel (lista candidatos)'          => '/app/Models/DirectorEbrModel.php',
    'Admin\DirectorEbrController (revalida)'       => '/app/Controllers/Admin/DirectorEbrController.php',
] as $nombre => $ruta) {
    $src = $leer($ruta);
    $chk("$nombre ancla a 'director_ebr' y NO usa la constante",
        str_contains($src, "'director_ebr'") && !str_contains($src, 'ROLES_DIRECCION'));
}

echo "\n4) El rol fosil 'secretaria' murio\n";
// Se mira el ARRAY de requireRole, no el archivo entero: el comentario que
// documenta la retirada menciona el codigo a proposito.
preg_match('/requireRole\(\[(.*?)\]\)/s', $leer('/app/Controllers/Admin/BuscadorEstudianteController.php'), $mm);
$chk("el array de requireRole ya no lista 'secretaria'", !str_contains($mm[1], "'secretaria'"));

echo "\n5) La tabla director_ebr_historial quedo intacta (trampa C2)\n";
// Contra git HEAD, no contra un numero magico: prueba que el reemplazo literal
// no toco el nombre de la tabla.
$hoy  = substr_count($leer('/app/Models/DirectorEbrModel.php'), 'director_ebr_historial');
$head = substr_count((string) shell_exec('git -C ' . escapeshellarg(ROOT_PATH) . ' show HEAD:app/Models/DirectorEbrModel.php'), 'director_ebr_historial');
$chk("aparece $hoy veces, igual que en git HEAD ($head)", $hoy === $head && $hoy > 0);

echo "\n6) Literales de rol que sobreviven — SOLO los 2 anclajes de firma\n";
// Al terminar la fase 5 no queda ningun listado de roles de direccion escrito a
// mano: los mapas de destino de Auth/Dashboard se borraron, y Reemplazo y
// Retorno pasaron a la constante o perdieron al director. Lo unico que puede
// nombrar 'director_ebr' en CODIGO es el par que sostiene "solo el EBR firma".
// Barrido en PHP puro: el escapado de comillas simples hacia el shell es
// justamente lo que hace fragil a un grep de literales entrecomillados.
$lineas = [];
foreach (['/app', '/resources', '/routes'] as $dir) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT_PATH . $dir));
    foreach ($it as $file) {
        if (!preg_match('/\.(php|js)$/', $file->getFilename())) {
            continue;
        }
        if ($file->getFilename() === 'helpers.php') {
            continue;
        }
        foreach (file($file->getPathname()) as $i => $linea) {
            // Se ignoran los comentarios: varios documentan a proposito por que
            // esos dos anclajes siguen en singular.
            $sinComentario = preg_replace('#(//|\*|/\*).*$#', '', $linea);
            if (str_contains($sinComentario, "'director_general'") || str_contains($sinComentario, "'director_ebr'")) {
                $lineas[] = str_replace('\\', '/', $file->getPathname()) . ':' . ($i + 1) . ': ' . trim($linea);
            }
        }
    }
}
sort($lineas);
$chk('quedan exactamente 2 literales, y son los de firma', count($lineas) === 2);
foreach ($lineas as $l) {
    echo '       · ' . trim(preg_replace('#^.*/siga_cociap/#', '', $l)) . "\n";
}

echo "\n", $ok ? "== FASE 1A EN VERDE ==\n" : "== HAY FALLOS ==\n";
exit($ok ? 0 : 1);
