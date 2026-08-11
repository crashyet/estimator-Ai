<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');
$routes->post('api/rab/analyze', 'RabController::analyze');
$routes->post('api/rab/analyze-image', 'RabController::analyzeImage');