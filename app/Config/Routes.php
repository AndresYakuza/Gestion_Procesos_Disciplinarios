<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'FurdController::index');

/** Lookup de empleados por cédula (JSON) */
$routes->get('empleados/lookup/(:segment)', 'EmpleadoLookupController::getByCedula/$1');


/** Registro FURD (fase 1) */
$routes->get('furd', 'FurdController::index');
$routes->post('furd', 'FurdController::store');
$routes->delete('furd/(:num)', 'FurdController::destroy/$1');    // elimina proceso entero
$routes->get('furd/adjuntos', 'FurdController::adjuntos');
$routes->post('furd/(:num)/faltas', 'FurdController::attachFalta/$1');              // AJAX opcional
$routes->delete('furd/(:num)/faltas/(:num)', 'FurdController::detachFalta/$1/$2');  // AJAX opcional
$routes->get('furd/adjuntos', 'FurdController::adjuntos');

$routes->get('files/furd/(:segment)/(:any)', 'FileController::furd/$1/$2');

$routes->get('adjuntos/(:num)/download', 'AdjuntosController::download/$1');
$routes->post('adjuntos/(:num)/delete', 'AdjuntosController::delete/$1');
$routes->get('adjuntos/(:num)/open', 'AdjuntosController::open/$1');


/** Citación (fase 2) */
$routes->get('citacion', 'CitacionController::create');
$routes->get('citacion/find', 'CitacionController::find'); // AJAX
$routes->post('citacion', 'CitacionController::store');
$routes->post('citacion/(:num)', 'CitacionController::update/$1'); // usa POST por simplicidad en formularios

/** Descargos (fase 3) */
$routes->get('descargos', 'DescargosController::create');
$routes->get('descargos/find', 'DescargosController::find'); // AJAX
$routes->post('descargos', 'DescargosController::store');
$routes->post('descargos/(:num)', 'DescargosController::update/$1');

/** Soporte citación y acta (fase 4) */
$routes->get('soporte', 'SoporteController::create');
$routes->get('soporte/find', 'SoporteController::find'); // AJAX
$routes->post('soporte', 'SoporteController::store');
$routes->post('soporte/(:num)', 'SoporteController::update/$1');

$routes->get('soporte/revision-cliente/(:segment)', 'SoporteController::reviewCliente/$1');
$routes->post('soporte/revision-cliente/(:segment)', 'SoporteController::reviewCliente/$1');



/** Decisión (fase 5) */
$routes->get('decision', 'DecisionController::create');
$routes->get('decision/find', 'DecisionController::find'); // (AJAX)
$routes->post('decision', 'DecisionController::store');
$routes->post('decision/(:num)', 'DecisionController::update/$1');

/** (Ya los tienes) Seguimiento / Línea temporal, Ajustes, etc. (si los vas a exponer ahora) */
// $routes->get('seguimiento', 'SeguimientoController::index');
// $routes->get('linea/(:segment)', 'LineaTemporalController::show/$1');


// Seguimiento (listado de solicitudes)
$routes->get('seguimiento', 'SeguimientoController::index', ['as' => 'seguimiento.index']);

// Línea de tiempo (detalle por consecutivo o id)
// $routes->get('linea-tiempo/(:segment)', 'LineaTiempoController::show/$1', ['as' => 'linea_tiempo.show']);
$routes->get('linea-tiempo/(:segment)', 'LineaTiempoController::show/$1');


// Ajustes
$routes->group('ajustes', static function ($routes) {
    // Pantalla de PIN
    $routes->get('acceso', 'AjustesAccessController::index');
    $routes->post('acceso', 'AjustesAccessController::check');
    $routes->get('salir',  'AjustesAccessController::salir');

    // Faltas protegidas por PIN
    $routes->get('faltas',                 'RitFaltaController::index',  ['filter' => 'ajustesPin']);
    $routes->post('faltas',                'RitFaltaController::store',  ['filter' => 'ajustesPin']);
    $routes->get('faltas/(:num)/edit',     'RitFaltaController::edit/$1',   ['filter' => 'ajustesPin']);
    $routes->post('faltas/(:num)',         'RitFaltaController::update/$1', ['filter' => 'ajustesPin']);
    $routes->post('faltas/(:num)/delete',  'RitFaltaController::delete/$1', ['filter' => 'ajustesPin']);
});

$routes->group('portal-cliente', ['namespace' => 'App\Controllers'], static function ($routes) {
    // Onepage embebible
    $routes->get('/', 'PortalClienteController::index');

    // AJAX: lista de procesos del cliente
    $routes->get('mis-procesos', 'PortalClienteController::misProcesos');

    // AJAX: línea de tiempo resumida para el cliente
    $routes->get('furd/(:segment)/timeline', 'PortalClienteController::timeline/$1');

    // AJAX: respuesta del cliente a la decisión (aprobar / solicitar ajuste)
    $routes->post('furd/(:segment)/respuesta', 'PortalClienteController::responderDecision/$1');
});
