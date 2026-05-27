<?php
// SOURCE: /home6/desal/public_html/application/controllers/duocms/Otomoto_parameters.php
// FETCHED: 2026-05-27 ~10:16 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2021-08-11 05:56:33, size 1625 B

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Otomoto_parameters extends Backend_Controller  {

    function __construct() {
        parent::__construct();
        $this->load->model("OtomotoModel");
        $this->load->vars(['activePage' => 'otomoto']);
    }

    public function index(){
        if($this->input->get('deleteId')){
            $this->OtomotoModel->deleteParameterVisibility($this->input->get('deleteId'));
        }
        if(!empty($this->input->post('parameter'))){
            //die(json_encode($this->input->post()));
            $this->OtomotoModel->saveParametersVisibility(
                    $this->input->post('category'),
                    $this->input->post('part'),
                    $this->input->post('parameter')
            );
        }
        $categories = $this->OtomotoModel->getCategoriesArray();
        $this->layout('duocms/Shop/Otomoto/parametersMain.php', [
            'categories' => $categories,
            'partsParameters' => $this->OtomotoModel->getAllParts()
        ]);
    }

    public function parts($categoryId){
        $oto_category = $this->OtomotoModel->get_category_data($categoryId);
        $parts = [];
        foreach ($oto_category->parameters as $op) {
            if($op->code != 'parts-type') { continue; }
            foreach ($op->options as $z => $o) {
                $parts[$z] = $o->pl;
            }

        }
        $this->load->view('duocms/Shop/Otomoto/partsOptionsSelect.php', [
            'parts' => $parts,
            'parameters' => $oto_category->parameters
        ]);
    }
}
