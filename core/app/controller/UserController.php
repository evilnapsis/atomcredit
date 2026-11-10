<?php
namespace App\Controller;

use UserData;
use RolData;
use ViewEngine;
use Req;
use Response;
use App\Service\AuthService;

class UserController {
    private $baseFolder;

    public function __construct() {
        $this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    }

    public function index() {
        $users = UserData::getAll();
        $roles = RolData::getAll();
        ViewEngine::render('users/index.html.twig', [
            'users' => $users,
            'roles' => $roles
        ]);
    }

    public function create() {
        $this->checkAccess();
        $u = new UserData();
        $u->name = trim(Req::post('name', ''));
        $u->lastname = trim(Req::post('lastname', ''));
        $u->username = trim(Req::post('username', ''));
        $u->email = trim(Req::post('email', ''));
        $pwd = trim(Req::post('password', ''));
        $u->password = !empty($pwd) ? sha1(md5($pwd)) : sha1(md5('123456'));
        $u->rol_id = (int)Req::post('rol_id', 1);
        $u->is_active = 1;

        $id = $u->add();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

        if ($isAjax) {
            Response::json(['success' => $id > 0, 'id' => $id, 'message' => 'Usuario creado']);
            return;
        }

        $_SESSION['success'] = 'Usuario creado con éxito.';
        Response::redirect(($this->baseFolder ?: '') . '/users');
    }

    public function update() {
        $this->checkAccess();
        $id = (int)Req::post('id');
        $u = UserData::getById($id);
        if ($u) {
            $u->name = trim(Req::post('name', $u->name));
            $u->lastname = trim(Req::post('lastname', $u->lastname));
            $u->username = trim(Req::post('username', $u->username));
            $u->email = trim(Req::post('email', $u->email));
            $pwd = trim(Req::post('password', ''));
            if (!empty($pwd)) {
                $u->password = sha1(md5($pwd));
            }
            $u->rol_id = (int)Req::post('rol_id', $u->rol_id);
            $u->is_active = Req::post('is_active', 1) ? 1 : 0;
            $u->update();

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

            if ($isAjax) {
                Response::json(['success' => true, 'message' => 'Usuario actualizado']);
                return;
            }

            $_SESSION['updated'] = 'Usuario actualizado correctamente.';
            Response::redirect(($this->baseFolder ?: '') . '/users');
            return;
        }

        Response::json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
    }

    public function delete() {
        $this->checkAccess();
        $id = (int)Req::post('id');
        if ($id === 1 || $id === (int)($_SESSION['user_id'] ?? 0)) {
            Response::json(['success' => false, 'message' => 'No se puede eliminar este usuario'], 400);
            return;
        }
        UserData::delById($id);
        Response::json(['success' => true, 'message' => 'Usuario eliminado']);
    }

    public function getJson(array $vars) {
        $this->checkAccess();
        $id = (int)($vars['id'] ?? 0);
        $user = UserData::getById($id);
        if ($user) {
            $user->password = ''; // Omitir hash
            Response::json($user);
            return;
        }
        Response::json(['error' => 'No encontrado'], 404);
    }
}
