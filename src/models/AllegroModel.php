<?php
// SOURCE: /home6/desal/public_html/application/models/AllegroModel.php
// FETCHED: 2026-05-27 ~10:21 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2021-08-11 05:58:15, size 54130 B

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class AllegroModel extends MY_Model {

    private $allegro;
    private $link;
    private $login_link;
    private $upload_link;
    private $clident_id;
    private $seller_id;
    private $client_secret;
    private $token;
    private $redirect_uri;
    private $access_token;
    private $refresh_token;
    private $token_expiration;
    
    function __construct() {
        parent::__construct();
        $this->link = get_option('admin_modules_allegro_link');
        $this->login_link = get_option('admin_modules_allegro_login_link');
        $this->upload_link = get_option('admin_modules_allegro_upload_link');
        $this->seller_id = get_option('admin_modules_allegro_seller_id');
        $this->clident_id = get_option('admin_modules_allegro_client_id');
        $this->client_secret = get_option('admin_modules_allegro_client_secret');
        $this->access_token = get_option('admin_modules_allegro_accesstoken');
        $this->refresh_token = get_option('admin_modules_allegro_refreshtoken');
        $this->token_expiration = get_option('admin_modules_allegro_token_expiration');
    }

    public function get_auctions($product_id) {
        $q = $this->db->get_where('duo_shop_allegro', array('product_id' => $product_id));
        return $q->result();
    }
    
    public function get_auction_record_by_allegro_id($allegro_id){
        $q = $this->db->get_where('duo_shop_allegro', array('allegro_id' => $allegro_id));
        return $q->row();
    }

    public function get_login_url($product_id = 0, $offer = 0, $listing = null) {
        if(!empty($listing)){
            $rtn =  $this->login_link . 'auth/oauth/authorize?response_type=code&client_id=' . $this->clident_id . '&redirect_uri=' . site_url('api/allegro/code/?listing=1');
            return $rtn;
        }
        if(empty($offer)){
            $lstr = $this->login_link . 'auth/oauth/authorize?response_type=code&client_id=' . $this->clident_id . '&redirect_uri=' . site_url('api/allegro/code/?product_id=' . $product_id);
        } else {
            $lstr = $this->login_link . 'auth/oauth/authorize?response_type=code&client_id=' . $this->clident_id . '&redirect_uri=' . site_url('api/allegro/code/?offer_id=' . $product_id);
        }
        
        return $lstr;
    }
    public function refresh_token(){
        $ch = curl_init();
        $headers = array(
            //"Content-Type: application/json",
            "Authorization: Basic " . base64_encode($this->clident_id . ':' . $this->client_secret)
        );

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_URL, $this->login_link . 'auth/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh_token
        ]));
        $response_body = curl_exec($ch);
        $res = json_decode($response_body, true);
        $res['expired'] = date('U') + $res['expires_in'];
        $this->session->set_userdata('allegro',$res);
        set_option('admin_modules_allegro_accesstoken', $res['access_token']);
        set_option('admin_modules_allegro_refreshtoken',$res['refresh_token']);
        set_option('admin_modules_allegro_token_expiration', $res['expired']);
        return $response_body;
    }
    
    public function get_token($code, $product_id = 0, $listing = 0) {
        $ch = curl_init();
        $headers = array(
            //"Content-Type: application/json",
            "Authorization: Basic " . base64_encode($this->clident_id . ':' . $this->client_secret)
        );

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_URL, $this->login_link . 'auth/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        if(empty($listing)){
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'api-key' => $this->clident_id,
            'redirect_uri' => site_url('api/allegro/code/?product_id=' . $product_id)
        ]));
        }else{
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'api-key' => $this->clident_id,
            'redirect_uri' => site_url('api/allegro/code/?listing=1')
        ]));
        }
        $response_body = curl_exec($ch);
        return $response_body;
    }

    public function token() {
//        if (!empty($this->session->userdata['allegro'])) {
//            return $this->session->userdata['allegro']['access_token'];
//        } else {
//            return null;
//        }
        return $this->access_token;
    }

    public function get_category_fields($category_id) {
        $res = $this->allegro_query2('GET', 'sale/categories/' . $category_id . '/parameters', '');
        
        return $res;
    }

    public function gen_uuid() {
        $uuid = array(
            'time_low' => 0,
            'time_mid' => 0,
            'time_hi' => 0,
            'clock_seq_hi' => 0,
            'clock_seq_low' => 0,
            'node' => array()
        );

        $uuid['time_low'] = mt_rand(0, 0xffff) + (mt_rand(0, 0xffff) << 16);
        $uuid['time_mid'] = mt_rand(0, 0xffff);
        $uuid['time_hi'] = (4 << 12) | (mt_rand(0, 0x1000));
        $uuid['clock_seq_hi'] = (1 << 7) | (mt_rand(0, 128));
        $uuid['clock_seq_low'] = mt_rand(0, 255);

        for ($i = 0; $i < 6; $i++) {
            $uuid['node'][$i] = mt_rand(0, 255);
        }

        $uuid = sprintf('%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x', $uuid['time_low'], $uuid['time_mid'], $uuid['time_hi'], $uuid['clock_seq_hi'], $uuid['clock_seq_low'], $uuid['node'][0], $uuid['node'][1], $uuid['node'][2], $uuid['node'][3], $uuid['node'][4], $uuid['node'][5]
        );

        return $uuid;
    }
    
    public function edit_offer($offer_array){
        $auction_id = $offer_array['allegro_id'];
        $offer = json_decode($this->get_offer($auction_id), TRUE);
        foreach ($offer_array['parameters'] as $k => $va) {
            $offer_array['parameters'][$k]['rangeValue'] = null;
            $offer_array['parameters'][$k]['values'] = empty($va['values']) ? [] : $va['values'];
            $offer_array['parameters'][$k]['valuesIds'] = empty($va['valuesIds']) ? [] : $va['valuesIds'];
        }
        $offer['name'] = $offer_array['name'];
        $offer['category'] = $offer_array['category'];
        $offer['parameters'] = $offer_array['parameters'];
        $a_sections = array();
        if(!empty($offer_array['images'][0]['url'])){
            $a_sections[] = array(
              'items' => array(
                  array(
                      'type' => 'TEXT',
                      'content' => $offer_array['description']['sections'][0]['items'][0]['content']
                  ),
                  array(
                      'type' => 'IMAGE',
                      'url' => $offer_array['images'][0]['url']
                  )
              )  
            );
        } else {
            $a_sections[] = $offer_array['description']['sections'][0];
        }
        if(!empty($offer_array['images'][1]['url'])){
            $a_sections[] = array(
              'items' => array(
                  array(
                      'type' => 'IMAGE',
                      'url' => $offer_array['images'][1]['url']
                  ),
                  array(
                      'type' => 'TEXT',
                      'content' => $offer_array['description']['sections'][1]['items'][0]['content']
                  )
              )  
            );
        } else {
            $a_sections[] = $offer_array['description']['sections'][1];
        }
        if(!empty($offer_array['images'][2]['url'])){
            $a_sections[] = array(
              'items' => array(
                  array(
                      'type' => 'TEXT',
                      'content' => $offer_array['description']['sections'][2]['items'][0]['content']
                  ),
                  array(
                      'type' => 'IMAGE',
                      'url' => $offer_array['images'][2]['url']
                  )
              )  
            );
        } else {
            $a_sections[] = $offer_array['description']['sections'][2];
        }
        if(!empty($offer_array['images'][3]['url'])){
            for($jj = 3; $jj<(count($offer_array['images'])-1); $jj++ ){
              $a_sections[] = array(
              'items' => array(
                  array(
                      'type' => 'IMAGE',
                      'url' => $offer_array['images'][$jj]['url']
                  )
              )  
            );
            }
        }
        $last_index = count($offer_array['images'])-1;
        if(!empty($offer_array['images'][$last_index]['url'])){
            $a_sections[] = array(
              'items' => array(
                  array(
                      'type' => 'TEXT',
                      'content' => $offer_array['description']['sections'][$last_index]['items'][0]['content']
                  ),
                  array(
                      'type' => 'IMAGE',
                      'url' => $offer_array['images'][$last_index]['url']
                  )
              )  
            );
        }
        
        $offer['description']['sections'] = $a_sections;
        
        $offer['images'] = $offer_array['images'];
        $offer['sellingMode'] = $offer_array['sellingMode'];
        $offer_array['stock']['available'] = $offer_array['stock']['available'] * 1;
        $offer['stock'] = $offer_array['stock'];
        $offer_array['publication']['startingAt'] = null;
        $offer_array['publication']['endingAt'] = null;
        $offer_array['publication']['status'] = "INACTIVE";
        if($offer_array['publication']['duration'] == "null"){
            $offer_array['publication']['duration'] = null;
        }
        $offer['publication'] = $offer_array['publication'];
        $offer['delivery'] = $offer_array['delivery'];
        $offer['payments'] = $offer_array['payments'];
        $offer['afterSalesServices'] = $offer_array['afterSalesServices'];
        $offer['location'] = $offer_array['location'];
        //$offer['location']['postCode'] = get_option('admin_modules_allegro_zipcode');
        $offer_json = json_encode($offer);
        
        if(empty($offer_array['id'])){
            $res = $this->allegro_query2('PUT', 'sale/offers/'.$auction_id, $offer_json);
            $info = json_decode($res);
        } else {
            $res = $this->get_offer($offer_array['id']);
            $info = json_decode($res);
            foreach($offer as $k=>$vv){
                $info->$k = json_decode(json_encode($vv));
            }
        }
        return $info;
        
    }

    public function add_auction_from_product($product_id){
        $product = new ProductModel($product_id);
        if(empty($product->id)){ 
//            $this->load->model('LogModel');
//        $this->LogModel->add_log(2, 0, 'Probowano dodać pustą część');
            return false;}
            
            $delivery = json_decode($this->get_shipping_rates());
            if(!empty($delivery->error)){
                $this->load->model('LogModel');
        $this->LogModel->add_log(-2, $product_id, 'bład z allegro: '.$delivery->error_description); 
                return 0;
            }
        $attributes = json_decode($product->type);
        $offer = array();
        $offer_array = [];
        foreach ($attributes as $key => $attrib) {
            $at = explode('_', $attrib);
            $k = $at[0];
            if(!empty($at[1])){
                $va['valuesIds']= [$attrib];
            } else {
                $va = [];
            }
            $offer_array['parameters'][$key]['id'] = $k;
            $offer_array['parameters'][$key]['rangeValue'] = null;
            $offer_array['parameters'][$key]['values'] = empty($va['values']) ? [] : $va['values'];
            $offer_array['parameters'][$key]['valuesIds'] = empty($va['valuesIds']) ? [] : $va['valuesIds'];
        }
        $tproduct = $product->getTranslation('pl');
        $offer['name'] = substr($tproduct->name, 0, 50);
        $offer['category'] = array('id' => $product->allegro_category_id);
        $offer['parameters'] = $offer_array['parameters'];
        $photos = $product->findAllPhotos();
        if(!empty($photos)){
            foreach ($photos as $photo) {
                $res0 = $this->upload_photo($photo);
                var_dump($res0);
            }
        }
        $uphotos = $this->get_uploaded_photos($product_id);
        if(!empty($uphotos)){
        foreach($uphotos as $up){
         $offer['images'][]['url'] = $up->location;   
        }
        }
        
        $a_sections = array();
        if(!empty($offer['images'][0]['url'])){
            $a_sections[] = array(
              'items' => array(
                  array(
                      'type' => 'TEXT',
                      'content' => '<p>Przedmiotem sprzedaży jest:</p><p>'.$tproduct->name.'</p>'.$tproduct->body.'<p>Ważne informacje!</p><p>Dane kontaktowe dostępne w zakładce "O sprzedającym"</p>'

                  ),
                  array(
                      'type' => 'IMAGE',
                      'url' => $offer['images'][0]['url']
                  )
              )  
            );
        } else {
            $a_sections[] = array(
              'items' => array(
                  array(
                      'type' => 'TEXT',
                      'content' => $tproduct->body
                  )
              )  
            );
        }
     

        $offer['description']['sections'] = $a_sections;
        
        
        $offer['sellingMode'] = [
            'format' => "BUY_NOW",
            "price" => [
                'amount' => $product->price,
                'currency' => 'PLN'
            ],
        ];
        $offer['stock'] = [
            'available' => $product->quantity * 1,
            'unit' => 'UNIT'
                ];
        $offer['publication']['startingAt'] = null;
        $offer['publication']['endingAt'] = null;
        $offer['publication']['status'] = "INACTIVE";
        $offer['publication']['duration'] = null;
        
        
        $offer['delivery'] =[
            'shippingRates' => ['id' => (!empty($product->delivery_id)) ? $product->delivery_id : $delivery->shippingRates[0]->id],
            'handlingTime' => 'P2D'
        ] ;
        $offer['payments'] = [
            'invoice' => "VAT"
        ];
        $implied_warranty = json_decode($this->get_impliedWarranty());
        $return_policy = json_decode($this->get_returnPolicy());
        $offer['afterSalesServices'] = [
            "impliedWarranty" => ['id' => $implied_warranty->impliedWarranties[0]->id],
            "returnPolicy" => ['id' => $return_policy->returnPolicies[0]->id]
        ];

        $offer['location'] = [
            'countryCode' => "PL",
            'postCode' => get_option('admin_modules_allegro_zipcode'),
            'province' =>  get_option('admin_modules_allegro_woj'),
            'city' => get_option('admin_modules_allegro_city')
        ];
        $offer_json = json_encode($offer);

            $res = $this->allegro_query2('POST', 'sale/offers', $offer_json);
            $info = json_decode($res);
      $this->load->model('LogModel');
        $this->LogModel->add_log(2, !empty($info->id) ? $info->id : 0, 'produkt '. $product_id .' dodawany do allegro<br>'. json_encode($info->validation));
//            var_dump($info); echo '<hr>';
        if (!empty($info->id)) {
            $allegro_id = $info->id;
            $allegro_status = $info->publication->status;
            $el = $info;
            $el->publication->status = "ACTIVE";
            $offer_json = json_encode($el);
            $res2 = $this->allegro_query2('PUT', 'sale/offers/' . $info->id, $offer_json);
            //$val_obj = json_decode($res2);
           // if(!empty($val_obj->validation->errors[0]->message)){
//                 echo  $val_obj->validation->errors[0]->message   ;
             //   setAlert('warning',$val_obj->validation->errors[0]->message);

      
//echo $res2.'<hr>';
//die();
            if (!empty($info->id)) {
                //publikowanie oferty
                $args_a = array(
                    'publication' => array(
                        'action' => "ACTIVATE"
                    ),
                    'offerCriteria' => 
                        [
                        array(
                            'offers' => [array(
                            'id' => $info->id
                                )],
                            "type" => "CONTAINS_OFFERS"
                        )
                    ]
                );
                $uid = $this->gen_uuid();
                $data_string = json_encode($args_a);
                $res3 = $this->allegro_query2('PUT', 'sale/offer-publication-commands/' . $uid, $data_string);
//                echo $res3.'<hr>';
                                usleep(100000);
                $uid2 = json_decode($res3)->id;
                $res4 = $this->allegro_query2('GET', 'sale/offer-publication-commands/' . $uid2);
//                echo $res4.'<hr>';
            }
            if(empty($offer_array['id'])){
                $this->db->insert('duo_shop_allegro', array(
                    'product_id' => $product_id,
                    'allegro_id' => $allegro_id,
                    'allegro_status' => $allegro_status
                ));
            }
        }
        $this->add_attributes2($product, $info);
        
    }
     public function edit_auction_from_product($product_id){
        $product = new ProductModel($product_id);
        if(empty($product->id)){ return false;}
        $auction_id = $this->get_allegro_auction_id($product->id);
        $auction_json = $this->get_offer($auction_id);
        $auction = json_decode($auction_json, true);
        if(empty($auction["id"])){ return false;}
        $attributes = json_decode($product->type);
        $offer = $auction;
        $offer_array = [];
        foreach ($attributes as $key => $attrib) {
            $at = explode('_', $attrib);
            $k = $at[0];
            if(!empty($at[1])){
                $va['valuesIds']= [$attrib];
            } else {
                $va = [];
            }
            $offer_array['parameters'][$key]['id'] = $k;
            $offer_array['parameters'][$key]['rangeValue'] = null;
            $offer_array['parameters'][$key]['values'] = empty($va['values']) ? [] : $va['values'];
            $offer_array['parameters'][$key]['valuesIds'] = empty($va['valuesIds']) ? [] : $va['valuesIds'];
        }
        $tproduct = $product->getTranslation('pl');
        $offer['name'] = $tproduct->name;
        $offer['parameters'] = $offer_array['parameters'];
//        $photos = $product->findAllPhotos();
//        if(!empty($photos)){
//            foreach ($photos as $photo) {
//                $res0 = $this->upload_photo($photo);
//            }
//        }
//        $uphotos = $this->get_uploaded_photos($product_id);
//        if(!empty($uphotos)){
//        foreach($uphotos as $up){
//         $offer['images'][]['url'] = $up->location;   
//        }
//        }
        
        $a_sections = array();
        if(!empty($offer['images'][0]['url'])){
            $a_sections[] = array(
              'items' => array(
                  array(
                      'type' => 'TEXT',
                      'content' => $tproduct->body
                  ),
                  array(
                      'type' => 'IMAGE',
                      'url' => $offer['images'][0]['url']
                  )
              )  
            );
        } else {
            $a_sections[] = array(
              'items' => array(
                  array(
                      'type' => 'TEXT',
                      'content' => $tproduct->body
                  )
              )  
            );
        }
     

        $offer['description']['sections'] = $a_sections;
        
        
        $offer['sellingMode'] = [
            'format' => "BUY_NOW",
            "price" => [
                'amount' => $product->price,
                'currency' => 'PLN'
            ],
        ];
        $offer['stock'] = [
            'available' => $product->quantity * 1,
            'unit' => 'UNIT'
                ];
        
       
        $offer_json = json_encode($offer);

        $res2 = $this->allegro_query2('PUT', 'sale/offers/' . $auction['id'], $offer_json);
        var_dump(json_decode($res2)); 
        
    }
    
    
    
    public function add_offer($offer_array, $product_id = 0) {
        $offer = array();
        foreach ($offer_array['parameters'] as $k => $va) {
            $offer_array['parameters'][$k]['rangeValue'] = null;
            $offer_array['parameters'][$k]['values'] = empty($va['values']) ? [] : $va['values'];
            $offer_array['parameters'][$k]['valuesIds'] = empty($va['valuesIds']) ? [] : $va['valuesIds'];
        }
        $offer['name'] = $offer_array['name'];
        $offer['category'] = $offer_array['category'];
        $offer['parameters'] = $offer_array['parameters'];
        $a_sections = array();
        if(!empty($offer_array['images'][0]['url'])){
            $a_sections[] = array(
              'items' => array(
                  array(
                      'type' => 'TEXT',
                      'content' => $offer_array['description']['sections'][0]['items'][0]['content']
                  ),
                  array(
                      'type' => 'IMAGE',
                      'url' => $offer_array['images'][0]['url']
                  )
              )  
            );
        } else {
            $a_sections[] = $offer_array['description']['sections'][0];
        }
     

        $offer['description']['sections'] = $a_sections;
        
        $offer['images'] = $offer_array['images'];
        $offer['sellingMode'] = $offer_array['sellingMode'];
        $offer_array['stock']['available'] = $offer_array['stock']['available'] * 1;
        $offer['stock'] = $offer_array['stock'];
        $offer_array['publication']['startingAt'] = null;
        $offer_array['publication']['endingAt'] = null;
        $offer_array['publication']['status'] = "INACTIVE";
        $offer['publication'] = $offer_array['publication'];
        $offer['delivery'] = $offer_array['delivery'];
        $offer['payments'] = $offer_array['payments'];
        $offer['afterSalesServices'] = $offer_array['afterSalesServices'];
        $offer['location'] = $offer_array['location'];
        $offer['location']['postCode'] = get_option('admin_modules_allegro_zipcode');
        
        $offer_json = json_encode($offer);
        if(empty($offer_array['id'])){
            $res = $this->allegro_query2('POST', 'sale/offers', $offer_json);
            $info = json_decode($res);
        } else {
            $res = $this->get_offer($offer_array['id']);
            $info = json_decode($res);
            foreach($offer as $k=>$vv){
                $info->$k = json_decode(json_encode($vv));
            }
        }
        
        
        if (!empty($info->id)) {
            $allegro_id = $info->id;
            $allegro_status = $info->publication->status;
            $el = $info;
            $el->publication->status = "ACTIVE";
            $offer_json = json_encode($el);
            $res2 = $this->allegro_query2('PUT', 'sale/offers/' . $info->id, $offer_json);
            $val_obj = json_decode($res2);
            if(!empty($val_obj->validation->errors[0]->message)){
//                 echo  $val_obj->validation->errors[0]->message   ;
                setAlert('warning',$val_obj->validation->errors[0]->message);
            }
      
//echo $res2;
//die();
            if (!empty($info->id)) {
                //publikowanie oferty
                $args_a = array(
                    'publication' => array(
                        'action' => "ACTIVATE"
                    ),
                    'offerCriteria' => 
                        [
                        array(
                            'offers' => [array(
                            'id' => $info->id
                                )],
                            "type" => "CONTAINS_OFFERS"
                        )
                    ]
                );
                $uid = $this->gen_uuid();
                $data_string = json_encode($args_a);
                $res3 = $this->allegro_query2('PUT', 'sale/offer-publication-commands/' . $uid, $data_string);
                                usleep(1000000);
                $uid2 = json_decode($res3)->id;
                $res4 = $this->allegro_query2('GET', 'sale/offer-publication-commands/' . $uid2);
            }
            if(empty($offer_array['id'])){
                $this->db->insert('duo_shop_allegro', array(
                    'product_id' => $product_id,
                    'allegro_id' => $allegro_id,
                    'allegro_status' => $allegro_status
                ));
            }
        }
        return $info;
    }
public function end_offer($id){
        $args_a = array(
            'publication' => array(
                'action' => "END"
            ),
            'offerCriteria' =>
            [
                array(
                    'offers' => [array(
                    'id' => $id
                        )],
                    "type" => "CONTAINS_OFFERS"
                )
            ]
        );
        $uid = $this->gen_uuid();
        $data_string = json_encode($args_a);
        $res3 = $this->allegro_query2('PUT', 'sale/offer-publication-commands/' . $uid, $data_string);
        usleep(1000000);
        $uid2 = json_decode($res3)->id;
        $res4 = $this->allegro_query2('GET', 'sale/offer-publication-commands/' . $uid2);
        $this->db->where('allegro_id', $id);
        $this->db->update('duo_shop_allegro', array(
            'allegro_status' => 'ENDED'
        ));
    }
    
    public function renew_offer($id){
        $args_a = array(
            'publication' => array(
                'action' => "ACTIVATE"
            ),
            'offerCriteria' =>
            [
                array(
                    'offers' => [array(
                    'id' => $id
                        )],
                    "type" => "CONTAINS_OFFERS"
                )
            ]
        );
        $uid = $this->gen_uuid();
        $data_string = json_encode($args_a);
        $res3 = $this->allegro_query2('PUT', 'sale/offer-publication-commands/' . $uid, $data_string);
        usleep(1000000);
        $uid2 = json_decode($res3)->id;
        $res4 = $this->allegro_query2('GET', 'sale/offer-publication-commands/' . $uid2);
        $this->db->where('allegro_id', $id);
        $this->db->update('duo_shop_allegro', array(
            'allegro_status' => 'ACTIVE'
        ));
        return $res4;
    }
    public function get_offer($id) { 
        $res = $this->allegro_query2('GET', 'sale/offers/' . $id, '');
        return $res;
    }
    
    public function get_offers_list(){
        $res = $this->allegro_query2('GET', 'offers/listing?seller.id='. $this->seller_id);
        return $res;
    }
    public function get_offers_list_limit($limit, $page){
        $res = $this->allegro_query2('GET', 'offers/listing?seller.id='. $this->seller_id.'&sort=startTime&limit='.$limit.'&offset='.($page*$limit));
        return $res;
    }
    public function get_shipping_rates() {
        $res = $this->allegro_query2('GET', 'sale/shipping-rates?seller.id=' . $this->seller_id);
        return $res;
    }

    public function get_own_auctions($offset = 0, $status = 'ACTIVE'){
        $res = $this->allegro_query2('GET', 'sale/offers?selled.id=' . $this->seller_id . '&limit=1000&offset='.$offset.'&publication.status='.$status);
        return $res;
    }
    public function get_delivery_methods(){
        $res = $this->allegro_query2('GET', 'sale/delivery-methods');
        return $res;
    }
    public function get_shipping_rates_details($id){
        $res = $this->allegro_query2('GET', 'sale/shipping-rates/'.$id);
        return $res;
    }
    public function get_impliedWarranty() {
        $res = $this->allegro_query2('GET', 'after-sales-service-conditions/implied-warranties?sellerId=' . $this->seller_id.'&limit=10');
        return $res;
    }

    public function get_returnPolicy() {
        $res = $this->allegro_query2('GET', 'after-sales-service-conditions/return-policies?sellerId=' . $this->seller_id.'&limit=10');
        return $res;
    }
    
   public function  get_allegro_orders(){
//        $res = $this->allegro_query('GET', 'order/checkout-forms?status=READY_FOR_PROCESSING');
         $res = $this->allegro_query('GET', 'order/checkout-forms');

        return $res;
    }
   public function  get_allegro_order_events(){
//        $res = $this->allegro_query('GET', 'order/checkout-forms?status=READY_FOR_PROCESSING');
         $res = $this->allegro_query('GET', 'order/events?type=READY_FOR_PROCESSING');

        return $res;
    }
    public function get_allegro_order($id){
        $res = $this->allegro_query('GET', 'order/checkout-forms/'.$id);
        return $res;
    }
    public function get_multivariants_auction_list($limit = 50, $page = 0){
        $res = $this->allegro_query('GET', 'sale/offer-variants?limit='.$limit.'&offset='.($page*$limit) . '&user.id=' . $this->seller_id);
        return $res;
    }
    
    public function get_multivariants_auction($setid){
        $res = $this->allegro_query('GET', 'sale/offer-variants/'.$setid);
        return $res;
    }

    public function upload_photo($photo_obj) {
        $photo_id = $photo_obj->id;
        $product_id = $photo_obj->product_id;

        $this->db->delete('duo_shop_allegro_photos', array('expiresat <' => date('Y-m-d H:i:s')));
        $q1 = $this->db->get_where('duo_shop_allegro_photos', array(
            'product_id' => $product_id,
            'photo_id' => $photo_id
        ));
        if ($q1->num_rows() > 0) {
            return 0;
        }

        $name = $photo_obj->name;
        $path = './uploads/products/' . $product_id . '/' . $photo_id . '/' . $name;
        $pathinfo = pathinfo($path);
        $ext = $pathinfo['extension'];
        $post = file_get_contents($path);
        if ($ext == 'jpg') {
            $ext = 'jpeg';
        }
        $headers = array(
            "accept: application/vnd.allegro.public.v1+json",
            "Authorization: Bearer {$this->token()}",
            'content-type: image/' . $ext,
            'accept-language: pl-PL'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->upload_link . 'sale/images');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code == 201) {
            $result = json_decode($result);
//            var_dump($result);
            $expiresat = $result->expiresAt;
            $location = $result->location;
            $res_insert = $this->db->insert('duo_shop_allegro_photos', array(
                'product_id' => $product_id,
                'photo_id' => $photo_id,
                'expiresat' => $expiresat,
                'location' => $location
            ));
        }
        curl_close($ch);
    }

    public function get_uploaded_photos($product_id) {
//        $q = $this->db->get_where('duo_shop_allegro_photos', array('product_id' => $product_id));
        $this->db->select('duo_shop_allegro_photos.*');
        $this->db->join('duo_product_photos', 'duo_product_photos.id = duo_shop_allegro_photos.photo_id');
        $this->db->where('duo_shop_allegro_photos.product_id', $product_id);      
        $this->db->order_by('duo_product_photos.order ASC');
        $q = $this->db->get('duo_shop_allegro_photos');
        return $q->result();
    }

    public function test() {
        return true;
    }

    function allegro_query($type, $point, $data_string = '', $link = 'link', $additional_headers = array()) {
        $ch = curl_init();
        if ($link == 'upload_link') {
            $headers = array(
                "accept: application/vnd.allegro.beta.v1+json",
                "Authorization: Bearer {$this->token()}",
                'content-type: "image/png","image/jpg","image/jpeg","image/gif"',
                'accept-language: pl-PL'
            );
        } else {
            $headers = array(
                "content-type: application/vnd.allegro.beta.v1+json",
                "Accept: application/vnd.allegro.beta.v1+json",
                "Authorization: Bearer {$this->token()}"
            );
        }
        if ($type == 'POST' || $type == 'PUT') {
            $headers[] = "Content-Length: " . strlen($data_string);
        }
        $headers = array_merge($headers, $additional_headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_URL, $this->$link . $point);
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
    
    //naglowki produkcyjne, nie beta
    function allegro_query2($type, $point, $data_string = '', $link = 'link', $additional_headers = array()) {
        $ch = curl_init();
        if ($link == 'upload_link') {
            $headers = array(
                "accept: application/vnd.allegro.public.v1+json",
                "Authorization: Bearer {$this->token()}",
                'content-type: "image/png","image/jpg","image/jpeg","image/gif"',
                'accept-language: pl-PL'
            );
        } else {
            $headers = array(
                "content-type: application/vnd.allegro.public.v1+json",
                "Accept: application/vnd.allegro.public.v1+json",
                "Authorization: Bearer {$this->token()}"
            );
        }
        if ($type == 'POST' || $type == 'PUT') {
            $headers[] = "Content-Length: " . strlen($data_string);
        }
        $headers = array_merge($headers, $additional_headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        curl_setopt($ch, CURLOPT_URL, $this->$link . $point);
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
    
   public function check_allegro_product($id){
        $this->db->where('allegro_id', $id);
        $query = $this->db->get('duo_shop_allegro');
        if($query->num_rows() > 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    public function get_allegro_category($cat_id){
        $res = $this->allegro_query2("GET", "sale/categories/" . $cat_id);
        return $res;
    }
    
    public function get_duo_auctions_list(){
        $this->db->select('allegro_id');
        $this->db->distinct();
        $query = $this->db->get('duo_shop_allegro');
        $array = $query->result();
        $res = array();
        foreach ($array as $a) {
            $res[] = $a->allegro_id;
        }
        return $res;
    }
    
    public function get_product_by_allegro_auction_id($allegro_id){
        $this->db->where('allegro_id', $allegro_id);
        $this->db->order_by('created_at','desc');
        $query = $this->db->get('duo_shop_allegro');
        $res = $query->result()[0]->product_id;
        $this->load->model('ProductModel');
        $product = new ProductModel($res);
        return $product;
    }
    
    public function update_amount($auction, $new_amount){
        $auction_json = $this->get_offer($auction);
        $auction_body = json_decode($auction_json);
        $status = $auction_body->publication->status;
        if($status == 'ACTIVE' && $new_amount == 0){
            $this->end_offer($auction);
            return true;
        }
        if($status != 'ACTIVE' && $new_amount > 0){
            $res = $this->renew_offer($auction);
        }
        $auction_body->stock->available = $new_amount;
        $offer = json_encode($auction_body);
        $res = $this->allegro_query2('PUT', 'sale/offers/'.$auction , $offer);

        return $res;
    }
    
       public function get_allegro_auction_id($product_id){
        $this->db->where('product_id', $product_id);
        $query = $this->db->get('duo_shop_allegro');
        if($query->num_rows() > 0){
            $res = $query->result()[0];
            return $res->allegro_id;
        } else {
            return -1;
        }
    }
    
//    public function synchronise_amount($auction, $difference = 0){
//            $allegro_amount = $auction->stock->available;
//            $product = $this->get_product_by_allegro_auction_id($auction->id);
//            $this->load->model("OrderModel");
//            $amount_sold_since_date = $this->OrderModel->find_amount_sold_since_date($product);
//            if(($amount_sold_since_date > 0) || ($difference != 0)){
//                $new_amount = $allegro_amount - $amount_sold_since_date + $difference;
//                
//                $this->update_amount($auction, $new_amount);
//                $product->quantity = $new_amount;
//                $product->amount_updated_at = (new DateTime())->format('Y-m-d H:i:s');
//                $product->update_product();
//                return $new_amount;
//            }
//            return -1;
//    }
    
    public function insert_allegro_delivery($allegro_id, $delivery_id){ 
        $this->db->insert('duo_shop_allegro_deliveries', array(
            "allegro_id" => $allegro_id,
            "delivery_id" => $delivery_id
        ));
    }
    
    public function clear_all_deliveries(){
        $this->db->where('id > 0');
        $this->db->delete('duo_shop_allegro_deliveries');
    }
    
    public function get_all_deliveries(){
        $q = $this->db->get('duo_shop_allegro_deliveries');
        $res =  $q->result();
        $arr = array();
        foreach ($res as $r){
            $arr[$r->allegro_id] = $r->delivery_id;
        }
        return $arr;
    }
    
    public function get_delivery_by_allegro_id($allegro_id){
        $this->db->where('allegro_id', $allegro_id);
        $q = $this->db->get('duo_shop_allegro_deliveries');
        if($q->num_rows() > 0){
            return $q->result()[0];
        } else {
            return null;
        }
    }
    
    public function get_product_allegro_attributes($product_id){
        $this->db->select('allegro_id');
        $this->db->join('duo_shop_attributes_relations', 'duo_shop_attributes_relations.attribute_id = duo_shop_attributes.id');
        $this->db->where('duo_shop_attributes_relations.product_id ='.$product_id);
        $q = $this->db->get("duo_shop_attributes");
        
        if($q->num_rows() > 0){
            $res = $q->result();
            $tmp = array();
            foreach($res as $r){
                $tmp[] = $r->allegro_id;
            }
            return $tmp;
        } else {
            return array();
        }
    }
    
    public function find_photo_by_id($photo_id){
        $this->db->where('photo_id', $photo_id);
        $q = $this->db->get('duo_shop_allegro_photos');
        if($q->num_rows() > 0){
            return $q->result()[0];
        } else {
            return null;
        }
    }
    
     public function download_orders(){
         $this->load->model('OtomotoModel');
        $regex = "/([\w\s.ąćęłńóśźżŁÓŃĆŻŹĄĘŚ]+)\s{1}([0-9]+[A-Za-z]?){1}\s?[\/mM]?[.]?\s?([0-9]+)?/";
        $this->load->model("OrderModel");
        $orders = json_decode($this->get_allegro_order_events());
        foreach ($orders->events as $event) {
            $order = json_decode($this->get_allegro_order($event->order->checkoutForm->id));
            if(!$this->OrderModel->check_if_order_in_db($order->id)){
            $data = array();
            $data['allegro_id'] = $order->id;
            $data['email'] = $order->buyer->email;
            $data['first_name'] = $order->delivery->address->firstName;
            $data['last_name'] = $order->delivery->address->lastName;
            $data['city'] = $order->delivery->address->city;
            $data['zip_code'] = $order->delivery->address->zipCode;
            $street = $order->delivery->address->street;
            $street_table = array();
            preg_match($regex, $street, $street_table);
            if(!empty($street_table[2])){
                $data['street'] = $street_table[1];
                $data['building_number'] = $street_table[2];
                $data["flat_number"] = (!empty($street_table[3])) ? $street_table[3] : null;
            } else {
                $data['street'] = $street;
                $data['building_number'] = 0;
            }
            $data['delivery'] = $this->get_delivery_by_allegro_id($order->delivery->method->id)->delivery_id;
            $data['phone'] = $order->delivery->address->phoneNumber;
            $data['comment'] = (!empty($order->messageToSeller)) ? 'dodane z allegro, '.$order->messageToSeller :'dodane z allegro';
            $data['price'] = $order->summary->totalToPay->amount;
            $data['wieght'] = 1;
            if($order->payment->type == 'CASH_ON_DELIVERY'){
                $data['method'] = 'upon_receipt';
            } else {
                $data['method'] = 'allegro';
            }
            $data['locker'] = null;
            if(!empty($order->delivery->pickupPoint)){
                $pickup_name = $order->delivery->pickupPoint->name;
                if(strpos($pickup_name, 'Paczkomat') !== FALSE){
                    $data['locker'] = trim(explode('Paczkomat', $pickup_name)[1]);
                } else if(strpos($pickup_name, 'PACZKA w RUCHu:') !== FALSE){
                    $data['locker'] = trim(explode(':', $pickup_name)[1]);
                } else {
                    $data['locker'] = $pickup_name;
                }
            }
            $flag = false;
            $order_id = $this->OrderModel->add_order_from_allegro($data);
            foreach($order->lineItems as $item){
                if(!$this->check_allegro_product($item->offer->id)){
                    $this->add_from_allegro_no_redirect($item->offer->id);
                    $flag = true;
                }
                $product = $this->get_product_by_allegro_auction_id($item->offer->id);
                $this->OrderModel->add_item_to_order($product->id, $item->quantity, $order_id, $flag);
                
                $this->OtomotoModel->deactivate_advert_from_product($product->id);
                $flag = false;
            }
            
            }
        }
        
    }
    
        private function add_attributes($product, $auction){
        $allegro_attributes_json = $this->get_category_fields($auction->category->id);
        $allegro_attributes= json_decode($allegro_attributes_json);
        $this->load->model('ProductAttributesModel');
        $units = array();
        foreach($allegro_attributes->parameters as $param_group){
            if(!$this->ProductAttributesModel->check_if_allegro_group_exist($param_group->id)){
            $attribute_group = array(
                'translations' => array(
                    array(
                    'lang' => 'pl',
                    'name' => $param_group->name,
                    'description' => ''
                        )
                ),
                'allegro' => $param_group->id
            );
            $this->ProductAttributesModel->add_group($attribute_group);
            }
            $group_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($param_group->id);
            switch ($param_group->type) {
                case 'dictionary':
                    foreach ($param_group->dictionary as $param) {
                        if(!$this->ProductAttributesModel->check_if_allegro_attr_exist($param->id)){
                            $duo_attr_id = $this->ProductAttributesModel->attribute_add(0, $group_id , $param->id);
                            $args = array('pl' => array('name'=> $param->value, 'description'=>''));
                            $this->ProductAttributesModel->attribute_update($duo_attr_id, 0, $args, $group_id);
                        }
                    }
                    break;
                case 'float':
                case 'integer':
                    $units[$param_group->id] = array ('name' => $param_group->unit );
                    break;
                default:
                    break;
            }            
        }
        foreach ($auction->parameters as $parameter) {
            if(!empty($parameter->valuesIds)){
                $attr_id = $this->ProductAttributesModel->find_attr_by_allegro_id($parameter->valuesIds[0]);
                if(!empty($attr_id)){
                    $product->attribute_add_to_product($attr_id, $product->id ,null);
                }
            }
            if(!empty($parameter->values)){
                $attr_val = $parameter->values[0];
                $attr_group_id = $parameter->id;
                $group_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($attr_group_id);
                $unit_name = '';
                if(!empty($units[$attr_group_id])){
                $unit_name = $units[$attr_group_id]['name'];}
                $duo_attr_name = $attr_val.' '.$unit_name;
                $duo_attr_allegro_id = $attr_group_id.'_'.$attr_val;
                if(!$this->ProductAttributesModel->check_if_allegro_attr_exist($duo_attr_allegro_id)){
                    $duo_attr_id = $this->ProductAttributesModel->attribute_add(0, $group_id , $duo_attr_allegro_id);
                    $args = array('pl' => array('name'=> $duo_attr_name, 'description'=>''));
                    $this->ProductAttributesModel->attribute_update($duo_attr_id, 0, $args, $group_id);
                }
                $attr_id = $this->ProductAttributesModel->find_attr_by_allegro_id($duo_attr_allegro_id);
                if(!empty($attr_id)){
                    $product->attribute_add_to_product($attr_id, $product->id ,null);
                }
            }
            $attr_id = null;
        }
    }
    
     private function add_attributes2($product, $auction){
        $allegro_attributes_json = $this->get_category_fields($product->allegro_category_id);
        $allegro_attributes= json_decode($allegro_attributes_json);
        $this->load->model('ProductAttributesModel');
        $units = array();
        foreach($allegro_attributes->parameters as $param_group){
            if(!$this->ProductAttributesModel->check_if_allegro_group_exist($param_group->id)){
            $attribute_group = array(
                'translations' => array(
                    array(
                    'lang' => 'pl',
                    'name' => $param_group->name,
                    'description' => ''
                        )
                ),
                'allegro' => $param_group->id
            );
            $this->ProductAttributesModel->add_group($attribute_group);
            }
            $group_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($param_group->id);
            switch ($param_group->type) {
                case 'dictionary':
                    foreach ($param_group->dictionary as $param) {
                        if(!$this->ProductAttributesModel->check_if_allegro_attr_exist($param->id)){
                            $duo_attr_id = $this->ProductAttributesModel->attribute_add(0, $group_id , $param->id);
                            $args = array('pl' => array('name'=> $param->value, 'description'=>''));
                            $this->ProductAttributesModel->attribute_update($duo_attr_id, 0, $args, $group_id);
                        }
                    }
                    break;
                case 'float':
                case 'integer':
                    $units[$param_group->id] = array ('name' => $param_group->unit );
                    break;
                default:
                    break;
            }            
        }
        foreach ($auction->parameters as $parameter) {
            if(!empty($parameter->valuesIds)){
                $attr_id = $this->ProductAttributesModel->find_attr_by_allegro_id($parameter->valuesIds[0]);
                if(!empty($attr_id)){
                    $product->attribute_add_to_product($attr_id, $product->id ,null);
                }
            }
            if(!empty($parameter->values)){
                $attr_val = $parameter->values[0];
                $attr_group_id = $parameter->id;
                $group_id = $this->ProductAttributesModel->find_group_by_allegro_group_id($attr_group_id);
                $unit_name = '';
                if(!empty($units[$attr_group_id])){
                $unit_name = $units[$attr_group_id]['name'];}
                $duo_attr_name = $attr_val.' '.$unit_name;
                $duo_attr_allegro_id = $attr_group_id.'_'.$attr_val;
                if(!$this->ProductAttributesModel->check_if_allegro_attr_exist($duo_attr_allegro_id)){
                    $duo_attr_id = $this->ProductAttributesModel->attribute_add(0, $group_id , $duo_attr_allegro_id);
                    $args = array('pl' => array('name'=> $duo_attr_name, 'description'=>''));
                    $this->ProductAttributesModel->attribute_update($duo_attr_id, 0, $args, $group_id);
                }
                $attr_id = $this->ProductAttributesModel->find_attr_by_allegro_id($duo_attr_allegro_id);
                if(!empty($attr_id)){
                    $product->attribute_add_to_product($attr_id, $product->id ,null);
                }
            }
            $attr_id = null;
        }
    }
    public function add_from_allegro_no_redirect($allegro_id){
        $auction_json = $this->get_offer($allegro_id);
        $auction = json_decode($auction_json);
        $product = new ProductModel();
        $allegro_category_id = $auction->category->id;
        
        if(empty($allegro_category_id)){
            return -1;
        }
        $this->load->model("OfferCategoryModel");
        $product->offer_category_id = 0;
        $product->quantity = $auction->stock->available;
        $product->price = $auction->sellingMode->price->amount;
        $product->active = 0;
        $product->status = 1;
        if (!empty($auction->ean)) {
            $product->code = $auction->ean;
        } else {
            $product->code = 'brak';
        }
        $product->insert_product();
        $this->load->model("ProductTranslationModel");
        $translation = new ProductTranslationModel();
        $translation->product_id = $product->id;
        $translation->lang = 'pl';
        $translation->name = $auction->name;
        $translation->body = "";
        $this->load->model("ProductPhotoModel");
        foreach ($auction->description->sections as $sections) {
            foreach ($sections as $sec) {
                foreach($sec as $item)
                    if($item->type == 'TEXT'){
                    $translation->body .=  $item->content ."<br>";
                    }
            }   
        }
        foreach ($auction->images as $item) {
            $photo = new ProductPhotoModel();
            $photo->product_id = $product->id;
            $photo->insert();
            $photo->save_from_remote_url($item->url);
        }
        $translation->insert();
        $res = $this->db->insert('duo_shop_allegro', [
                    'product_id' => $product->id,
                    'allegro_id' => $allegro_id,
                    'created_at' => (new DateTime())->format('Y-m-d H:i:s')
        ]);

        $this->add_attributes($product, $auction);
    }
    
    public function removeDeleted(){
         $own_acution_json = $this->get_own_auctions(0, 'INACTIVE,ENDED');
        $own_acution = json_decode($own_acution_json);
        $total_count = $own_acution->count;
        $count = 0;
        $offers = array();
        do{
           if(!empty($own_acution->offers)){
               foreach($own_acution->offers as $single){
                   if($this->check_allegro_product($single->id)){
                      // $offers[] = $single;
                       $prod = $this->get_product_by_allegro_auction_id($single->id);
                       $prod->active = 1;
                       $prod->quantity = 0;
                       $prod->update_product();
                   }
               }
           }
            
           $count+=1000;
           $own_acution_json =  $this->get_own_auctions($count, 'INACTIVE,ENDED');
           $own_acution = json_decode($own_acution_json);
        }while($count < $total_count);
    }
}
