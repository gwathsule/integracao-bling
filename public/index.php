<?php

error_reporting(E_ERROR | E_PARSE);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

use Klein\Klein;
use Klein\Request;
use Klein\Response;
use Src\Adapter\Controller\APIBlingController;
use Src\Adapter\Controller\Bling;


require_once '../vendor/autoload.php';

$server = new Klein();

$server->respond('GET', '/', function (Request $request, Response $response, $service, $app) {
    $service->render("../view/integration.html");
});

$server->respond('POST', '/bling-integration', function (Request $request, Response $response) {
    $controller = new APIBlingController();
    return $controller->registerAppBling($request, $response);
});

$server->respond('GET', '/bling-callback', function (Request $request, Response $response) {
    $controller = new APIBlingController();
    return $controller->callback($request, $response);
});

$server->respond('GET', '/api/bling', function (Request $request, Response $response) {
    $controller = new Bling($request, $response);
    return $controller->api();
});

$server->respond('POST', '/api/bling', function (Request $request, Response $response) {
    $controller = new Bling($request, $response);
    return $controller->api();
});

$server->dispatch();