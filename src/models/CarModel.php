<?php
// SOURCE: /home6/desal/public_html/application/models/CarModel.php
// FETCHED: 2026-05-27 ~10:21 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2021-09-03 08:37:16, size 15337 B
// UWAGA: tu siedzi product_from_sketch() — KLUCZOWA metoda pipeline (auto sketch → produkt)

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class CarModel extends MY_Model {

    private $_table = 'duo_cars';
    public $id;
    public $name;
    public $production_date;
    public $brand;
    public $buy_price;
    public $image = '';
    public $version;
    public $template_id;
    public $template_items;
    public $description = '';

    public function add_car() {
        $args = array(
            'name' => $this->name,
            'production_date' => $this->production_date,
            'brand' => $this->brand,
            'buy_price' => $this->buy_price,
            'image' => $this->image,
            'version' => $this->version,
            'description' => $this->description
        );
        $this->db->insert($this->_table, $args);
        $this->id = $this->db->insert_id();
        return $this->id;
    }

    public function get_car_list_with_string($string = null, $sort = null){
        if(!empty($string)){
        $this->db->where("LOWER(duo_cars.name) LIKE '%" . strtolower($string) . "%'");
        $this->db->or_where("LOWER(duo_cars.brand) LIKE '%" . strtolower($string) . "%'");
        $this->db->or_where("MATCH (duo_cars.name) AGAINST ('" . $string . "' IN BOOLEAN MODE)");
        $this->db->or_where("MATCH (duo_cars.brand) AGAINST ('" . $string . "' IN BOOLEAN MODE)");
        }
        if(!empty($sort)){
            switch ($sort) {
                case 'default':
                    break;
                case 'date_asc':
                    $this->db->order_by('duo_cars.production_date', 'asc');
                    break;
                case 'date_desc':
                     $this->db->order_by('duo_cars.production_date', 'desc');
                    break;
                default:
                    break;
            }
        }
        $q = $this->db->get($this->_table);
        return $q->result('CarModel');
    }
    public function get_car_list() {
        $this->db->order_by('brand ASC, name ASC');
        $q = $this->db->get($this->_table);
        return $q->result('CarModel');
    }

    public function get_car($id) {
        $this->db->where('id', $id);
        $q = $this->db->get($this->_table);
        return $q->row(0, 'CarModel');
    }

    public function update_car() {
        $args = array(
            'name' => $this->name,
            'production_date' => $this->production_date,
            'brand' => $this->brand,
            'buy_price' => $this->buy_price,
            'image' => $this->image,
            'version' => $this->version,
            'description' => $this->description
        );
        $this->db->where('id', $this->id);
        $this->db->update($this->_table, $args);
    }

    public function delete_car($id) {
        $this->db->where('id', $id);
        $q = $this->db->delete($this->_table);
        return true;
    }

    public function save_sketch($args){
        $this->db->insert('duo_car_sketches', $args);
        return $this->db->insert_id();
    }
    public function get_sketches_by_car_id($car_id, $page = 1){
        $this->db->select('duo_car_sketches.*');
        $this->db->join('duo_templates', 'duo_templates.id = duo_car_sketches.template_item_id');
        $this->db->where('car_id', $car_id);
        $this->db->order_by('name', 'ASC');
        $this->db->limit(20, ($page-1)*20);
        $q = $this->db->get("duo_car_sketches");
        return $q->result();
    }

    public function count_sketches_by_car_id($car_id){
        $this->db->select('duo_car_sketches.*');
        $this->db->join('duo_templates', 'duo_templates.id = duo_car_sketches.template_item_id');
        $this->db->where('car_id', $car_id);
        $this->db->order_by('name', 'ASC');
        $q = $this->db->get("duo_car_sketches");
        return $q->num_rows();
    }

    public function groupOtomotoIds($parts){
        $ids = [];
        if(empty($parts)){
            return $ids;
        }
        foreach($parts as $part){
            if(empty($ids[empty($part->otomoto_category_id) ? 163 : $part->otomoto_category_id])){
                $ids[empty($part->otomoto_category_id) ? 163 : $part->otomoto_category_id] = empty($part->otomoto_category_id) ? 163 : $part->otomoto_category_id;
            }
        }
        return $ids;
    }

    public function get_sketch($sketch_id){
        $this->db->where('id', $sketch_id);
        $q = $this->db->get("duo_car_sketches");
        return $q->row();
    }

    public function update_sketch($id, $args){
        $this->db->where('id', $id);
        $res = $this->db->update("duo_car_sketches", $args);
        $sketchRow = $this->db->get_where('duo_car_sketches',['id' => $id])->row();
        $productRow = $this->db->get_where('duo_products',['car_id' => $sketchRow->car_id, 'sketch_item_id' => $sketchRow->template_item_id])->row();
        if(!empty($productRow)){
            $this->db->where('id', $productRow->id)->update('duo_products',['type2' => $sketchRow->attributes_json_otomoto]);
        }


        return $res;
    }

    public function check_if_part_already_added($car_id, $sketch_item_id){
        $this->db->where('car_id', $car_id);
        $this->db->where('sketch_item_id', $sketch_item_id);
        $q = $this->db->get('duo_products');
        if($q->num_rows() > 0){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    public function get_product_if_part_already_added($car_id, $sketch_item_id){
        $this->db->where('car_id', $car_id);
        $this->db->where('sketch_item_id', $sketch_item_id);
        $q = $this->db->get('duo_products');
        if($q->num_rows() > 0){
            return $q->result()[0]->id;
        } else {
            return null;
        }
    }

    public function search_prompt($brand, $model){
        $this->db->where('brand', $brand);
        $this->db->where("name LIKE '%".$model."%'");
        $this->db->distinct();
        $q = $this->db->get($this->_table);
        return $q->result();
    }

    public function search($brand, $model){
        $this->load->model("ProductModel");
        $this->db->where('brand', $brand);
        $this->db->where("name LIKE '%".$model."%'");
        $this->db->distinct();
        $q = $this->db->get($this->_table);
        if($q->num_rows()>0){
            $car_ids = [];
            foreach($q->result() as $row){
                $car_ids[] = $row->id;
            }
            $this->db->where('active', 0);
            $this->db->where_in('car_id', $car_ids);
            $z = $this->db->get('duo_products');

            return $z->result('ProductModel');
        }else {
            return null;
        }
    }

    public function list_brands(){
        $this->db->select('brand');
        $this->db->distinct();
        $this->db->order_by('brand', "ASC");
        $q = $this->db->get($this->_table);
        return $q->result();
    }

    public function get_info(){
        $this->db->select('SUM(price) as sum');
        $this->db->where('car_id', $this->id);
        $q1 = $this->db->get('duo_products');
        $total = $q1->row()->sum;
        $this->db->select('SUM(price) as sum');
        $this->db->where('car_id', $this->id);
        $this->db->where('sold > 0');
        $q2 = $this->db->get('duo_products');
        $income = $q2->row()->sum;
        return array(
            'total' => $total,
            'income' => $income
        );
    }

    public function get_product_from_part($car_id, $sketch_item_id){
        $this->load->model("ProductModel");
        $this->db->where('car_id', $car_id);
        $this->db->where('sketch_item_id', $sketch_item_id);
        $q = $this->db->get('duo_products');
        if($q->num_rows() > 0){
            return new ProductModel($q->row()->id);
        } else {
            return null;
        }
    }

    public function product_from_sketch($sketch_id){
        $this->load->model('TemplatesModel');
        $this->load->model('ProductModel');
        $this->load->model('ProductPhotoModel');
        $this->load->model('ProductTranslationModel');
        $sketch = $this->get_sketch($sketch_id);
        $template = $this->TemplatesModel->get_template_item($sketch->template_item_id);
        $car = $this->get_car($sketch->car_id);
    if($this->check_if_part_already_added($sketch->car_id, $sketch->template_item_id)){
        $this->load->model('LogModel');
        $this->LogModel->add_log(-1, $sketch_id, 'część '. $sketch->name .' z samochodu o id'. $car->id.' już jest w bazie jako część');
        return $this->get_product_if_part_already_added($sketch->car_id, $sketch->template_item_id); }
    if(empty($sketch->image) && empty($car->image)){
        $this->load->model('LogModel');
        $this->LogModel->add_log(-1, $sketch_id, 'część '. $sketch->name .' z samochodu o id'. $car->id.' nie ma obrazka i samochód nie ma obrazka');
        return 0; }
        $product = new ProductModel();

        $product->car_id = $sketch->car_id;
        $this->load->model("OfferCategoryModel");
        $category = new OfferCategoryModel($template->category_id);
        if(empty($category->id)){
            $this->load->model('LogModel');
            $this->LogModel->add_log(-1, $sketch_id, 'część '. $sketch->name .' z samochodu o id'. $car->id.' ma w szablonie niepoprawną kategorię');
            return 0;
        }
        $product->offer_category_id = $template->category_id;
        $product->allegro_category_id = $template->allegro_category;
        $product->price = $sketch->price;
        $product->quantity = 1;
        $product->active = !empty($sketch->shop) ? 0 : 1;
        $product->status = $sketch->allegro;
        $product->status2 = $sketch->otomoto;
        $product->type = $sketch->attributes_json;
        $product->type2 = $sketch->attributes_json_otomoto;
        $product->sketch_item_id = $sketch->template_item_id;
        $product->delivery_id = $template->delivery_id;
        $product->insert_product();

        $photo = new ProductPhotoModel();
        $photo->product_id = $product->id;
        $photo->insert();
        $dest = FCPATH.'uploads/products/'.$photo->product_id.'/'.$photo->id.'/';
        if (!is_dir($dest)) {
            mkdir($dest, 0777, true);
                        mkdir($dest."mini/", 0777, true);
        }
        if(!empty($sketch->image)){
        $source = FCPATH . 'uploads/sketch/' . $sketch->id .'/';

        copy($source.$sketch->image, $dest.$sketch->image);
        copy($source.'mini/'.$sketch->image, $dest.'mini/'.$sketch->image);
        $photo->name = $sketch->image;
        } else {
            $source = FCPATH . 'uploads/cars/' . $car->id .'/';

        copy($source.$car->image, $dest.$car->image);
        copy($source.'mini/'.$car->image, $dest.'mini/'.$car->image);
        $photo->name = $car->image;
        }
        $photo->update();

        $tproduct = new ProductTranslationModel();
        $tproduct->product_id = $product->id;
        $tproduct->name = $sketch->name;
        $tproduct->body = $sketch->description;
        $tproduct->lang = 'pl';
        $tproduct->insert();
        $this->load->model('LogModel');
        $this->LogModel->add_log(1, $sketch_id, 'część '. $sketch->name .' z samochodu o id'. $car->id.' została dodana jako produkt');
        return $product->id;
    }
}
