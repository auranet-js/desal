<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Cars extends Backend_Controller {

    public $languages = array();

    public function __construct() {
        parent::__construct();
        $langs = get_languages();
        foreach ($langs as $l) {
            $this->languages[] = $l->short;
        }
        $this->load->model('CarModel');

        $this->load->vars(['activePage' => 'cars']);
    }

    public function index() {
        // GET zamiast POST — zeby stronicowanie wspolgralo z wyszukiwarka i sortowaniem
        $string = trim((string) $this->input->get('str'));
        $sort = $this->input->get('sort') ?: 'default';
        $page = max(1, (int) $this->input->get('page'));
        $limit = 30;
        $offset = ($page - 1) * $limit;

        $total = $this->CarModel->count_car_list_with_string($string);
        $cars = $this->CarModel->get_car_list_with_string($string, $sort, $limit, $offset);

        if(!empty($cars)){
            $ids = array_map(fn($c) => $c->id, $cars);
            $info = $this->CarModel->get_info_bulk($ids);
            foreach($cars as $car){
                $car->info = $info[$car->id] ?? ['total' => 0, 'income' => 0];
            }
        }

        $this->layout('duocms/Cars/index', [
            'cars' => $cars,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'str' => $string,
            'sort' => $sort
        ]);
    }

    public function create() {
        $this->load->model('CarModel');
        $this->load->model('TemplatesModel');
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $items = $this->input->post('items');
            $template_id = $this->input->post('template');
            $car = new CarModel();

            $car->name = $this->input->post('name');
            $car->production_date = $this->input->post('production_date');
            $car->brand = $this->input->post('brand');
            $car->buy_price = $this->input->post('buy_price');
            $car->version = $this->input->post('version');
            $car->description = $this->input->post('description');
            $car->add_car();

            if (!$car->id) {
                $this->setError('Wystąpił błąd.');
                redirect('duocms/cars/create');
            }
            $car->saveImage();

            if (!empty($items)) {
                foreach ($items as $item) {
                    $template = $this->TemplatesModel->get_template_item($item);
                    $args = [
                        "car_id" => $car->id,
                        "template_item_id" => $item,
                        "name" => $template->name.' '.$car->brand.' '. $car->name,
                        "description" => $car->description,
                        "price" => 0,
                        "image" => '',
                        "shop" => $template->shop,
                        "allegro" => $template->allegro,
                        "otomoto" => $template->otomoto,
                        "otomoto_category_id" => $template->otomoto_category_id,
                        'attributes_json' => '',
                        'attributes_json_otomoto' => json_encode(array_merge([ 'parts-type' => $template->otomoto_part_type ], (!empty($template->otomoto_parts_category) ? [ 'parts-category' => $template->otomoto_parts_category ] : []), [ 'title' => $template->name.' '.$car->brand.' '. $car->name ]))
                    ];
                    $this->CarModel->save_sketch($args);
                }
            }


            $this->setOkay('Samochód został zapisany');
            redirect('duocms/cars/edit/' . $car->id);
        }

        $this->load->helper('form');

        $templates = ['-1' => 'Wybierz szablon'];
        foreach ($this->TemplatesModel->get_template_list() as $t) {
            $templates[$t->id] = $t->name;
        }
        $this->layout('duocms/Cars/create', [
            'templates' => $templates
        ]);
    }

    public function edit($id) {
        $this->load->model('CarModel');

        $car = $this->CarModel->get_car($id);

        if (!$car->id) {
            show_404();
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $args = [
                        "name" => $this->input->post('name'),
                        "description" => $this->input->post('description'),
                        "price" => $this->input->post('price'),
                        "shop" => !empty($this->input->post('shop'))? 1 : 0,
                        "allegro" => !empty($this->input->post('allegro')) ? 1 : 0,
                        'otomoto' => !empty($this->input->post('otomoto')) ? 1 : 0,
                        'otomoto_category_id' => empty($this->input->post('otomoto_category_id')) ? 0 : $this->input->post('otomoto_category_id'),
                        'attributes_json' => json_encode($this->input->post('attributes')),
                        'attributes_json_otomoto' => json_encode(array_merge($this->input->post('parameters'),['title'=>$this->input->post("name")])),
                    ];
            $res = $this->CarModel->update_sketch($this->input->post('id'), $args);

            $this->CarModel->saveSketchImage($this->input->post('id'));
            $this->CarModel->save_sketch_photos($this->input->post('id'));

            if ($res) {
                $this->setOkay('Część została zapisana.');
            } else {
                $this->setError('Wystąpił błąd.');
            }

            //$category->saveImage();



            //redirect('duocms/offer_categories/edit/' . $category->id);
        }


        $this->load->helper('form');
        $page = empty($this->input->get('page')) ? 1 : $this->input->get('page') ;
        $limit = 20;
        $parts = $this->CarModel->get_sketches_by_car_id($car->id, $page);
        $otomotoGroupsIds = $this->CarModel->groupOtomotoIds($parts);
        $countParts = $this->CarModel->count_sketches_by_car_id($car->id);
        $this->load->model("AllegroModel");
        $this->load->model("TemplatesModel");
        $this->load->model("OtomotoModel");
        $otoMotoCategories = $this->OtomotoModel->getCategoriesArray(1);
        $oto_parameteres = [];
        foreach($otomotoGroupsIds as $otoCategoryId){
            $oto_parameteres[$otoCategoryId] = $this->OtomotoModel->get_category_data($otoCategoryId)->parameters;
        }


        if(!empty($parts)){
            foreach($parts as $part){
                $part->template = $this->TemplatesModel->get_template_item($part->template_item_id);
                $allegroCategoryFields = $this->AllegroModel->get_category_fields($part->template->allegro_category);
                if(!empty(json_decode($allegroCategoryFields)->parameters)){
                    $part->allegro_attributes = json_decode($allegroCategoryFields)->parameters;
                } else {
                    $part->allegro_attributes = [];
                }
                $part->otomoto_attributes = $oto_parameteres[empty($part->otomoto_category_id) ? 163 : $part->otomoto_category_id];
                $part->photos = $this->CarModel->get_sketch_photos($part->id);
            }
        }
        $this->layout('duocms/Cars/edit', [
            'parts' => $parts,
            'car' => $car,
            'countParts' => $countParts,
            'limit' => $limit,
            'page' => $page,
            'otomotoCategories' => $otoMotoCategories,
            'parametersVisibility' => $this->OtomotoModel->createVisibilityArray()
        ]);
    }
public function ajax_edit($id) {
        $this->load->model('CarModel');

        $car = $this->CarModel->get_car($id);

        if (!$car->id) {
            show_404();
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $description = '';
            if(!empty($_FILES['description'])){
                $description = file_get_contents($_FILES['description']['tmp_name']);
                //var_dump($description);
            }
            $args = [
                        "name" => $this->input->post('name'),
                        "description" => $description,
                        "price" => $this->input->post('price'),
                        "shop" => !empty($this->input->post('shop'))? 1 : 0,
                        "allegro" => !empty($this->input->post('allegro')) ? 1 : 0,
                        "otomoto" => !empty($this->input->post('otomoto')) ? 1 : 0,
                        'attributes_json' => $this->input->post('attributes'),
                        'attributes_json_otomoto' => json_encode(array_merge(json_decode($this->input->post('parameters'),true),['title'=>$this->input->post("name")])),
                    ];
            $res = $this->CarModel->update_sketch($this->input->post('id'), $args);

            $res2 = $this->CarModel->saveSketchImage($this->input->post('id'));
            $this->CarModel->save_sketch_photos($this->input->post('id'));

            //$category->saveImage();



            //redirect('duocms/offer_categories/edit/' . $category->id);
        }
    }
//    public function image_delete($id) {
//        $this->load->model('OfferCategoryModel');
//
//        $category = new OfferCategoryModel($id);
//
//        if (!$category->id) {
//            show_404();
//        }
//
//        $category->image = null;
//        $res = $category->update_category();
//
//        if ($res) {
//            $this->setOkay('Zdjęcie zostało usunięte.');
//        } else {
//            $this->setError('Wystąpił błąd.');
//        }
//
//        redirect($this->input->server('HTTP_REFERER'));
//    }
//    public function delete($id) {
//        $this->load->model('OfferCategoryModel');
//
//        $category = new OfferCategoryModel($id);
//
//        if (!$category->id) {
//            show_404();
//        }
//
//        $res = $category->delete();
//
//        if ($res) {
//            $this->setOkay('Kategoria została usunięta.');
//        } else {
//            $this->setError('Wystąpił błąd.');
//        }
//
//        redirect($this->input->server('HTTP_REFERER'));
//    }

    public function template($id) {
        $this->load->model('TemplatesModel');
        $items = $this->TemplatesModel->get_template_items($id);

        $this->load->view('duocms/Cars/template_items_ajax', [
            'items' => $items
        ]);
    }

    public function product_from_sketch($sketch_id){
        $this->load->model('TemplatesModel');
        $this->load->model('ProductModel');
        $this->load->model('CarModel');
        $this->load->model('ProductPhotoModel');
        $this->load->model('ProductTranslationModel');
        $sketch = $this->CarModel->get_sketch($sketch_id);
        $template = $this->TemplatesModel->get_template_item($sketch->template_item_id);
        $car = $this->CarModel->get_car($sketch->car_id);
    if($this->CarModel->check_if_part_already_added($sketch->car_id, $sketch->template_item_id)){ return 0; }
    if(empty($sketch->image) && empty($car->image)){ return 0; }
        $product = new ProductModel();
        $product->new = 1;
        $product->car_id = $sketch->car_id;
        $product->offer_category_id = $template->category_id;
        $product->allegro_category_id = $template->allegro_category;
        $product->price = $sketch->price;
        $product->quantity = 1;
        $product->active = !empty($sketch->shop) ? 0 : 1;
        $product->status = $sketch->allegro;
        $product->status2 = $sketch->otomoto;
        $product->type = $sketch->attributes_json;
        $product->type2 = $sketch->attributes_json_otomoto;
        $product->sketch_item_id = $sketch->template_item_id;
        $product->delivery_id = $template->delivery_id;
        $product->insert_product();

        $this->CarModel->copy_sketch_photos_to_product($sketch, $car, $product->id);


        $tproduct = new ProductTranslationModel();
        $tproduct->product_id = $product->id;
        $tproduct->name = $sketch->name;
        $tproduct->body = $sketch->description;
        $tproduct->lang = 'pl';
        $tproduct->insert();

        echo $product->id;
    }

    public function edit_products($sketch_id){
        $this->load->model('TemplatesModel');
        $this->load->model('ProductModel');
        $this->load->model('CarModel');
        $this->load->model('ProductPhotoModel');
        $this->load->model('ProductTranslationModel');
        $sketch = $this->CarModel->get_sketch($sketch_id);
        $template = $this->TemplatesModel->get_template_item($sketch->template_item_id);
        $car = $this->CarModel->get_car($sketch->car_id);
    if(empty($sketch->image) && empty($car->image)){ return 0; }
        $product = $this->CarModel->get_product_from_part($sketch->car_id, $sketch->template_item_id);
        if($product === null){ $this->product_from_sketch($sketch_id); die();}
        $product->price = $sketch->price;
        $product->quantity = 1;
        $product->active = !empty($sketch->shop) ? 0 : 1;
        $product->status = $sketch->allegro;
        $product->status2 = $sketch->otomoto;
        $product->type = $sketch->attributes_json;
        $product->type2 = $sketch->attributes_json_otomoto;
        $product->update_product();
        foreach($product->findAllPhotos() as $op){ if(is_object($op) && method_exists($op,'delete')){ $op->delete(); } }
        $this->CarModel->copy_sketch_photos_to_product($sketch, $car, $product->id);
        $tproduct = $product->getTranslation('pl');
        $tproduct->name = $sketch->name;
        $tproduct->body = $sketch->description;
        $tproduct->update();

        echo $product->id;
    }

    function add_auction($product_id = null){
        if(empty($product_id)){
           echo 'coś poszło nie tak';
           return 0;
        }
        $this->load->model('ProductModel');
        $product = new ProductModel($product_id);
        if($product->status){
            $this->load->model('AllegroModel');
            if($this->AllegroModel->get_allegro_auction_id($product_id) === -1){
                $this->AllegroModel->add_auction_from_product($product->id);
            } else {
                echo 'aukcja już istnieje';
            }
        }
    }
    function edit_auction($product_id = null){
        if(empty($product_id)){
           echo 'coś poszło nie tak';
           return 0;
        }
        $this->load->model('ProductModel');
        $product = new ProductModel($product_id);
        if($product->status){
            $this->load->model('AllegroModel');
            $this->AllegroModel->edit_auction_from_product($product->id);
        }
    }


    function car_delete($car_id) {
        $this->db->where('car_id', $car_id);
        $q = $this->db->get('duo_products')->result();
        if (!empty($q)) {
            $this->load->model("AllegroModel");
            $this->load->model("ProductModel");
            foreach ($q as $r) {
                $allegro = $this->AllegroModel->get_allegro_auction_id($r->id);
                if ($allegro > 0) {
                    $this->AllegroModel->end_offer($allegro);
                }
                $prod = new ProductModel($r->id);
                $prod->delete();
            }
        }

        $this->load->helper('file');
        $this->db->where('car_id', $car_id);
        $z = $this->db->get('duo_car_sketches')->result();
        if(!empty($z)){
            foreach($z as $c){
            $dir = FCPATH . 'uploads/sketch/' . $c->id;
            delete_files($dir, true);
            rmdir($dir);
            }
        }
        $this->db->where('car_id', $car_id);
        $this->db->delete('duo_car_sketches');


        $this->db->where('id', $car_id);
        $this->db->delete('duo_cars');

        redirect(site_url('duocms/cars'));
    }


    public function delete_sketch_photo($photo_id){
        $this->load->model('CarModel');
        $this->CarModel->delete_sketch_photo($photo_id);
        redirect($this->input->server('HTTP_REFERER'));
    }

    public function add_to_timetable($product_id){
        $this->db->where('product_id', $product_id);
        $q = $this->db->get('duo_allegro_timetable');
        if($q->num_rows() > 0){
            echo 'id już w kolejce';
        } else {
        $this->db->insert('duo_allegro_timetable', [
            'product_id' => $product_id
        ]);
        }
    }

}
