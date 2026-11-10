<?php
namespace App\Controller;

use LoanProductData;
use ViewEngine;
use Req;
use Response;
use App\Service\AuthService;

class LoanProductController {
    private $baseFolder;

    public function __construct() {
        $this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    }

    public function index() {
        $products = LoanProductData::getAll();
        ViewEngine::render('loan_products/index.html.twig', [
            'products' => $products
        ]);
    }

    public function create() {
        $this->checkAccess();
        $p = new LoanProductData();
        $p->name = trim(Req::post('name', ''));
        $p->description = trim(Req::post('description', ''));
        $p->interest_rate = (float)Req::post('interest_rate', 5.00);
        $p->interest_period = (int)Req::post('interest_period', 1);
        $p->min_amount = (float)Req::post('min_amount', 0);
        $p->max_amount = (float)Req::post('max_amount', 0);
        $p->min_term = (int)Req::post('min_term', 1);
        $p->max_term = (int)Req::post('max_term', 12);
        $p->frequency = (int)Req::post('frequency', 4);
        $p->is_active = 1;

        $id = $p->add();

        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

        if ($isAjax) {
            Response::json(['success' => $id > 0, 'id' => $id, 'message' => 'Producto creado']);
            return;
        }

        $_SESSION['success'] = 'Producto creado con éxito.';
        Response::redirect(($this->baseFolder ?: '') . '/loan_products');
    }

    public function update() {
        $this->checkAccess();
        $id = (int)Req::post('id');
        $p = LoanProductData::getById($id);
        if ($p) {
            $p->name = trim(Req::post('name', $p->name));
            $p->description = trim(Req::post('description', $p->description));
            $p->interest_rate = (float)Req::post('interest_rate', $p->interest_rate);
            $p->interest_period = (int)Req::post('interest_period', $p->interest_period);
            $p->min_amount = (float)Req::post('min_amount', $p->min_amount);
            $p->max_amount = (float)Req::post('max_amount', $p->max_amount);
            $p->min_term = (int)Req::post('min_term', $p->min_term);
            $p->max_term = (int)Req::post('max_term', $p->max_term);
            $p->frequency = (int)Req::post('frequency', $p->frequency);
            $p->update();

            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
                || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

            if ($isAjax) {
                Response::json(['success' => true, 'message' => 'Producto actualizado']);
                return;
            }

            $_SESSION['updated'] = 'Producto actualizado correctamente.';
            Response::redirect(($this->baseFolder ?: '') . '/loan_products');
            return;
        }

        Response::json(['success' => false, 'message' => 'Producto no encontrado'], 404);
    }

    public function delete() {
        $this->checkAccess();
        $id = (int)Req::post('id');
        LoanProductData::delById($id);
        Response::json(['success' => true, 'message' => 'Producto eliminado']);
    }

    public function getJson(array $vars) {
        $this->checkAccess();
        $id = (int)($vars['id'] ?? 0);
        $product = LoanProductData::getById($id);
        if ($product) {
            Response::json($product);
            return;
        }
        Response::json(['error' => 'No encontrado'], 404);
    }
}
