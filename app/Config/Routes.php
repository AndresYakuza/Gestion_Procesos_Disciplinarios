<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// =====================================================
// Home
// =====================================================
$routes->get('/', 'FurdController::index');

// =====================================================
// Lookup / utilidades
// =====================================================
$routes->get('empleados/lookup/(:segment)', 'EmpleadoLookupController::getByCedula/$1');

$routes->get('files/furd/(:segment)/(:any)', 'FileController::furd/$1/$2');

$routes->get('adjuntos/(:num)/download', 'AdjuntosController::download/$1');
$routes->get('adjuntos/(:num)/open', 'AdjuntosController::open/$1');
$routes->post('adjuntos/(:num)/delete', 'AdjuntosController::delete/$1');

// =====================================================
// FURD - Fase 1
// =====================================================
$routes->get('furd', 'FurdController::index');
$routes->post('furd', 'FurdController::store');
$routes->delete('furd/(:num)', 'FurdController::destroy/$1');

$routes->post('furd/(:num)/faltas', 'FurdController::attachFalta/$1');
$routes->delete('furd/(:num)/faltas/(:num)', 'FurdController::detachFalta/$1/$2');

$routes->get('furd/adjuntos', 'FurdController::adjuntos');
$routes->get('furd/(:num)/formato', 'FurdController::descargarFormato/$1');

// =====================================================
// Citación - Fase 2
// =====================================================
$routes->get('citacion', 'CitacionController::create');
$routes->get('citacion/find', 'CitacionController::find');
$routes->post('citacion', 'CitacionController::store');
$routes->post('citacion/(:num)', 'CitacionController::update/$1');

$routes->get('citacion/docx/(:num)', 'CitacionController::downloadDocx/$1');

// =====================================================
// Descargos - Fase 3
// =====================================================
$routes->get('descargos', 'DescargosController::create');
$routes->get('descargos/find', 'DescargosController::find');
$routes->post('descargos', 'DescargosController::store');
$routes->post('descargos/(:num)', 'DescargosController::update/$1');

// =====================================================
// Soporte - Fase 4
// =====================================================
$routes->get('soporte', 'SoporteController::create');
$routes->get('soporte/find', 'SoporteController::find');
$routes->post('soporte', 'SoporteController::store');
$routes->post('soporte/(:num)', 'SoporteController::update/$1');

$routes->get('soporte/revision-cliente/(:segment)', 'SoporteController::reviewCliente/$1');
$routes->post('soporte/revision-cliente/(:segment)', 'SoporteController::reviewCliente/$1');

$routes->get('soporte/reviewClienteOk/(:any)', 'SoporteController::reviewClienteOk/$1');

// =====================================================
// Decisión - Fase 5
// =====================================================
$routes->get('decision', 'DecisionController::create');
$routes->get('decision/find', 'DecisionController::find');
$routes->post('decision', 'DecisionController::store');
$routes->post('decision/(:num)', 'DecisionController::update/$1');

$routes->get('decision/plantilla/suspension', 'DecisionController::plantillaSuspension');

// =====================================================
// Seguimiento / Línea de tiempo
// =====================================================
$routes->get('seguimiento', 'SeguimientoController::index', ['as' => 'seguimiento.index']);
$routes->get('linea-tiempo/(:segment)', 'LineaTiempoController::show/$1');

// =====================================================
// Ajustes
// =====================================================
$routes->group('ajustes', static function ($routes) {
    // Acceso por PIN
    $routes->get('acceso', 'AjustesAccessController::index');
    $routes->post('acceso', 'AjustesAccessController::check');
    $routes->get('salir', 'AjustesAccessController::salir');

    // Faltas protegidas
    $routes->get('faltas', 'RitFaltaController::index', ['filter' => 'ajustesPin']);
    $routes->post('faltas', 'RitFaltaController::store', ['filter' => 'ajustesPin']);
    $routes->get('faltas/(:num)/edit', 'RitFaltaController::edit/$1', ['filter' => 'ajustesPin']);
    $routes->post('faltas/(:num)', 'RitFaltaController::update/$1', ['filter' => 'ajustesPin']);
    $routes->post('faltas/(:num)/delete', 'RitFaltaController::delete/$1', ['filter' => 'ajustesPin']);
});

$routes->get('ajustes/faltas/all', 'RitFaltaController::all');

// =====================================================
// Portal cliente
// =====================================================
$routes->group('portal-cliente', ['namespace' => 'App\Controllers'], static function ($routes) {
    $routes->get('/', 'PortalClienteController::index');
    $routes->get('mis-procesos', 'PortalClienteController::misProcesos');
    $routes->get('furd/(:segment)/timeline', 'PortalClienteController::timeline/$1');
});