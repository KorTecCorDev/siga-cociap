<?php
/**
 * Vista pública — boleta digital consultada con código de acceso.
 * Reutiliza el componente .bd- de boleta/digital.php.
 *
 * @var array  $alumno      { nombre_completo, dni, grado_nombre, seccion_nombre,
 *                            nivel_nombre, nivel_codigo, escala_boleta, anio_academico }
 * @var array  $periodos    [{ id, numero, nombre_display }]
 * @var array  $areas       areas[nombre][comp_id] = { nombre, bimestres, literal_final }
 * @var array  $conducta    [periodo_id => literal]
 * @var string $institucion
 * @var string $url_boleta
 * @var string $codigo
 */

// Incluye el componente de boleta digital — mismas variables, mismo componente .bd-
require VIEW_PATH . '/boleta/digital.php';
