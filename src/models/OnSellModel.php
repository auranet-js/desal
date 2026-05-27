<?php
// SOURCE: /home6/desal/public_html/application/models/OnSellModel.php
// FETCHED: 2026-05-27 ~10:16 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2022-10-26 10:14:40, size 670 B

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class OnSellModel extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function checkAdvert($products)
    {
        foreach ($products as $product) {
            if (!empty($product->otomoto_id)) {
                $otomotoModel = new OtomotoModel();
                $response = $otomotoModel->getAdvertInfo($product->otomoto_id);
                if ($response->status != 'active') {
                    $product->sellForOtomoto();
                    return true;
                }
            }
        }
        return false;
    }
}
