<?php
// SOURCE: /home6/desal/public_html/application/models/CategoryOtomotoModel.php
// FETCHED: 2026-05-27 ~10:16 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2022-10-25 12:38:27, size 569 B


class CategoryOtomotoModel extends CI_Model
{
    public $id;
    public $otomoto_id;
    public $name;
    private $_table = 'duo_category_otomoto';

    public function __construct()
    {
        $this->load->model('OtomotoModel');
    }

    public function integrate()
    {
        $otomotoModel = new OtomotoModel();
        $results = $otomotoModel->getAdvertInfo(6098058995);
        return $results;
    }

    public function getCategories()
    {
        $otomotoModel = new OtomotoModel();
        return $otomotoModel->get_category_data();
    }
}
