<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');

$routes->get('empleados/lookup/(:segment)', 'EmpleadoLookupController::getByCedula/$1');


