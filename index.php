<?php
/**
 * AtomCredit 2 - Front Controller
 * Arquitectura: Legobox 5
 * Autor: Evilnapsis
 */

define("ROOT", dirname(__FILE__));

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "core/autoload.php";
include_once "core/app/autoload.php";

ob_start();
session_start();
Core::$root = "";

use App\Service\AuthService;

$routes = require __DIR__ . '/core/app/routes.php';
$dispatcher = FastRoute\simpleDispatcher($routes);

$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if (!empty($baseFolder) && strpos($uri, $baseFolder) === 0) {
    $uri = substr($uri, strlen($baseFolder));
}
if (empty($uri)) {
    $uri = '/';
}

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

if ($routeInfo[0] === FastRoute\Dispatcher::FOUND) {
    if ($httpMethod === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Session::validateCsrf($token)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Token CSRF inválido. Por favor recarga e intenta de nuevo.']);
            exit;
        }
    }

    $protectedControllers = [
        App\Controller\HomeController::class,
        App\Controller\LoanController::class,
        App\Controller\ClientController::class,
        App\Controller\LoanProductController::class,
        App\Controller\SettingController::class,
        App\Controller\UserController::class,
        App\Controller\ReportController::class,
    ];

    $handler = $routeInfo[1];
    if (in_array($handler[0], $protectedControllers, true) && !AuthService::check()) {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_POST['ajax']) && $_POST['ajax'] == '1');
        if ($isAjax) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'message' => 'Sesión expirada. Por favor inicie sesión nuevamente.']);
            exit;
        }
        header('Location: ' . ($baseFolder ?: '') . '/login');
        exit;
    }

    [$controllerClass, $method] = $handler;
    $controller = new $controllerClass();
    $controller->$method($routeInfo[2]);
    exit;
}

http_response_code(404);
ViewEngine::render('404.html.twig');
exit;
