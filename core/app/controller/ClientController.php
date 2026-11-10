<?php
namespace App\Controller;

use ClientData;
use ViewEngine;
use Req;
use Response;
use App\Service\AuthService;

class ClientController {
    private $baseFolder;

    public function __construct() {
        $this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    }

    public function index() {
        $clients = ClientData::getAll();

        ViewEngine::render('clients/index.html.twig', [
            'clients' => $clients
        ]);
    }

    public function create() {
        $c = new ClientData();
        $c->name = trim(Req::post('name', ''));
        $c->lastname = trim(Req::post('lastname', ''));
        $c->document = trim(Req::post('document', ''));
        $c->phone = trim(Req::post('phone', ''));
        $c->email = trim(Req::post('email', ''));
        $c->address = trim(Req::post('address', ''));
        $c->occupation = trim(Req::post('occupation', ''));
        $c->employer = trim(Req::post('employer', ''));
        $c->monthly_income = (float)Req::post('monthly_income', 0);
        $c->is_active = 1;

        $id = $c->add();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

        if ($isAjax) {
            Response::json(['success' => $id > 0, 'id' => $id, 'message' => 'Cliente guardado']);
            return;
        }

        $_SESSION['success'] = 'Cliente creado con éxito.';
        Response::redirect(($this->baseFolder ?: '') . '/clients');
    }

    public function update() {
        $id = (int)Req::post('id');
        $c = ClientData::getById($id);
        if ($c) {
            $c->name = trim(Req::post('name', $c->name));
            $c->lastname = trim(Req::post('lastname', $c->lastname));
            $c->document = trim(Req::post('document', $c->document));
            $c->phone = trim(Req::post('phone', $c->phone));
            $c->email = trim(Req::post('email', $c->email));
            $c->address = trim(Req::post('address', $c->address));
            $c->occupation = trim(Req::post('occupation', $c->occupation));
            $c->employer = trim(Req::post('employer', $c->employer));
            $c->monthly_income = (float)Req::post('monthly_income', $c->monthly_income);
            $c->update();

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

            if ($isAjax) {
                Response::json(['success' => true, 'message' => 'Cliente actualizado']);
                return;
            }

            $_SESSION['updated'] = 'Cliente actualizado correctamente.';
            Response::redirect(($this->baseFolder ?: '') . '/clients');
            return;
        }

        Response::json(['success' => false, 'message' => 'Cliente no encontrado'], 404);
    }

    public function delete() {
        $id = (int)Req::post('id');
        ClientData::delById($id);
        Response::json(['success' => true, 'message' => 'Cliente eliminado']);
    }

    public function getJson(array $vars) {
        $id = (int)($vars['id'] ?? 0);
        $client = ClientData::getById($id);
        if ($client) {
            Response::json($client);
            return;
        }
        Response::json(['error' => 'No encontrado'], 404);
    }
}
