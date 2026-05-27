<?php
// SOURCE: /home6/desal/public_html/application/controllers/duocms/Allegro.php
// FETCHED: 2026-05-27 ~10:21 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2021-08-11 05:56:26, size 36293 B
// UWAGA: kompletny panel admin Allegro — wystawianie/edycja aukcji, paczki, dostawy, orders


class Allegro extends Backend_Controller {

    public $allegro;


    public function __construct() {
        parent::__construct();
        $this->load->model('AllegroModel');
        $this->load->model('OfferCategoryModel');
        $this->load->model('ProductModel');
        $this->allegro = new AllegroModel();
        $this->load->vars(['activePage' => 'allegro']);
    }

    public function index(){
        $res = $this->allegro->test();
        echo 'test >>';
        echo json_encode($res);
        die();
    }

    public function product($product_id){
        $data = array();
        $data['product_id'] = $product_id;
        $data['auctions'] = $this->allegro->get_auctions($product_id);
        $this->layout('duocms/Shop/Allegro/product', $data);
    }

    public function show($offer_id){
        $allegro_session = null;
        $offer_record = $this->allegro->get_auction_record_by_allegro_id($offer_id);
        $data = array();
        if(get_option('admin_modules_allegro_token_expiration') > date('U')){
            $data['allegro_session'] = array('session' => 1);
        } else {
            $data['allegro_session'] = $allegro_session;
        }
        $offer = $this->allegro->get_offer($offer_id);
        $data['offer'] = json_decode($offer);
        $data['allegro_category_id'] = !empty($data['offer']->category->id) ? $data['offer']->category->id : '';
        $afields = array();
        if(!empty($this->session->userdata['allegro'])){
            $fields_obj = json_decode($this->allegro->get_category_fields($data['offer']->category->id));
            $afields = $fields_obj->parameters;
        }
        $data['fields'] = $afields;
        $data['uploaded_photos'] = $this->allegro->get_uploaded_photos($offer_record->product_id);
        $data['allegro_login_link'] = $this->allegro->get_login_url($offer_id,1);
        $data['shipping_rates'] = $this->allegro->get_shipping_rates();
        $data['impliedWarranty'] = $this->allegro->get_impliedWarranty();
        $data['returnPolicy'] = $this->allegro->get_returnPolicy();
         $post_data = $this->input->post();
        if(!empty($post_data)){
            $res = $this->allegro->add_offer($post_data, $offer_record->product_id);
            if(!empty($res->id)){
                setAlert('info','Zaktualizowano aukkcję !! Aktywacja może chwilę potrwać');
                redirect(site_url('duocms/allegro/show/'.$offer_id));
            }
            echo json_encode($res);
            die();
        }
        $this->layout('duocms/Shop/Allegro/show', $data);
    }

    public function add_auction($product_id){
        $productCategoryObj = new OfferCategoryModel();
        $allegro_session = null;
        $data = array();
        if(get_option('admin_modules_allegro_token_expiration') > date('U')){
            $data['allegro_session'] = array('session' => 1);
        } else {
            $data['allegro_session'] = $allegro_session;
        }
        $product = new ProductModel($product_id);
        $allegro_id = $product->allegro_category_id;
        $afields = array();
        if(!empty($data['allegro_session'])){
            $fields_obj = json_decode($this->allegro->get_category_fields($product->allegro_category_id));
            $afields = $fields_obj->parameters;
        }
        $data['fields'] = $afields;

        $data['product_id'] = $product_id;


        $data['product'] = $product;
        $data['product_translation'] = $product->getTranslation('pl');
        $data['allegro_category_id'] = $allegro_id;
        $data['error'] = '';
        $data['success'] = '';
        $post_data = $this->input->post();
        if(!empty($post_data)){

            $res = $this->allegro->add_offer($post_data, $product_id);
            if(!empty($res->id)){
                setAlert('success','Wystawiono aukkcję !!');
                redirect(site_url('duocms/allegro/show/'.$res->id));
            }
            echo json_encode($res);
            die();
        }
        $data['allegro_login_link'] = $this->allegro->get_login_url($product_id);
        $data['product_photos'] = $product->findAllPhotos();
        if(!empty($data['product_photos'] && !empty($data['allegro_session']))){
            $tmp_photos = array();
            foreach ($data['product_photos'] as $photo){
                $res = $this->allegro->upload_photo($photo);
            }
        }

        $data['uploaded_photos'] = $this->allegro->get_uploaded_photos($product_id);
        $data['shipping_rates'] = $this->allegro->get_shipping_rates();
        $data['impliedWarranty'] = $this->allegro->get_impliedWarranty();
        $data['returnPolicy'] = $this->allegro->get_returnPolicy();
        $this->layout('duocms/Shop/Allegro/add_auction', $data);
    }

    public function allegro_list($page = 0){
        $allegro_session = null;
          if(get_option('admin_modules_allegro_token_expiration') > date('U')){
            $data['allegro_session'] = array('session' => 1);
        } else {
            $data['allegro_session'] = $allegro_session;
        }
        if($this->input->server('REQUEST_METHOD') === 'POST' && !empty($this->input->post("aukcja"))) {
            $aukcje = $this->input->post("aukcja");
            foreach ($aukcje as $aukcja=>$value) {
                $this->add_from_allegro_no_redirect($aukcja);
            }
        }
        $data['allegro_login_link'] = $this->allegro->get_login_url(0,0,1);
        $data['offers'] = $this->allegro->get_offers_list_limit(100, $page);
        $data['page'] = $page;
        $this->layout('duocms/Shop/Allegro/allegro_list', $data);
    }

    public function add_from_allegro_no_redirect($allegro_id){
        $this->allegro->add_from_allegro_no_redirect($allegro_id);
    }

    public function add_from_allegro($allegro_id){
        $this->add_from_allegro_no_redirect($allegro_id);
        redirect('duocms/allegro/allegro_list');
        die();
    }

    public function allegro_multilist($page = 0){
        $list_json = $this->allegro->get_multivariants_auction_list(15,$page);
        $count = json_decode($list_json)->count;
        $list = json_decode($list_json)->offerVariants;

        $allegro_session = null;
         if(!empty($this->session->userdata['allegro'])){
            $allegro_session = $this->session->userdata['allegro'];
            if($allegro_session['expired'] < date('U')){
                $this->session->set_userdata('allegro',null);
                $data['allegro_session'] = $this->session->userdata['allegro'];
            } else {
                $data['allegro_session'] = $allegro_session;
            }
        }
        $data['allegro_login_link'] = $this->allegro->get_login_url(0,0,1);
        $data['offers'] = $list;
        $data['page'] = $page;
        $this->layout('duocms/Shop/Allegro/allegro_multilist', $data);
    }


    public function download_packs(){
        $this->load->model('ProductModel');
        $this->load->model('ProductPackModel');
        $this->load->model('ProductAttributesModel');
        $list_json = $this->allegro->get_multivariants_auction_list(50,0);
        $count = json_decode($list_json)->count;
        $pages = ceil($count / 50);
        for($k=0; $k<=$pages-1; $k++){
        $list_json = $this->allegro->get_multivariants_auction_list(50,$k);
        $list = json_decode($list_json)->offerVariants;
        foreach ($list as $l) {
            if(!$this->ProductPackModel->check_if_pack_exist($l->id)){
            $this->ProductPackModel->add_pack($l->name, $l->id);
            }
            $pack = $this->ProductPackModel->get_pack_by_allegro_id($l->id);
            $pack_args['id'] = $pack->id;
            $pack_args['name'] = $pack->name;
            $pack_args['attr_grp_2_id'] = 0;
            $product_list_json = $this->allegro->get_multivariants_auction($l->id);
            $product_list = json_decode($product_list_json);
            $i = 1;
            foreach ($product_list->parameters as $params) {
                    if ($params->id == 'color/pattern') {
                        $atr_group_name = 'colorPattern_' . $pack->id;
                        if (!$this->ProductAttributesModel->check_if_allegro_group_exist($atr_group_name)) {
                            $attribute_group = array(
                                'translations' => array(
                                    array(
                                        'lang' => 'pl',
                                        'name' => $atr_group_name,
                                        'description' => ''
                                    )
                                ),
                                'allegro' => $atr_group_name
                            );
                            $this->ProductAttributesModel->add_group($attribute_group);
                        }
                        $group_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($atr_group_name);
                        $pack_args['attr_grp_' . $i . '_id'] = $group_id;
                    } else {
                        $g_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($params->id);
                        $pack_args['attr_grp_' . $i . '_id'] = $g_id;
                    }
                    $i++;
                }
                $this->ProductPackModel->update_pack($pack_args);
        }
        }
        redirect(site_url('duocms/ProductPacks/index'));
    }
    public function update_pack($pack_id){
        $this->load->model('ProductModel');
        $this->load->model('ProductPackModel');
        $this->load->model('ProductAttributesModel');

        $pack = (new ProductPackModel())->get_pack($pack_id);

        $product_list_json = $this->allegro->get_multivariants_auction($pack->allegro_uid);
        $product_list = json_decode($product_list_json);

        $products = $product_list->offers;

        foreach ($products as $p) {
            if (!$this->allegro->check_allegro_product($p->id)) {
                echo 'produktu z aukcji '.$p->id.' nie ma w bazie';
                continue;
            }
            $pr = $this->allegro->get_product_by_allegro_auction_id($p->id);
            if (empty($pr->id)) {
                continue;
            }

            if (!$this->ProductPackModel->check_product_is_in_pack($pr->id, $pack->id)) {
                $this->ProductPackModel->add_product_to_pack($pr->id, $pack->id);
            }
            if (!empty($p->colorPattern)) {
                $atr_group_name = 'colorPattern_' . $pack->id;
                 $group_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($atr_group_name);
                $pattern_id = $group_id . '_' . $p->colorPattern;
                if (!$this->ProductAttributesModel->check_if_allegro_attr_exist($pattern_id)) {
                    $duo_attr_id = $this->ProductAttributesModel->attribute_add(0, $group_id, $pattern_id);
                    $args = array('pl' => array('name' => $p->colorPattern, 'description' => ''));
                    $this->ProductAttributesModel->attribute_update($duo_attr_id, 0, $args, $group_id);
                }
                $attr_id = $this->ProductAttributesModel->find_attr_by_allegro_id($pattern_id);
                if (!empty($attr_id)) {
                    $this->ProductModel->attribute_add_to_product($attr_id, $pr->id, null);
                }
            }
        }
         redirect(site_url('duocms/ProductPacks/index'));
    }

    public function delivery_options(){
        $allegro_session = null;
        if(get_option('admin_modules_allegro_token_expiration') > date('U')){
            $allegro_session = array('session' => 1);
            $data['allegro_session'] = $allegro_session;
        } else {
            $data['allegro_session'] = $allegro_session;
        }
        if(!empty($this->input->post('id'))){
            $this->allegro->clear_all_deliveries();
            $delivery_ids = $this->input->post('delivery');
            $allegro_ids = $this->input->post('id');
            for($i=0;$i<count($allegro_ids);$i++){
                $this->allegro->insert_allegro_delivery($allegro_ids[$i], $delivery_ids[$i]);
            }
        }
        if (!empty($allegro_session)) {
            $dm_allegro_json = $this->allegro->get_delivery_methods();
            $dm_allegro = json_decode($dm_allegro_json)->deliveryMethods;
            $dma = array();
            foreach ($dm_allegro as $dm) {
                $dma[$dm->id] = $dm->name;
            }
            $ships = json_decode($this->AllegroModel->get_shipping_rates())->shippingRates;
            $dost = array();
            foreach ($ships as $s) {
                $dost[] = json_decode($this->AllegroModel->get_shipping_rates_details($s->id));
            }

            $this->load->model("Delivery_Model");
            $cms_delivery = $this->Delivery_Model->get_list_for_dropdown();
        } else {
            $cms_delivery = array();
            $dma = array();
            $dost = array();
        }

        $data['selected_data'] = $this->allegro->get_all_deliveries();
        $data['allegro_login_link'] = $this->allegro->get_login_url(0,0,1);
        $data['cms_delivery'] = $cms_delivery;
        $data['dma'] = $dma;
        $data['dost'] = $dost;
        $this->layout('duocms/Shop/Allegro/allegro_delivery', $data);
    }

    public function allegro_orders(){
        $this->allegro->download_orders();
    }

    public function end_auction($product_id){
        $allegro_id = $this->allegro->get_allegro_auction_id($product_id);
        if($allegro_id > 0){
            $this->allegro->end_offer($allegro_id);
            setAlert('info', "auckja zakończona");
        } else {
            setAlert('error', "coś poszło nie tak");
        }
    }
    public function renew_auction($product_id) {
        $allegro_id = $this->allegro->get_allegro_auction_id($product_id);
        if ($allegro_id > 0) {
            $this->allegro->renew_offer($allegro_id);
        } else {
            //TODO TOASTER
        }
    }

    public function edit_auction($product_id){
        $productCategoryObj = new OfferCategoryModel();
        $allegro_id = $productCategoryObj->get_allegro_id_by_product($product_id);
        $allegro_session = null;
        $data = array();
        if(get_option('admin_modules_allegro_token_expiration') > date('U')){
            $allegro_session = array('session' => 1);
            $data['allegro_session'] = $allegro_session;
        } else {
            $data['allegro_session'] = $allegro_session;
        }

        $afields = array();
        if(!empty($allegro_session)){
            $fields_obj = json_decode($this->allegro->get_category_fields($allegro_id));
            $afields = $fields_obj->parameters;
            $auction_id = $this->allegro->get_allegro_auction_id($product_id);
            if($auction_id > 0){
            $data['auction'] = $this->allegro->get_offer($auction_id);
            } else {
                $data['auction'] = null;
            }
        }
        $data['fields'] = $afields;

        $data['product_id'] = $product_id;

        $product = new ProductModel($product_id);
        $data['product'] = $product;
        $data['product_translation'] = $product->getTranslation('pl');
        $data['allegro_category_id'] = $allegro_id;
        $data['error'] = '';
        $data['success'] = '';

        $data['allegro_login_link'] = $this->allegro->get_login_url($product_id);
        $all_photos = $product->findAllPhotos();
        $data['product_photos'] = $all_photos;


        $uploaded_photos = $this->allegro->get_uploaded_photos($product_id);

        $data['shipping_rates'] = $this->allegro->get_shipping_rates();
        $data['impliedWarranty'] = $this->allegro->get_impliedWarranty();
        $data['returnPolicy'] = $this->allegro->get_returnPolicy();

        $data['usedAttributes'] = $this->allegro->get_product_allegro_attributes($product_id);
        $u_photos_id = array();
        if (!empty($uploaded_photos)) {
            foreach ($uploaded_photos as $up) {
                $u_photos_id[] = $up->photo_id;
            }
        }
        $uploaded_photos2 = array();
        foreach($all_photos as $ap){
            if(!in_array($ap->id, $u_photos_id)){
                $this->allegro->upload_photo($ap);
            }
            $uploaded_photos2[] = $this->allegro->find_photo_by_id($ap->id);
        }
        $post_data = $this->input->post();
        if(!empty($post_data)){
            $res = $this->allegro->edit_offer($post_data);
            if(!empty($res->id)){
                setAlert('success','Wyedytowano aukcję!');
                redirect(site_url('duocms/allegro/show/'.$res->id));
            }
            echo json_encode($res);
            die();
        }
        $data['auction_data'] = json_decode($this->allegro->get_offer($auction_id));
        $data['uploaded_photos'] = $uploaded_photos2;
        $data['allegro_auction_id'] = !empty($auction_id) ? $auction_id : '';
        $this->layout('duocms/Shop/Allegro/edit_auction', $data);
    }

    public function download_stock_amount($starting_point = 0){
        $products = (new ProductModel())->findAll();
        $count = count($products);
        if($starting_point < $count){
        if(!empty($products)){
            for($i = $starting_point; $i<$starting_point+30; $i++){
                $p = null;
               $p = $products[$i];
               $allegro_auction_id = $this->allegro->get_allegro_auction_id($p->id);
               if($allegro_auction_id > 0){
               $auction_json = $this->allegro->get_offer($allegro_auction_id);
               $auction = json_decode($auction_json);
                   if($auction->publication->status !== 'ACTIVE'){
                    $p->quantity = 0;
                    $p->update_product();
                   }

               }

            }
            redirect(site_url('duocms/allegro/download_stock_amount/'.($starting_point+30)));
        }
        } else {
            echo 'skończono';
        }
    }

    public function allegro_not_added_list(){
        $allegro_session = null;
        $data = array();
        if(get_option('admin_modules_allegro_token_expiration') > date('U')){
            $allegro_session = array('session' => 1);
            $data['allegro_session'] = $allegro_session;
        } else {
            $data['allegro_session'] = $allegro_session;
        }

        $own_acution_json = $this->allegro->get_own_auctions();
        $own_acution = json_decode($own_acution_json);
        $total_count = $own_acution->count;
        $count = 0;
        $offers = array();
        do{
           if(!empty($own_acution->offers)){
               foreach($own_acution->offers as $single){
                   if(!$this->allegro->check_allegro_product($single->id)){
                       $offers[] = $single;
                   }
               }
           }
           $count+=1000;
           $own_acution_json =  $this->allegro->get_own_auctions($count);
           $own_acution = json_decode($own_acution_json);
        }while($count < $total_count);
        $data['offers'] = $offers;
        $this->layout('duocms/Shop/Allegro/allegro_list_not_added_2', $data);
    }

    public function for_deletion_list(){
        $idata = $this->input->post();
        if(!empty($idata)){
            $this->load->model('ProductModel');
            foreach($idata['product'] as $key => $value){
                $pDeletion = new ProductModel($key);
                $pDeletion->delete();
            }
        }
        $allegro_session = null;
        $data = array();
        if(get_option('admin_modules_allegro_token_expiration') > date('U')){
            $allegro_session = array('session' => 1);
            $data['allegro_session'] = $allegro_session;
        } else {
            $data['allegro_session'] = $allegro_session;
        }

        $own_acution_json = $this->allegro->get_own_auctions(0, 'INACTIVE,ENDED');
        $own_acution = json_decode($own_acution_json);
        $total_count = $own_acution->count;
        $count = 0;
        $offers = array();
        do{
           if(!empty($own_acution->offers)){
               foreach($own_acution->offers as $single){
                   if($this->allegro->check_allegro_product($single->id)){
                       $prod = $this->allegro->get_product_by_allegro_auction_id($single->id);
                       $offers[] = [ 'duocms' => $prod, 'allegro'=>$single];
                   }
               }
           }

           $count+=1000;
           $own_acution_json =  $this->allegro->get_own_auctions($count, 'INACTIVE,ENDED');
           $own_acution = json_decode($own_acution_json);
        }while($count < $total_count);


        $data['offers'] = $offers;
        $this->layout('duocms/Shop/Allegro/allegro_for_deletion_list', $data);
    }

}
