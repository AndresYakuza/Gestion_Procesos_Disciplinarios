<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'FurdController::index');

$routes->get('empleados/lookup/(:segment)', 'EmpleadoLookupController::getByCedula/$1');


$routes->group('contratos', static function ($routes) {
    $routes->get('/',      'Api\ContratosController::index');
    $routes->get('(:num)', 'Api\ContratosController::show/$1');
});



// $routes->resource('furd', [
//     'controller' => 'FurdController',
//     'only' => ['index','show','create','update','delete'],
// ]);
$routes->post('furd/(:num)/faltas',          'Api\FurdController::attachFalta/$1');
$routes->delete('furd/(:num)/faltas/(:num)', 'Api\FurdController::detachFalta/$1/$2');
$routes->post('furd/(:num)/adjuntos',        'Api\FurdController::uploadAdjunto/$1');
$routes->delete('furd/adjuntos/(:num)',      'Api\FurdController::deleteAdjunto/$1');

$routes->get('furd', 'FurdController::index');
$routes->get('furd/create', 'FurdController::form');
$routes->post('furd', 'FurdController::store'); 
$routes->post('furd', 'FurdController::create');
$routes->get('furd/(:num)', 'FurdController::show/$1');
$routes->put('furd/(:num)', 'FurdController::update/$1');
$routes->delete('furd/(:num)', 'FurdController::delete/$1');

// CITACIÓN (esta es la nueva vista)
$routes->get('citacion',               'CitacionController::create');
$routes->post('citacion',              'CitacionController::store');
$routes->get('citacion/adjuntos/(:num)','CitacionController::adjuntosByFurd/$1');

// Cargos y Descargos
$routes->get('cargos-descargos',  'CargosDescargosController::create');
$routes->post('cargos-descargos', 'CargosDescargosController::store');

// Soporte de citación y acta
$routes->get('soporte',  'SoporteController::create');
$routes->post('soporte', 'SoporteController::store');

// Decisión
$routes->get('decision',  'DecisionController::create');
$routes->post('decision', 'DecisionController::store');

// Seguimiento (listado de solicitudes)
$routes->get('seguimiento', 'SeguimientoController::index', ['as' => 'seguimiento.index']);

// Línea de tiempo (detalle por consecutivo o id)
$routes->get('linea-tiempo/(:segment)', 'LineaTiempoController::show/$1', ['as' => 'linea_tiempo.show']);