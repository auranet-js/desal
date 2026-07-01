<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Templates extends Backend_Controller {

    public $languages = array();

    public function __construct() {
        parent::__construct();
        $langs = get_languages();
        foreach ($langs as $l) {
            $this->languages[] = $l->short;
        }
        $this->load->model('CategoryOtomotoModel');
        $this->load->vars(['activePage' => 'templates']);
    }

    public function index() {
        $this->load->model('TemplatesModel');
        $data['templates'] = $this->TemplatesModel->get_template_list();
        $this->layout('duocms/Templates/index', $data);
    }

    public function create() {
        $pdata = $this->input->post();
        if (!empty($pdata)) {
            $name = !empty($pdata['template_name']) ? $pdata['template_name'] : 'Szablon';
            $this->load->model('TemplatesModel');
            $template_id = $this->TemplatesModel->add_template($name);
            if ($template_id > 0) {
                if (!empty($pdata['name'])) {
                    for ($i = 0; $i < count($pdata['name']); $i++) {
                        if (!empty($pdata['name'][$i]) && !empty($pdata['allegro_category'][$i]) && !empty($pdata['parent_id'][$i])) {
                            $args = array(
                                'template_id' => $template_id,
                                'name' => $pdata['name'][$i],
                                'allegro_category' => $pdata['allegro_category'][$i],
                                'otomoto_part_type' => $pdata['otomoto_part_type'][$i], 'otomoto_parts_category' => isset($pdata['otomoto_parts_category'][$i]) ? $pdata['otomoto_parts_category'][$i] : null,
                                'otomoto_category_id' => $pdata['otomoto_category'][$i],
                                'category_id' => $pdata['parent_id'][$i],
                                'delivery_id' => $pdata['delivery_id'][$i],
                                 'shop' => !empty($pdata['shop'][$i]) ? 1 : 0,
                             'allegro' =>  !empty($pdata['allegro'][$i]) ? 1 : 0,
                             'otomoto' =>  !empty($pdata['otomoto'][$i]) ? 1 : 0,
                            );
                            $this->TemplatesModel->add_template_item($args);
                        }
                    }
                     setAlert('success', "Zapisano szablon");
                }
            } else {
                setAlert('error', "Nie udało się dodać szablonu");
            }
        }
        $this->load->model('OfferCategoryModel');
        $categories = (new OfferCategoryModel())->getListForDropdown();
        $this->load->model('AllegroModel');
        $deliveries = json_decode($this->AllegroModel->get_shipping_rates());
        $deliveries_array = [];
         if(!empty($deliveries->shippingRates)){
        foreach($deliveries->shippingRates as $sr){
            $deliveries_array[$sr->id] = $sr->name;
        }
         }
        if(empty($deliveries_array)){
            $deliveries_array[-1] = 'Dodaj cenniki lub zaloguj się na allegro';
        }
         $this->load->model('OtomotoModel');
        $oto_parts = $this->CategoryOtomotoModel->get_parts_type_cached();
        $otoMotoCategories = $this->CategoryOtomotoModel->get_all_cached();
        $this->layout('duocms/Templates/form', [
            'parents' => $categories,
            'deliveries' => $deliveries_array,
            'oto_parts' => $oto_parts,
            'oto_categories' => $otoMotoCategories,
            'oto_parts_category' => $this->CategoryOtomotoModel->get_parts_category_cached()
        ]);
    }

    public function new_row(){
        $number = $this->input->post('number');
        if(isset($number) && $number != null){
             $this->load->model('OfferCategoryModel');
            $categories = (new OfferCategoryModel())->getListForDropdown();
            $this->load->model('AllegroModel');
        $deliveries = json_decode($this->AllegroModel->get_shipping_rates());
        $deliveries_array = [];
        if(!empty($deliveries->shippingRates)){
        foreach($deliveries->shippingRates as $sr){
            $deliveries_array[$sr->id] = $sr->name;
        }}
        if(empty($deliveries_array)){
            $deliveries_array[-1] = 'Dodaj cenniki lub zaloguj się na allegro';
        }
            $this->load->model('OtomotoModel');
            $oto_parts = $this->CategoryOtomotoModel->get_parts_type_cached();
            $this->load->view('duocms/Templates/form-temp.php', [
                'number' => $this->input->post('number'),
                 'parents' => $categories,
                'deliveries' => $deliveries_array,
                'oto_parts' => $oto_parts,
                'oto_categories' => $this->CategoryOtomotoModel->get_all_cached(),
                'oto_parts_category' => $this->CategoryOtomotoModel->get_parts_category_cached()
            ]);
        }
    }

    public function edit($id){
        $this->load->model('TemplatesModel');
        $pdata = $this->input->post();
        if (!empty($pdata)) {
            $name = !empty($pdata['template_name']) ? $pdata['template_name'] : 'Szablon';
            $this->load->model('TemplatesModel');
            $this->TemplatesModel->update_template($id, $name);
            if (!empty($pdata['name'])) {
                for ($i = 0; $i < count($pdata['name']); $i++) {
                    if (!empty($pdata['id'][$i])) {
                         $args = array(
                                'name' => $pdata['name'][$i],
                                'allegro_category' => $pdata['allegro_category'][$i],
                              'otomoto_part_type' => $pdata['otomoto_part_type'][$i], 'otomoto_parts_category' => isset($pdata['otomoto_parts_category'][$i]) ? $pdata['otomoto_parts_category'][$i] : null,
                             'otomoto_category_id' => $pdata['otomoto_category'][$i],
                                'category_id' => $pdata['parent_id'][$i],
                             'delivery_id' => $pdata['delivery_id'][$i],
                             'shop' => !empty($pdata['shop'][$i]) ? 1 : 0,
                             'allegro' =>  !empty($pdata['allegro'][$i]) ? 1 : 0,
                             'otomoto' =>  !empty($pdata['otomoto'][$i]) ? 1 : 0,
                            );
                        $this->TemplatesModel->update_template_item($pdata['id'][$i], $args);
                    } else {
                        if (!empty($pdata['name'][$i]) && !empty($pdata['allegro_category'][$i]) && !empty($pdata['parent_id'][$i])) {
                            $args = array(
                                'template_id' => $id,
                                'name' => $pdata['name'][$i],
                                'allegro_category' => $pdata['allegro_category'][$i],
                                 'otomoto_part_type' => $pdata['otomoto_part_type'][$i], 'otomoto_parts_category' => isset($pdata['otomoto_parts_category'][$i]) ? $pdata['otomoto_parts_category'][$i] : null,
                                'otomoto_category_id' => $pdata['otomoto_category'][$i],
                                'category_id' => $pdata['parent_id'][$i],
                                'delivery_id' => $pdata['delivery_id'][$i],
                                'shop' => !empty($pdata['shop'][$i]) ? 1 : 0,
                             'allegro' =>  !empty($pdata['allegro'][$i]) ? 1 : 0,
                             'otomoto' =>  !empty($pdata['otomoto'][$i]) ? 1 : 0
                            );
                            $this->TemplatesModel->add_template_item($args);
                        }
                    }
                }
            }
        }

        $data['template'] = $this->TemplatesModel->get_template($id);
        $data['template_items'] = $this->TemplatesModel->get_template_items($id);
        $this->load->model('OfferCategoryModel');
        $data['parents'] = (new OfferCategoryModel())->getListForDropdown();
        $this->load->model('AllegroModel');
        $deliveries = json_decode($this->AllegroModel->get_shipping_rates());
        $deliveries_array = [];
        if(!empty($deliveries->shippingRates)){
        foreach($deliveries->shippingRates as $sr){
            $deliveries_array[$sr->id] = $sr->name;
        }
        }
        if(empty($deliveries_array)){
            $deliveries_array[-1] = 'Dodaj cenniki lub zaloguj się na allegro';
        }
        $this->load->model('OtomotoModel');
        $oto_parts = $this->CategoryOtomotoModel->get_parts_type_cached();
        $data['oto_parts'] = $oto_parts;
        $data['deliveries'] = $deliveries_array;
        $data['oto_categories'] = $this->CategoryOtomotoModel->get_all_cached();
        $data['oto_parts_category'] = $this->CategoryOtomotoModel->get_parts_category_cached();
        $this->layout('duocms/Templates/edit_form', $data);
    }

    public function delete_item($id){
        $this->load->model('TemplatesModel');
        $this->TemplatesModel->delete_template_item($id);
        redirect($_SERVER['HTTP_REFERER']);
    }

    public function delete($id){
        $this->load->model('TemplatesModel');
        $this->TemplatesModel->delete_template($id);
        redirect(site_url('duocms/templates'));
    }
}
