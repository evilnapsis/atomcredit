<?php
namespace App\Controller;

use App\Service\AuthService;
use ViewEngine;
use Req;
use Response;

class AuthController {
    private $baseFolder;

    public function __construct() {
        $this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    }

    public function login() {
        if (AuthService::check()) {
            Response::redirect($this->baseFolder ?: '/');
            return;
        }
        ViewEngine::render('auth/login.html.twig');
    }

    public function doLogin() {
        $username = trim(Req::post('username', ''));
        $password = trim(Req::post('password', ''));

        if (empty($username) || empty($password)) {
            $_SESSION['error'] = 'Por favor ingresa usuario y contraseña.';
            Response::redirect(($this->baseFolder ?: '') . '/login');
            return;
        }

        if (AuthService::attempt($username, $password)) {
            Response::redirect($this->baseFolder ?: '/');
            return;
        }

        $_SESSION['error'] = 'Usuario o contraseña incorrectos.';
        Response::redirect(($this->baseFolder ?: '') . '/login');
    }

    public function logout() {
        AuthService::logout();
        Response::redirect(($this->baseFolder ?: '') . '/login');
    }
}
