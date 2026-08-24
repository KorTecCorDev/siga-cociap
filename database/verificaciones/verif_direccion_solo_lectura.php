<?php

/**
 * Verificación de la FASE 3 — retirada de la escritura a los directores.
 * Solo lectura sobre el código fuente.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
require ROOT_PATH . '/app/Helpers/helpers.php';

$ok  = true;
$chk = function (string $t, bool $c) use (&$ok) {
    printf("  [%s] %s\n", $c ? 'OK ' : 'FAIL', $t);
    $ok = $ok && $c;
};
$leer = fn(string $r): string => file_get_contents(ROOT_PATH . $r);

// ── 1. Cada metodo de escritura lleva su guarda ──────────────────
$plan = [
    '/app/Controllers/Director/AnioAcademicoController.php'    => ['create', 'store', 'activar', 'cerrar'],
    '/app/Controllers/Director/PeriodoController.php'          => ['editar', 'abrir', 'cerrar', 'reabrir'],
    '/app/Controllers/Director/CargaAcademicaController.php'   => ['create', 'store', 'edit', 'update', 'toggleEstado'],
    '/app/Controllers/Director/ReemplazoDocenteController.php' => ['form', 'reemplazar'],
    '/app/Controllers/Director/OrdenMeritoController.php'      => ['desempate', 'guardarDesempate'],
    '/app/Controllers/Admin/ControlOperativoController.php'    => ['aprobarBimestre', 'anularAprobacion'],
    '/app/Controllers/Director/BloqueoController.php'          => [
        'bloquear', 'limpiarBloqueosCierre', 'desbloquear',
        'cerrarTransversal', 'reabrirTransversal', 'liberarTransversalCompetencia',
        'bloquearConducta', 'cerrarConducta', 'reabrirConducta',
        'bloquearAsistencia', 'reabrirAsistencia',
    ],
];

echo "1) Guarda de escritura como PRIMERA sentencia de cada metodo\n";
$totalMetodos = 0;
foreach ($plan as $rel => $metodos) {
    $src = $leer($rel);
    $mal = [];
    foreach ($metodos as $m) {
        $totalMetodos++;
        $patron = '/public function ' . preg_quote($m, '/')
                . '\([^)]*\)(?:\s*:\s*\w+)?\s*\r?\n\s*\{\s*\r?\n\s*\$this->requireRole\(self::ROLES_ESCRIBEN\);/';
        if (!preg_match($patron, $src)) { $mal[] = $m; }
    }
    $chk(basename($rel) . ' — ' . count($metodos) . ' metodos guardados'
        . ($mal ? ' | SIN GUARDA: ' . implode(', ', $mal) : ''), empty($mal));
}
$chk("total de metodos de escritura guardados = 30", $totalMetodos === 30);

echo "\n2) ROLES_ESCRIBEN vale lo mismo en los 7 controladores\n";
foreach (array_keys($plan) as $rel) {
    preg_match('/private const ROLES_ESCRIBEN = (\[[^\]]*\]);/', $leer($rel), $m);
    $chk(basename($rel) . " = ['admin','registro_academico']",
        isset($m[1]) && eval("return {$m[1]};") === ['admin', 'registro_academico']);
}

echo "\n3) Constructores — quien ENTRA (lectura)\n";
$entra = [
    '/app/Controllers/Director/BloqueoController.php'          => "['admin', 'registro_academico', ...ROLES_DIRECCION]",
    '/app/Controllers/Director/ReemplazoDocenteController.php' => "['admin', 'registro_academico', ...ROLES_DIRECCION]",
    '/app/Controllers/Matricula/RetornoGradoController.php'    => "['admin', 'registro_academico']",
    '/app/Controllers/Admin/DirectorEbrController.php'         => "['admin', 'registro_academico']",
];
foreach ($entra as $rel => $esperado) {
    $chk(basename($rel) . " entra con $esperado", str_contains($leer($rel), $esperado));
}
$chk('RetornoGradoController ya NO deja entrar a ningun director',
    !str_contains($leer('/app/Controllers/Matricula/RetornoGradoController.php'), 'ROLES_DIRECCION')
    && !str_contains($leer('/app/Controllers/Matricula/RetornoGradoController.php'), "'director_ebr'"));

echo "\n4) INTEGRIDAD DE VISTAS — toda vista que usa \$puedeEscribir lo recibe\n";
// Es la comprobacion que atrapo el bug del Centro de Control: el flag se habia
// insertado en la rama de salida temprana y NO en la que pinta los botones.
$vistas = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT_PATH . '/resources/views'));
foreach ($it as $f) {
    if (!str_ends_with($f->getFilename(), '.php')) { continue; }
    if (!str_contains(file_get_contents($f->getPathname()), '$puedeEscribir')) { continue; }
    $rel = str_replace('\\', '/', $f->getPathname());
    $vistas[] = substr($rel, strpos($rel, 'resources/views/') + 16, -4);
}

$ctrls = '';
$it2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(ROOT_PATH . '/app/Controllers'));
foreach ($it2 as $f) {
    if (str_ends_with($f->getFilename(), '.php')) { $ctrls .= file_get_contents($f->getPathname()); }
}

foreach ($vistas as $v) {
    // Cada llamada a esa vista debe pasar 'puedeEscribir' en su array.
    preg_match_all('/view\(\x27' . preg_quote($v, '/') . '\x27,\s*\[(.{0,2500}?)\]\);/s', $ctrls, $mm);
    $llamadas = $mm[1] ?? [];
    $sinFlag  = array_filter($llamadas, fn($a) => !str_contains($a, "'puedeEscribir'"));
    $chk("$v — " . count($llamadas) . ' render(s), todos con el flag',
        count($llamadas) > 0 && empty($sinFlag));
}

echo "\n5) Sintaxis de todo lo tocado\n";
$sinErrores = true;
foreach (array_merge(array_keys($plan), array_keys($entra)) as $rel) {
    exec('php -l ' . escapeshellarg(ROOT_PATH . $rel) . ' 2>&1', $o, $rc);
    if ($rc !== 0) { $sinErrores = false; }
}
$chk('todos los controladores compilan', $sinErrores);

echo "\n", $ok ? "== FASE 3 EN VERDE ==\n" : "== HAY FALLOS ==\n";
exit($ok ? 0 : 1);
