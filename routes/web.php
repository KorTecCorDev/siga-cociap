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

// ─── Admin — Currículo Académico ────────────────────────────
$router->get( '/admin/curriculum',                                  'Admin\CurriculumController@index');
$router->post('/admin/curriculum/areas/{id}/editar',               'Admin\CurriculumController@guardarArea');
$router->post('/admin/curriculum/areas/{id}/toggle',               'Admin\CurriculumController@toggleActivaArea');
$router->post('/admin/curriculum/areas/{id}/mover',                'Admin\CurriculumController@moverArea');
$router->post('/admin/curriculum/subareas/{id}/editar',            'Admin\CurriculumController@guardarSubarea');
$router->post('/admin/curriculum/competencias/{id}/editar',        'Admin\CurriculumController@guardarCompetencia');

// ─── Admin — Centro de Control Operativo ────────────────────
$router->get( '/admin/control',               'Admin\ControlOperativoController@index');
// Cierre de bimestre — Hito A (aprobar boletas -> borrador para docentes).
$router->post('/admin/control/{periodo_id}/aprobar-bimestre',   'Admin\ControlOperativoController@aprobarBimestre');
$router->post('/admin/control/{periodo_id}/anular-aprobacion',  'Admin\ControlOperativoController@anularAprobacion');

// ─── Admin — Secciones y Tutores ────────────────────────────
$router->get( '/admin/secciones',             'Admin\SeccionController@index');
$router->post('/admin/secciones/{id}/tutor',  'Admin\SeccionController@asignarTutor');

// ─── Admin — Buscador de estudiantes ────────────────────────
$router->get( '/admin/buscar-estudiante',     'Admin\BuscadorEstudianteController@index');
$router->get( '/admin/buscar-estudiante/api', 'Admin\BuscadorEstudianteController@buscar');

// ─── Admin — Conducta ───────────────────────────────────────
$router->get( '/admin/conducta',              'Admin\ConductaController@index');
$router->post('/admin/conducta/guardar',      'Admin\ConductaController@guardar');
$router->post('/admin/conducta/{id}/bloquear','Admin\ConductaController@bloquear');
$router->get( '/admin/conducta/{id}',         'Admin\ConductaController@seccion');

// ─── Admin — Asistencia (incidencias) ───────────────────────
$router->get( '/admin/asistencia',            'Admin\AsistenciaController@index');
$router->get( '/admin/asistencia/{id}',       'Admin\AsistenciaController@seccion');
$router->post('/admin/asistencia/guardar',    'Admin\AsistenciaController@guardar');

// ─── Admin — Exoneraciones ──────────────────────────────────
$router->get( '/admin/exoneraciones',                         'Admin\ExoneracionController@index');
$router->get( '/admin/exoneraciones/{seccion_id}',            'Admin\ExoneracionController@seccion');
$router->post('/admin/exoneraciones/{seccion_id}/registrar',  'Admin\ExoneracionController@registrar');
$router->post('/admin/exoneraciones/{id}/revocar',            'Admin\ExoneracionController@revocar');

// ─── Admin — Director EBR ───────────────────────────────────
$router->get( '/admin/director-ebr',                       'Admin\DirectorEbrController@index');
$router->post('/admin/director-ebr/{anio_id}/asignar',     'Admin\DirectorEbrController@asignar');
$router->post('/admin/director-ebr/{id}/imagenes',         'Admin\DirectorEbrController@actualizarImagenes');

// ─── Admin — Usuarios ───────────────────────────────────────
$router->get( '/admin/usuarios',             'Admin\UsuarioController@index');
$router->get( '/admin/usuarios/crear',       'Admin\UsuarioController@create');
$router->post('/admin/usuarios/crear',       'Admin\UsuarioController@store');
$router->get( '/admin/usuarios/{id}/editar', 'Admin\UsuarioController@edit');
$router->post('/admin/usuarios/{id}/editar', 'Admin\UsuarioController@update');
$router->post('/admin/usuarios/{id}/estado', 'Admin\UsuarioController@toggleEstado');

// ─── Director — Año académico y bimestres ───────────────────
// Las rutas literales (crear) van ANTES del patrón {id} para que el router no capture "crear" como parámetro
$router->get( '/director/anios',                 'Director\AnioAcademicoController@index');
$router->get( '/director/anios/crear',           'Director\AnioAcademicoController@create');
$router->post('/director/anios/crear',           'Director\AnioAcademicoController@store');
$router->post('/director/anios/{id}/activar',    'Director\AnioAcademicoController@activar');
$router->post('/director/anios/{id}/cerrar',     'Director\AnioAcademicoController@cerrar');
$router->get( '/director/anios/{id}',            'Director\AnioAcademicoController@show');
// Bimestres
$router->post('/director/periodos/{id}/editar',  'Director\PeriodoController@editar');
$router->post('/director/periodos/{id}/abrir',   'Director\PeriodoController@abrir');
$router->post('/director/periodos/{id}/cerrar',  'Director\PeriodoController@cerrar');
$router->post('/director/periodos/{id}/reabrir', 'Director\PeriodoController@reabrir');
$router->get( '/director/periodos/{id}/stats',   'Director\PeriodoController@stats');

// ─── Secciones y cargas ──────────────────────────────────────
$router->get( '/director/secciones',          'Director\SeccionController@index');
$router->get( '/director/secciones/crear',    'Director\SeccionController@create');
$router->post('/director/secciones/crear',    'Director\SeccionController@store');
$router->get( '/director/cargas',                          'Director\CargaAcademicaController@index');
$router->get( '/director/cargas/crear',                    'Director\CargaAcademicaController@create');
$router->post('/director/cargas/crear',                    'Director\CargaAcademicaController@store');
$router->get( '/director/cargas/seccion/{seccion_id}',     'Director\CargaAcademicaController@porSeccion');
$router->get( '/director/cargas/{id}/editar',              'Director\CargaAcademicaController@edit');
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

// ─── Módulo de Matrículas ────────────────────────────────────
// Las rutas literales (crear) van ANTES del patrón {id} para que el router
// no capture "crear" como parámetro. Lo mismo con los sub-recursos del {id}.
$router->get( '/matriculas',                     'Matricula\MatriculaController@index');
$router->get( '/matriculas/resumen',             'Matricula\MatriculaController@resumen');
$router->get( '/matriculas/resumen/imprimir',    'Matricula\MatriculaController@resumenImprimir');
$router->get( '/matriculas/nomina/imprimir',     'Matricula\MatriculaController@nominaImprimir');
$router->get( '/matriculas/crear',               'Matricula\MatriculaController@create');
$router->post('/matriculas/crear',               'Matricula\MatriculaController@store');
$router->post('/matriculas/{id}/estudiante',     'Matricula\MatriculaController@actualizarEstudiante');
$router->get( '/matriculas/{id}/apoderado',      'Matricula\MatriculaController@apoderado');
$router->post('/matriculas/{id}/apoderado',      'Matricula\MatriculaController@storeApoderado');
$router->get( '/matriculas/{id}/documentos',     'Matricula\MatriculaController@documentos');
$router->post('/matriculas/{id}/documentos',     'Matricula\MatriculaController@storeDocumentos');
$router->post('/matriculas/{id}/activar',        'Matricula\MatriculaController@activar');
$router->post('/matriculas/{id}/desactivar',     'Matricula\MatriculaController@desactivar');
// Traslado de salida (constancia oficial): formulario + registro.
$router->get( '/matriculas/{id}/trasladar',      'Matricula\TrasladoController@form');
$router->post('/matriculas/{id}/trasladar',      'Matricula\TrasladoController@store');
$router->get( '/matriculas/{id}/notas-externas', 'Matricula\MatriculaController@notasExternas');
$router->post('/matriculas/{id}/notas-externas', 'Matricula\MatriculaController@storeNotasExternas');
// Retorno de grado
$router->get( '/matriculas/{id}/retorno/revertir', 'Matricula\RetornoGradoController@confirmarReversion');
$router->post('/matriculas/{id}/retorno/revertir', 'Matricula\RetornoGradoController@revertir');
$router->get( '/matriculas/{id}/retorno',        'Matricula\RetornoGradoController@create');
$router->post('/matriculas/{id}/retorno',        'Matricula\RetornoGradoController@store');
// El detalle {id} va al FINAL para no capturar los sub-recursos anteriores.
$router->get( '/matriculas/{id}',                'Matricula\MatriculaController@show');

// ─── Rectificación de calificaciones (auditada) ──────────────
// Módulo general: corrige notas ya cerradas/bloqueadas con traza. Solo
// admin/registro_academico (gateado en el controlador). Las literales van
// ANTES del patrón {id} de matrícula y entre sí (editar/guardar antes de
// matricula/{id}) para que el router no capture mal los segmentos.
$router->get( '/rectificaciones',                 'Rectificacion\RectificacionController@index');
$router->get( '/rectificaciones/editar',          'Rectificacion\RectificacionController@editar');
$router->post('/rectificaciones/guardar',         'Rectificacion\RectificacionController@guardar');
$router->get( '/rectificaciones/matricula/{id}',  'Rectificacion\RectificacionController@matricula');

// ─── Consulta de calificaciones (solo lectura) ───────────────
// Supervision read-only por periodo -> seccion -> area/carga. Solo muestra
// lo oficial (bloqueado). admin/registro_academico/director (gateado en el
// controlador). Literales primero; las sub-rutas con dos params no chocan con
// la literal /consulta-notas por tener mas segmentos.
$router->get('/consulta-notas',                                   'Consulta\ConsultaNotasController@index');
$router->get('/consulta-notas/{periodo_id}/seccion/{seccion_id}', 'Consulta\ConsultaNotasController@seccion');
$router->get('/consulta-notas/{periodo_id}/carga/{carga_id}',     'Consulta\ConsultaNotasController@carga');

// ─── Constancias de traslado (registro oficial) ──────────────
$router->get( '/traslados',                'Matricula\TrasladoController@index');
$router->get( '/traslados/{id}/imprimir',  'Matricula\TrasladoController@imprimir');
$router->post('/traslados/{id}/anular',    'Matricula\TrasladoController@anular');

// ─── Docente — Panel / Nómina ────────────────────────────────
$router->get( '/docente/inicio',                       'Docente\PanelController@index');
$router->get( '/docente/nomina',                       'Docente\PanelController@nomina');
$router->get( '/docente/nomina/{seccion_id}/imprimir', 'Docente\PanelController@nominaImprimir');
$router->get( '/docente/horario/imprimir',             'Docente\PanelController@horarioImprimir');
// Boletas del docente (validadas por nivel). La literal /imprimir va ANTES del
// patron generico para que el router no capture "imprimir" como matricula_id.
$router->get( '/docente/boleta/{matricula_id}/imprimir', 'Boleta\BoletaController@verImprimirDocente');
$router->get( '/docente/boleta/{matricula_id}',          'Boleta\BoletaController@verDigitalDocente');
// Orden de merito (lectura publica para el claustro). Dos flujos separados:
// orden de merito por GRADO (media beca) y ranking por SECCION (sin media beca).
$router->get( '/docente/orden-merito',                  'Docente\OrdenMeritoController@index');
$router->get( '/docente/orden-merito/{periodo_id}',     'Docente\OrdenMeritoController@porPeriodo');
$router->get( '/docente/ranking-seccion',               'Docente\OrdenMeritoController@seccionIndex');
$router->get( '/docente/ranking-seccion/{periodo_id}',  'Docente\OrdenMeritoController@seccionPorPeriodo');

// ─── Calificaciones ──────────────────────────────────────────
$router->get( '/docente/mis-cargas',                        'Docente\CalificacionController@misCargas');
// Vista de AREA (solo secciones unidocente): una pantalla por area con TODAS
// las subarea-cargas del area + transversales. La literal "area" distingue del
// patron base {carga_id}; las literales/largas van ANTES por orden de lectura.
$router->get( '/docente/calificaciones/area/{seccion_id}/{area_id}/historial/{periodo_id}', 'Docente\CalificacionController@historialArea');
$router->get( '/docente/calificaciones/area/{seccion_id}/{area_id}', 'Docente\CalificacionController@formularioArea');
// Historico del docente: grilla read-only de SU carga en un bimestre cerrado.
// 5 segmentos: no colisiona con el patron base de 3 (el router ancla ^...$).
$router->get( '/docente/calificaciones/{carga_id}/historial/{periodo_id}', 'Docente\CalificacionController@historial');
$router->get( '/docente/calificaciones/{carga_id}',         'Docente\CalificacionController@formulario');
$router->post('/docente/calificaciones/{carga_id}/guardar',   'Docente\CalificacionController@guardar');
$router->post('/docente/calificaciones/{carga_id}/autosave',  'Docente\CalificacionController@autosave');
$router->post('/docente/calificaciones/{carga_id}/omisiones', 'Docente\CalificacionController@guardarOmisiones');

// ─── Criterios ───────────────────────────────────────────────
$router->post('/docente/criterios/crear',             'Docente\CalificacionController@crearCriterio');
$router->post('/docente/criterios/{id}/renombrar',   'Docente\CalificacionController@renombrarCriterio');
$router->post('/docente/criterios/{id}/eliminar',    'Docente\CalificacionController@eliminarCriterio');
$router->post('/docente/calificaciones/conclusion', 'Docente\CalificacionController@guardarConclusion');


// ─── Panel padre ─────────────────────────────────────────────
$router->get('/padre/inicio',  'Padre\PanelController@index');
$router->get('/padre/notas',   'Padre\PanelController@notas');
$router->get('/padre/alertas', 'Padre\PanelController@alertas');

// ─── Boletas públicas por CÓDIGO — DORMIDO (se conserva para reactivar) ──────
// El acceso público por código tecleado se jubiló en favor del QR por token
// (enlace permanente por estudiante). El controlador y las vistas
// (BoletaPublicaController, boleta-publica/*) se conservan intactos; basta
// re-registrar estas dos rutas ANTES de /boleta/{id} para reactivarlo:
//   $router->get( '/boleta-publica',           'BoletaPublicaController@formulario');
//   $router->post('/boleta-publica/consultar', 'BoletaPublicaController@consultar');

// ─── Firmas/sello del Director EBR (servido público desde almacenamiento externo) ───
$router->get('/firmas/{archivo}', 'FirmaController@servir');

// ─── Admin — Boletas públicas ────────────────────────────────
$router->get( '/admin/boletas-publicas',                             'Admin\BoletaPublicaController@index');
$router->post('/admin/boletas-publicas/generar-tokens',              'Admin\BoletaPublicaController@generarTokens');
$router->get( '/admin/boletas-publicas/{periodo_id}',                'Admin\BoletaPublicaController@porPeriodo');
$router->get( '/admin/boletas-publicas/{periodo_id}/vista-previa',   'Admin\BoletaPublicaController@vistaPrevia');
$router->get( '/admin/boletas-publicas/{periodo_id}/boletas-alumno', 'Admin\BoletaPublicaController@boletasAlumno');
$router->get( '/admin/boletas-publicas/{periodo_id}/archivar',       'Admin\BoletaPublicaController@archivar');
// CÓDIGO dormido (se conserva para reactivar — métodos generar/actualizar/imprimir intactos):
//   $router->post('/admin/boletas-publicas/{periodo_id}/generar',    'Admin\BoletaPublicaController@generar');
//   $router->post('/admin/boletas-publicas/{periodo_id}/actualizar', 'Admin\BoletaPublicaController@actualizar');
//   $router->get( '/admin/boletas-publicas/{periodo_id}/imprimir',   'Admin\BoletaPublicaController@imprimir');

// ─── Boleta de calificaciones — SOLO por token (seguridad) ───
// Las rutas anónimas por id ({matricula_id}/{periodo_id}) se retiraron: eran
// enumerables. Todo acceso público es por token permanente (inadivinable).
// El acceso interno (docente/admin) va por sus rutas autenticadas con alcance.
$router->get('/boleta/digital/{token}', 'Boleta\BoletaController@verDigitalToken');
$router->get('/boleta/ver/{token}',     'Boleta\BoletaController@verToken');

// ─── Orden de mérito ─────────────────────────────────────────
$router->get('/director/orden-merito',                          'Director\OrdenMeritoController@index');
$router->get('/director/orden-merito/{periodo_id}/imprimir',    'Director\OrdenMeritoController@imprimir');
// Desempate: rutas literales ANTES del patrón genérico {periodo_id} para que el
// router no capture "desempate" como periodo.
$router->get('/director/orden-merito/{periodo_id}/desempate/{grado_id}',  'Director\OrdenMeritoController@desempate');
$router->post('/director/orden-merito/{periodo_id}/desempate/{grado_id}', 'Director\OrdenMeritoController@guardarDesempate');
// Acta de desempates: la mas especifica (/imprimir) antes que la de pantalla.
$router->get('/director/orden-merito/{periodo_id}/desempates/imprimir', 'Director\OrdenMeritoController@desempatesImprimir');
$router->get('/director/orden-merito/{periodo_id}/desempates',          'Director\OrdenMeritoController@desempates');
$router->get('/director/orden-merito/{periodo_id}',             'Director\OrdenMeritoController@porPeriodo');

// ─── Gestión de bloqueos ─────────────────────────────────────
$router->get( '/director/bloqueos',                     'Director\BloqueoController@index');
$router->post('/director/bloqueos/bloquear',             'Director\BloqueoController@bloquear');
$router->post('/director/bloqueos/limpiar-cierre',       'Director\BloqueoController@limpiarBloqueosCierre');
$router->post('/director/bloqueos/transversal/{seccion_id}/cerrar',  'Director\BloqueoController@cerrarTransversal');
$router->post('/director/bloqueos/transversal/{seccion_id}/reabrir', 'Director\BloqueoController@reabrirTransversal');
$router->post('/director/bloqueos/conducta/{seccion_id}/bloquear',   'Director\BloqueoController@bloquearConducta');
$router->post('/director/bloqueos/conducta/{seccion_id}/cerrar',     'Director\BloqueoController@cerrarConducta');
$router->post('/director/bloqueos/conducta/{seccion_id}/reabrir',    'Director\BloqueoController@reabrirConducta');
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

// ─── Tutoría — transversales y cierre del tutor ──────────────
$router->get( '/docente/tutoria',                          'Docente\TutoriaController@index');
$router->post('/docente/tutoria/{periodo_id}/conclusion',  'Docente\TutoriaController@guardarConclusion');
$router->post('/docente/tutoria/{periodo_id}/cerrar',      'Docente\TutoriaController@cerrar');
$router->get( '/docente/tutoria/{periodo_id}',             'Docente\TutoriaController@index');

// ─── Conducta — cierre del tutor (Etapa 2) ──────────────────
$router->get( '/docente/conducta',                         'Docente\ConductaTutorController@index');
$router->post('/docente/conducta/{periodo_id}/nota',       'Docente\ConductaTutorController@guardarNota');
$router->post('/docente/conducta/{periodo_id}/cerrar',     'Docente\ConductaTutorController@cerrar');
$router->get( '/docente/conducta/{periodo_id}',            'Docente\ConductaTutorController@index');
