<?php
namespace App\Controller;

use SettingData;
use ViewEngine;
use Req;
use Response;

class SettingController {
    private $baseFolder;

    public function __construct() {
        $this->baseFolder = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    }

    public function index() {
        $settings = [
            "company_name"  => SettingData::getValue("company_name", "AtomCredit"),
            "company_email" => SettingData::getValue("company_email", ""),
            "company_phone" => SettingData::getValue("company_phone", ""),
            "currency"      => SettingData::getValue("currency", "Q")
        ];

        ViewEngine::render('settings/index.html.twig', [
            'settings'    => $settings,
            'active_menu' => 'settings'
        ]);
    }

    public function updateGeneral() {
        SettingData::updateValue("company_name", Req::post('company_name'));
        SettingData::updateValue("company_email", Req::post('company_email'));
        SettingData::updateValue("company_phone", Req::post('company_phone'));
        SettingData::updateValue("currency", Req::post('currency'));

        $_SESSION['success'] = 'Configuración general guardada.';
        Response::redirect(($this->baseFolder ?: '') . '/settings');
    }
}
