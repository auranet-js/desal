<?php
// SOURCE: /home6/desal/public_html/application/controllers/duocms/Otomoto.php
// FETCHED: 2026-05-27 ~10:16 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2021-08-11 05:56:33, size 3999 B

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Otomoto extends Backend_Controller {

    public $langs;
    public $codes;

    function __construct() {
        parent::__construct();
       $this->load->vars(['activePage' => 'otomoto']);
    }

    public function index(){
        $this->load->model("OtomotoModel");
        $this->load->model('ProductModel');
        $p = new ProductModel(1496);

//        var_dump($this->OtomotoModel->add_advert_from_product(1496));
//        $pp = $p->findAllPhotos();
//        $photos = [];
//        $i = 1;
//        foreach($pp as $g){
//            $photos[$i++] = $g->getUrl();
//        }
//        var_dump($photos); echo '<hr>';
//        var_dump($this->OtomotoModel->create_image_collection($photos)); //828771920
    }


   public function settings_region(){
       $this->load->model("OtomotoModel");
       $regions_data = $this->OtomotoModel->get_regions()->results;
       foreach($regions_data as $region){
           if($region->name->pl == 'Małopolskie'){
               set_option('admin_modules_otomoto_region_id', $region->id);
               break;
           }
       }
       echo 'wykonane';
   }

     public function settings_city(){
       $this->load->model("OtomotoModel");
       $city_data = $this->OtomotoModel->search_city('Wojnicz');  // Wojnicz = 19453
       var_dump($city_data); die();
//       echo 'wykonane';
   }

   public function settings_district(){
       $this->load->model("OtomotoModel");
       $city_data = $this->OtomotoModel->get_district_for_city(19453);
       var_dump($city_data); die();
   }

   public function category_test(){
       $this->load->model("OtomotoModel");
       $category = $this->OtomotoModel->get_category_data();
      foreach($category->children as $c){
          $z = $this->OtomotoModel->get_category_data($c);
          unset($z->parameters);
          var_dump($z);
          echo '<hr>';
      }
//       foreach($category->parameters as $p){
//           echo $p->labels->pl.'<br>';
//           echo $p->code.'<br>';
//           echo 'wymagane ' . ($p->required ? 'TAK' : 'NIE');
//           switch($p->type){
//               case 'input':
//                   echo '--input';
//                   break;
//               case 'select':
//                   echo '--select<br>';
//                   foreach($p->options as $z => $o){
//                       echo $z .'  '.$o->pl.'<br>';
//                   }
//                   break;
//               case 'price':
//                   var_dump($p);
//                   echo '<br>';
//                   break;
//           }
//
//           echo '<hr>';
//       }

   }

   public function category_test2(){
        $this->load->model("OtomotoModel");
       $category = $this->OtomotoModel->get_categories();
       foreach($category->results as $cat){
           unset($cat->parameters);
           var_dump($cat); echo '<hr>';
       }
   }

   public function category_test3(){
        $this->load->model("OtomotoModel");
       $category = $this->OtomotoModel->get_category_data();

       foreach($category->parameters as $cat){
//           unset($cat->parameters);
           var_dump($cat); echo '<hr>';
       }
       echo '<hr>';
       unset($category->parameters); var_dump($category);
   }
//   public function allegro(){
//       $this->load->model("AllegroModel");
//       $allegro = new AllegroModel();
//       var_dump($allegro->get_shipping_rates());
//   }

   public function test_wystawienia(){
      // 11589

       $this->load->model('OtomotoModel');
       var_dump($this->OtomotoModel->add_advert_from_product(11591) );
       //var_dump($this->OtomotoModel->activate_advert(1202));
   }

   public function test_deaktywacji(){
       $this->load->model('OtomotoModel');
       var_dump($this->OtomotoModel->deactivate_advert_from_product(11620) );
   }
}
