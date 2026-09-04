<?php

/**
 * Verificación — los indicadores por grado salen del MOTOR OFICIAL del mérito.
 * Uso: php database/verificaciones/verif_cuadros_merito_motor.php
 *
 * SOLO LECTURA: no escribe nada, no abre transacciones. Corre en PRODUCCIÓN
 * (por eso NO lleva el guard del archivo de secretos).
 *
 * POR QUÉ EXISTE
 *   Hasta el 04/09/2026 `AnioAcademicoModel::getStatsCierre` calculaba su propio
 *   ranking por grado (`getRankingGrado`, privado), una copia simplificada del
 *   universo del mérito: sin exigir competencias BLOQUEADAS, sin excluir
 *   extraordinarias ni áreas exoneradas, con la TOE entera en vez de solo Ética,
 *   y sin ROSTER_MERITO, anclaje de retorno ni cascada de desempate. Alimentaba
 *   tres pantallas —`/admin/cuadros`, su imprimible A4 y
 *   `/director/periodos/{id}/stats`— con cifras plausibles y falsas.
 *
 *   Medido el 04/09/2026 ANTES de migrar, en el bimestre ABIERTO: 1.º primaria
 *   anunciaba un primer puesto con 22 competidores donde el orden de mérito
 *   tiene CERO (nada bloqueado aún), y el último puesto de 1.º secundaria salía
 *   12.50 contra los 15.00 reales. En bimestre CERRADO las dos fuentes
 *   coincidían: el defecto no tenía síntoma para quien mirara un bimestre ya
 *   cerrado, que es lo que lo mantuvo vivo.
 *
 *   Es otra instancia del patrón de fallo del repositorio (cascada de empates,
 *   retorno de grado, universo del mérito, carga dueña de las transversales).
 *   Este verificador es lo que impide que vuelva: si alguien reescribe un
 *   ranking dentro de `AnioAcademicoModel`, aquí falla.
 *
 * QUÉ COMPRUEBA, para CADA periodo con ranking:
 *   1. IDENTIDAD DE FUENTE: `getStatsCierre()['por_grado']` cuadra grado a grado
 *      con `OrdenMeritoModel::rankingGrado` — mismos grados y en su orden, mismo
 *      `total`, misma matrícula en el primer puesto y mismo promedio.
 *   2. `peores` sale de la cola de ese mismo ranking y nunca incluye al 1.er puesto.
 *   3. `en_riesgo` = exactamente los que tienen `num_c >= RIESGO_MIN_C`, ordenados
 *      de más C a menos.
 *   4. NO HAY RANKING PROPIO: ninguna consulta de `AnioAcademicoModel` ordena
 *      estudiantes por promedio. Las tres de arriba miden datos; ésta impide que
 *      la copia renazca.
 *
 *      ⚠️ Lo que se prohíbe es el RANKING, no el promedio. `getResumenBimestre`
 *      sigue promediando por estudiante a propósito, y debe seguir haciéndolo:
 *      su «en riesgo» es OTRA pregunta —promedio general por debajo de
 *      NOTA_MIN_B, contado por nivel— y alimenta el bloque de Calificaciones de
 *      la misma pantalla. Las dos cifras conviven y son legítimamente distintas;
 *      el paso 5 lo deja medido para que nadie las "unifique".
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
date_default_timezone_set(config('timezone'));

use App\Models\AnioAcademicoModel;
use App\Models\OrdenMeritoModel;

$pdo = Core\Database::connect();

$fallos = 0;
$ok = function (bool $cond, string $etiqueta, string $detalle = '') use (&$fallos): void {
    printf("  %-5s %-58s %s\n", $cond ? 'OK' : '***', $etiqueta, $detalle);
    if (!$cond) { $fallos++; }
};

echo "== Huella ==\n";
printf("  bd=%s · usuario=%s · so=%s\n\n",
    $pdo->query("SELECT DATABASE()")->fetchColumn(),
    $pdo->query("SELECT USER()")->fetchColumn(),
    $pdo->query("SELECT @@version_compile_os")->fetchColumn());

$periodos = $pdo->query("
    SELECT p.id, p.numero, p.nombre_display, p.estado
    FROM periodos p
    ORDER BY p.anio_id, p.numero
")->fetchAll(PDO::FETCH_ASSOC);

// ── 1-3. Los datos, periodo a periodo ─────────────────────────────
$gradosMedidos = 0;
$filasRiesgo   = 0;

foreach ($periodos as $p) {
    $pid = (int) $p['id'];

    // Instancias NUEVAS por periodo: `debeUsarSnapshot` está memoizado, y
    // reutilizarlas podría esconder un fallo de memoización entre bimestres.
    $stats  = (new AnioAcademicoModel())->getStatsCierre($pid);
    $merito = new OrdenMeritoModel();

    $porGrado = $stats['por_grado'];
    if (!$porGrado) {
        printf("== %s (%s) ==  sin ranking, se omite\n\n", $p['nombre_display'], $p['estado']);
        continue;
    }

    printf("== %s (%s) ==\n", $p['nombre_display'], $p['estado']);

    // Los grados de la fachada tienen que ser los del motor, en su mismo orden.
    // El motor puede enumerar un grado que luego no devuelve filas y la fachada
    // lo omite, así que se compara como SUBCONJUNTO EN ORDEN, no como igualdad.
    $gradosStats = array_map(static fn($g) => (int) $g['grado']['id'], $porGrado);
    $gradosMotor = array_map('intval', array_column($merito->gradosConRanking($pid), 'id'));
    $ok($gradosStats === array_values(array_filter(
            $gradosMotor,
            static fn($id) => in_array($id, $gradosStats, true)
        )),
        'los grados salen de gradosConRanking, en su orden',
        count($gradosStats) . ' de ' . count($gradosMotor));

    foreach ($porGrado as $g) {
        $gid  = (int) $g['grado']['id'];
        $etq  = $g['grado']['nombre_display'] . ' ' . $g['grado']['nivel_codigo'];
        $rank = $merito->rankingGrado($gid, $pid);
        $gradosMedidos++;

        // 1. Identidad de fuente.
        $mismoTotal = (int) $g['total'] === count($rank);
        $mismoUno   = (int) $g['mejor']['matricula_id'] === (int) $rank[0]['matricula_id']
                   && (float) $g['mejor']['promedio_general'] === (float) $rank[0]['promedio_general'];
        $ok($mismoTotal && $mismoUno, "  $etq · total y 1.er puesto = rankingGrado",
            $g['total'] . ' alumnos · ' . $g['mejor']['promedio_general']);

        // 2. `peores` es la cola del MISMO ranking y excluye al primer puesto.
        $colaEsperada = array_values(array_filter(
            array_slice($rank, -2),
            static fn($e) => (int) $e['matricula_id'] !== (int) $rank[0]['matricula_id']
        ));
        $ok(array_map('intval', array_column($g['peores'], 'matricula_id'))
            === array_map('intval', array_column($colaEsperada, 'matricula_id')),
            "  $etq · peores = cola del ranking, sin el 1.er puesto",
            count($g['peores']) . ' fila(s)');

        // 3. `en_riesgo` = umbral exacto + orden por número de C descendente.
        $esperados = [];
        foreach ($rank as $e) {
            if ((int) $e['num_c'] >= OrdenMeritoModel::RIESGO_MIN_C) {
                $esperados[] = (int) $e['matricula_id'];
            }
        }
        sort($esperados);

        $obtenidos = array_map('intval', array_column($g['en_riesgo'], 'matricula_id'));
        $comparar  = $obtenidos;
        sort($comparar);

        $cs       = array_map('intval', array_column($g['en_riesgo'], 'num_c'));
        $csOrden  = $cs;
        rsort($csOrden);

        $ok($comparar === $esperados && $cs === $csOrden,
            "  $etq · en riesgo = num_c >= " . OrdenMeritoModel::RIESGO_MIN_C . ', de mayor a menor',
            count($obtenidos) . ' de ' . count($rank));

        $filasRiesgo += count($obtenidos);
    }
    echo "\n";
}

echo "== Cobertura de la medición ==\n";
$ok($gradosMedidos > 0, 'la comparación midió grados de verdad', "$gradosMedidos grado(s)");
// Un verificador que pasa en verde sin haber ejercitado la rama que vigila es
// peor que uno roto: avisa en vez de dar luz verde sobre una premisa falsa.
$ok($filasRiesgo > 0, 'la rama "en riesgo" es observable en estos datos', "$filasRiesgo fila(s)");

// ── 4. La copia no puede renacer ──────────────────────────────────
echo "\n== Estructura ==\n";
$fuente = file_get_contents(APP_PATH . '/Models/AnioAcademicoModel.php');

// Se miran solo las CADENAS del archivo (donde viven los SQL), nunca los
// comentarios: el docblock de getStatsCierre nombra a getRankingGrado a
// propósito, para contar por qué se fue.
$cadenas = '';
foreach (token_get_all($fuente) as $t) {
    if (is_array($t) && in_array($t[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE], true)) {
        $cadenas .= ' ' . $t[1];
    }
}
// La copia borrada se reconocía por dos marcas que su sustituto NO tiene:
// exponía `promedio_general` por estudiante y lo ordenaba para dar puestos.
// El AVG a secas NO sirve como marca: `getResumenBimestre` promedia por
// estudiante de forma legítima (ver la cabecera).
$ok(!str_contains($cadenas, 'promedio_general'),
    'AnioAcademicoModel no expone promedio_general por estudiante',
    'esa columna es del motor del merito');
$ok(!preg_match('~ORDER\s+BY\s+promedio~i', $cadenas),
    'AnioAcademicoModel no ordena estudiantes por promedio',
    'ordenar por promedio es rankear');
$ok(str_contains($fuente, 'statsPorGrado'),
    'getStatsCierre delega en OrdenMeritoModel::statsPorGrado');

// ── 5. Las dos preguntas de "riesgo" siguen siendo dos ────────────
// Se miden juntas a propósito: comparten pantalla y rótulo, y la tentación
// recurrente de este repositorio es unificar dos reglas que solo se PARECEN.
echo "\n== Los dos 'en riesgo' de /admin/cuadros ==\n";
foreach ($periodos as $p) {
    $pid   = (int) $p['id'];
    $anio  = new AnioAcademicoModel();
    $porGr = $anio->getStatsCierre($pid)['por_grado'];
    if (!$porGr) { continue; }

    $porPromedio = 0;
    foreach ($anio->getResumenBimestre($pid) as $nivel) {
        $porPromedio += (int) ($nivel['en_riesgo'] ?? 0);
    }
    $porC = array_sum(array_map(static fn($g) => count($g['en_riesgo']), $porGr));

    printf("  %-14s promedio<%d por nivel: %-4d · %d C o mas por grado: %-4d\n",
        $p['nombre_display'], NOTA_MIN_B, $porPromedio, OrdenMeritoModel::RIESGO_MIN_C, $porC);
}
$ok(true, 'las dos cifras se miden por separado y no se unifican',
    'universos distintos a proposito');

echo "\n";
printf("%s — %d fallo(s)\n", $fallos === 0 ? 'TODO OK' : 'HAY FALLOS', $fallos);
exit($fallos === 0 ? 0 : 1);
