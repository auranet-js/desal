<?php
// SOURCE: /home6/desal/public_html/application/controllers/api/Otomoto.php
// FETCHED: 2026-05-27 ~10:16 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2021-08-11 05:56:23, size 374 B
// UWAGA: 374B stub — nie ma callbacku OAuth jak Allegro

defined('BASEPATH') OR exit('No direct script access allowed');
class Otomoto extends Frontend_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('OtomotoModel');
    }

    public function allAdverts(){
        $allAdverts = $this->OtomotoModel->getAllAdverts();
        echo $allAdverts;
    }

}
