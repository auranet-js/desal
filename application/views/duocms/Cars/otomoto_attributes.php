<?php if (!empty($otomoto)) : 
$saved_array = (array) $saved;
?>
<select name="otomoto_category_id" class="form-control">
    <option value="163">Części</option>
    <?php
    if(!empty($otomotoCategories)){
        foreach($otomotoCategories as $categoryId => $otomotoCategory){
            ?>
    <option value="<?= $categoryId;?>" <?= $part->otomoto_category_id == $categoryId ? 'selected' : '';?>><?= $otomotoCategory;?></option>
    <?php
        }
    }
    ?>
</select>
<input type="hidden" name="parameters[parts-type]" value="<?= $part->template->otomoto_part_type; ?>" />
    <?php foreach ($otomoto as $a): ?>
        <?php if(in_array($a->code, ['price', 'parts-type', 'compatibility', 'video'] )) { continue; }//== 'price' || $a->code == 'parts-type' ) { continue; } ?>
        <?php
        // PHP 8 fix: otomoto_part_type bywa NULL (138/279 itemów szablonu) → klucz visibility "0"
        // jak w OtomotoModel::createVisibilityArray(). Guard ?? [] zapobiega TypeError in_array(null).
        $oto_part_type = !empty($part->template->otomoto_part_type) ? $part->template->otomoto_part_type : 0;
        $visible_codes = (isset($parametersVisibility[161][$oto_part_type]) && is_array($parametersVisibility[161][$oto_part_type])) ? $parametersVisibility[161][$oto_part_type] : [];
        if(!in_array($a->code, $visible_codes)){
            continue;
        }
        if($a->code == 'wheels-rims'){
            if(!in_array($part->template->otomoto_part_type, ['kola'])) { continue; }
        } 
        if($a->code == 'wheels-tyres'){
            if(!in_array($part->template->otomoto_part_type, ['kola'])) { continue; }
        } 
        if($a->code == 'rims-type'){
            if(!in_array($part->template->otomoto_part_type, ['felgi'])) { continue; }
        } 
        if($a->code == 'rims-inches'){
            if(!in_array($part->template->otomoto_part_type, ['felgi'])) { continue; }
        } 
        if($a->code == 'tyres-type'){
            if(!in_array($part->template->otomoto_part_type, ['opony'])) { continue; }
        } 
              if($a->code == 'tyres-inches'){
            if(!in_array($part->template->otomoto_part_type, ['opony'])) { continue; }
        } 
              if($a->code == 'tyres-width'){
            if(!in_array($part->template->otomoto_part_type, ['opony'])) { continue; }
        } 
              if($a->code == 'tyres-profile'){
            if(!in_array($part->template->otomoto_part_type, ['opony'])) { continue; }
        } 
        ?>
        <?php if (TRUE || $a->required) : ?>
            <p><?= $a->labels->pl; ?> <?= ($a->required) ? "*" : ''; ?></p>
            <?php
            switch ($a->type) {
                case "date":
                    ?>
                    <input type="date" value="" name="parameters[<?= $a->code; ?>]" class="form-control"/>
                    <?php
                    break;
                case "checkbox":
                    ?>
                    <input type="checkbox" value="1" name="parameters[<?= $a->code; ?>]" />
                    <?php
                    break;
                case "select":
                    ?>
                    <select name="parameters[<?= $a->code; ?>]" class="form-control select2">
                        <?php
                        foreach ($a->options as $z => $d):
                            $selected = false;
                            if (empty($saved_array[$a->code])) {
                                $x = strripos($z, strtolower($car->brand));
                                if ($x !== false) {
                                    $selected = true;
                                } else if(strtolower($z) == strtolower($car->brand)){
                                    $selected = true;
                                }
                            } else {
                                $selected = ($z == $saved_array[$a->code] );
                                if(empty($selected) && $a->code == 'make' && strripos($z, strtolower($car->brand)) !== false){
                                    $selected = true;
                                }
                            }
                            ?>
                            <option value="<?= $z; ?>" <?= $selected ? 'selected' : ''; ?>><?= $d->pl; ?></option>
                    <?php endforeach; ?>
                    </select>
                    <?php
                    break;
                case 'input':
                    $val = !empty($saved_array[$a->code]) ? $saved_array[$a->code] : ( $a->code == 'title' ? $part->name : ''); ?>
                    <input name="parameters[<?= $a->code; ?>]" value="<?= $val; ?>"  class="form-control" /> 
               <?php     break;
                default:
                    break;
            }
            ?>
        <?php endif; ?>
    <?php endforeach; ?>
<?php else: ?>
    Błąd logowania otomoto
<?php endif; ?>
