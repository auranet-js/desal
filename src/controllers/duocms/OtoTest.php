<?php
// SOURCE: /home6/desal/public_html/application/controllers/duocms/OtoTest.php
// FETCHED: 2026-05-27 ~10:16 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2022-10-25 12:35:04, size 426 B
// UWAGA: ślad porzuconej próby naprawy Otomoto z jesieni 2022

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class OtoTest extends Backend_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('CategoryOtomotoModel');
    }

    public function index()
    {
        $productCategoryModel = new CategoryOtomotoModel();
        var_dump($productCategoryModel->integrate());

        return 2;
    }
}
