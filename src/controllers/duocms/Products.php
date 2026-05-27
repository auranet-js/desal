<?php
// SOURCE: /home6/desal/public_html/application/controllers/duocms/Products.php
// FETCHED: 2026-05-27 ~10:23 UTC (PEŁEN MIRROR — nadpisanie wcześniejszego skrótu)
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2022-10-25 12:45:05, size 22124 B

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Products extends Backend_Controller {

    public $languages = array();
    public $product_obj;
    public $attr_obj;

    public $menu_item = ['product','product_list'];

    public function __construct() {
        parent::__construct();
        $this->load->model("ProductPhotoModel");
        $this->load->model('ProductModel');
        $this->load->model('ProductTranslationModel');
        $this->load->model('OfferCategoryModel');
        $this->load->model("ProductAttributesModel");
        $this->load->model("CarModel");
        $this->load->helper('form');
        $langs = get_languages();
        foreach ($langs as $l) {
            $this->languages[] = $l->short;
        }
        $this->load->vars(['activePage' => 'products']);

        $this->product_obj = new ProductModel();
        $this->attr_obj = new ProductAttributesModel();
    }

    public function index($page = 0) {
        $productModel = new ProductModel();
        if (!empty($_POST['element'])) {
            $i = 1;
            foreach ($_POST['element'] as $id) {
                $productModel->sort_item($id, $i);
                $i++;
            }
        }
        $cars = $this->CarModel->get_car_list();
        $data = $this->input->post();
        if(empty($data)){
            $data['s'] = $this->session->userdata('admin_product_string_search');
            $data['category'] = $this->session->userdata('admin_product_cat_search');
            $data['car'] = $this->session->userdata('admin_product_car_search');
        }
        //$categories = $this->OfferCategoryModel->findAllForHome();
        $categories = (new OfferCategoryModel())->getListForProductDropdown();
        if(!empty($data['s']) || !empty($data['category']) || !empty($data['car'])){
            if(!empty($data['category']) && $data['category'] != 'all'){
                $this->db->where('products.offer_category_id', $data['category']);
            }
            if(!empty($data['car']) && $data['car'] != 'all'){
                $this->db->where('products.car_id', $data['car']);
            }
            if(!empty($data['s'])){
                $string = str_replace(["  ",' ',"'"], ["",' +',"\'"], $data['s']);
                $string = '+'.$string;
                $this->db->where('(code LIKE "%'.$data['s'].'%" OR duo_products_translations.name LIKE "%'. $data['s']. '%" OR MATCH (duo_products_translations.name) AGAINST ("'. $string .'" IN BOOLEAN MODE))');
              //  $this->db->or_where("MATCH (duo_products_translations.name) AGAINST ('" . $string . "' IN BOOLEAN MODE)");
            }
        }
        $limit = 50;
        $products = $productModel->findAllForCmsList(1, $limit, ($page-1)*$limit);
        if(!empty($data['s']) || !empty($data['category']) || !empty($data['car'])){
            if(!empty($data['category']) && $data['category'] != 'all'){
                $this->db->where('products.offer_category_id', $data['category']);
                $this->session->set_userdata('admin_product_cat_search', $data['category']);
            } else {
                $this->session->set_userdata('admin_product_cat_search', 'all');
            }
            if(!empty($data['car']) && $data['car'] != 'all'){
                $this->db->where('products.car_id', $data['car']);
                $this->session->set_userdata('admin_product_car_search', $data['car']);
            } else {
                $this->session->set_userdata('admin_product_car_search', 'all');
            }
            if(!empty($data['s'])){
                $this->db->where('(code LIKE "%'.$data['s'].'%" OR duo_products_translations.name LIKE "%'. $data['s']. '%")');
                $this->db->or_where("MATCH (duo_products_translations.name) AGAINST ('" . $string . "' IN BOOLEAN MODE)");
                $this->session->set_userdata('admin_product_string_search', $data['s']);
            } else {
                $this->session->set_userdata('admin_product_string_search', '');
            }
        }
        $this->load->library('pagination');
        $config = [
            'base_url' => site_url('duocms/products'),
            'total_rows' => $productModel->findAllForCmsListCount(1),
            'per_page' => $limit,
            'use_page_numbers' => true,
        ];
//        $config['uri_segment'] = 4;

//        $config["cur_page"] = $strona;
//        if (count($_GET) > 0) $config['suffix'] = '?' . http_build_query($_GET, '', "&");
//        $config['first_url'] = $config['base_url'].'?'.http_build_query($_GET);
        $this->pagination->initialize($config);


        $this->layout('duocms/Products/index', [
            'data' => $data,
            'products' => $products,
            'categories' => $categories,
            'cars' => $cars
        ]);
    }

    public function create() {
        $this->menu_item[1] = 'product_create';
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $offer_category_id = $this->input->post('offer_category_id');
            $type = $this->input->post('type');

            $product = new ProductModel();
            $product->offer_category_id = $offer_category_id;
            $product->new = $this->input->post('new');
            $product->promo = $this->input->post('promo');
            $product->bestseller = $this->input->post('bestseller');
            $product->weight = $this->input->post('weight');
            $product->quantity = $this->input->post('quantity');
            $product->type = $type;
            $product->price = $this->input->post('price');
            $product->code = $this->input->post('code');
            $product->status = 1;
            $product->insert_product();

            if (!$product->id) {
                $this->setError('Wystąpił błąd.');
                redirect('duocms/products/create');
            }

            foreach ($this->languages as $lang) {
                $data = $this->input->post($lang);

                $translation = new ProductTranslationModel();
                $translation->product_id = $product->id;
                $translation->lang = $lang;
                $translation->name = $data['name'];
                $translation->format = !empty($data['format']) ? $data['format'] : '';
                $translation->slogan = $data['slogan'];
                $translation->body = $data['body'];
                $translation->insert();
            }

            setAlert('success','Produkt został zapisany.');
            redirect('duocms/products/edit/' . $product->id);
        }

        $categories = (new OfferCategoryModel())->getListForProductDropdown();

        $groups = $this->attr_obj->get_groups();
        $groups_array = array();
        if(!empty($groups)){
            foreach ($groups as $group){
                $groups_array[] = array(
                    'group' => $group,
                    'attributes' => $this->attr_obj->get_attributes_by_group($group->attributes_group_id)
                );
            }
        }

        $this->layout('duocms/Products/form', [
            'categories' => $categories,
            'types' => array(),
            'groups' => $groups_array
        ]);
    }

    public function edit($id) {
        $this->menu_item[1] = 'product_edit';
        $this->add_css(assets('plugins/plupload/js/jquery.plupload.queue/css/jquery.plupload.queue.css'));
        $this->add_js(assets('plugins/plupload/js/plupload.min.js'));
        $this->add_js(assets('plugins/plupload/js/jquery.plupload.queue/jquery.plupload.queue.min.js'));
        $this->add_js(assets('plugins/plupload/js/i18n/pl.js'));

        $product = new ProductModel($id);
        $products = $product->get_product_list_for_relations($product->id);
        $relations = $product->get_product_relations($product->id);
        if (!$product->id) {
            show_404();
        }

        if ($this->input->server('REQUEST_METHOD') === 'POST' && $this->input->post("action") == "add_product") {
            save_custom_fields('product', $id);
            $photo_order = $this->input->post('photo_order');
            if ($photo_order) {

                foreach ($photo_order as $order => $photoId) {
                    $photo = new ProductPhotoModel($photoId);
                    $photo->order = $order;
                    $photo->update();
                }
            }

            //produkty powiazane
            $rels = $this->input->post('relations');
            if(!empty($rels)){
                foreach($relations as $rel_stare){
                    if(!in_array($rel_stare, $rels)){
                        $product->delete_product_relations($rel_stare,$product->id);
                    }
                }
                foreach($rels as $rel ){
                    if(!$product->check_product_relation($rel, $product->id)){
                        $product->save_product_relations($product->id, $rel);
                    }
                }
            } else {
                foreach($relations as $rel_stare){
                   $product->delete_product_relations($rel_stare,$product->id);
                }
            }
            // koniec produkty powiazane

            $offer_category_id = $this->input->post('offer_category_id', true);
            $type = $this->input->post('type', true);

            $product->offer_category_id = $offer_category_id;
            $product->type = $type;
            $product->new = $this->input->post('new');
            $product->weight = $this->input->post('weight');


            $new_quanity = ($this->input->post('quantity')) *1;
            $old_quanity = ($product->quantity) *1;

            $this->load->model('AllegroModel');
            $product->quantity = $new_quanity;
            if (!empty($this->input->post('allegro_stan'))) {
                if ($this->input->post('allegro_stan') == 'zmien') {
                    $allegro_id3 = $this->AllegroModel->get_allegro_auction_id($product->id);
                    if($allegro_id3 > 0){
                    $this->AllegroModel->update_amount($allegro_id3, $new_quanity);
                    }
                }
            }
//            if($old_quanity !== $new_quanity){
//                $this->load->model("AllegroModel");
//                $allegro_id = $this->AllegroModel->get_allegro_auction_id($product->id);
//                if($allegro_id > 0){
//                    $auction_json = $this->AllegroModel->get_offer($allegro_id);
//                    $auction = json_decode($auction_json);
//                    $res = $this->AllegroModel->synchronise_amount($auction, ($new_quanity-$old_quanity));
//                    $product->quantity = $res;
//                } else {
//                    $product->quantity = $new_quanity;
//                }
//            }

            $product->promo = $this->input->post('promo');
            $product->bestseller = $this->input->post('bestseller');
            $product->price = $this->input->post('price');
            $product->code = $this->input->post('code');
            $res = $product->update_product();

            if (!$res) {
                $this->setError('Wystąpił błąd.');
                redirect('duocms/products/edit/' . $product->id);
            }

            foreach ($this->languages as $lang) {
                $data = $this->input->post($lang);

                $translation = new ProductTranslationModel($data['id']);
                $translation->name = $data['name'];
                $translation->format = !empty($data['format']) ? $data['format'] : '';
                $translation->slogan = !empty($data['slogan']) ? $data['slogan'] : '';
                $translation->body = $data['body'];
                $translation->update();
            }

            setAlert('success','Produkt został zapisany.');
            redirect('duocms/products/edit/' . $product->id);
        }
        if ($this->input->post("action") === "desc_photo") {
            $photo_id = $this->input->post("id");
            $descriptions = $this->input->post("description");
            $pM = new ProductPhotoModel();
            foreach ($descriptions as $lang => $description) {
                $pM->update_description($photo_id, $description, $lang);
            }
        }
        if ($this->input->post("action") === "add_option") {
            $args = array(
                'product_id' => $product->id,
                'name' => $this->input->post('name'),
                'description' => $this->input->post('description'),
                'price_change' => $this->input->post('price_change'),
                'quantity' => $this->input->post('quantity'),
                'quantity_left' => $this->input->post('quantity'),
                'visibility' => $this->input->post('visibility')
            );
            if($product->add_option($args)){
                setAlert('success','Opcja została dodana.');
                redirect('duocms/products/edit/' . $product->id);
            }
        }
        //aktualizacja opcji wszystkich naraz
        if($this->input->post('action') === 'update_options'){
            $data2 = $this->input->post();
            $prices = $data2['price'];
            $old_prices = $data2['old_price'];
            $weights = $data2['weight'];
            $quantities_left = $data2['quantity_left'];
            foreach ($prices as $o_id => $n_price){
                $oargs = array();
                $oargs['id'] = $o_id;
                $oargs['price_change'] = $n_price;
                $oargs['old_price'] = $old_prices[$o_id];
                $oargs['weight'] = $weights[$o_id];
                $oargs['quantity_left'] = $quantities_left[$o_id];
                $this->product_obj->edit_option($oargs);
            }
            setAlert('success','Opcje zaktualizowane.');
             redirect('duocms/products/edit/' . $product->id);
        }

        //dodawanie atrybutu
        if($this->input->post('action') === "add_attribute"){
            $res = $this->product_obj->attribute_add_to_product($this->input->post('attribute'), $id, $value);
            if($res){
                setAlert('success','Atrybut został dodany.');
                redirect('duocms/products/edit/' . $product->id);
            } else {
                setAlert('error','Nie dodano atrybutu. <br> Prawdopodobnie już jest dodany.');
                redirect('duocms/products/edit/' . $product->id);
            }
        }

        $categories = (new OfferCategoryModel())->getListForProductDropdown();
        $photos = $product->findAllPhotos();
        $product_options = $product->get_options_admin($product->id);

        $attributes = $this->product_obj->attribute_get_list();
        $product_attributes = $this->product_obj->attribute_get_list_for_product($product->id);

        $groups = $this->attr_obj->get_groups();
        $groups_array = array();
        if(!empty($groups)){
            foreach ($groups as $group){
//                $groups_array[] = array(
//                    'group' => $group,
//                    'attributes' => $this->attr_obj->get_attributes_by_group($group->attributes_group_id)
//                );
                $groups_array[] = array(
                    'group' => $group
                );
            }
        }
        $allegro_id = null;
        $this->load->model("AllegroModel");
        $allegro_id_rows = $this->AllegroModel->get_auctions($product->id);
        if(!empty($allegro_id_rows)){
            $allegro_id = $allegro_id_rows[0]->allegro_id;
        }

        $this->layout('duocms/Products/form', [
            'product' => $product,
            'products' => $products,
            'relations' => $relations,
            'photos' => $photos,
            'categories' => $categories,
            'types' => array(),
            'product_options' => $product_options,
            'attributes' => $attributes,
            'product_attributes' => $product_attributes,
            'groups' => $groups_array,
            'allegro_id' => $allegro_id
        ]);
    }

    //kopiowanie produktu
    public function copy_product($id){
        $new_id = $this->product_obj->copy_product($id);
        if(!empty($new_id)){
            setAlert('info','Produkt został skopiowany');
        }
        redirect(site_url('duocms/products/edit/'.$new_id));
    }

    public function edit_option($option_id, $product_id){
        $product_model = new ProductModel($product_id);
        $option_data = $product_model->select_option($option_id);
        if(!empty($_POST)){
            $args = $this->input->post();
            $args['id'] = $option_id;
            unset($args['add_option']);
            $product_model->edit_option($args);
            setAlert('info','Opcja została zaktualizowana.');
            redirect('duocms/products/edit/' . $product_id);
        }

        $this->layout('duocms/Products/edit_option', [
            'option' => $option_data
        ]);
    }

    public function delete_option($option_id, $product_id){
        $product = new ProductModel();
        $res = $product->delete_option($option_id, $product_id);
        if($res){
            $this->setOkay('Opcja została usunięta.');
            redirect('duocms/products/edit/' . $product_id);
        } else {
            echo 'Błąd!';
        }
    }

    public function delete($id) {
        $discount = new ProductModel($id);

        if (!$discount->id) {
            show_404();
        }

        $res = $discount->delete();

        if ($res) {
            setAlert('info','Produkt został uzunięty.');
        } else {
            setAlert('error','Wystąpił błąd.');
        }

        redirect($this->input->server('HTTP_REFERER'));
    }

    public function upload_photo() {
        $discountId = $this->input->post('product_id');
        $discount = new ProductModel($discountId);

        if (!$discount->id) {
            show_404();
        }

        $photo = new ProductPhotoModel();
        $photo->product_id = $discount->id;
        $photo->insert();

        if (!$photo->id) {
            show_404();
        }

        $res = $photo->saveImage($_FILES['file']);

        if ($res) {
            echo json_encode(['result' => 1]);
        } else {
            echo json_encode(['result' => 0]);
        }
    }

    public function ajax_delete_photo($id) {
        $photo = new ProductPhotoModel($id);

        if (!$photo->id) {
            show_404();
        }

        $res = $photo->delete();

        if ($res) {
            echo json_encode(['result' => 1]);
        } else {
            echo json_encode(['result' => 0]);
        }
    }

    public function delete_attribute($attribute_id, $product_id){
        $res = $this->product_obj->delete_attribute($attribute_id, $product_id);
        if($res){
            setAlert('info','Usunięto atrybut');
        } else {
            setAlert('error','Nie udało się usunąć atrybutu');
        }
        redirect(site_url('duocms/products/edit/'.$product_id));
    }

    //Płatności
    public function payment(){
        $this->layout('duocms/Products/payment',[]);
    }

    //zmiana aktywności
    public function change_status($id){
        $r = $this->product_obj->change_status($id);
        echo $r;
        die();
    }

    public function stationary_sell($id) {
        $product = new ProductModel($id);
        $pdata = $this->input->post();
        if (!empty($pdata)) {

            $product->quantity = 0;
            $product->active = 1;
            $product->sold = 1;
            $product->price = $pdata['new_price'];
            $product->update_product();

            $this->load->model("AllegroModel");
            $allegro = $this->AllegroModel->get_allegro_auction_id($product->id);
            if ($allegro > 0) {
                $this->AllegroModel->end_offer($allegro);
            }
            if(!empty($product->otomoto_id)){
                 $this->load->model('OtomotoModel');
                $this->OtomotoModel->deactivate_advert_from_product($product->id);
            }
            setAlert('info', 'Sprzedano produkt');
            redirect(site_url('duocms/products'));
        } else {
            $this->layout('duocms/Products/stationary_sell', [
                'product' => $product
            ]);
        }
    }
    public function sellForOtomoto($id)
    {
        $product = new ProductModel($id);
        $product->quantity = 0;
        $product->active = 1;
        $product->sold = 1;
        $product->update_product();
        $this->load->model("AllegroModel");
        $allegro = $this->AllegroModel->get_allegro_auction_id($product->id);
        if ($allegro > 0) {
            $this->AllegroModel->end_offer($allegro);
        }
    }

    public function product_return($id){
        $product = new ProductModel($id);
        $pdata = $this->input->post();
        if (!empty($pdata)) {

            $product->quantity = 1;
            $product->active = 0;
            $product->sold = 0;
            $product->price = $pdata['new_price'];
            $product->update_product();

            $this->load->model("AllegroModel");
            $allegro = $this->AllegroModel->get_allegro_auction_id($product->id);
            if ($allegro > 0) {
                $this->AllegroModel->renew_offer($allegro);
            } else {
                $this->AllegroModel->add_auction_from_product($product->id);
            }
            if(!empty($product->otomoto_id)){
                 $this->load->model('OtomotoModel');
                $this->OtomotoModel->activate_advert_from_product($product->id);
            }
            setAlert('info', 'Zwrot towaru przyjęty');
            redirect(site_url('duocms/products'));
        } else {
            $this->layout('duocms/Products/product_return', [
                'product' => $product
            ]);
        }
    }

}
