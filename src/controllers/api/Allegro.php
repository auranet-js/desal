<?php
// SOURCE: /home6/desal/public_html/application/controllers/api/Allegro.php
// FETCHED: 2026-05-27 ~10:16 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2021-08-11 05:56:22, size 2306 B
// UWAGA: tu jest OAuth callback Allegro — KLUCZOWE dla diagnozy odświeżania tokenu

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . 'libraries/REST_Controller.php';

/**
 * This is an example of a few basic user interaction methods you could use
 * all done with a hardcoded array
 *
 * @package         CodeIgniter
 * @subpackage      Rest Server
 * @category        Controller
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 */
class Allegro extends REST_Controller {
    private $allegro;

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('AllegroModel');
        $this->allegro = new AllegroModel();
        $this->load->helper('functions');
    }

    public function code_get(){
        $code = $this->get('code');
        $product_id = $this->get('product_id');
        $offer_id = $this->get('offer_id');
        $listing = $this->get('listing');
        if(empty($listing)){
        $res = (array)json_decode($this->allegro->get_token($code,$product_id));
        } else {
        $res = (array)json_decode($this->allegro->get_token($code,null,1));
        }
        if(!empty($res['access_token'])){
            $res['expired'] = date('U') + $res['expires_in'];
            $this->session->set_userdata('allegro',$res);
            set_option('admin_modules_allegro_accesstoken', $res['access_token']);
            set_option('admin_modules_allegro_refreshtoken',$res['refresh_token']);
            set_option('admin_modules_allegro_token_expiration', $res['expired']);

            setAlert("success", "Autoryzowano allegro");
            if(!empty($listing)){
                redirect(site_url('duocms/allegro/allegro_list'));
                die();
            }
            if(empty($offer_id)){
                redirect(site_url('duocms/allegro/add_auction/'.$product_id));
            } else {
                redirect(site_url('duocms/allegro/show/'.$offer_id));
            }
        } else {
            echo 'Błąd!';
            echo '<br>'.json_encode($res);
        }
        die();
    }
}
