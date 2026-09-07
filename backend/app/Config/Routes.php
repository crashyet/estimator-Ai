<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// AI Estimator Analysis Proxy Endpoints
$routes->post('api/rab/analyze', 'RabController::analyze');
$routes->post('api/rab/analyze-image', 'RabController::analyzeImage');

// Projects CRUD Endpoints (Supports both Integer ID and UUID)
$routes->group('api/projects', function ($routes) {
    $routes->get('', 'ProjectController::index');
    $routes->post('', 'ProjectController::create');
    $routes->get('(:segment)', 'ProjectController::show/$1');
    $routes->put('(:segment)', 'ProjectController::update/$1');
    $routes->patch('(:segment)', 'ProjectController::update/$1');
    $routes->delete('(:segment)', 'ProjectController::delete/$1');

    // Project Estimation Endpoints
    $routes->post('(:segment)/save-estimation', 'EstimationController::saveEstimation/$1');
    $routes->get('(:segment)/latest-estimation', 'EstimationController::getLatestEstimation/$1');
});

// Estimation Specific Runs & Items Endpoints
$routes->get('api/estimation-runs/(:segment)', 'EstimationController::getEstimationRun/$1');
$routes->put('api/estimation-items/(:segment)', 'EstimationController::updateItem/$1');
$routes->patch('api/estimation-items/(:segment)', 'EstimationController::updateItem/$1');
$routes->delete('api/estimation-items/(:segment)', 'EstimationController::deleteItem/$1');

// Preflight CORS Options Handler for API
$routes->options('api/(:any)', 'Home::index');