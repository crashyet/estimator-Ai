<?php

use CodeIgniter\Router\RouteCollection;

$routes->get('/', 'ProjectWebController::index');
$routes->get('proyek', 'ProjectWebController::index');
$routes->get('projects', 'ProjectWebController::index');
$routes->get('buat_proyek', 'ProjectWebController::create');
$routes->get('proyek/create', 'ProjectWebController::create');
$routes->get('anggaran', 'ProjectWebController::anggaran');
$routes->get('proyek/(:segment)/anggaran', 'ProjectWebController::anggaran/$1');
$routes->get('proyek/(:segment)/deteksi', 'ProjectWebController::anggaran/$1');
$routes->get('pemetaan-ahsp', 'ProjectWebController::pemetaanAhsp');
$routes->get('proyek/(:segment)/pemetaan-ahsp', 'ProjectWebController::pemetaanAhsp/$1');
$routes->get('rab', 'ProjectWebController::rab');
$routes->get('proyek/(:segment)/rab', 'ProjectWebController::rab/$1');

// AI Estimator Analysis Proxy Endpoints
$routes->post('api/rab/analyze', 'RABController::analyze');
$routes->post('api/rab/analyze-image', 'RABController::analyzeImage');
$routes->post('api/rab/analyze-prompt', 'RABController::analyzePrompt');

// AHSP Master Data & Mapping Proxy Endpoints
$routes->get('api/ahsp/list', 'AHSPController::list');
$routes->get('api/ahsp/search', 'AHSPController::search');
$routes->post('api/ahsp/map-item', 'AHSPController::mapItem');
$routes->get('api/ahsp/stats', 'AHSPController::stats');

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

// Interactive Web API Documentation (Scalar & Swagger UI)
$routes->match(['get', 'head'], 'docs', 'DocsController::index');
$routes->match(['get', 'head'], 'api/docs', 'DocsController::index');
$routes->match(['get', 'head'], 'docs/swagger', 'DocsController::swagger');
$routes->match(['get', 'head'], 'api/openapi.json', 'DocsController::openapi');

// Preflight CORS Options Handler for API
$routes->options('api/(:any)', 'Home::index');