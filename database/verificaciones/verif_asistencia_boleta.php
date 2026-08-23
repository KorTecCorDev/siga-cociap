<?php

/**
 * Verificación — el bloque de ASISTENCIA de la boleta usa el mismo umbral que
 * las notas, sin alterar lo que ven las familias.
 * Uso: php database/verificaciones/verif_asistencia_boleta.php
 *
 * Hasta el 04/08/2026 `BoletaModel::armar()` armaba el cuadro de asistencia con un
 * criterio propio ("solo bimestres cerrados", independiente del modo `$datos`).
 * Consecuencia: la vista previa de RA mostraba las NOTAS y la CONDUCTA del bimestre
 * en curso pero NO su asistencia — la asistencia era el unico de los tres bloques
 * que no honraba la excepcion de la vista previa. Ahora delega en
 * `periodoAportaNotas`, el mismo umbral de las notas.
 *
 * LO QUE COMPRUEBA, modo por modo:
 *   1. 'oficial'  -> SOLO bimestres cerrados Y publicados al nivel del alumno.
 *      Es el invariante de la compuerta 044: NO debe cambiar nunca.
 *   2. 'archivo'  -> SOLO bimestres cerrados (ignora la publicacion). NO cambia.
 *   3. 'borrador' -> cerrados + activos con Hito A. El paso 4 SIMULA el Hito A en el
 *      bimestre activo, porque en local no lo tiene y sin el la asercion no probaria
 *      nada (daria el mismo resultado que 'archivo').
 *   4. 'todos'    -> todos los bimestres; los 'pendiente' con `sin_registro = true`
 *      para que la vista los pinte apagados en vez de con ceros (un 0 se lee como
 *      "no falto ningun dia", que seria un dato inventado).
 *   5. El TOTAL suma solo los bimestres CON registro.
 *
 * ESCRIBE en el paso 4 (marca el Hito A del bimestre activo), pero TODO corre dentro
 * de una TRANSACCION con ROLLBACK. El paso 5 comprueba que el Hito A volvio a su
 * valor original.
 */

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH',  ROOT_PATH . '/app');
define('CORE_PATH', ROOT_PATH . '/core');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Guarda: nunca contra produccion (este script ESCRIBE, aunque haga rollback).
if (is_file('/home/u761410128/siga_secrets/database.php')) {
    fwrite(STDERR, "ABORTA: detectado el archivo de secretos de PRODUCCION.\n");
    exit(1);
}

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

/** Firma del bloque de asistencia: "numero[:sin_registro]" por columna. */
$firma = static function (?array $data): string {
    if (!$data) { return '(sin boleta)'; }
    $out = [];
    foreach ($data['asistencia']['bimestres'] as $b) {
        $out[] = $b['numero'] . (!empty($b['sin_registro']) ? ':sin_registro' : '');
    }
    return $out ? implode(' ', $out) : '(vacio)';
};

$check = static function (string $caso, string $esperado, string $obtenido) use (&$fallos): void {
    if ($esperado === $obtenido) {
        printf("  OK    %-52s [%s]\n", $caso, $obtenido);
        return;
    }
    $fallos++;
    printf("  ***   %-52s esperado [%s] · obtenido [%s]\n", $caso, $esperado, $obtenido);
};

// Matricula de prueba: una con incidencias registradas, para que el total no sea 0.
$mat = $pdo->query("
    SELECT i.matricula_id, m.seccion_id
    FROM inasistencias i
    INNER JOIN matriculas m ON m.id = i.matricula_id
    WHERE m.estado = 'aprobada' AND i.faltas > 0
    ORDER BY i.matricula_id LIMIT 1
")->fetch();
if (!$mat) {
    fwrite(STDERR, "ABORTA: no hay ninguna matricula con faltas registradas para probar.\n");
    exit(1);
}
$matriculaId = (int) $mat['matricula_id'];

$periodos = $pdo->query("
    SELECT id, numero, estado, boletas_aprobadas_en
    FROM periodos
    WHERE anio_id = (SELECT id FROM anios_academicos WHERE estado = 'activo' LIMIT 1)
    ORDER BY numero
")->fetchAll();

$cerrados = array_values(array_filter($periodos, fn($p) => $p['estado'] === 'cerrado'));
$activo   = null;
foreach ($periodos as $p) { if ($p['estado'] === 'activo') { $activo = $p; break; } }

echo "=== Escenario (matricula {$matriculaId}) ===\n";
foreach ($periodos as $p) {
    printf("   bimestre %d: estado=%-9s hito_A=%s\n",
        $p['numero'], $p['estado'], $p['boletas_aprobadas_en'] ? 'si' : 'no');
}
$verPeriodo = $activo ? (int) $activo['id'] : (int) $cerrados[0]['id'];
echo "   boleta pedida para el periodo id={$verPeriodo}\n\n";

// Publicados: [periodo_id] con publicacion vigente para el nivel del alumno.
// Se pregunta al MISMO punto que usa la boleta (`BoletaModel` linea ~99) en vez
// de replicar la regla aqui.
//
// 🔴 POR QUE SE DELEGA, Y NO SE COPIA (22/08/2026): este bloque tenia su propia
// copia de la compuerta —`primera_publicacion_en IS NOT NULL`— que NO es la
// regla que aplica la boleta. `periodosPublicados()` corta por `publica_en <=
// ahora` y ni siquiera mira ese sello. La divergencia estuvo LATENTE mientras
// todas las publicaciones fueron INMEDIATAS y se activo en cuanto vencio la
// primera publicacion PROGRAMADA (B2, 13 y 14 de agosto), cuyas filas conservan
// `primera_publicacion_en` en NULL. Mismo fallo, misma causa y mismo dia que en
// `verif_estructura_boleta.php`: eran DOS copias de la misma regla incompleta.
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

// Bimestres en los que el alumno TIENE registro de asistencia de verdad. Es la
// TERCERA condicion de `sin_registro` en `BoletaModel` (~linea 199), la que se
// resuelve con `AsistenciaModel::tieneRegistroUnion`.
//
// 🔴 POR QUE SE ANADE (22/08/2026): el esperado replicaba solo las DOS primeras
// condiciones del modelo (el umbral y el estado 'pendiente') y daba por hecho
// que un bimestre ACTIVO ya tiene asistencia registrada. No es cierto: el
// bimestre en curso puede no tener ni una fila en `inasistencias`, y entonces la
// boleta lo marca `sin_registro` con toda la razon. El verificador acusaba a un
// guard que funcionaba. La tercera condicion NACIO para corregir un dato FALSO
// —un alumno que llego despues del bimestre tenia impreso "0 faltas" de un
// bimestre que no curso, medido en la matricula 694— y este esperado se habia
// quedado en la version anterior de la regla.
//
// Se consulta con la matricula sola: la de prueba NO participa en ningun retorno
// de grado (comprobado abajo), asi que sus `fuentes` son exactamente ella.
$enRetorno = $pdo->prepare("
    SELECT COUNT(*) FROM retornos_grado
    WHERE matricula_oficial_id = ? OR matricula_operativa_id = ?
");
$enRetorno->execute([$matriculaId, $matriculaId]);
if ((int) $enRetorno->fetchColumn() > 0) {
    fwrite(STDERR, "ABORTA: la matricula de prueba {$matriculaId} participa en un retorno de\n"
        . "grado, asi que sus fuentes de asistencia son la UNION de dos matriculas y este\n"
        . "esperado (que consulta solo una) mentiria. Elegir otra matricula de prueba.\n");
    exit(1);
}

$asistenciaModel = new App\Models\AsistenciaModel();
$conRegistro = [];
foreach ($periodos as $p) {
    if ($asistenciaModel->tieneRegistroUnion([$matriculaId], (int) $p['id'])) {
        $conRegistro[] = (int) $p['id'];
    }
}

/**
 * Firma ESPERADA: SIEMPRE una columna por bimestre del anio (estructura anual
 * completa, parte del modelo oficial). Lleva ':sin_registro' la que no aporta a
 * este umbral, la que aun no puede tener registro ('pendiente') y la que no
 * tiene ni una fila de asistencia — las tres condiciones del modelo.
 * @param array<int> $hitoA ids de periodo a los que se les simula el Hito A.
 */
$espera = static function (string $modo, array $publicados, array $periodos, array $hitoA = [])
    use ($conRegistro): string {
    $out = [];
    foreach ($periodos as $p) {
        $aprob  = in_array((int) $p['id'], $hitoA, true) ? '2026-01-01 00:00:00' : $p['boletas_aprobadas_en'];
        $estado = boleta_estado_bimestre($p['estado'], $aprob);
        $aporta = match ($modo) {
            'oficial'  => $estado === 'oficial' && in_array((int) $p['id'], $publicados, true),
            'archivo'  => $estado === 'oficial',
            'borrador' => $estado !== 'registro',
            'todos'    => true,
        };
        $sinRegistro = !$aporta
                    || $p['estado'] === 'pendiente'
                    || !in_array((int) $p['id'], $conRegistro, true);
        $out[] = $p['numero'] . ($sinRegistro ? ':sin_registro' : '');
    }
    return implode(' ', $out);
};

$numsCerrados    = array_map(fn($p) => (int) $p['numero'], $cerrados);
$numsCerradosPub = array_map(fn($p) => (int) $p['numero'],
    array_values(array_filter($cerrados, fn($p) => in_array((int) $p['id'], $publicados, true))));

echo "=== 1-2. Umbrales de FAMILIAS: estructura anual, datos intactos ===\n";
$check("'oficial'  aporta solo cerrados Y publicados",
    $espera('oficial', $publicados, $periodos), $firma($boletas->armar($matriculaId, $verPeriodo, 'oficial')));
$check("'archivo'  aporta solo cerrados",
    $espera('archivo', $publicados, $periodos), $firma($boletas->armar($matriculaId, $verPeriodo, 'archivo')));

echo "\n=== 3. 'todos' (vista previa de RA) = aporta todo lo que existe ===\n";
$check("'todos'    sin registro solo si no hay dato que mostrar",
    $espera('todos', $publicados, $periodos), $firma($boletas->armar($matriculaId, $verPeriodo, 'todos')));

echo "\n=== 3b. TODOS los umbrales dibujan las " . count($periodos) . " columnas ===\n";
foreach (['oficial', 'archivo', 'borrador', 'todos'] as $modo) {
    $d = $boletas->armar($matriculaId, $verPeriodo, $modo);
    $check("'{$modo}' numero de columnas", (string) count($periodos), (string) count($d['asistencia']['bimestres']));
}

echo "\n=== 4. 'borrador' con Hito A SIMULADO (transaccion + ROLLBACK) ===\n";
if (!$activo) {
    echo "  (omitido: no hay bimestre activo en este entorno)\n";
} else {
    // El PUNTO DE PARTIDA depende del estado REAL del bimestre activo, no de
    // suponer que aun no tiene Hito A: RA puede aprobarlo en cualquier momento
    // (paso el 05/08/2026 a media sesion, y este bloque empezo a fallar sin que
    // hubiera ningun defecto). Con Hito A ya aprobado, 'borrador' SUMA el
    // bimestre en curso; sin el, se comporta como 'archivo'.
    $hitoAYaAprobado = !empty($activo['boletas_aprobadas_en']);
    $check(
        $hitoAYaAprobado
            ? "'borrador' con Hito A YA aprobado = suma el bimestre en curso"
            : "'borrador' SIN Hito A = igual que 'archivo'",
        $hitoAYaAprobado
            ? $espera('borrador', $publicados, $periodos, [(int) $activo['id']])
            : $espera('archivo', $publicados, $periodos),
        $firma($boletas->armar($matriculaId, $verPeriodo, 'borrador'))
    );

    $pdo->beginTransaction();
    $pdo->prepare("UPDATE periodos SET boletas_aprobadas_en = NOW() WHERE id = ?")
        ->execute([(int) $activo['id']]);

    $hitoA = [(int) $activo['id']];

    // ⚠️ AVISO DE ALCANCE, no fallo: este paso solo DISCRIMINA si el bimestre en
    // curso tiene asistencia registrada. Si no la tiene, la tercera condicion de
    // `sin_registro` lo apaga igual y la asercion pasa sin probar que el Hito A
    // suma el bimestre en curso. Se dice en voz alta en vez de dar un verde que
    // no significa nada — es el mismo criterio del "control" de los pasos 0 de
    // los verificadores del merito.
    if (!in_array((int) $activo['id'], $conRegistro, true)) {
        printf("  %-5s %-52s %s\n", 'AVISO',
            'el bimestre en curso no tiene asistencia registrada',
            'este paso pasa pero NO discrimina');
    }

    $check("'borrador' CON Hito A = suma el bimestre en curso",
        $espera('borrador', $publicados, $periodos, $hitoA), $firma($boletas->armar($matriculaId, $verPeriodo, 'borrador')));

    // El bimestre en curso NO debe filtrarse a las familias ni al impreso.
    $check("  ...y 'oficial' sigue sin el bimestre en curso",
        $espera('oficial', $publicados, $periodos, $hitoA), $firma($boletas->armar($matriculaId, $verPeriodo, 'oficial')));
    $check("  ...y 'archivo' sigue sin el bimestre en curso",
        $espera('archivo', $publicados, $periodos, $hitoA), $firma($boletas->armar($matriculaId, $verPeriodo, 'archivo')));

    $pdo->rollBack();

    $tras = $pdo->prepare("SELECT boletas_aprobadas_en FROM periodos WHERE id = ?");
    $tras->execute([(int) $activo['id']]);
    $valor = $tras->fetchColumn();
    $check("ROLLBACK devolvio el Hito A a su valor original",
        var_export($activo['boletas_aprobadas_en'], true), var_export($valor, true));
}

echo "\n=== 5. El TOTAL suma solo los bimestres CON registro ===\n";
$data = $boletas->armar($matriculaId, $verPeriodo, 'todos');
$suma = ['faltas' => 0, 'faltas_justificadas' => 0, 'tardanzas' => 0, 'tardanzas_justificadas' => 0];
foreach ($data['asistencia']['bimestres'] as $b) {
    if (!empty($b['sin_registro'])) { continue; }
    foreach ($suma as $k => $_) { $suma[$k] += (int) $b['datos'][$k]; }
}
$check("total == suma de columnas con registro",
    json_encode($suma), json_encode($data['asistencia']['total']));

echo "\n" . ($fallos === 0
    ? "RESULTADO: OK — el umbral de asistencia quedo unificado sin tocar a las familias.\n"
    : "RESULTADO: {$fallos} FALLO(S).\n");

exit($fallos === 0 ? 0 : 1);
