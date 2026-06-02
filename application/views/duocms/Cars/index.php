<div class="col-sm-12">
    <h2>Samochody</h2>

    <p><a href="<?php echo site_url('duocms/cars/create'); ?>" class="btn btn-primary">+ Dodaj</a></p>
    <p><a href="<?php echo site_url('duocms/otomoto_parameters'); ?>" class="btn btn-warning">Konfiguracja parametrów</a></p>

    <div class="col-sm-12">
        <form method="GET">
            <div class="col-sm-12">
                Sortowanie:
                <input type="radio" name="sort" value="default" <?= (empty($sort) || $sort === 'default') ? 'checked' : ''; ?>>Domyślne (najnowsze)
                <input type="radio" name="sort" value="date_asc" <?= $sort === 'date_asc' ? 'checked' : ''; ?>> Od najdawniejszych
                <input type="radio" name="sort" value="date_desc" <?= $sort === 'date_desc' ? 'checked' : ''; ?>> Od najwcześniejszych
            </div>
            <div class="col-sm-10">
            <input name="str" value="<?= htmlspecialchars((string) $str, ENT_QUOTES); ?>" placeholder="wpisz poszukiwany samochod" class="form-control">
            </div>
            <div class="col-sm-2">
                <input type="submit" name="send" value="Znajdź" class="form-control">
            </div>
        </form>
    </div>
    <?php
    $limit = !empty($limit) ? (int) $limit : 30;
    $page  = !empty($page) ? (int) $page : 1;
    $total = isset($total) ? (int) $total : (is_array($cars) ? count($cars) : 0);
    $pages = (int) ceil($total / $limit);
    $base  = http_build_query(['str' => $str, 'sort' => $sort]);
    ?>
    <div class="col-sm-12">
        <p>Znaleziono: <strong><?= $total; ?></strong><?= !empty($str) ? ' dla frazy "'.htmlspecialchars((string) $str, ENT_QUOTES).'"' : ''; ?>. Strona <?= $page; ?> z <?= max(1, $pages); ?>.</p>
        <?php if ($pages > 1): ?>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li <?= $page == $i ? 'class="active"' : ''; ?>><a href="?<?= $base; ?>&page=<?= $i; ?>"><?= $i; ?></a></li>
            <?php endfor; ?>
        </ul>
        <?php endif; ?>
    </div>
    <div class="col-sm-12">
    <?php if (!empty($cars)): ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>id</th>
                        <th></th>
                        <th>Data przyjęcia</th>
                        <th width="20%">Nazwa</th>
                        <th width="30%">Opis</th>
                        <th >Cena zakupu</th>
                        <th >Cena sumy części</th>
                        <th>Cena częsci sprzedanych</th>
                        <th></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cars as $car): ?>
                        <tr>
                            <td><?=$car->id;?></td>
                            <td><img src="<?=$car->getUrl('mini');?>" loading="lazy" decoding="async" style="width: 100px; height: auto;"></td>
                            <td><?= $car->production_date; ?></td>
                            <td><?= $car->brand.' '.$car->name; ?></td>
                            <td><?= $car->version;?></td> 
                            <td><?= number_format($car->buy_price, 2,',','');?></td>
                            <td><?= !(empty($car->info['total'])) ? number_format($car->info['total'], 2,',','') : '0,00';?></td>
                            <td><?= !(empty($car->info['income'])) ? number_format($car->info['income'], 2,',','') : '0.00';?></td>
                            <td><a href="<?= site_url('duocms/cars/edit/' . $car->id); ?>"><i class="fa fa-pencil"></i></a></td>
                            <td><a href="<?php echo site_url('duocms/cars/car_delete/' . $car->id); ?>"><i class="fa fa-trash" onclick="javascript:return confirm('Ta operacja jest nieodwracalna. Kontyunować?')"></i></a></td>

                        </tr>
    <?php endforeach;
    ?>
                </tbody>
            </table>
        </div>
        <?php if ($pages > 1): ?>
        <ul class="pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li <?= $page == $i ? 'class="active"' : ''; ?>><a href="?<?= $base; ?>&page=<?= $i; ?>"><?= $i; ?></a></li>
            <?php endfor; ?>
        </ul>
        <?php endif; ?>

<?php else: ?>
        <p>Brak wyników.</p>
    <?php endif; ?>
</div>
</div>

