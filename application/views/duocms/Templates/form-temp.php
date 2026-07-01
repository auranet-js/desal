<div class="data-row" style="border-bottom: 1px solid black; ">
    <div class="col-lg-2 col-md-2 col-sm-12">
        <p>Nazwa części:</p>
        <?php echo form_input('name['.$number.']', !empty($templates) ? $templates->name : '', ' class="form-control" '); ?>
    </div>
    <div class="col-lg-2 col-md-2 col-sm-12">
        <p>Numer kategorii allegro:</p>
        <?php echo form_input('allegro_category['.$number.']', !empty($templates) ? $templates->allegro_category : '', ' class="form-control" '); ?>
    </div>
    <div class="col-lg-2 col-md-2 col-sm-12">
                <p>Typ częsci na otomoto:</p>
                <?php echo form_dropdown('otomoto_part_type['.$number.']', $oto_parts, !empty($templates) ? $templates->otomoto_part_type : null, ' class="form-control" '); ?>
                <p>Kategoria otomoto</p>
                <?php echo form_dropdown('otomoto_category['.$number.']', $oto_categories, !empty($templates) ? $templates->otomoto_category_id:163, ' class="form-control" ');?>
                <p style="margin-top:6px;">Podkategoria Otomoto (część końcowa):</p>
                <input type="text" name="otomoto_parts_category[<?= $number; ?>]" value="" list="otomoto_pc_dl" class="form-control otomoto-pc-input" autocomplete="off" placeholder="wpisz np. drzwi..." />
                <small class="otomoto-pc-hint" style="display:block; color:#777; min-height:14px;"></small>
            </div>
    <div class="col-lg-2 col-md-2 col-sm-12">
        <p>Kategoria w sklepie:</p>
        <p><?php echo form_dropdown('parent_id['.$number.']', $parents, !empty($templates) ? $templates->category_id : null, ' class="form-control" '); ?></p>
    </div>
     <div class="col-lg-1 col-md-1 col-sm-12">
                <input type="checkbox" name="shop[<?= $number; ?>]" value="1" <?= !empty($templates) && $templates->shop == 1 ? 'checked' : '' ; ?> /> Sklep<br>
                 <input type="checkbox" name="allegro[<?= $number; ?>]" value="1" <?= !empty($templates) && $templates->allegro == 1 ? 'checked' : '' ; ?> /> Allegro<br>
                  <input type="checkbox" name="otomoto[<?= $number; ?>]" value="1"  <?= !empty($templates) && $templates->otomoto == 1 ? 'checked' : '' ; ?> /> Otomoto
            </div>
    <div class="col-lg-3 col-md-3 col-sm-12">
                <p>Cennik wysyłki na allegro</p>
                <p><?php echo form_dropdown('delivery_id['.$number.']', $deliveries, !empty($templates) ? $templates->delivery_id : null, ' class="form-control" '); ?></p>
            </div>
     <div style="clear: both;"></div>
</div>