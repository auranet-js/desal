<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class OtomotoModel extends MY_Model {

    private $link;
    private $clident_id;
    private $client_secret;
    private $token;
    private $token_expiration;
    private $client_type;
    private $username;
    private $password;
    
    function __construct() {
        parent::__construct();
        if(get_option('admin_modules_otomoto_mode') == 'production'){
            $this->link = 'https://www.otomoto.pl/api/open/';
        } else {
            $this->link = 'https://sbotomotopl.playground.lisbontechhub.com/api/open/';
        }
        
        $this->clident_id = get_option('admin_modules_otomoto_client_id');
        $this->client_secret = get_option('admin_modules_otomoto_client_secret');
       
        $this->token = get_option('admin_modules_otomoto_token');
        $this->token_expiration = get_option('admin_modules_otomoto_token_expiration');
        
        $this->client_type = get_option('admin_modules_otomoto_client_type');
        $this->username = get_option('admin_modules_otomoto_username');
        $this->password = get_option('admin_modules_otomoto_password');
    }




    
    public function get_token() {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_USERPWD, $this->clident_id . ":" . $this->client_secret);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_URL, $this->link . 'oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        if($this->client_type == 'dealer'){
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'partner',
            'partner_code' => $this->username,
            'partner_secret' => $this->password
        ]));
        }else{
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'password',
            'username' => $this->username,
            'password' => $this->password
        ]));
        }
        $headers = array(
            "User-Agent: WebApp",
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response_body = curl_exec($ch);
        curl_copy_handle($ch);
        $res = json_decode($response_body);
        $this->token = $res->access_token;
        $this->token_expiration = time()+ $res->expires_in*1;
        set_option('admin_modules_otomoto_token', $res->access_token);
        set_option('admin_modules_otomoto_token_expiration', $this->token_expiration);
        
    }

    public function token() {
        if(time() > $this->token_expiration){
            $this->get_token();
        } elseif(empty($this->token)){
            $this->get_token();
        }
        return $this->token;
    }

    private function otomoto_query($type, $point, $data_string = '', $additional_headers = array()) {
        $ch = curl_init();

        $headers = array(
            "User-Agent: WebApp",
            "Content-Type: application/json",
            "Authorization: Bearer " . $this->token()
        );
        if ($type == 'POST' || $type == 'PUT') {
            $headers[] = "Content-Length: " . strlen($data_string);
        }
        $headers = array_merge($headers, $additional_headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_URL, $this->link . $point);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if ($type == 'POST' || $type == 'PUT') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response_body = curl_exec($ch);
//        $info = curl_getinfo($ch);
//            echo json_encode($info) . '<br>';
    //      echo curl_getinfo($ch, CURLINFO_HTTP_CODE) . '<br>';
        curl_close($ch);
        return $response_body;
    }
    public function getAdvertInfo($id)
    {
        $res = $this->otomoto_query('GET', 'adverts/'.$id);
        return json_decode($res);
    }
    public function get_categories($all = 1){
        $res = $this->otomoto_query('GET', 'categories?all='.$all);
        return json_decode($res);
    }

    public function getCategoriesForTest()
    {
        $res = $this->otomoto_query('GET', 'categories');
        return $res;
    }
    
    public function getCategoriesArray($parent = 163){
        $otoMotoCategories = $this->get_categories($parent);
        $categories = [];
        if(!empty($otoMotoCategories->results)){
            foreach($otoMotoCategories->results as $r){
                if(isset($r->id) && isset($r->names->pl)){
                    $categories[$r->id] = $r->names->pl;
                }
            }
        }
        return $categories;
    }
    
    public function get_category_data($category_id = 163){
        $res = $this->otomoto_query('GET', 'categories/'.$category_id);
        return json_decode($res);
    }
    
    public function get_regions(){
        $res = $this->otomoto_query('GET', 'regions');
        return json_decode($res);
    }
    public function get_cities(){
        $res = $this->otomoto_query('GET', 'cities');
        return json_decode($res);
    }
    
    public function search_city($city){
        $in = [ 'cityName' => $city, ];
        $res = $this->otomoto_query('POST', 'cities/search', json_encode($in));
        return json_decode($res);
    }
    
    public function get_district_for_city($city_id){
        //districts/for-city-id/:id
         $res = $this->otomoto_query('GET', 'districts/for-city-id/'.$city_id);
        return json_decode($res);
    }
    
    public function create_image_collection($photos){
        $in = $photos;
        $res = $this->otomoto_query('POST', 'imageCollections', json_encode($in));
        return json_decode($res);
    }
    
    public function add_advert_from_product($product_id){
        $this->load->model('ProductModel');
        $product = new ProductModel($product_id);
       if(empty($product->id)){ 
            $this->load->model('LogModel');
        $this->LogModel->add_log(3, 0, 'Probowano dodać pustą część');
            return false;        
       }
       $tproduct = $product->getTranslation('pl');
       $image_collection =  [];
       $photos = $product->findAllPhotos();
       $i = 1;
       foreach($photos as $photo){
           $image_collection[$i++] = $photo->getUrl();
       }
       
       $image_collection_id = $this->create_image_collection($image_collection)->id;
       
       $price = 
        [
            "0" => "fixed", // "arranged",
            "1" => $product->price,
            "currency" => "PLN",
            "gross_net" => "gross"
        ];
       $params = json_decode($product->type2, TRUE);
       $params['price'] = $price;

       // Auranet 2026-05-29: fallback parts-type z offer_category_id (mapping z 12932 historic).
       if (empty($params['parts-type'])) {
           $params['parts-type'] = $this->fallback_parts_type($product->offer_category_id);
       }

        $in = [
           "title" => substr($tproduct->name, 0, 50),
    "description" => '<p>Przedmiotem sprzedaży jest:</p><p>'.$tproduct->name.'</p>'.$tproduct->body,
    "category_id" => !empty($product->otomoto_category_id) ? (int)$product->otomoto_category_id : 163,
    "region_id" => get_option('admin_modules_otomoto_region_id'),
    "city_id" => get_option('admin_modules_otomoto_city_id'),
    "district_id" => null,
    "coordinates" =>
    [
        "latitude" => 49.969218, 
        "longitude" => 20.874152
    ],
    "contact" =>
    [
        "person" => "DeSal - Zakład Złomowania Pojazdów"
    ],
    "params" => $params,
    "image_collection_id" => $image_collection_id,
    "advertiser_type" => "business",
    "brand_program_id" => null
];
        
        $res = $this->otomoto_query('POST', 'account/adverts', json_encode($in));
        $result = json_decode($res);
        
        if(!empty($result->id)){
        
        $this->activate_advert($result->id);
        $product->otomoto_url = $result->url;
        $product->otomoto_id = $result->id;
        
        $product->update_product();
        
         $this->load->model('LogModel');
        $this->LogModel->add_log(3, $product->id, 'produkt '. $product->id .' dodany do otomoto ');
        return true;
        } else {
            return $result;
        }
    }

    /**
     * Auranet 2026-05-29: fallback parts-type gdy template/sketch nie ustawił go w type2.
     * Mapping na podstawie 12932 udanych historycznych wystawień Otomoto (top-frequency per kategoria).
     */
    private function fallback_parts_type($offer_category_id) {
        $MAP = [
            1=>'uklad-elektryczny', 2=>'akcesoria', 5=>'elementy-wnetrza', 9=>'karoseria',
            10=>'karoseria', 14=>'karoseria', 16=>'karoseria', 18=>'karoseria',
            19=>'karoseria', 23=>'inne', 26=>'karoseria', 29=>'karoseria',
            30=>'karoseria', 32=>'inne', 33=>'inne', 36=>'karoseria',
            37=>'karoseria', 39=>'karoseria', 42=>'inne', 43=>'inne',
            44=>'inne', 47=>'karoseria', 48=>'karoseria', 49=>'uklad-chlodzenia',
            50=>'karoseria', 51=>'inne', 53=>'karoseria', 54=>'karoseria',
            58=>'karoseria', 59=>'karoseria', 60=>'karoseria', 61=>'karoseria',
            62=>'inne', 64=>'karoseria', 67=>'karoseria', 68=>'karoseria',
            69=>'karoseria', 72=>'karoseria', 77=>'felgi', 78=>'felgi',
            79=>'felgi', 83=>'oswietlenie', 85=>'oswietlenie', 86=>'oswietlenie',
            91=>'oswietlenie', 95=>'akcesoria', 98=>'akcesoria', 102=>'silnik',
            103=>'silnik', 104=>'silnik', 106=>'silnik', 108=>'inne',
            109=>'inne', 111=>'silnik', 116=>'silnik', 117=>'silnik',
            125=>'uklad-wydechowy', 137=>'silnik', 138=>'uklad-chlodzenia',
            145=>'uklad-chlodzenia', 146=>'uklad-chlodzenia', 155=>'uklad-elektryczny',
            156=>'uklad-elektryczny', 157=>'uklad-elektryczny', 159=>'uklad-elektryczny',
            161=>'uklad-elektryczny', 163=>'uklad-elektryczny', 169=>'elementy-wnetrza',
            171=>'uklad-elektryczny', 172=>'uklad-elektryczny', 176=>'uklad-hamulcowy',
            180=>'uklad-hamulcowy', 181=>'uklad-hamulcowy', 182=>'uklad-hamulcowy',
            184=>'uklad-hamulcowy', 190=>'układ-hydrauliczny', 193=>'uklad-kierowniczy',
            196=>'układ-hydrauliczny', 198=>'ogrzewanie-wentylacja-klimatyzacja',
            205=>'ogrzewanie-wentylacja-klimatyzacja', 208=>'ogrzewanie-wentylacja-klimatyzacja',
            215=>'uklad-napedowy', 216=>'uklad-napedowy', 219=>'uklad-napedowy',
            250=>'uklad-paliwowy', 276=>'uklad-zawieszenia', 277=>'uklad-zawieszenia',
            282=>'uklad-zawieszenia', 284=>'uklad-zawieszenia', 293=>'elementy-wnetrza',
            294=>'elementy-wnetrza', 300=>'elementy-wnetrza', 302=>'uklad-elektryczny',
            306=>'uklad-elektryczny', 309=>'uklad-elektryczny', 314=>'uklad-elektryczny',
        ];
        $cat = (int)$offer_category_id;
        return isset($MAP[$cat]) ? $MAP[$cat] : 'inne';
    }

    public function activate_advert($id){
        
        return $this->otomoto_query('POST', 'account/adverts/'. $id. '/activate');
    }
    
    public function deactivate_advert($id){
        $in = [ 'reason' => [ 'id' => 1, 'description'=> 'Część została sprzedana.'] ];
        return $this->otomoto_query('POST', 'account/adverts/'. $id. '/activate', json_encode($in));
    }
    
    public function deactivate_advert_from_product($pid){
        $this->db->where('id', $pid);
        $r = $this->db->get('duo_products')->row();
        if(!empty($r->otomoto_id)){
           return $this->deactivate_advert($r->otomoto_id);
        }
    }
    
    public function getAllAdverts(){
        return $this->otomoto_query('GET', 'account/adverts/');
    }
    
    public function saveParametersVisibility($category, $partType, $parameters){
        if(!empty($parameters)){
            foreach($parameters as $parameter){
                $searchParameter = $this->db->get_where('duo_otomoto_parameter_bind',['part_type' => $partType, 'parameter' => $parameter])->result();
                if(empty($searchParameter)){
                    $this->db->insert('duo_otomoto_parameter_bind',['category' => $category,'part_type' => $partType, 'parameter' => $parameter]);
                }
            }
        }
    }
    
    public function getAllParts(){
        return $this->db->get('duo_otomoto_parameter_bind')->result();
    }
    
    public function deleteParameterVisibility($paramterId){
        $searchParameter = $this->db->get_where('duo_otomoto_parameter_bind',['id' => $paramterId])->result();
        if(!empty($searchParameter)){
            $this->db->where('id', $paramterId)->delete('duo_otomoto_parameter_bind');
        }
    }
    
    public function createVisibilityArray(){
        $visibilityArray = [];
        $parameters = $this->getAllParts();
        foreach($parameters as $parameter){
            $visibilityArray[$parameter->category][!empty($parameter->part_type) ? $parameter->part_type : 0][] = $parameter->parameter;
        }
        return $visibilityArray;
    }
    
//  
//    public function add_auction_from_product($product_id){
//        $product = new ProductModel($product_id);
//        if(empty($product->id)){ 
////            $this->load->model('LogModel');
////        $this->LogModel->add_log(2, 0, 'Probowano dodać pustą część');
//            return false;}
//            
//            $delivery = json_decode($this->get_shipping_rates());
//            if(!empty($delivery->error)){
//                $this->load->model('LogModel');
//        $this->LogModel->add_log(-2, $product_id, 'bład z allegro: '.$delivery->error_description); 
//                return 0;
//            }
//        $attributes = json_decode($product->type);
//        $offer = array();
//        $offer_array = [];
//        foreach ($attributes as $key => $attrib) {
//            $at = explode('_', $attrib);
//            $k = $at[0];
//            if(!empty($at[1])){
//                $va['valuesIds']= [$attrib];
//            } else {
//                $va = [];
//            }
//            $offer_array['parameters'][$key]['id'] = $k;
//            $offer_array['parameters'][$key]['rangeValue'] = null;
//            $offer_array['parameters'][$key]['values'] = empty($va['values']) ? [] : $va['values'];
//            $offer_array['parameters'][$key]['valuesIds'] = empty($va['valuesIds']) ? [] : $va['valuesIds'];
//        }
//        $tproduct = $product->getTranslation('pl');
//        $offer['name'] = substr($tproduct->name, 0, 50);
//        $offer['category'] = array('id' => $product->allegro_category_id);
//        $offer['parameters'] = $offer_array['parameters'];
//        $photos = $product->findAllPhotos();
//        if(!empty($photos)){
//            foreach ($photos as $photo) {
//                $res0 = $this->upload_photo($photo);
//                var_dump($res0);
//            }
//        }
//        $uphotos = $this->get_uploaded_photos($product_id);
//        if(!empty($uphotos)){
//        foreach($uphotos as $up){
//         $offer['images'][]['url'] = $up->location;   
//        }
//        }
//        
//        $a_sections = array();
//        if(!empty($offer['images'][0]['url'])){
//            $a_sections[] = array(
//              'items' => array(
//                  array(
//                      'type' => 'TEXT',
//                      'content' => '<p>Przedmiotem sprzedaży jest:</p><p>'.$tproduct->name.'</p>'.$tproduct->body.'<p>Ważne informacje!</p><p>Dane kontaktowe dostępne w zakładce "O sprzedającym"</p>'
//
//                  ),
//                  array(
//                      'type' => 'IMAGE',
//                      'url' => $offer['images'][0]['url']
//                  )
//              )  
//            );
//        } else {
//            $a_sections[] = array(
//              'items' => array(
//                  array(
//                      'type' => 'TEXT',
//                      'content' => $tproduct->body
//                  )
//              )  
//            );
//        }
//     
//
//        $offer['description']['sections'] = $a_sections;
//        
//        
//        $offer['sellingMode'] = [
//            'format' => "BUY_NOW",
//            "price" => [
//                'amount' => $product->price,
//                'currency' => 'PLN'
//            ],
//        ];
//        $offer['stock'] = [
//            'available' => $product->quantity * 1,
//            'unit' => 'UNIT'
//                ];
//        $offer['publication']['startingAt'] = null;
//        $offer['publication']['endingAt'] = null;
//        $offer['publication']['status'] = "INACTIVE";
//        $offer['publication']['duration'] = null;
//        
//        
//        $offer['delivery'] =[
//            'shippingRates' => ['id' => (!empty($product->delivery_id)) ? $product->delivery_id : $delivery->shippingRates[0]->id],
//            'handlingTime' => 'P2D'
//        ] ;
//        $offer['payments'] = [
//            'invoice' => "VAT"
//        ];
//        $implied_warranty = json_decode($this->get_impliedWarranty());
//        $return_policy = json_decode($this->get_returnPolicy());
//        $offer['afterSalesServices'] = [
//            "impliedWarranty" => ['id' => $implied_warranty->impliedWarranties[0]->id],
//            "returnPolicy" => ['id' => $return_policy->returnPolicies[0]->id]
//        ];
//
//        $offer['location'] = [
//            'countryCode' => "PL",
//            'postCode' => get_option('admin_modules_allegro_zipcode'),
//            'province' =>  get_option('admin_modules_allegro_woj'),
//            'city' => get_option('admin_modules_allegro_city')
//        ];
//        $offer_json = json_encode($offer);
//
//            $res = $this->allegro_query2('POST', 'sale/offers', $offer_json);
//            $info = json_decode($res);
//      $this->load->model('LogModel');
//        $this->LogModel->add_log(2, !empty($info->id) ? $info->id : 0, 'produkt '. $product_id .' dodawany do allegro<br>'. json_encode($info->validation));
////            var_dump($info); echo '<hr>';
//        if (!empty($info->id)) {
//            $allegro_id = $info->id;
//            $allegro_status = $info->publication->status;
//            $el = $info;
//            $el->publication->status = "ACTIVE";
//            $offer_json = json_encode($el);
//            $res2 = $this->allegro_query2('PUT', 'sale/offers/' . $info->id, $offer_json);
//            //$val_obj = json_decode($res2);
//           // if(!empty($val_obj->validation->errors[0]->message)){
////                 echo  $val_obj->validation->errors[0]->message   ;
//             //   setAlert('warning',$val_obj->validation->errors[0]->message);
//
//      
////echo $res2.'<hr>';
////die();
//            if (!empty($info->id)) {
//                //publikowanie oferty
//                $args_a = array(
//                    'publication' => array(
//                        'action' => "ACTIVATE"
//                    ),
//                    'offerCriteria' => 
//                        [
//                        array(
//                            'offers' => [array(
//                            'id' => $info->id
//                                )],
//                            "type" => "CONTAINS_OFFERS"
//                        )
//                    ]
//                );
//                $uid = $this->gen_uuid();
//                $data_string = json_encode($args_a);
//                $res3 = $this->allegro_query2('PUT', 'sale/offer-publication-commands/' . $uid, $data_string);
////                echo $res3.'<hr>';
//                                usleep(100000);
//                $uid2 = json_decode($res3)->id;
//                $res4 = $this->allegro_query2('GET', 'sale/offer-publication-commands/' . $uid2);
////                echo $res4.'<hr>';
//            }
//            if(empty($offer_array['id'])){
//                $this->db->insert('duo_shop_allegro', array(
//                    'product_id' => $product_id,
//                    'allegro_id' => $allegro_id,
//                    'allegro_status' => $allegro_status
//                ));
//            }
//        }
//        $this->add_attributes2($product, $info);
//        
//    }
//     public function edit_auction_from_product($product_id){
//        $product = new ProductModel($product_id);
//        if(empty($product->id)){ return false;}
//        $auction_id = $this->get_allegro_auction_id($product->id);
//        $auction_json = $this->get_offer($auction_id);
//        $auction = json_decode($auction_json, true);
//        if(empty($auction["id"])){ return false;}
//        $attributes = json_decode($product->type);
//        $offer = $auction;
//        $offer_array = [];
//        foreach ($attributes as $key => $attrib) {
//            $at = explode('_', $attrib);
//            $k = $at[0];
//            if(!empty($at[1])){
//                $va['valuesIds']= [$attrib];
//            } else {
//                $va = [];
//            }
//            $offer_array['parameters'][$key]['id'] = $k;
//            $offer_array['parameters'][$key]['rangeValue'] = null;
//            $offer_array['parameters'][$key]['values'] = empty($va['values']) ? [] : $va['values'];
//            $offer_array['parameters'][$key]['valuesIds'] = empty($va['valuesIds']) ? [] : $va['valuesIds'];
//        }
//        $tproduct = $product->getTranslation('pl');
//        $offer['name'] = $tproduct->name;
//        $offer['parameters'] = $offer_array['parameters'];
////        $photos = $product->findAllPhotos();
////        if(!empty($photos)){
////            foreach ($photos as $photo) {
////                $res0 = $this->upload_photo($photo);
////            }
////        }
////        $uphotos = $this->get_uploaded_photos($product_id);
////        if(!empty($uphotos)){
////        foreach($uphotos as $up){
////         $offer['images'][]['url'] = $up->location;   
////        }
////        }
//        
//        $a_sections = array();
//        if(!empty($offer['images'][0]['url'])){
//            $a_sections[] = array(
//              'items' => array(
//                  array(
//                      'type' => 'TEXT',
//                      'content' => $tproduct->body
//                  ),
//                  array(
//                      'type' => 'IMAGE',
//                      'url' => $offer['images'][0]['url']
//                  )
//              )  
//            );
//        } else {
//            $a_sections[] = array(
//              'items' => array(
//                  array(
//                      'type' => 'TEXT',
//                      'content' => $tproduct->body
//                  )
//              )  
//            );
//        }
//     
//
//        $offer['description']['sections'] = $a_sections;
//        
//        
//        $offer['sellingMode'] = [
//            'format' => "BUY_NOW",
//            "price" => [
//                'amount' => $product->price,
//                'currency' => 'PLN'
//            ],
//        ];
//        $offer['stock'] = [
//            'available' => $product->quantity * 1,
//            'unit' => 'UNIT'
//                ];
//        
//       
//        $offer_json = json_encode($offer);
//
//        $res2 = $this->allegro_query2('PUT', 'sale/offers/' . $auction['id'], $offer_json);
//        var_dump(json_decode($res2)); 
//        
//    }
//    
//    
//    
//    public function add_offer($offer_array, $product_id = 0) {
//        $offer = array();
//        foreach ($offer_array['parameters'] as $k => $va) {
//            $offer_array['parameters'][$k]['rangeValue'] = null;
//            $offer_array['parameters'][$k]['values'] = empty($va['values']) ? [] : $va['values'];
//            $offer_array['parameters'][$k]['valuesIds'] = empty($va['valuesIds']) ? [] : $va['valuesIds'];
//        }
//        $offer['name'] = $offer_array['name'];
//        $offer['category'] = $offer_array['category'];
//        $offer['parameters'] = $offer_array['parameters'];
//        $a_sections = array();
//        if(!empty($offer_array['images'][0]['url'])){
//            $a_sections[] = array(
//              'items' => array(
//                  array(
//                      'type' => 'TEXT',
//                      'content' => $offer_array['description']['sections'][0]['items'][0]['content']
//                  ),
//                  array(
//                      'type' => 'IMAGE',
//                      'url' => $offer_array['images'][0]['url']
//                  )
//              )  
//            );
//        } else {
//            $a_sections[] = $offer_array['description']['sections'][0];
//        }
//     
//
//        $offer['description']['sections'] = $a_sections;
//        
//        $offer['images'] = $offer_array['images'];
//        $offer['sellingMode'] = $offer_array['sellingMode'];
//        $offer_array['stock']['available'] = $offer_array['stock']['available'] * 1;
//        $offer['stock'] = $offer_array['stock'];
//        $offer_array['publication']['startingAt'] = null;
//        $offer_array['publication']['endingAt'] = null;
//        $offer_array['publication']['status'] = "INACTIVE";
//        $offer['publication'] = $offer_array['publication'];
//        $offer['delivery'] = $offer_array['delivery'];
//        $offer['payments'] = $offer_array['payments'];
//        $offer['afterSalesServices'] = $offer_array['afterSalesServices'];
//        $offer['location'] = $offer_array['location'];
//        $offer['location']['postCode'] = get_option('admin_modules_allegro_zipcode');
//        
//        $offer_json = json_encode($offer);
//        if(empty($offer_array['id'])){
//            $res = $this->allegro_query2('POST', 'sale/offers', $offer_json);
//            $info = json_decode($res);
//        } else {
//            $res = $this->get_offer($offer_array['id']);
//            $info = json_decode($res);
//            foreach($offer as $k=>$vv){
//                $info->$k = json_decode(json_encode($vv));
//            }
//        }
//        
//        
//        if (!empty($info->id)) {
//            $allegro_id = $info->id;
//            $allegro_status = $info->publication->status;
//            $el = $info;
//            $el->publication->status = "ACTIVE";
//            $offer_json = json_encode($el);
//            $res2 = $this->allegro_query2('PUT', 'sale/offers/' . $info->id, $offer_json);
//            $val_obj = json_decode($res2);
//            if(!empty($val_obj->validation->errors[0]->message)){
////                 echo  $val_obj->validation->errors[0]->message   ;
//                setAlert('warning',$val_obj->validation->errors[0]->message);
//            }
//      
////echo $res2;
////die();
//            if (!empty($info->id)) {
//                //publikowanie oferty
//                $args_a = array(
//                    'publication' => array(
//                        'action' => "ACTIVATE"
//                    ),
//                    'offerCriteria' => 
//                        [
//                        array(
//                            'offers' => [array(
//                            'id' => $info->id
//                                )],
//                            "type" => "CONTAINS_OFFERS"
//                        )
//                    ]
//                );
//                $uid = $this->gen_uuid();
//                $data_string = json_encode($args_a);
//                $res3 = $this->allegro_query2('PUT', 'sale/offer-publication-commands/' . $uid, $data_string);
//                                usleep(1000000);
//                $uid2 = json_decode($res3)->id;
//                $res4 = $this->allegro_query2('GET', 'sale/offer-publication-commands/' . $uid2);
//            }
//            if(empty($offer_array['id'])){
//                $this->db->insert('duo_shop_allegro', array(
//                    'product_id' => $product_id,
//                    'allegro_id' => $allegro_id,
//                    'allegro_status' => $allegro_status
//                ));
//            }
//        }
//        return $info;
//    }
//public function end_offer($id){
//        $args_a = array(
//            'publication' => array(
//                'action' => "END"
//            ),
//            'offerCriteria' =>
//            [
//                array(
//                    'offers' => [array(
//                    'id' => $id
//                        )],
//                    "type" => "CONTAINS_OFFERS"
//                )
//            ]
//        );
//        $uid = $this->gen_uuid();
//        $data_string = json_encode($args_a);
//        $res3 = $this->allegro_query2('PUT', 'sale/offer-publication-commands/' . $uid, $data_string);
//        usleep(1000000);
//        $uid2 = json_decode($res3)->id;
//        $res4 = $this->allegro_query2('GET', 'sale/offer-publication-commands/' . $uid2);
//        $this->db->where('allegro_id', $id);
//        $this->db->update('duo_shop_allegro', array(
//            'allegro_status' => 'ENDED'
//        ));
//    }
//    
//    public function renew_offer($id){
//        $args_a = array(
//            'publication' => array(
//                'action' => "ACTIVATE"
//            ),
//            'offerCriteria' =>
//            [
//                array(
//                    'offers' => [array(
//                    'id' => $id
//                        )],
//                    "type" => "CONTAINS_OFFERS"
//                )
//            ]
//        );
//        $uid = $this->gen_uuid();
//        $data_string = json_encode($args_a);
//        $res3 = $this->allegro_query2('PUT', 'sale/offer-publication-commands/' . $uid, $data_string);
//        usleep(1000000);
//        $uid2 = json_decode($res3)->id;
//        $res4 = $this->allegro_query2('GET', 'sale/offer-publication-commands/' . $uid2);
//        $this->db->where('allegro_id', $id);
//        $this->db->update('duo_shop_allegro', array(
//            'allegro_status' => 'ACTIVE'
//        ));
//        return $res4;
//    }
//    public function get_offer($id) { 
//        $res = $this->allegro_query2('GET', 'sale/offers/' . $id, '');
//        return $res;
//    }
//    
//    public function get_offers_list(){
//        $res = $this->allegro_query2('GET', 'offers/listing?seller.id='. $this->seller_id);
//        return $res;
//    }
//    public function get_offers_list_limit($limit, $page){
//        $res = $this->allegro_query2('GET', 'offers/listing?seller.id='. $this->seller_id.'&sort=startTime&limit='.$limit.'&offset='.($page*$limit));
//        return $res;
//    }
//    public function get_shipping_rates() {
//        $res = $this->allegro_query2('GET', 'sale/shipping-rates?seller.id=' . $this->seller_id);
//        return $res;
//    }
//
//    public function get_own_auctions($offset = 0, $status = 'ACTIVE'){
//        $res = $this->allegro_query2('GET', 'sale/offers?selled.id=' . $this->seller_id . '&limit=1000&offset='.$offset.'&publication.status='.$status);
//        return $res;
//    }
//    public function get_delivery_methods(){
//        $res = $this->allegro_query2('GET', 'sale/delivery-methods');
//        return $res;
//    }
//    public function get_shipping_rates_details($id){
//        $res = $this->allegro_query2('GET', 'sale/shipping-rates/'.$id);
//        return $res;
//    }
//    public function get_impliedWarranty() {
//        $res = $this->allegro_query2('GET', 'after-sales-service-conditions/implied-warranties?sellerId=' . $this->seller_id.'&limit=10');
//        return $res;
//    }
//
//    public function get_returnPolicy() {
//        $res = $this->allegro_query2('GET', 'after-sales-service-conditions/return-policies?sellerId=' . $this->seller_id.'&limit=10');
//        return $res;
//    }
//    
//   public function  get_allegro_orders(){
////        $res = $this->allegro_query('GET', 'order/checkout-forms?status=READY_FOR_PROCESSING');
//         $res = $this->allegro_query('GET', 'order/checkout-forms');
//
//        return $res;
//    }
//   public function  get_allegro_order_events(){
////        $res = $this->allegro_query('GET', 'order/checkout-forms?status=READY_FOR_PROCESSING');
//         $res = $this->allegro_query('GET', 'order/events?type=READY_FOR_PROCESSING');
//
//        return $res;
//    }
//    public function get_allegro_order($id){
//        $res = $this->allegro_query('GET', 'order/checkout-forms/'.$id);
//        return $res;
//    }
//    public function get_multivariants_auction_list($limit = 50, $page = 0){
//        $res = $this->allegro_query('GET', 'sale/offer-variants?limit='.$limit.'&offset='.($page*$limit) . '&user.id=' . $this->seller_id);
//        return $res;
//    }
//    
//    public function get_multivariants_auction($setid){
//        $res = $this->allegro_query('GET', 'sale/offer-variants/'.$setid);
//        return $res;
//    }
//
//    public function upload_photo($photo_obj) {
//        $photo_id = $photo_obj->id;
//        $product_id = $photo_obj->product_id;
//
//        $this->db->delete('duo_shop_allegro_photos', array('expiresat <' => date('Y-m-d H:i:s')));
//        $q1 = $this->db->get_where('duo_shop_allegro_photos', array(
//            'product_id' => $product_id,
//            'photo_id' => $photo_id
//        ));
//        if ($q1->num_rows() > 0) {
//            return 0;
//        }
//
//        $name = $photo_obj->name;
//        $path = './uploads/products/' . $product_id . '/' . $photo_id . '/' . $name;
//        $pathinfo = pathinfo($path);
//        $ext = $pathinfo['extension'];
//        $post = file_get_contents($path);
//        if ($ext == 'jpg') {
//            $ext = 'jpeg';
//        }
//        $headers = array(
//            "accept: application/vnd.allegro.public.v1+json",
//            "Authorization: Bearer {$this->token()}",
//            'content-type: image/' . $ext,
//            'accept-language: pl-PL'
//        );
//
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $this->upload_link . 'sale/images');
//        curl_setopt($ch, CURLOPT_POST, 1);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//
//        $result = curl_exec($ch);
//        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
//        if ($code == 201) {
//            $result = json_decode($result);
////            var_dump($result);
//            $expiresat = $result->expiresAt;
//            $location = $result->location;
//            $res_insert = $this->db->insert('duo_shop_allegro_photos', array(
//                'product_id' => $product_id,
//                'photo_id' => $photo_id,
//                'expiresat' => $expiresat,
//                'location' => $location
//            ));
//        }
//        curl_close($ch);
//    }
//
//    public function get_uploaded_photos($product_id) {
////        $q = $this->db->get_where('duo_shop_allegro_photos', array('product_id' => $product_id));
//        $this->db->select('duo_shop_allegro_photos.*');
//        $this->db->join('duo_product_photos', 'duo_product_photos.id = duo_shop_allegro_photos.photo_id');
//        $this->db->where('duo_shop_allegro_photos.product_id', $product_id);      
//        $this->db->order_by('duo_product_photos.order ASC');
//        $q = $this->db->get('duo_shop_allegro_photos');
//        return $q->result();
//    }
//
//    public function test() {
//        return true;
//    }
//
//    function allegro_query($type, $point, $data_string = '', $link = 'link', $additional_headers = array()) {
//        $ch = curl_init();
//        if ($link == 'upload_link') {
//            $headers = array(
//                "accept: application/vnd.allegro.beta.v1+json",
//                "Authorization: Bearer {$this->token()}",
//                'content-type: "image/png","image/jpg","image/jpeg","image/gif"',
//                'accept-language: pl-PL'
//            );
//        } else {
//            $headers = array(
//                "content-type: application/vnd.allegro.beta.v1+json",
//                "Accept: application/vnd.allegro.beta.v1+json",
//                "Authorization: Bearer {$this->token()}"
//            );
//        }
//        if ($type == 'POST' || $type == 'PUT') {
//            $headers[] = "Content-Length: " . strlen($data_string);
//        }
//        $headers = array_merge($headers, $additional_headers);
//        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
//        curl_setopt($ch, CURLOPT_URL, $this->$link . $point);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        if ($type == 'POST' || $type == 'PUT') {
//            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
//        }
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//        $response_body = curl_exec($ch);
////        $info = curl_getinfo($ch);
////            echo json_encode($info) . '<br>';
//    //      echo curl_getinfo($ch, CURLINFO_HTTP_CODE) . '<br>';
//        curl_close($ch);
//        return $response_body;
//    }
//    
//    //naglowki produkcyjne, nie beta
//    function allegro_query2($type, $point, $data_string = '', $link = 'link', $additional_headers = array()) {
//        $ch = curl_init();
//        if ($link == 'upload_link') {
//            $headers = array(
//                "accept: application/vnd.allegro.public.v1+json",
//                "Authorization: Bearer {$this->token()}",
//                'content-type: "image/png","image/jpg","image/jpeg","image/gif"',
//                'accept-language: pl-PL'
//            );
//        } else {
//            $headers = array(
//                "content-type: application/vnd.allegro.public.v1+json",
//                "Accept: application/vnd.allegro.public.v1+json",
//                "Authorization: Bearer {$this->token()}"
//            );
//        }
//        if ($type == 'POST' || $type == 'PUT') {
//            $headers[] = "Content-Length: " . strlen($data_string);
//        }
//        $headers = array_merge($headers, $additional_headers);
//        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
//        curl_setopt($ch, CURLOPT_URL, $this->$link . $point);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//        if ($type == 'POST' || $type == 'PUT') {
//            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
//        }
//        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//        $response_body = curl_exec($ch);
////        $info = curl_getinfo($ch);
////            echo json_encode($info) . '<br>';
//    //      echo curl_getinfo($ch, CURLINFO_HTTP_CODE) . '<br>';
//        curl_close($ch);
//        return $response_body;
//    }
//    
//   public function check_allegro_product($id){
//        $this->db->where('allegro_id', $id);
//        $query = $this->db->get('duo_shop_allegro');
//        if($query->num_rows() > 0){
//            return TRUE;
//        } else {
//            return FALSE;
//        }
//    }
//    
//    public function get_allegro_category($cat_id){
//        $res = $this->allegro_query2("GET", "sale/categories/" . $cat_id);
//        return $res;
//    }
//    
//    public function get_duo_auctions_list(){
//        $this->db->select('allegro_id');
//        $this->db->distinct();
//        $query = $this->db->get('duo_shop_allegro');
//        $array = $query->result();
//        $res = array();
//        foreach ($array as $a) {
//            $res[] = $a->allegro_id;
//        }
//        return $res;
//    }
//    
//    public function get_product_by_allegro_auction_id($allegro_id){
//        $this->db->where('allegro_id', $allegro_id);
//        $this->db->order_by('created_at','desc');
//        $query = $this->db->get('duo_shop_allegro');
//        $res = $query->result()[0]->product_id;
//        $this->load->model('ProductModel');
//        $product = new ProductModel($res);
//        return $product;
//    }
//    
//    public function update_amount($auction, $new_amount){
//        $auction_json = $this->get_offer($auction);
//        $auction_body = json_decode($auction_json);
//        $status = $auction_body->publication->status;
//        if($status == 'ACTIVE' && $new_amount == 0){
//            $this->end_offer($auction);
//            return true;
//        }
//        if($status != 'ACTIVE' && $new_amount > 0){
//            $res = $this->renew_offer($auction);
//        }
//        $auction_body->stock->available = $new_amount;
//        $offer = json_encode($auction_body);
//        $res = $this->allegro_query2('PUT', 'sale/offers/'.$auction , $offer);
//
//        return $res;
//    }
//    
//       public function get_allegro_auction_id($product_id){
//        $this->db->where('product_id', $product_id);
//        $query = $this->db->get('duo_shop_allegro');
//        if($query->num_rows() > 0){
//            $res = $query->result()[0];
//            return $res->allegro_id;
//        } else {
//            return -1;
//        }
//    }
//    
////    public function synchronise_amount($auction, $difference = 0){
////            $allegro_amount = $auction->stock->available;
////            $product = $this->get_product_by_allegro_auction_id($auction->id);
////            $this->load->model("OrderModel");
////            $amount_sold_since_date = $this->OrderModel->find_amount_sold_since_date($product);
////            if(($amount_sold_since_date > 0) || ($difference != 0)){
////                $new_amount = $allegro_amount - $amount_sold_since_date + $difference;
////                
////                $this->update_amount($auction, $new_amount);
////                $product->quantity = $new_amount;
////                $product->amount_updated_at = (new DateTime())->format('Y-m-d H:i:s');
////                $product->update_product();
////                return $new_amount;
////            }
////            return -1;
////    }
//    
//    public function insert_allegro_delivery($allegro_id, $delivery_id){ 
//        $this->db->insert('duo_shop_allegro_deliveries', array(
//            "allegro_id" => $allegro_id,
//            "delivery_id" => $delivery_id
//        ));
//    }
//    
//    public function clear_all_deliveries(){
//        $this->db->where('id > 0');
//        $this->db->delete('duo_shop_allegro_deliveries');
//    }
//    
//    public function get_all_deliveries(){
//        $q = $this->db->get('duo_shop_allegro_deliveries');
//        $res =  $q->result();
//        $arr = array();
//        foreach ($res as $r){
//            $arr[$r->allegro_id] = $r->delivery_id;
//        }
//        return $arr;
//    }
//    
//    public function get_delivery_by_allegro_id($allegro_id){
//        $this->db->where('allegro_id', $allegro_id);
//        $q = $this->db->get('duo_shop_allegro_deliveries');
//        if($q->num_rows() > 0){
//            return $q->result()[0];
//        } else {
//            return null;
//        }
//    }
//    
//    public function get_product_allegro_attributes($product_id){
//        $this->db->select('allegro_id');
//        $this->db->join('duo_shop_attributes_relations', 'duo_shop_attributes_relations.attribute_id = duo_shop_attributes.id');
//        $this->db->where('duo_shop_attributes_relations.product_id ='.$product_id);
//        $q = $this->db->get("duo_shop_attributes");
//        
//        if($q->num_rows() > 0){
//            $res = $q->result();
//            $tmp = array();
//            foreach($res as $r){
//                $tmp[] = $r->allegro_id;
//            }
//            return $tmp;
//        } else {
//            return array();
//        }
//    }
//    
//    public function find_photo_by_id($photo_id){
//        $this->db->where('photo_id', $photo_id);
//        $q = $this->db->get('duo_shop_allegro_photos');
//        if($q->num_rows() > 0){
//            return $q->result()[0];
//        } else {
//            return null;
//        }
//    }
//    
//     public function download_orders(){
//        $regex = "/([\w\s.ąćęłńóśźżŁÓŃĆŻŹĄĘŚ]+)\s{1}([0-9]+[A-Za-z]?){1}\s?[\/mM]?[.]?\s?([0-9]+)?/";
//        $this->load->model("OrderModel");
//        $orders = json_decode($this->get_allegro_order_events());
//        foreach ($orders->events as $event) {
//            $order = json_decode($this->get_allegro_order($event->order->checkoutForm->id));
//            if(!$this->OrderModel->check_if_order_in_db($order->id)){
//            $data = array();
//            $data['allegro_id'] = $order->id;
//            $data['email'] = $order->buyer->email;
//            $data['first_name'] = $order->delivery->address->firstName;
//            $data['last_name'] = $order->delivery->address->lastName;
//            $data['city'] = $order->delivery->address->city;
//            $data['zip_code'] = $order->delivery->address->zipCode;
//            $street = $order->delivery->address->street;
//            $street_table = array();
//            preg_match($regex, $street, $street_table);
//            if(!empty($street_table[2])){
//                $data['street'] = $street_table[1];
//                $data['building_number'] = $street_table[2];
//                $data["flat_number"] = (!empty($street_table[3])) ? $street_table[3] : null;
//            } else {
//                $data['street'] = $street;
//                $data['building_number'] = 0;
//            }
//            $data['delivery'] = $this->get_delivery_by_allegro_id($order->delivery->method->id)->delivery_id;
//            $data['phone'] = $order->delivery->address->phoneNumber;
//            $data['comment'] = (!empty($order->messageToSeller)) ? 'dodane z allegro, '.$order->messageToSeller :'dodane z allegro';
//            $data['price'] = $order->summary->totalToPay->amount;
//            $data['wieght'] = 1;
//            if($order->payment->type == 'CASH_ON_DELIVERY'){
//                $data['method'] = 'upon_receipt';
//            } else {
//                $data['method'] = 'allegro';
//            }
//            $data['locker'] = null;
//            if(!empty($order->delivery->pickupPoint)){
//                $pickup_name = $order->delivery->pickupPoint->name;
//                if(strpos($pickup_name, 'Paczkomat') !== FALSE){
//                    $data['locker'] = trim(explode('Paczkomat', $pickup_name)[1]);
//                } else if(strpos($pickup_name, 'PACZKA w RUCHu:') !== FALSE){
//                    $data['locker'] = trim(explode(':', $pickup_name)[1]);
//                } else {
//                    $data['locker'] = $pickup_name;
//                }
//            }
//            $flag = false;
//            $order_id = $this->OrderModel->add_order_from_allegro($data);
//            foreach($order->lineItems as $item){
//                if(!$this->check_allegro_product($item->offer->id)){
//                    $this->add_from_allegro_no_redirect($item->offer->id);
//                    $flag = true;
//                }
//                $product = $this->get_product_by_allegro_auction_id($item->offer->id);
//                $this->OrderModel->add_item_to_order($product->id, $item->quantity, $order_id, $flag);
//                $flag = false;
//            }
//            
//            }
//        }
//        
//    }
//    
//        private function add_attributes($product, $auction){
//        $allegro_attributes_json = $this->get_category_fields($auction->category->id);
//        $allegro_attributes= json_decode($allegro_attributes_json);
//        $this->load->model('ProductAttributesModel');
//        $units = array();
//        foreach($allegro_attributes->parameters as $param_group){
//            if(!$this->ProductAttributesModel->check_if_allegro_group_exist($param_group->id)){
//            $attribute_group = array(
//                'translations' => array(
//                    array(
//                    'lang' => 'pl',
//                    'name' => $param_group->name,
//                    'description' => ''
//                        )
//                ),
//                'allegro' => $param_group->id
//            );
//            $this->ProductAttributesModel->add_group($attribute_group);
//            }
//            $group_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($param_group->id);
//            switch ($param_group->type) {
//                case 'dictionary':
//                    foreach ($param_group->dictionary as $param) {
//                        if(!$this->ProductAttributesModel->check_if_allegro_attr_exist($param->id)){
//                            $duo_attr_id = $this->ProductAttributesModel->attribute_add(0, $group_id , $param->id);
//                            $args = array('pl' => array('name'=> $param->value, 'description'=>''));
//                            $this->ProductAttributesModel->attribute_update($duo_attr_id, 0, $args, $group_id);
//                        }
//                    }
//                    break;
//                case 'float':
//                case 'integer':
//                    $units[$param_group->id] = array ('name' => $param_group->unit );
//                    break;
//                default:
//                    break;
//            }            
//        }
//        foreach ($auction->parameters as $parameter) {
//            if(!empty($parameter->valuesIds)){
//                $attr_id = $this->ProductAttributesModel->find_attr_by_allegro_id($parameter->valuesIds[0]);
//                if(!empty($attr_id)){
//                    $product->attribute_add_to_product($attr_id, $product->id ,null);
//                }
//            }
//            if(!empty($parameter->values)){
//                $attr_val = $parameter->values[0];
//                $attr_group_id = $parameter->id;
//                $group_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($attr_group_id);
//                $unit_name = '';
//                if(!empty($units[$attr_group_id])){
//                $unit_name = $units[$attr_group_id]['name'];}
//                $duo_attr_name = $attr_val.' '.$unit_name;
//                $duo_attr_allegro_id = $attr_group_id.'_'.$attr_val;
//                if(!$this->ProductAttributesModel->check_if_allegro_attr_exist($duo_attr_allegro_id)){
//                    $duo_attr_id = $this->ProductAttributesModel->attribute_add(0, $group_id , $duo_attr_allegro_id);
//                    $args = array('pl' => array('name'=> $duo_attr_name, 'description'=>''));
//                    $this->ProductAttributesModel->attribute_update($duo_attr_id, 0, $args, $group_id);
//                }
//                $attr_id = $this->ProductAttributesModel->find_attr_by_allegro_id($duo_attr_allegro_id);
//                if(!empty($attr_id)){
//                    $product->attribute_add_to_product($attr_id, $product->id ,null);
//                }
//            }
//            $attr_id = null;
//        }
//    }
//    
//     private function add_attributes2($product, $auction){
//        $allegro_attributes_json = $this->get_category_fields($product->allegro_category_id);
//        $allegro_attributes= json_decode($allegro_attributes_json);
//        $this->load->model('ProductAttributesModel');
//        $units = array();
//        foreach($allegro_attributes->parameters as $param_group){
//            if(!$this->ProductAttributesModel->check_if_allegro_group_exist($param_group->id)){
//            $attribute_group = array(
//                'translations' => array(
//                    array(
//                    'lang' => 'pl',
//                    'name' => $param_group->name,
//                    'description' => ''
//                        )
//                ),
//                'allegro' => $param_group->id
//            );
//            $this->ProductAttributesModel->add_group($attribute_group);
//            }
//            $group_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($param_group->id);
//            switch ($param_group->type) {
//                case 'dictionary':
//                    foreach ($param_group->dictionary as $param) {
//                        if(!$this->ProductAttributesModel->check_if_allegro_attr_exist($param->id)){
//                            $duo_attr_id = $this->ProductAttributesModel->attribute_add(0, $group_id , $param->id);
//                            $args = array('pl' => array('name'=> $param->value, 'description'=>''));
//                            $this->ProductAttributesModel->attribute_update($duo_attr_id, 0, $args, $group_id);
//                        }
//                    }
//                    break;
//                case 'float':
//                case 'integer':
//                    $units[$param_group->id] = array ('name' => $param_group->unit );
//                    break;
//                default:
//                    break;
//            }            
//        }
//        foreach ($auction->parameters as $parameter) {
//            if(!empty($parameter->valuesIds)){
//                $attr_id = $this->ProductAttributesModel->find_attr_by_allegro_id($parameter->valuesIds[0]);
//                if(!empty($attr_id)){
//                    $product->attribute_add_to_product($attr_id, $product->id ,null);
//                }
//            }
//            if(!empty($parameter->values)){
//                $attr_val = $parameter->values[0];
//                $attr_group_id = $parameter->id;
//                $group_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($attr_group_id);
//                $unit_name = '';
//                if(!empty($units[$attr_group_id])){
//                $unit_name = $units[$attr_group_id]['name'];}
//                $duo_attr_name = $attr_val.' '.$unit_name;
//                $duo_attr_allegro_id = $attr_group_id.'_'.$attr_val;
//                if(!$this->ProductAttributesModel->check_if_allegro_attr_exist($duo_attr_allegro_id)){
//                    $duo_attr_id = $this->ProductAttributesModel->attribute_add(0, $group_id , $duo_attr_allegro_id);
//                    $args = array('pl' => array('name'=> $duo_attr_name, 'description'=>''));
//                    $this->ProductAttributesModel->attribute_update($duo_attr_id, 0, $args, $group_id);
//                }
//                $attr_id = $this->ProductAttributesModel->find_attr_by_allegro_id($duo_attr_allegro_id);
//                if(!empty($attr_id)){
//                    $product->attribute_add_to_product($attr_id, $product->id ,null);
//                }
//            }
//            $attr_id = null;
//        }
//    }
//    public function add_from_allegro_no_redirect($allegro_id){
//        $auction_json = $this->get_offer($allegro_id);
//        $auction = json_decode($auction_json);
//        $product = new ProductModel();
//        $allegro_category_id = $auction->category->id;
//        
//        if(empty($allegro_category_id)){
//            return -1;
//        }
//        $this->load->model("OfferCategoryModel");
//        $product->offer_category_id = 0;
//        $product->quantity = $auction->stock->available;
//        $product->price = $auction->sellingMode->price->amount;
//        $product->active = 0;
//        $product->status = 1;
//        if (!empty($auction->ean)) {
//            $product->code = $auction->ean;
//        } else {
//            $product->code = 'brak';
//        }
//        $product->insert_product();
//        $this->load->model("ProductTranslationModel");
//        $translation = new ProductTranslationModel();
//        $translation->product_id = $product->id;
//        $translation->lang = 'pl';
//        $translation->name = $auction->name;
//        $translation->body = "";
//        $this->load->model("ProductPhotoModel");
//        foreach ($auction->description->sections as $sections) {
//            foreach ($sections as $sec) {
//                foreach($sec as $item)
//                    if($item->type == 'TEXT'){
//                    $translation->body .=  $item->content ."<br>";
//                    }
//            }   
//        }
//        foreach ($auction->images as $item) {
//            $photo = new ProductPhotoModel();
//            $photo->product_id = $product->id;
//            $photo->insert();
//            $photo->save_from_remote_url($item->url);
//        }
//        $translation->insert();
//        $res = $this->db->insert('duo_shop_allegro', [
//                    'product_id' => $product->id,
//                    'allegro_id' => $allegro_id,
//                    'created_at' => (new DateTime())->format('Y-m-d H:i:s')
//        ]);
//
//        $this->add_attributes($product, $auction);
//    }
//    
//    public function removeDeleted(){
//         $own_acution_json = $this->get_own_auctions(0, 'INACTIVE,ENDED');
//        $own_acution = json_decode($own_acution_json);
//        $total_count = $own_acution->count;
//        $count = 0;
//        $offers = array();
//        do{
//           if(!empty($own_acution->offers)){
//               foreach($own_acution->offers as $single){
//                   if($this->check_allegro_product($single->id)){
//                      // $offers[] = $single;
//                       $prod = $this->get_product_by_allegro_auction_id($single->id);
//                       $prod->active = 1;
//                       $prod->quantity = 0;
//                       $prod->update_product();
//                   }
//               }
//           }
//            
//           $count+=1000;
//           $own_acution_json =  $this->get_own_auctions($count, 'INACTIVE,ENDED');
//           $own_acution = json_decode($own_acution_json);
//        }while($count < $total_count);
//    }
}
