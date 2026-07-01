<h2><?= !empty($template) ? 'Edycja' : 'Dodawanie'; ?> szablonu</h2>
<form action="" method="post" enctype="multipart/form-data">
     <?php echo form_input('template_name', !empty($template) ? $template->name : '', ' class="form-control" '); ?>
    <div class="list">
        <?php if(!empty($template_items)):
            $i=0;?>
        <?php foreach($template_items as $ti): ?>
        <div class="data-row row">
            <?= form_hidden('id['.$i.']', $ti->id); ?>
            <div class="col-lg-2 col-md-2 col-sm-12">
                <p>Nazwa części:</p>
                <?php echo form_input('name['.$i.']', !empty($ti) ? $ti->name : '', ' class="form-control" '); ?>
            </div>
            <div class="col-lg-2 col-md-2 col-sm-12">
                <p>Numer kategorii allegro:</p>
                <?php echo form_input('allegro_category['.$i.']', !empty($ti) ? $ti->allegro_category : '', ' class="form-control" '); ?>
            </div>
             <div class="col-lg-2 col-md-2 col-sm-12">
                <p>Typ częsci na otomoto:</p>
                <?php echo form_dropdown('otomoto_part_type['.$i.']', $oto_parts, !empty($ti) ? $ti->otomoto_part_type : null, ' class="form-control" '); ?>
                <p>Kategoria otomoto</p>
                <?php echo form_dropdown('otomoto_category['.$i.']', $oto_categories, !empty($ti) ? $ti->otomoto_category_id:163, ' class="form-control" ');?>
                <p style="margin-top:6px;">Podkategoria Otomoto (część końcowa):</p>
                <input type="text" name="otomoto_parts_category[<?= $i; ?>]" value="<?= !empty($ti->otomoto_parts_category) ? htmlspecialchars($ti->otomoto_parts_category) : ''; ?>" list="otomoto_pc_dl" class="form-control otomoto-pc-input" autocomplete="off" placeholder="wpisz np. drzwi..." />
                <small class="otomoto-pc-hint" style="display:block; color:#777; min-height:14px;"></small>
            </div>
            <div class="col-lg-1 col-md-1 col-sm-12">
                <p>Kategoria w sklepie:</p>
                <p><?php echo form_dropdown('parent_id['.$i.']', $parents, !empty($ti) ? $ti->category_id : null, ' class="form-control" '); ?></p>
            </div>
             <div class="col-lg-1 col-md-1 col-sm-12">
                <input type="checkbox" name="shop[<?= $i; ?>]" value="1" <?= !empty($ti) && $ti->shop == 1 ? 'checked' : '' ; ?> /> Sklep<br>
                 <input type="checkbox" name="allegro[<?= $i; ?>]" value="1" <?= !empty($ti) && $ti->allegro == 1 ? 'checked' : '' ; ?> /> Allegro<br>
                  <input type="checkbox" name="otomoto[<?= $i; ?>]" value="1"  <?= !empty($ti) && $ti->otomoto == 1 ? 'checked' : '' ; ?> /> Otomoto
            </div>
             <div class="col-lg-3 col-md-3 col-sm-12">
                <p>Cennik wysyłki na allegro</p>
                <p><?php echo form_dropdown('delivery_id['.$i.']', $deliveries, !empty($ti) ? $ti->delivery_id : null, ' class="form-control" '); ?></p>
            </div>
            <div class="col-lg-1 col-md-1 col-sm-12"><a style="margin-top: 35px; font-size:35px; display:block;" href="<?= site_url('duocms/templates/delete_item/'. $ti->id);?>"><i class="fa fa-trash"></i></a></div>
        </div>
        <?php $i++; endforeach; endif;?>
    </div>

    <datalist id="otomoto_pc_dl">
    <?php if(!empty($oto_parts_category)) foreach($oto_parts_category as $pcK=>$pcL): ?>
        <option value="<?= htmlspecialchars($pcK); ?>"><?= htmlspecialchars($pcL); ?></option>
    <?php endforeach; ?>
    </datalist>

    <div >
        <a id="load_more" class="btn btn-primary"> Więcej </a>
    </div>

    <br><br><br><br>

    <p>
        <button type="submit" class="btn btn-primary" style="width: 50%;">Zapisz</button>
        <a href="<?php echo site_url('duocms/templates'); ?>" class="btn btn-warning" style="float: right;">Powrót</a>
    </p>
</form>
  <script>
        var OTOMOTO_PC = <?= json_encode(!empty($oto_parts_category) ? $oto_parts_category : new \stdClass(), JSON_UNESCAPED_UNICODE); ?>;
        function pcHint(inp){
            var v=($(inp).val()||'').trim();
            var s=$(inp).siblings('.otomoto-pc-hint');
            if(v && OTOMOTO_PC[v]){ s.html('&#10003; '+OTOMOTO_PC[v]).css('color','#2e7d32'); }
            else if(v){ s.html('&#9888; nieznany klucz — wpisz frazę i wybierz z listy').css('color','#c62828'); }
            else { s.html('').css('color','#777'); }
        }
        $(document).ready(function () {
            $(document).on('input change','.otomoto-pc-input',function(){ pcHint(this); });
            $('.otomoto-pc-input').each(function(){ pcHint(this); });
//            $('select[name^="parent_id"').multiselect({enableCaseInsensitiveFiltering: true,
//            maxHeight: 300});
            $("#load_more").on("click", function () {
                var number = $("input[name^='name'").length;

                var data = new Object();
                data.number = number;
                $.ajax({
                    type: 'POST',
                    data: data,
                    url: '<?= site_url('duocms/templates/new_row');?>',
                    success: function(data){
                        $('.list').append(data);
                        $('select[name^="parent_id['+number+']"').multiselect({enableCaseInsensitiveFiltering: true,
                        maxHeight: 300});
                    }
                });
            });
        });
    </script>
