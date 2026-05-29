<?php
// AURANET_SCHEMA_BLOCK 2026-05-29 — JSON-LD Product/Offer dla rich results Google
$_schema_url = current_url();
$_schema_name = !empty($title) ? $title : $product->name;
$_schema_desc = trim(strip_tags($product->body));
if (strlen($_schema_desc) > 5000) $_schema_desc = substr($_schema_desc, 0, 5000);
$_schema_images = [];
if (!empty($photos)) {
    foreach ($photos as $_p) {
        $_url = $_p->getUrl();
        if (strpos($_url, 'http') !== 0) $_url = base_url(ltrim($_url, '/'));
        $_schema_images[] = $_url;
    }
}
$_schema_brand = null;
if (!empty($product->car_id)) {
    $CI =& get_instance();
    $CI->load->model('CarModel');
    $_car = $CI->db->where('id', $product->car_id)->get('duo_cars')->row();
    if (!empty($_car->brand)) $_schema_brand = $_car->brand;
}
$_schema_avail = ($product_data->quantity > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';

$_schema = [
    '@context' => 'https://schema.org/',
    '@type' => 'Product',
    'name' => $_schema_name,
    'description' => $_schema_desc,
    'sku' => (string)$product_data->id,
    'image' => $_schema_images,
    'offers' => [
        '@type' => 'Offer',
        'url' => $_schema_url,
        'priceCurrency' => 'PLN',
        'price' => number_format((float)$price, 2, '.', ''),
        'availability' => $_schema_avail,
        'itemCondition' => 'https://schema.org/UsedCondition',
        'seller' => [
            '@type' => 'Organization',
            'name' => 'DESAL — Zakład Złomowania Pojazdów',
            'url' => 'https://desal.pl',
        ],
    ],
];
if ($_schema_brand) {
    $_schema['brand'] = ['@type' => 'Brand', 'name' => $_schema_brand];
}
echo '<script type="application/ld+json">' . json_encode($_schema, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . '</script>';
?>

<div class="container product">
    <div class="row">
        <div class="col-sm-12">
            <?php $this->load->view('partials/breadcrumbs'); ?>

            <h1 class="title"><?= !empty($title) ? $title : $product->name; ?> <?= !empty($option) && empty($title) ? ' - ' . $option['name'] : ''; ?></h1>
            <hr>
            <br>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="form-section">
                <?php
                if (!empty($message)) {
                    foreach ($message as $m) {
                        echo '<div class="col-sm-12">';
                        echo '<div class="alert alert-' . $m[0] . '">' . $m[1] . '</div>';
                        echo '</div>';
                    }
                }
                if ($product_data->quantity > 0) {
                    ?>
                    <form method="POST">
                        <div class="row">
                            <?php if ($product_data->options) { ?>
                                <div class="col-sm-12 form-group">
                                    <select name="option" id="option" class="form-control" required="true">
                                        <option value=""><?= (new CustomElementModel('16'))->getField('wybierz opcje'); ?></option>
                                        <?php
                                        if (!empty($options)) {
                                            foreach ($options as $option) {
                                                echo '<option value="' . $option->id . '">' . $option->name . ' - ' . $option->price_change . ' ' . (new CustomElementModel('16'))->getField('waluta') . ' ' . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            <?php } ?>
                            <div class="col-sm-12" id="description">
                            </div>

                            <div class="form-group">
                                <!--<div class="col-sm-6 col-md-3">
                                <?= (new CustomElementModel('11'))->getField('Ilość'); ?>:
                                </div>-->

                                <div class="col-sm-12 col-md-6">
                                    <p>
                                    <big class="price">Cena: <span id="price"> <?= number_format($price, 2, ',', ' '); ?> <?= (new CustomElementModel('16'))->getField('waluta'); ?></span></big>
                                    <span id="bonus-price">
                                        <?php
                                        $bonus_price = $product_data->getField(23, $product_data->id, LANG);
                                        if (!empty($bonus_price) && $bonus_price > 0) {
                                            ?>       
                                            <?= number_format((float) $bonus_price, 2, ',', ' '); ?><?= (new CustomElementModel('16'))->getField('waluta'); ?>
                                            <?php
                                        }
                                        ?>
                                    </span>
                                    <span id="available"><?=$product_data->quantity;?></span> 
                                    </p>

                                </div>
                                <div class="col-sm-12 col-md-6">
                                    <input type="hidden" name="product_id" value="<?= $product_data->id; ?>"  id="product_id"/>
                                    <div class="col-xs-3 col-sm-3">
                                        <span class="btn btn-danger product-quantity-changer" id="p_minus">-</span>
                                    </div>
                                    <div class="col-xs-6 col-sm-6">
                                        <input type="number" step="1" name="quantity" value="1"  class="form-control" id="quantity"/>
                                    </div>
                                    <div class="col-xs-3 col-sm-3">
                                        <span class="btn btn-success product-quantity-changer" id="p_plus">+</span>
                                    </div>
                                    <div class="form-group text-center">
                                        <br><br>
                                        <button class="btn btn-primary"><?= (new CustomElementModel('16'))->getField('Do koszyka'); ?></button>
                                    </div>
                                </div>
                            </div>


                        </div>
                    </form>
                    <?php
                } else {
                    ?>
                    <div class="col-sm-12">
                        <a href="<?= site_url('kontakt'); ?>" class="btn btn-primary"><?= (new CustomElementModel('16'))->getField('zapytaj o produkt'); ?></a>
                        <br><br>
                    </div>
                    <?php
                }
                ?>
                <hr><br><br>
            </div>


            <hr>
            <div class="description">
                <?php echo $product->body; ?>
            </div>



        </div>
        <div class="col-sm-12 col-md-6">
            <?php
            if (!empty($photos)):
                $i = 1;
                ?>
                <div class="product-photos">

                    <div class='col-xs-12 col-sm-12 tab-content product-slider-rwd'>
                        <?php foreach ($photos as $photo) : ?>
                            <div id='<?= $photo->id; ?>' class="product-maxi tab-pane fade <?= ($i === 1) ? 'in active' : ''; ?>" style="background-image: url('<?= $photo->getUrl(); ?>');"></div>
        <?php $i++;
    endforeach; ?>
                    </div>
                    <div class="hidden-xs hidden-sm col-md-12 product-mini-container">
                        <ul>
    <?php foreach ($photos as $photo) : ?>
                                <li <?= ($i === 1) ? 'class="active"' : ''; ?>>
            <!--                        <a data-toggle="tab" href='#<?= $photo->id; ?>'>-->
                                    <a href="<?= $photo->getUrl(); ?>" data-lightbox="Galeria" >
                                        <div class="product-mini" style="background-image: url('<?= $photo->getUrl('mini'); ?>');"></div>
                                    </a>
                                </li>
        <?php $i++;
    endforeach; ?>
                        </ul>

                    </div>
                </div>
<?php endif ?>
            <!--            </div>-->

            <div style="clear:both;"></div>
        </div>
    </div>
    <div style="height:150px;"></div>
</div>
    <div style="clear:both;"></div>
    <script>
        $(document).ready(function () {
            $('#p_minus').click(function () {
                var value = $('#quantity').val();
                if (value > 1) {
                    value = value * 1 - 1;
                }
                $("#quantity").val(value);
            });
            $('#p_plus').click(function () {
                var value = $('#quantity').val();
                if(value < <?=$product_data->quantity;?>){
                value = value * 1 + 1;
            }
                $("#quantity").val(value);
            });
        });
        function number_format(number, decimals, dec_point, thousands_sep) {
            // Strip all characters but numerical ones.
            number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
            var n = !isFinite(+number) ? 0 : +number,
                    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                    s = '',
                    toFixedFix = function (n, prec) {
                        var k = Math.pow(10, prec);
                        return '' + Math.round(n * k) / k;
                    };
            // Fix for IE parseFloat(0.55).toFixed(0) = 0;
            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) {
                s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
            }
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        }

        $(document).ready(function () {
            calculate();
        });
        $('#option').change(function () {
            calculate();
        });
        $('.attribute_select').change(function () {
            calculate();
        });
        $("#quantity").change(function () {
            calculate();
        });
        $("#quantity").keyup(function () {
            calculate();
        });

        function calculate() {

            //ściągam wszystkie atrybuty i opcje i kalkuluje na ich podstawie cenę
            var product_id = $('#product_id').val();
            var option = $('#option').val();
            var attributes = $(".attribute_select").map(function () {
                return $(this).val();
            }).get();
            var quantity = $("#quantity").val();

            $.ajax({
                url: '<?= site_url('oferta/ajax_calculate_price'); ?>',
                dataType: 'JSON',
                type: 'POST',
                data: {
                    product_id: product_id,
                    option: option,
                    attributes: attributes,
                    quantity: quantity
                },
                success: function (res) {
                    $('#price').html(number_format(res['price'], 2, ',', ' ') + " <?= (new CustomElementModel('16'))->getField('waluta'); ?>");

                    $('#bonus-price').html(res['bonus_price']);

                    $('#available').html("Dostępnych: " + res['quantity_left']);
                    if (res['description'] > ' ') {
                        $('#description').html('<div class="alert alert-info">' + res['description'] + '</div>');
                    } else {
                        $('#description').html('');
                    }
                }
            });
        }
    </script>
    <script type="text/javascript">
        var google_tag_params = {
            ecomm_prodid: '<?= $product->product_id; ?>',
            ecomm_pagetype: 'product',
            ecomm_totalvalue: '<?= $price; ?>'
        };
    </script>