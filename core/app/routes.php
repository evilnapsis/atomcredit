<?php
use FastRoute\RouteCollector;

return function(RouteCollector $r) {
    // Autenticación
    $r->addRoute('GET', '/login', [App\Controller\AuthController::class, 'login']);
    $r->addRoute('POST', '/login', [App\Controller\AuthController::class, 'doLogin']);
    $r->addRoute('GET', '/logout', [App\Controller\AuthController::class, 'logout']);

    // Panel Principal
    $r->addRoute('GET', '/', [App\Controller\HomeController::class, 'index']);

    // Pagos del día y atrasados
    $r->addRoute('GET', '/payments/today', [App\Controller\PaymentController::class, 'today']);
    $r->addRoute('GET', '/payments/overdue', [App\Controller\PaymentController::class, 'overdue']);

    // Préstamos
    $r->addRoute('GET', '/loans', [App\Controller\LoanController::class, 'index']);
    $r->addRoute('GET', '/loans/new', [App\Controller\LoanController::class, 'new']);
    $r->addRoute('GET', '/loans/{id:\d+}', [App\Controller\LoanController::class, 'show']);
    $r->addRoute('GET', '/loans/{id:\d+}/pdf', [App\Controller\LoanController::class, 'pdf']);
    $r->addRoute('POST', '/loans/create', [App\Controller\LoanController::class, 'create']);
    $r->addRoute('POST', '/loans/pay', [App\Controller\LoanController::class, 'pay']);
    $r->addRoute('POST', '/loans/status', [App\Controller\LoanController::class, 'updateStatus']);

    // Reportes
    $r->addRoute('GET', '/reports/loans', [App\Controller\ReportController::class, 'loans']);
    $r->addRoute('GET', '/reports/loans/pdf', [App\Controller\ReportController::class, 'loansPdf']);
    $r->addRoute('GET', '/reports/payments', [App\Controller\ReportController::class, 'payments']);
    $r->addRoute('GET', '/reports/payments/pdf', [App\Controller\ReportController::class, 'paymentsPdf']);

    // Clientes
    $r->addRoute('GET', '/clients', [App\Controller\ClientController::class, 'index']);
    $r->addRoute('POST', '/clients/create', [App\Controller\ClientController::class, 'create']);
    $r->addRoute('POST', '/clients/update', [App\Controller\ClientController::class, 'update']);
    $r->addRoute('POST', '/clients/delete', [App\Controller\ClientController::class, 'delete']);
    $r->addRoute('GET', '/clients/{id:\d+}/json', [App\Controller\ClientController::class, 'getJson']);

    // Productos de Préstamo
    $r->addRoute('GET', '/loan_products', [App\Controller\LoanProductController::class, 'index']);
    $r->addRoute('POST', '/loan_products/create', [App\Controller\LoanProductController::class, 'create']);
    $r->addRoute('POST', '/loan_products/update', [App\Controller\LoanProductController::class, 'update']);
    $r->addRoute('POST', '/loan_products/delete', [App\Controller\LoanProductController::class, 'delete']);
    $r->addRoute('GET', '/loan_products/{id:\d+}/json', [App\Controller\LoanProductController::class, 'getJson']);

    // Configuración
    $r->addRoute('GET', '/settings', [App\Controller\SettingController::class, 'index']);
    $r->addRoute('POST', '/settings/general', [App\Controller\SettingController::class, 'updateGeneral']);

    // Usuarios
    $r->addRoute('GET', '/users', [App\Controller\UserController::class, 'index']);
    $r->addRoute('POST', '/users/create', [App\Controller\UserController::class, 'create']);
    $r->addRoute('POST', '/users/update', [App\Controller\UserController::class, 'update']);
    $r->addRoute('POST', '/users/delete', [App\Controller\UserController::class, 'delete']);
    $r->addRoute('GET', '/users/{id:\d+}/json', [App\Controller\UserController::class, 'getJson']);
};
