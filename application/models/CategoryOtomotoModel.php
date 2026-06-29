<?php


class CategoryOtomotoModel extends CI_Model
{
    public $id;
    public $otomoto_id;
    public $name;
    private $_table = 'duo_category_otomoto';

    public function __construct()
    {
        $this->load->model('OtomotoModel');
    }

    public function integrate()
    {
        $otomotoModel = new OtomotoModel();
        $results = $otomotoModel->getAdvertInfo(6098058995);
        return $results;
    }

    public function getCategories()
    {
        $otomotoModel = new OtomotoModel();
        return $otomotoModel->get_category_data();
    }

    /**
     * Auranet 2026-06-29 (pakiet 2000, self-service kategorie):
     * odśwież cache kategorii Otomoto z API. Truncate + reinsert.
     * Zwraca liczbę zapisanych kategorii. 0 = błąd/pusto z API -> NIE czyścimy cache.
     */
    public function refresh_from_api()
    {
        $otomotoModel = new OtomotoModel();
        $cats = $otomotoModel->get_categories(1); // ->results[] z id + names->pl
        if (empty($cats->results)) {
            return 0;
        }
        $rows = [];
        foreach ($cats->results as $r) {
            if (empty($r->id) || empty($r->names->pl)) {
                continue;
            }
            $rows[] = ['otomoto_id' => (int)$r->id, 'name' => $r->names->pl];
        }
        if (empty($rows)) {
            return 0;
        }
        $this->db->truncate($this->_table);
        $this->db->insert_batch($this->_table, $rows);

        // Auranet 2026-06-29: przy okazji cache opcji parts-type (Rodzaj części) z kat. 163.
        $this->refresh_parts_type_cache($otomotoModel);

        return count($rows);
    }

    /**
     * Auranet 2026-06-29: ścieżka pliku cache parts-type (JSON).
     * Plik zamiast duo_options bo set_option/updateOption tylko aktualizuje istniejące klucze.
     */
    private function parts_type_cache_path()
    {
        return FCPATH . 'uploads/otomoto_parts_type_cache.json';
    }

    /**
     * Auranet 2026-06-29: cache opcji parts-type (Rodzaj części) z kategorii 163.
     * Zapis jako JSON do pliku. [ optionKey => polski_label ].
     */
    public function refresh_parts_type_cache($otomotoModel = null)
    {
        if ($otomotoModel === null) {
            $otomotoModel = new OtomotoModel();
        }
        $oto_category = $otomotoModel->get_category_data(); // domyślnie 163
        if (empty($oto_category->parameters)) {
            return 0;
        }
        $parts = [];
        foreach ($oto_category->parameters as $op) {
            if ($op->code != 'parts-type') {
                continue;
            }
            foreach ($op->options as $z => $o) {
                $parts[$z] = $o->pl;
            }
        }
        if (empty($parts)) {
            return 0;
        }
        file_put_contents($this->parts_type_cache_path(), json_encode($parts, JSON_UNESCAPED_UNICODE));
        return count($parts);
    }

    /**
     * Auranet 2026-06-29: kategorie z cache do dropdownow [ otomoto_id => name ].
     */
    public function get_all_cached()
    {
        $out = [];
        foreach ($this->db->order_by('name', 'asc')->get($this->_table)->result() as $r) {
            $out[$r->otomoto_id] = $r->name;
        }
        return $out;
    }

    /**
     * Auranet 2026-06-29: opcje parts-type z cache [ optionKey => polski_label ].
     * Fallback: pusty array (widok pokaże pusty select, refresh uzupełni).
     */
    public function get_parts_type_cached()
    {
        $f = $this->parts_type_cache_path();
        if (!is_file($f)) {
            return [];
        }
        $arr = json_decode(file_get_contents($f), true);
        return is_array($arr) ? $arr : [];
    }
}
