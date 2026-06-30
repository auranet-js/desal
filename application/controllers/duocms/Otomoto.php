<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Panel Otomoto — Auranet 2026-05-29.
 * Settings panel z polem hasła + Sprawdź połączenie + status tokenu + licznik kolejki.
 * Dev metody (settings_region/city/category_test) zachowane pod prefix dev_*.
 */
class Otomoto extends Backend_Controller {

    function __construct() {
        parent::__construct();
        $this->load->vars(['activePage' => 'otomoto']);
        $this->load->helper('url');
    }

    public function index() {
        $m = $this->db;
        $waiting = $m->where('status2', 1)->where('(otomoto_id IS NULL OR otomoto_id=0)', null, false)->count_all_results('duo_products');
        $waiting_no_price = $m->where('status2', 1)->where('(otomoto_id IS NULL OR otomoto_id=0)', null, false)->where('(price IS NULL OR price <= 0)', null, false)->count_all_results('duo_products');
        $waiting_with_price = $waiting - $waiting_no_price;
        $total_listed = $this->db->where('otomoto_id >', 0)->count_all_results('duo_products');

        $token_exp = (int) get_option('admin_modules_otomoto_token_expiration');
        $token_valid = ($token_exp > time());
        $token_remaining_h = $token_valid ? round(($token_exp - time())/3600, 1) : 0;

        $data = [
            'mode' => get_option('admin_modules_otomoto_mode'),
            'username' => get_option('admin_modules_otomoto_username'),
            'client_type' => get_option('admin_modules_otomoto_client_type'),
            'has_password' => !empty(get_option('admin_modules_otomoto_password')),
            'token_valid' => $token_valid,
            'token_exp' => $token_exp,
            'token_remaining_h' => $token_remaining_h,
            'waiting' => $waiting,
            'waiting_with_price' => $waiting_with_price,
            'waiting_no_price' => $waiting_no_price,
            'total_listed' => $total_listed,
        ];

        $this->layout('duocms/Otomoto/settings.php', $data);
    }

    /**
     * POST: zapis hasła + ewentualnie loginu/trybu.
     */
    public function save_credentials() {
        if ($this->input->method() !== 'post') redirect(site_url('duocms/Otomoto'));

        $password = trim($this->input->post('password'));
        $username = trim($this->input->post('username'));
        $mode = $this->input->post('mode');

        if (!empty($password)) set_option('admin_modules_otomoto_password', $password);
        if (!empty($username)) set_option('admin_modules_otomoto_username', $username);
        if (in_array($mode, ['production', 'sandbox'])) set_option('admin_modules_otomoto_mode', $mode);

        // Wymuś refresh tokenu na nowych credentials
        set_option('admin_modules_otomoto_token', '');
        set_option('admin_modules_otomoto_token_expiration', '0');

        $this->session->set_flashdata('msg', ['success', 'Zapisano. Sprawdź połączenie żeby pobrać nowy token.']);
        redirect(site_url('duocms/Otomoto'));
    }

    /**
     * AJAX: live OAuth test bez modyfikacji bazy. Zwraca JSON.
     */
    public function test_connection() {
        $this->load->model('OtomotoModel');
        $client_id = get_option('admin_modules_otomoto_client_id');
        $client_secret = get_option('admin_modules_otomoto_client_secret');
        $username = get_option('admin_modules_otomoto_username');
        $password = get_option('admin_modules_otomoto_password');
        $mode = get_option('admin_modules_otomoto_mode');
        $url = ($mode == 'production') ? 'https://www.otomoto.pl/api/open/oauth/token' : 'https://sbotomotopl.playground.lisbontechhub.com/api/open/oauth/token';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'password',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'username' => $username,
            'password' => $password,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded', 'User-Agent: ' . $username . '/1.0']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res = curl_exec($ch);
        $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        header('Content-Type: application/json');
        $json = json_decode($res, true);
        if ($http === 200 && !empty($json['access_token'])) {
            echo json_encode(['ok' => true, 'expires_in' => $json['expires_in'], 'token_preview' => substr($json['access_token'], 0, 12) . '...']);
        } else {
            $err = $json['error_description'] ?? ($json['error'] ?? 'Unknown error');
            echo json_encode(['ok' => false, 'http' => $http, 'error' => $err]);
        }
    }

    /**
     * Wymuś refresh tokenu (czyści cached token, OtomotoModel pobierze nowy przy następnym call).
     */
    public function refresh_token_now() {
        set_option('admin_modules_otomoto_token', '');
        set_option('admin_modules_otomoto_token_expiration', '0');
        $this->session->set_flashdata('msg', ['success', 'Token wyczyszczony. Najbliższe wystawienie wymusi nowy OAuth.']);
        redirect(site_url('duocms/Otomoto'));
    }

    /**
     * Auranet 2026-06-30 (pakiet 2000): odśwież cache kategorii Otomoto z API (kategorie + parts-type).
     */
    public function refresh_categories() {
        $this->load->model('CategoryOtomotoModel');
        $n = (new CategoryOtomotoModel())->refresh_from_api();
        $this->session->set_flashdata('msg', $n
            ? ['success', "Zaktualizowano kategorie Otomoto z API ($n szt.) wraz z listą parts-type."]
            : ['danger', 'Nie udało się pobrać kategorii z Otomoto — sprawdź połączenie/token.']);
        redirect(site_url('duocms/Otomoto'));
    }

    // === DEV / LEGACY (zachowane) ===

    public function dev_settings_region(){
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

    public function dev_settings_city(){
        $this->load->model("OtomotoModel");
        $city_data = $this->OtomotoModel->search_city('Wojnicz');
        var_dump($city_data); die();
    }

    public function dev_category_test(){
        $this->load->model("OtomotoModel");
        $category = $this->OtomotoModel->get_category_data();
        foreach($category->children as $c){
            $z = $this->OtomotoModel->get_category_data($c);
            unset($z->parameters);
            var_dump($z);
            echo '<hr>';
        }
    }

    public function dev_test_wystawienia(){
        $this->load->model('OtomotoModel');
        var_dump($this->OtomotoModel->add_advert_from_product(11591));
    }

    public function dev_test_deaktywacji(){
        $this->load->model('OtomotoModel');
        var_dump($this->OtomotoModel->deactivate_advert_from_product(11620));
    }
}
