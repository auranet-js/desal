<?php
// SOURCE: /home6/desal/public_html/application/controllers/duocms/Otomoto/ParametersController.php
// FETCHED: 2026-05-27 ~10:16 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2021-08-11 05:56:32, size 391 B

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class ParametersController extends Backend_Controller  {

    function __construct() {
        parent::__construct();
        $this->load->vars(['activePage' => 'otomoto']);
    }

    public function parametersBind(){

        $this->layout('duocms/Shop/Otomoto/parametersMain.php', []);
    }
}
