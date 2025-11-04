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