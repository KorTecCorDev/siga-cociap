<?php

/**
 * Rutas web — SIGA-COCIAP
 * Todas las rutas de la aplicación definidas aquí.
 * Convención: GET para mostrar, POST para procesar.
 *
 * Formato: $router->get('/ruta', 'Namespace/Controlador@metodo')
 */

use Core\Router;

/** @var Router $router */

// ─── Autenticación ──────────────────────────────────────────
$router->get( '/login',          'Auth\AuthController@showLogin');
$router->post('/login/procesar', 'Auth\AuthController@login');
$router->get( '/logout',         'Auth\AuthController@logout');

// ─── Dashboard ──────────────────────────────────────────────
$router->get('/',          'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// ─── Admin — Usuarios ───────────────────────────────────────
$router->get( '/admin/usuarios',             'Admin\UsuarioController@index');
$router->get( '/admin/usuarios/crear',       'Admin\UsuarioController@create');
$router->post('/admin/usuarios/crear',       'Admin\UsuarioController@store');
$router->get( '/admin/usuarios/{id}/editar', 'Admin\UsuarioController@edit');
$router->post('/admin/usuarios/{id}/editar', 'Admin\UsuarioController@update');
$router->post('/admin/usuarios/{id}/estado', 'Admin\UsuarioController@toggleEstado');

// ─── Director — Configuración académica ─────────────────────
$router->get( '/director/anios',                'Director\AnioAcademicoController@index');
$router->get( '/director/anios/crear',          'Director\AnioAcademicoController@create');
$router->post('/director/anios/crear',          'Director\AnioAcademicoController@store');
$router->get( '/director/anios/{id}',           'Director\AnioAcademicoController@show');
$router->get( '/director/periodos',             'Director\PeriodoController@index');
$router->post('/director/periodos',             'Director\PeriodoController@store');
$router->post('/director/periodos/{id}/limite', 'Director\PeriodoController@setLimite');

// ─── Secciones y cargas ──────────────────────────────────────
$router->get( '/director/secciones',          'Director\SeccionController@index');
$router->get( '/director/secciones/crear',    'Director\SeccionController@create');
$router->post('/director/secciones/crear',    'Director\SeccionController@store');
$router->get( '/director/cargas',             'Director\CargaAcademicaController@index');
$router->get( '/director/cargas/crear',       'Director\CargaAcademicaController@create');
$router->post('/director/cargas/crear',       'Director\CargaAcademicaController@store');
$router->get( '/director/cargas/{id}/editar', 'Director\CargaAcademicaController@edit');
$router->post('/director/cargas/{id}/editar', 'Director\CargaAcademicaController@update');
$router->post('/director/cargas/{id}/estado', 'Director\CargaAcademicaController@toggleEstado');

// ─── Matrícula ───────────────────────────────────────────────
$router->get( '/secretaria/matriculas',             'Secretaria\MatriculaController@index');
$router->get( '/secretaria/matriculas/crear',       'Secretaria\MatriculaController@create');
$router->post('/secretaria/matriculas/crear',       'Secretaria\MatriculaController@store');
$router->get( '/secretaria/matriculas/{id}',        'Secretaria\MatriculaController@show');
$router->post('/secretaria/matriculas/{id}/estado', 'Secretaria\MatriculaController@updateEstado');
$router->get( '/director/matriculas/{id}/aprobar',  'Director\MatriculaController@aprobar');
$router->post('/director/matriculas/{id}/aprobar',  'Director\MatriculaController@confirmarAprobacion');

// ─── Calificaciones ──────────────────────────────────────────
$router->get( '/docente/mis-cargas',                        'Docente\CalificacionController@misCargas');
$router->get( '/docente/calificaciones/{carga_id}',         'Docente\CalificacionController@formulario');
$router->post('/docente/calificaciones/{carga_id}/guardar', 'Docente\CalificacionController@guardar');

// ─── Criterios ───────────────────────────────────────────────
$router->post('/docente/criterios/crear',           'Docente\CalificacionController@crearCriterio');
$router->post('/docente/criterios/{id}/eliminar',   'Docente\CalificacionController@eliminarCriterio');
$router->post('/docente/calificaciones/conclusion', 'Docente\CalificacionController@guardarConclusion');

// ─── Tutor ───────────────────────────────────────────────────
$router->get( '/docente/tutor/seccion',        'Docente\TutorController@verSeccion');
$router->get( '/docente/tutor/transversales',  'Docente\TutorController@formularioTransversales');
$router->post('/docente/tutor/transversales',  'Docente\TutorController@guardarTransversales');
$router->post('/docente/tutor/alerta',         'Docente\TutorController@enviarAlerta');

// ─── Panel padre ─────────────────────────────────────────────
$router->get('/padre/inicio',  'Padre\PanelController@index');
$router->get('/padre/notas',   'Padre\PanelController@notas');
$router->get('/padre/alertas', 'Padre\PanelController@alertas');

// ─── Boleta de calificaciones ────────────────────────────────
// La ruta literal /boleta/digital/... debe ir antes del patrón con parámetros
$router->get('/boleta/digital/{matricula_id}/{periodo_id}', 'Boleta\BoletaController@verDigital');
$router->get('/boleta/{matricula_id}/{periodo_id}',         'Boleta\BoletaController@ver');

// ─── Orden de mérito ─────────────────────────────────────────
$router->get('/director/orden-merito',              'Director\OrdenMeritoController@index');
$router->get('/director/orden-merito/{periodo_id}', 'Director\OrdenMeritoController@porPeriodo');

// ─── Gestión de bloqueos ─────────────────────────────────────
$router->get( '/director/bloqueos',                     'Director\BloqueoController@index');
$router->post('/director/bloqueos/bloquear',             'Director\BloqueoController@bloquear');
$router->post('/director/bloqueos/{id}/desbloquear',     'Director\BloqueoController@desbloquear');

// ─── Resumen y bloqueo de competencia ────────────────────────
$router->get(
    '/docente/calificaciones/{carga_id}/resumen/{competencia_id}',
    'Docente\CalificacionController@resumen'
);
$router->post(
    '/docente/calificaciones/{carga_id}/bloquear/{competencia_id}',
    'Docente\CalificacionController@bloquear'
);
$router->post(
    '/docente/calificaciones/{carga_id}/conclusion/{competencia_id}',
    'Docente\CalificacionController@guardarConclusionAlumno'
);
