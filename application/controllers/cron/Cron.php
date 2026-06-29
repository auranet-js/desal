<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Cron extends MY_Controller {

    public $langs;
    public $newsletter;

    function __construct() {
        parent::__construct();
        $this->load->helper('form');
        $this->load->model('NewsletterModel');
        $this->load->model('ConfigurationModel');
        $this->newsletter = new NewsletterModel();
        $this->configurationModel = new ConfigurationModel();
        $langs = get_languages();
        foreach ($langs as $l) {
            $this->languages[] = $l->short;
        }
    }

    public function index() {
        $limit = get_option('email_limit');
        $email_sended = $this->newsletter->send_emails($limit);
        echo '<p>Wysłano maile w ilości ' . $email_sended . '</p>';
        echo 'Prace Crona';
    }

    public function index2() {
        $this->load->model('AllegroModel');
        $this->AllegroModel->refresh_token();
        echo "odswiezono token";
    }

    public function index3() {
        $this->load->model('AllegroModel');
        $this->AllegroModel->download_orders();
        echo "pobrano zamowienia";
//        $this->allegro->removeDeleted();
//        echo "Zdezaktywowano niekatywne aukcje";
    }

    public function car_timetable() {
        $this->db->limit(5, 0);
        $this->db->order_by('id', 'desc');
        $q = $this->db->get('duo_allegro_timetable');
        if ($q->num_rows() > 0) {
            $this->load->model('AllegroModel');
            $this->load->model('OtomotoModel');
            $this->load->model('ProductModel');
            $this->load->model('CarModel');
            foreach ($q->result() as $r) {
                $product_id = $this->CarModel->product_from_sketch($r->product_id);
                if ($product_id == 0) {
                    $this->db->where('id', $r->id);
                    $this->db->delete('duo_allegro_timetable');
                    continue;
                }
                $product = new ProductModel($product_id);
                echo $product->id . '<br>';
                if ($product->status) {
                    if ($this->AllegroModel->get_allegro_auction_id($product_id) === -1) {
                        $this->AllegroModel->add_auction_from_product($product->id);
                    } else {
                        $this->load->model('LogModel');
                        $this->LogModel->add_log(-2, $product_id, 'produkt ' . $product_id . ' posiada juz aukcję ');
                    }
                }
                if ($product->status2) {
                    if (empty($product->otomoto_id)) {
                        $g = $this->OtomotoModel->add_advert_from_product($product->id);
                        echo json_encode($g);
                        echo '<p>Wystaiono ogłoszenie</p>';
                    } else {
                        $this->load->model('LogModel');
                        $this->LogModel->add_log(-3, $product_id, 'produkt ' . $product_id . ' posiada juz ogłoszenie ');
                    }
                }
                $this->db->where('id', $r->id);
                $this->db->delete('duo_allegro_timetable');
            }
        } else {
            echo 'Wszystko dodane.';
        }
    }

    public function createotomotoad($productId){
        $this->load->model('ProductModel');
        $this->load->model('OtomotoModel');
        $product = new ProductModel($productId);
        if ($product->status2) {
            if (empty($product->otomoto_id)) {
                $g = $this->OtomotoModel->add_advert_from_product($product->id);
                echo json_encode($g);
                echo '<p>Wystaiono ogłoszenie</p>';
            } else {
                $this->load->model('LogModel');
                $logMessage = 'produkt ' . $productId . ' posiada juz ogłoszenie ';
                $this->LogModel->add_log(-3, $productId, $logMessage);
            }
        }
        echo '<p>Koniec</p>';
    }

    public function clear_blocked() {
        $this->load->model('ProductModel');
        $this->ProductModel->clear_blocked();
    }

    public function otomotoTest()
    {
        $this->load->model('OtomotoModel');
        $otomoto = new OtomotoModel();
        var_dump($otomoto->getCategoriesForTest());
    }

    public function otomoto_categories_refresh() {
        $this->load->model('CategoryOtomotoModel');
        $n = (new CategoryOtomotoModel())->refresh_from_api();
        echo "Kategorie Otomoto odswiezone: $n";
    }
}
