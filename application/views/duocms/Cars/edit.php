<?php $this->load->view('duocms/Shop/menu'); ?>
<h2>Dodawanie części samochodu <?= $car->brand.' '.$car->name.' '.$car->production_date;?></h2>
<p>Części w sumie jest: <?= $countParts;?></p>
<ul class="pagination">
    <?php
    $pages = ceil($countParts / $limit);
    for($i = 1; $i <= $pages; $i++){
        ?>
    <li <?= $page == $i ? 'class="active"' : '';?>><a href="?page=<?= $i;?>"><?= $i; ?></a></li>
    <?php
    }
    ?>
</ul>
<?php if (!empty($parts)): ?>
    <?php foreach ($parts as $part): ?>
<div class="col-sm-12">
<form method="post" enctype="multipart/form-data">
    <div class="col-sm-12 col-md-4">
    <input type="hidden" name="id" value="<?=$part->id;?>" >
    <p>Nazwa części</p>
    <input type="text" name="name" value="<?=$part->name;?>" maxlength="50" class="form-control">
    <p>Opis części</p>
    <textarea name="description" class="form-control"><?= trim(preg_replace(array("#</p>\s*<p>#i","#</?p>#i"), array("\n",""), (string)$part->description)); ?></textarea>
    </div>
    <div class="col-sm-12 col-md-4">
        <div class="row">
            <div class="col-sm-12 col-md-4">
        <p>Cena</p>
        <input type="text" name="price" value="<?=$part->price;?>" class="form-control">
        <p><input type="checkbox" name="shop" <?= $part->shop == 1 ? 'checked' : '';?>> Sklep</p>
        <p><input type="checkbox" name="allegro" <?= $part->allegro == 1 ? 'checked' : '';?>> Allegro</p>
        <p><input type="checkbox" name="otomoto" value="1" <?= $part->otomoto == 1 ? 'checked' : '';?>> Otomoto</p>
            </div>
        <div class="col-sm-12 col-md-8">
            <input type="file" name="images[]" multiple accept="image/*" class="sketch-photos"><br>
            <small class="text-muted">Możesz dodać kilka zdjęć naraz. Duże zdjęcia z aparatu są automatycznie zmniejszane.</small>
            <div class="sketch-thumbs" style="margin-top:5px;">
            <?php if(!empty($part->photos)): foreach($part->photos as $sp): ?>
                <span class="sketch-thumb" style="display:inline-block;position:relative;margin:2px;vertical-align:top;">
                    <img src="<?=base_url('uploads/sketch/'.$part->id.'/mini/'.$sp->name);?>" style="height:60px;border:1px solid #ccc;">
                    <a href="<?=site_url('duocms/cars/delete_sketch_photo/'.$sp->id);?>" onclick="return confirm('Usunąć to zdjęcie?');" title="Usuń" style="position:absolute;top:0;right:0;background:#c9302c;color:#fff;padding:0 5px;text-decoration:none;line-height:1.4;">&times;</a>
                </span>
            <?php endforeach; elseif(!empty($part->image)): ?>
                <div class="part-mini-image" style="background-image: url('<?=base_url('uploads/sketch/'. $part->id.'/mini/'.$part->image);?>');" ></div>
            <?php endif;?>
            </div>
        </div>
        </div>
    </div>
    <div class="col-sm-12 col-md-2">
        <p>Atrybuty allegro: </p>
        <?php $this->load->view('duocms/Cars/allegro_attributes', [
            'allegro' => $part->allegro_attributes,
            'saved' => !(empty($part->attributes_json)) ? json_decode($part->attributes_json) : [],
            'car' => $car
        ]); ?>
    </div>
    <div class="col-sm-12 col-md-2">
        <p>Atrybuty otomoto: </p>
        <?php $this->load->view('duocms/Cars/otomoto_attributes', [
            'otomoto' => $part->otomoto_attributes,
            'saved' => !(empty($part->attributes_json_otomoto)) ? json_decode($part->attributes_json_otomoto) : [],
            'car' => $car,
            'part' => $part,
            'otomotoCategories' => $otomotoCategories,
            'parametersVisibility' => $parametersVisibility
        ]); ?>
         <div class="col-sm-12">
            <div class="row">
                <br><br>
                <input type="submit" value="Zapisz część" name="submit" class="btn btn-primary">
            </div>
        </div>
    </div>
</form>
    <div class="col-sm-12">
<hr>
    </div>
</div>
    <?php endforeach; ?>
<?php endif; ?>
<br><br>
<a class="btn btn-primary" id="save_button">Zapisz do EDYCJI</a>
<a class="btn btn-success pull-right" id="action3_button">Zapisz i dodaj do kolejki</a>
<!--<a class="btn btn-info pull-right" id="action2_button">Zapisz, edytuj produkty i aukcje</a>
<a class="btn btn-danger pull-right" id="action_button">Zapisz, stwórz produkty i wystaw aukcje</a>-->



<div class="modal fade" role="dialog" id="save_dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center">
                <img src="<?= assets('img/ajax-loader.gif');?>">
                </div>
                <br><br>
                <div class="progress">
                    <div class="progress-bar progress-bar-info progress-bar-striped save_progress" role="progressbar"
                         aria-valuenow="50" aria-valuemin="0" aria-valuemax="100" style="width:50%" id="save_progress">
                        50%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" role="dialog" id="action_dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center">
                    <img src="<?= assets('img/ajax-loader.gif'); ?>">
                </div>
                <br><br>
                <p> Zapisywanie zmian </p>
                <div class="progress">
                    <div class="progress-bar progress-bar-info progress-bar-striped save_progress" role="progressbar"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" id="save_progress">
                        0%
                    </div>
                </div>
                <br>
                <p>Tworzenie produktów</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-warning progress-bar-striped products_progress" role="progressbar"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" >
                        0%
                    </div>
                </div>
                <br>
                <p>Wystawianie aukcji(średnio 9s na aukcję)</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-success progress-bar-striped auction_progress" role="progressbar"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" >
                        0%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" role="dialog" id="action2_dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center">
                    <img src="<?= assets('img/ajax-loader.gif'); ?>">
                </div>
                <br><br>
                <p> Zapisywanie zmian </p>
                <div class="progress">
                    <div class="progress-bar progress-bar-info progress-bar-striped save_progress" role="progressbar"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" id="save_progress">
                        0%
                    </div>
                </div>
                <br>
                <p>Edytowanie produktów</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-warning progress-bar-striped products_progress" role="progressbar"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" >
                        0%
                    </div>
                </div>
                <br>
                <p>Edytowanie aukcji</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-success progress-bar-striped auction_progress" role="progressbar"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" >
                        0%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" role="dialog" id="action3_dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-body">
                <div class="text-center">
                    <img src="<?= assets('img/ajax-loader.gif'); ?>">
                </div>
                <br><br>
                <p> Zapisywanie zmian </p>
                <div class="progress">
                    <div class="progress-bar progress-bar-info progress-bar-striped save_progress" role="progressbar"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" id="save_progress">
                        0%
                    </div>
                </div>
                <br>
                <p>Dodawanie do kolejki</p>
                <div class="progress">
                    <div class="progress-bar progress-bar-success progress-bar-striped timetable_progress" role="progressbar"
                         aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%" >
                        0%
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function () {
        function _resizeFile(file, maxDim, quality){
            return new Promise(function(resolve){
                if(!file || !file.type || file.type.indexOf('image/')!==0){ resolve({blob:file, name:(file&&file.name)||'foto'}); return; }
                var url = URL.createObjectURL(file);
                var img = new Image();
                img.onload = function(){
                    var w=img.naturalWidth, h=img.naturalHeight;
                    if(w<=maxDim && h<=maxDim){ URL.revokeObjectURL(url); resolve({blob:file, name:file.name}); return; }
                    var s=Math.min(maxDim/w, maxDim/h), cw=Math.round(w*s), ch=Math.round(h*s);
                    var cv=document.createElement('canvas'); cv.width=cw; cv.height=ch;
                    cv.getContext('2d').drawImage(img,0,0,cw,ch);
                    URL.revokeObjectURL(url);
                    cv.toBlob(function(b){ resolve({blob:b||file, name:(file.name||'foto').replace(/\.[^.]+$/,'')+'.jpg'}); }, 'image/jpeg', quality||0.85);
                };
                img.onerror = function(){ URL.revokeObjectURL(url); resolve({blob:file, name:file.name}); };
                img.src = url;
            });
        }
        $(document).on('change', 'input.sketch-photos', function(){
            var el=this; el._resized=null; el._processing=true;
            var files=Array.prototype.slice.call(el.files||[]);
            Promise.all(files.map(function(f){ return _resizeFile(f,1920,0.85); })).then(function(res){ el._resized=res; el._processing=false; });
        });
        function _appendSketchPhotos(input_file, formdata){
            if(!input_file) return;
            if(input_file._resized){
                input_file._resized.forEach(function(r){ if(r && r.blob){ formdata.append('images[]', r.blob, r.name); } });
            } else if(input_file.files){
                for(var _i=0;_i<input_file.files.length;_i++){ formdata.append('images[]', input_file.files[_i]); }
            }
        }
        $('#save_button').click(function(){
            $('#save_dialog').modal({backdrop: 'static', keyboard: false});
            var amount = $('form').length;
            var i = 1;
            var save_promises = [];
             $('form').each(function(index, item){
                 var dfrd = $.Deferred();
                 save_promises.push(dfrd);
                 window.setTimeout(function(){
                 if(window.FormData){
                     var formdata = new FormData();
                     formdata.append('id', $(item).find('input[name="id"]').val());
                     formdata.append('name', $(item).find('input[name="name"]').val());
                     var desc = $(item).find('textarea[name="description"]').val();
                    var blob; 
                    if(desc.indexOf('<p>') !== 0){
                         var par = $('<p></p>');
                         var div = $('<div></div>');
                         par.html(desc);
                         div.html(par);
                         blob = new Blob([$(div).html()], {type: "text/xml"});
                     } else {
                         var div = $('<div></div>');
                         div.html(desc);
                         blob = new Blob([$(div).html()], {type: "text/xml"});
                     }
                     formdata.append('description', blob);
                     formdata.append('price', $(item).find('input[name="price"]').val());
                     var arr = [];
                     $(item).find('select[name^="attribute"]').each(function(index1, item1){
                         arr.push($(item1).val());
                     });
                     formdata.append('attributes', JSON.stringify(arr));
                      var arr2 = new Object();
                     $(item).find('input[name^="parameters"]').each(function(index1, item2){
                         var n = $(item2).attr('name');
                         var nsub = n.substring(n.lastIndexOf('[')+1, n.lastIndexOf(']'));
                         arr2[nsub] = $(item2).val();
                     });
                      $(item).find('select[name^="parameters"]').each(function(index1, item2){
                        var n = $(item2).attr('name');
                         var nsub = n.substring(n.lastIndexOf('[')+1, n.lastIndexOf(']'));
                         arr2[nsub] = $(item2).val();
                     });
                     formdata.append('parameters', JSON.stringify(arr2));
                     if($(item).find('input[name="shop"]').is(':checked')){
                     formdata.append('shop', $(item).find('input[name="shop"]').val());
                        }
                     if($(item).find('input[name="allegro"]').is(':checked')){
                     formdata.append('allegro', $(item).find('input[name="allegro"]').val());
                        }
                        if($(item).find('input[name="otomoto"]').is(':checked')){
                     formdata.append('otomoto', $(item).find('input[name="otomoto"]').val());
                        }
                        var input_file = $(item).find('input[type="file"]')[0];
                        _appendSketchPhotos(input_file, formdata); 
                     var request = $.ajax({
                        cache: false,
                    contentType: false,
                    processData: false,
                    method: 'POST',
                    url: '<?= site_url('duocms/cars/ajax_edit/' . $car->id); ?>',
                    data: formdata,
                    success: function(){
                         $('.save_progress').html(i+' część z ' +amount);
                         $('.save_progress').css('width', ((100*i/amount)).toString() +'%');
                         i++;
                         
                     },
                     error: function(xhr, ajaxOptions, thrownError) {
       console.log(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
    },
            complete: function(){
                dfrd.resolve();
            }
                 });
                 } else{
                     alert('nie wgrano obrazka, Twoja przeglądarka nie obsługuje potrzebnej funkcji. Proszę edytuj część po części');
                     var data = $(item).serialize();
                     $.ajax({

                    method: 'POST',
                    url: '<?= site_url('duocms/cars/ajax_edit/' . $car->id); ?>',
                    data: data,
                    success: function(){
                         $('.save_progress').html(i+' część z ' +amount);
                         $('.save_progress').css('width', ((100*i/amount)).toString() +'%');
                         i++;
                         
                     }, 
                             complete: function(){
                dfrd.resolve();
            }
                 });
                 }
                 
                  }, index*200);
                  
             });
             $.when.apply(null, save_promises).done(function(){
             $('#save_dialog').modal('hide');
             //window.location = '<?= site_url('duocms/cars/edit/' . $car->id); ?>';
             });
        });
        
        $('#action_button').click(function(){
            $('#action_dialog').modal({backdrop: 'static', keyboard: false});
            var amount = $('form').length;
                var i = 1;
                var save_promises = [];
                $('form').each(function (index, item) {
                    var def_save = $.Deferred();
                    save_promises.push(def_save);
                    window.setTimeout(function () {
                        if (window.FormData) {
                            var formdata = new FormData();
                            formdata.append('id', $(item).find('input[name="id"]').val());
                            formdata.append('name', $(item).find('input[name="name"]').val());
                            var desc = $(item).find('textarea[name="description"]').val();
                            console.log(desc);
                            var blob;
                            if (desc.indexOf('<p>') !== 0) {
                                var par = $('<p></p>');
                                var div = $('<div></div>');
                                par.html(desc);
                                div.html(par);
                                blob = new Blob([$(div).html()], {type: "text/xml"});
                            } else {
                                var div = $('<div></div>');
                                div.html(desc);
                                blob = new Blob([$(div).html()], {type: "text/xml"});
                            }
                            formdata.append('description', blob);
                            formdata.append('price', $(item).find('input[name="price"]').val());
                            var arr = [];
                            $(item).find('select[name^="attribute"]').each(function (index1, item1) {
                                arr.push($(item1).val());
                            });
                            formdata.append('attributes', JSON.stringify(arr));
                            var arr2 = new Object();
               $(item).find('input[name^="parameters"]').each(function(index1, item2){
                         var n = $(item2).attr('name');
                         var nsub = n.substring(n.lastIndexOf('[')+1, n.lastIndexOf(']'));
                         arr2[nsub] = $(item2).val();
                     });
                      $(item).find('select[name^="parameters"]').each(function(index1, item2){
                        var n = $(item2).attr('name');
                         var nsub = n.substring(n.lastIndexOf('[')+1, n.lastIndexOf(']'));
                         arr2[nsub] = $(item2).val();
                     });
                     formdata.append('parameters', JSON.stringify(arr2));
                            if ($(item).find('input[name="shop"]').is(':checked')) {
                                formdata.append('shop', $(item).find('input[name="shop"]').val());
                            }
                            if ($(item).find('input[name="allegro"]').is(':checked')) {
                                formdata.append('allegro', $(item).find('input[name="allegro"]').val());
                            }
                            if($(item).find('input[name="otomoto"]').is(':checked')){
                     formdata.append('otomoto', $(item).find('input[name="otomoto"]').val());
                        }
                            var input_file = $(item).find('input[type="file"]')[0];
                        _appendSketchPhotos(input_file, formdata);
                            $.ajax({
                                cache: false,
                                contentType: false,
                                processData: false,
                                method: 'POST',
                                url: '<?= site_url('duocms/cars/ajax_edit/' . $car->id); ?>',
                                data: formdata,
                                success: function () {
                                    $('.save_progress').html(i + ' część z ' + amount);
                                    $('.save_progress').css('width', ((100 * i / amount)).toString() + '%');
                                    i++;

                                },
                                complete: function () {
                                    def_save.resolve();
                                }
                            });

                        } else {
                            console.log('Twoja przeglądarka nie obsluguje formdata, zapisuj każdą część z osobna.');
                        }
                    }, index * 300);
                });

                $.when.apply(null, save_promises).done(function () {
                    i = 1;
                    var product_promises = [];
                    var products = [];
                    $('form').each(function (index, item) {
                        var product_dfr = $.Deferred();
                        product_promises.push(product_dfr);
                        window.setTimeout(function(){
                        var id = $(item).find('input[name="id"]').val();
                        $.ajax({
                            url: '<?= site_url('duocms/cars/product_from_sketch/'); ?>' + id.toString(),
                            success: function (data) {
                                $('.products_progress').html(i + ' część z ' + amount);
                                $('.products_progress').css('width', ((100 * i / amount)).toString() + '%');
                                i++;
                                products.push(data);
                            },
                            complete: function(){
                                product_dfr.resolve();
                            }
                        });
                        }, index*700);
                    });
                    $.when.apply(null, product_promises).done(function () {
                        i = 1;
                        var allegro_promises = [];
                        $.each(products, function (index, item) {
                            var allegro_dfr = $.Deferred();
                            allegro_promises.push(allegro_dfr);
                            window.setTimeout(function(){
                            $.ajax({
                                url: '<?= site_url('duocms/cars/add_auction/'); ?>' + item,
                                success: function (data) {
                                    $('.auction_progress').html(i + ' część z ' + amount);
                                    $('.auction_progress').css('width', ((100 * i / amount)).toString() + '%');
                                    i++;
                                },
                                        complete: function(){
                                            allegro_dfr.resolve();
                                        }
                            });
                            }, index*9000);
                        });
                        $.when.apply(null, allegro_promises).done(function () {
                            $('#action_dialog').modal('hide');
                        });
                    });
                });

            });
            
            
            $("form :input").change(function() {
                $(this).closest('form').attr('changed', true);
            });
            
            
            
            $('#action2_button').click(function(){
            $('#action2_dialog').modal({backdrop: 'static', keyboard: false});
            var amount = $('form[changed="true"]').length;
                var i = 1;
                var save_promises = [];
                $('form[changed="true"]').each(function (index, item) {
                    var def_save = $.Deferred();
                    save_promises.push(def_save);
                    window.setTimeout(function () {
                        if (window.FormData) {
                            var formdata = new FormData();
                            formdata.append('id', $(item).find('input[name="id"]').val());
                            formdata.append('name', $(item).find('input[name="name"]').val());
                            var desc = $(item).find('textarea[name="description"]').val();
                            console.log(desc);
                            var blob;
                            if (desc.indexOf('<p>') !== 0) {
                                var par = $('<p></p>');
                                var div = $('<div></div>');
                                par.html(desc);
                                div.html(par);
                                blob = new Blob([$(div).html()], {type: "text/xml"});
                            } else {
                                var div = $('<div></div>');
                                div.html(desc);
                                blob = new Blob([$(div).html()], {type: "text/xml"});
                            }
                            formdata.append('description', blob);
                            formdata.append('price', $(item).find('input[name="price"]').val());
                            var arr = [];
                            $(item).find('select[name^="attribute"]').each(function (index1, item1) {
                                arr.push($(item1).val());
                            });
                            formdata.append('attributes', JSON.stringify(arr));
                             var arr2 = new Object();
               $(item).find('input[name^="parameters"]').each(function(index1, item2){
                         var n = $(item2).attr('name');
                         var nsub = n.substring(n.lastIndexOf('[')+1, n.lastIndexOf(']'));
                         arr2[nsub] = $(item2).val();
                     });
                      $(item).find('select[name^="parameters"]').each(function(index1, item2){
                        var n = $(item2).attr('name');
                         var nsub = n.substring(n.lastIndexOf('[')+1, n.lastIndexOf(']'));
                         arr2[nsub] = $(item2).val();
                     });
                     formdata.append('parameters', JSON.stringify(arr2));
                            if ($(item).find('input[name="shop"]').is(':checked')) {
                                formdata.append('shop', $(item).find('input[name="shop"]').val());
                            }
                            if ($(item).find('input[name="allegro"]').is(':checked')) {
                                formdata.append('allegro', $(item).find('input[name="allegro"]').val());
                            }
                            if($(item).find('input[name="otomoto"]').is(':checked')){
                     formdata.append('otomoto', $(item).find('input[name="otomoto"]').val());
                        }
                            var input_file = $(item).find('input[type="file"]')[0];
                        _appendSketchPhotos(input_file, formdata);
                            $.ajax({
                                cache: false,
                                contentType: false,
                                processData: false,
                                method: 'POST',
                                url: '<?= site_url('duocms/cars/ajax_edit/' . $car->id); ?>',
                                data: formdata,
                                success: function () {
                                    $('#action2_dialog .save_progress').html(i + ' część z ' + amount);
                                    $('#action2_dialog .save_progress').css('width', ((100 * i / amount)).toString() + '%');
                                    i++;

                                },
                                complete: function () {
                                    def_save.resolve();
                                }
                            });

                        } else {
                            console.log('Twoja przeglądarka nie obsluguje formdata, zapisuj każdą część z osobna.');
                        }
                    }, index * 30);
                });

                $.when.apply(null, save_promises).done(function () {
                    i = 1;
                    var product_promises = [];
                    var products = [];
                    $('form[changed="true"]').each(function (index, item) {
                        var product_dfr = $.Deferred();
                        product_promises.push(product_dfr);
                        window.setTimeout(function(){
                        var id = $(item).find('input[name="id"]').val();
                        $.ajax({
                            url: '<?= site_url('duocms/cars/edit_products/'); ?>' + id.toString(),
                            success: function (data) {
                                $('#action2_dialog .products_progress').html(i + ' część z ' + amount);
                                $('#action2_dialog .products_progress').css('width', ((100 * i / amount)).toString() + '%');
                                i++;
                                products.push(data);
                            },
                            complete: function(){
                                product_dfr.resolve();
                            }
                        });
                        }, index*10);
                    });
                    $.when.apply(null, product_promises).done(function () {
                        i = 1;
                        var allegro_promises = [];
                        $.each(products, function (index, item) {
                            var allegro_dfr = $.Deferred();
                            allegro_promises.push(allegro_dfr);
                            window.setTimeout(function(){
                            $.ajax({
                                url: '<?= site_url('duocms/cars/edit_auction/'); ?>' + item,
                                success: function (data) {
                                    $('#action2_dialog .auction_progress').html(i + ' część z ' + amount);
                                    $('#action2_dialog .auction_progress').css('width', ((100 * i / amount)).toString() + '%');
                                    i++;
                                },
                                        complete: function(){
                                            allegro_dfr.resolve();
                                        }
                            });
                            }, index*90);
                        });
                        $.when.apply(null, allegro_promises).done(function () {
                            $('#action2_dialog').modal('hide');
                        });
                    });
                });

            });
            
            
            $('#action3_button').click(function(){
            $('#action3_dialog').modal({backdrop: 'static', keyboard: false});
            var amount = $('form').length;
                var i = 1;
                var save_promises = [];
                $('form').each(function (index, item) {
                    var def_save = $.Deferred();
                    save_promises.push(def_save);
                    window.setTimeout(function () {
                        if (window.FormData) {
                            var formdata = new FormData();
                            formdata.append('id', $(item).find('input[name="id"]').val());
                            formdata.append('name', $(item).find('input[name="name"]').val());
                            var desc = $(item).find('textarea[name="description"]').val();
                            console.log(desc);
                            var blob;
                            if (desc.indexOf('<p>') !== 0) {
                                var par = $('<p></p>');
                                var div = $('<div></div>');
                                par.html(desc);
                                div.html(par);
                                blob = new Blob([$(div).html()], {type: "text/xml"});
                            } else {
                                var div = $('<div></div>');
                                div.html(desc);
                                blob = new Blob([$(div).html()], {type: "text/xml"});
                            }
                            formdata.append('description', blob);
                            formdata.append('price', $(item).find('input[name="price"]').val());
                            var arr = [];
                            $(item).find('select[name^="attribute"]').each(function (index1, item1) {
                                arr.push($(item1).val());
                            });
                            formdata.append('attributes', JSON.stringify(arr));
                var arr2 = new Object();
               $(item).find('input[name^="parameters"]').each(function(index1, item2){
                         var n = $(item2).attr('name');
                         var nsub = n.substring(n.lastIndexOf('[')+1, n.lastIndexOf(']'));
                         arr2[nsub] = $(item2).val();
                     });
                      $(item).find('select[name^="parameters"]').each(function(index1, item2){
                        var n = $(item2).attr('name');
                         var nsub = n.substring(n.lastIndexOf('[')+1, n.lastIndexOf(']'));
                         arr2[nsub] = $(item2).val();
                     });
                     formdata.append('parameters', JSON.stringify(arr2));
                            if ($(item).find('input[name="shop"]').is(':checked')) {
                                formdata.append('shop', $(item).find('input[name="shop"]').val());
                            }
                            if ($(item).find('input[name="allegro"]').is(':checked')) {
                                formdata.append('allegro', $(item).find('input[name="allegro"]').val());
                            }
                            if($(item).find('input[name="otomoto"]').is(':checked')){
                     formdata.append('otomoto', $(item).find('input[name="otomoto"]').val());
                        }
                            var input_file = $(item).find('input[type="file"]')[0];
                        _appendSketchPhotos(input_file, formdata);
                            $.ajax({
                                cache: false,
                                contentType: false,
                                processData: false,
                                method: 'POST',
                                url: '<?= site_url('duocms/cars/ajax_edit/' . $car->id); ?>',
                                data: formdata,
                                success: function () {
                                    $('#action3_dialog .save_progress').html(i + ' część z ' + amount);
                                    $('#action3_dialog .save_progress').css('width', ((100 * i / amount)).toString() + '%');
                                    i++;

                                },
                                complete: function () {
                                    def_save.resolve();
                                }
                            });

                        } else {
                            console.log('Twoja przeglądarka nie obsluguje formdata, zapisuj każdą część z osobna.');
                        }
                    }, index * 30);
                });

                $.when.apply(null, save_promises).done(function () {
                    i = 1;
                    var product_promises = [];
                    var products = [];
                    $('form').each(function (index, item) {
                        var product_dfr = $.Deferred();
                        product_promises.push(product_dfr);
                        window.setTimeout(function(){
                        var id = $(item).find('input[name="id"]').val();
                        $.ajax({
                            url: '<?= site_url('duocms/cars/add_to_timetable/'); ?>' + id.toString(),
                            success: function (data) {
                                $('#action3_dialog .timetable_progress').html(i + ' część z ' + amount);
                                $('#action3_dialog .timetable_progress').css('width', ((100 * i / amount)).toString() + '%');
                                i++;
                                products.push(data);
                            },
                            complete: function(){
                                product_dfr.resolve();
                            }
                        });
                        }, index*10);
                    });
                    $.when.apply(null, product_promises).done(function () {
                        $('#action3_dialog').modal('hide');
                    });
                });

            });
        });
    
</script>