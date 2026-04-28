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

// ─── Autenticación ─────────────────────────────────────────────────────────
$router->get( '/login',           'Auth\AuthController@showLogin');
$router->post('/login/procesar',  'Auth\AuthController@login');
$router->get( '/logout',          'Auth\AuthController@logout');

// ─── Dashboard (redirige según rol) ─────────────────────────────────────────
$router->get('/',          'DashboardController@index');
$router->get('/dashboard', 'DashboardController@index');

// ─── Admin — Gestión de usuarios ────────────────────────────────────────────
$router->get( '/admin/usuarios',              'Admin\UsuarioController@index');
$router->get( '/admin/usuarios/crear',        'Admin\UsuarioController@create');
$router->post('/admin/usuarios/crear',        'Admin\UsuarioController@store');
$router->get( '/admin/usuarios/{id}/editar',  'Admin\UsuarioController@edit');
$router->post('/admin/usuarios/{id}/editar',  'Admin\UsuarioController@update');
$router->post('/admin/usuarios/{id}/estado',  'Admin\UsuarioController@toggleEstado');

// ─── Director — Configuración académica ─────────────────────────────────────
$router->get( '/director/anios',             'Director\AnioAcademicoController@index');
$router->get( '/director/anios/crear',       'Director\AnioAcademicoController@create');
$router->post('/director/anios/crear',       'Director\AnioAcademicoController@store');
$router->get( '/director/anios/{id}',        'Director\AnioAcademicoController@show');
$router->get( '/director/periodos',          'Director\PeriodoController@index');
$router->post('/director/periodos',          'Director\PeriodoController@store');
$router->post('/director/periodos/{id}/limite', 'Director\PeriodoController@setLimite');

// ─── Secciones y cargas académicas ──────────────────────────────────────────
$router->get( '/director/secciones',              'Director\SeccionController@index');
$router->get( '/director/secciones/crear',        'Director\SeccionController@create');
$router->post('/director/secciones/crear',        'Director\SeccionController@store');
$router->get( '/director/cargas',                 'Director\CargaAcademicaController@index');
$router->get( '/director/cargas/crear',           'Director\CargaAcademicaController@create');
$router->post('/director/cargas/crear',           'Director\CargaAcademicaController@store');
$router->get( '/director/cargas/{id}/editar',     'Director\CargaAcademicaController@edit');
$router->post('/director/cargas/{id}/editar',     'Director\CargaAcademicaController@update');

// ─── Matrícula ───────────────────────────────────────────────────────────────
$router->get( '/secretaria/matriculas',               'Secretaria\MatriculaController@index');
$router->get( '/secretaria/matriculas/crear',         'Secretaria\MatriculaController@create');
$router->post('/secretaria/matriculas/crear',         'Secretaria\MatriculaController@store');
$router->get( '/secretaria/matriculas/{id}',          'Secretaria\MatriculaController@show');
$router->post('/secretaria/matriculas/{id}/estado',   'Secretaria\MatriculaController@updateEstado');
$router->get( '/director/matriculas/{id}/aprobar',    'Director\MatriculaController@aprobar');
$router->post('/director/matriculas/{id}/aprobar',    'Director\MatriculaController@confirmarAprobacion');

// ─── Calificaciones (módulo crítico) ─────────────────────────────────────────
$router->get( '/docente/mis-cargas',                       'Docente\CalificacionController@misCargas');
$router->get( '/docente/calificaciones/{carga_id}',        'Docente\CalificacionController@formulario');
$router->post('/docente/calificaciones/{carga_id}',        'Docente\CalificacionController@guardar');
$router->get( '/docente/calificaciones/{carga_id}/ver',    'Docente\CalificacionController@ver');

// ─── Tutor ────────────────────────────────────────────────────────────────────
$router->get('/docente/tutor/seccion',        'Docente\TutorController@verSeccion');
$router->get('/docente/tutor/transversales',  'Docente\TutorController@formularioTransversales');
$router->post('/docente/tutor/transversales', 'Docente\TutorController@guardarTransversales');
$router->post('/docente/tutor/alerta',        'Docente\TutorController@enviarAlerta');

// ─── Panel del padre de familia ──────────────────────────────────────────────
$router->get('/padre/inicio',      'Padre\PanelController@index');
$router->get('/padre/notas',       'Padre\PanelController@notas');
$router->get('/padre/alertas',     'Padre\PanelController@alertas');

// ─── Orden de mérito ─────────────────────────────────────────────────────────
$router->get('/director/orden-merito',              'Director\OrdenMeritoController@index');
$router->get('/director/orden-merito/{periodo_id}', 'Director\OrdenMeritoController@porPeriodo');
