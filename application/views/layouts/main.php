<!DOCTYPE html>
<html lang="<?php echo LANG; ?>">
    <head>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="Description" content="<?php echo $this->meta_desc; ?>" />
        <meta name="Keywords" content="<?php echo $this->keywords; ?>" />
        <meta name="Author" content="Desal — Zakład Złomowania Pojazdów" />

        <!-- AURANET_SEO_BLOCK 2026-05-29 -->
        <meta name="description" content="<?php echo !empty($this->meta_desc) ? htmlspecialchars($this->meta_desc) : 'Desal — autoryzowany zakład złomowania pojazdów. 27 000+ używanych części samochodowych z ponad 300 marek aut. Wysyłka kurierem i paczkomatami InPost.'; ?>" />
        <link rel="canonical" href="<?php $CI =& get_instance(); $CI->load->helper('url'); echo current_url(); ?>" />
        <meta property="og:title" content="<?php echo $this->meta_title; ?>" />
        <meta property="og:description" content="<?php echo !empty($this->meta_desc) ? htmlspecialchars($this->meta_desc) : 'Części używane do samochodów — autoryzowany zakład złomowania, 27k pozycji w katalogu.'; ?>" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="<?php echo current_url(); ?>" />
        <meta property="og:site_name" content="DESAL" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" content="<?php echo $this->meta_title; ?>" />
        <!-- GSC verification meta tag — wstawić po założeniu property w Search Console -->
        <meta name="google-site-verification" content="luFWLsuO2V_diLrcKFhQvWDy_i6EuT9RXO3k2QHHnGQ" />
        <!-- /AURANET_SEO_BLOCK -->

        <title><?php echo $this->meta_title; ?></title>

        <link href="https://fonts.googleapis.com/css?family=Lora:400,700%7COpen+Sans:300,400,400i,600,700,800&amp;subset=latin-ext" rel="stylesheet">
        <link href="<?php echo assets('img/favicon.ico'); ?>" type="image/x-icon" rel="icon" />
        <link rel="stylesheet" href="<?php echo assets('css/bootstrap.min.css'); ?>" type="text/css"  />      
        <link rel="stylesheet" href="<?php echo assets('plugins/toastr/toastr.min.css'); ?>" type="text/css"  />   
        <link rel="stylesheet" href="<?php echo assets('css/jquery.bxslider.min.css'); ?>" type="text/css"  />
        <link rel="stylesheet" href="<?php echo assets('css/hamburgers.css'); ?>" type="text/css" />
        <link rel="stylesheet" href="<?php echo assets('plugins/lightbox2-master/dist/css/lightbox.min.css'); ?>" type="text/css"  />
        <link rel="stylesheet" href="<?php echo assets('plugins/cookie-policy/css/cookie-policy.min.css'); ?>" type="text/css"  />
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css" integrity="sha384-hWVjflwFxL6sNzntih27bfxkr27PmbbK/iSvJ+a4+0owXq79v+lsFkW54bOGbiDQ" crossorigin="anonymous">
        <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.min.css" >
        <link rel="stylesheet" type="text/css" href="<?= assets('plugins/slick/slick.css'); ?>"/>
        <link rel="stylesheet" type="text/css" href="<?= assets('plugins/slick/slick-theme.css'); ?>"/>
        <link rel="stylesheet" type="text/css" href="<?= assets('plugins/EasyAutocomplete-1.3.5/easy-autocomplete.min.css'); ?>"/>
        <link rel="stylesheet" type="text/css" href="<?= assets('plugins/EasyAutocomplete-1.3.5/easy-autocomplete.themes.min.css'); ?>"/>
        <link rel="stylesheet" href="<?php echo assets('css/default.css'); ?>" type="text/css"  />
        <link rel="stylesheet" href="<?php echo assets('css/style.css'); ?>" type="text/css" />

        <script async   src="<?php echo assets('js/html5shiv.js'); ?>"></script>
        <script    type="text/javascript" src="<?php echo assets('js/jquery-3.2.0.min.js'); ?>"></script>
        <script  src="<?= assets('js/jquery-ui.min.js'); ?>"></script>
        <script src="https://malsup.github.io/jquery.cycle2.js"></script>
        <script src="https://malsup.github.io/jquery.cycle2.swipe.js"></script>
        <script src="https://malsup.github.io/ios6fix.js"></script>
        <script  async  src="<?php echo assets('plugins/toastr/toastr.min.js'); ?>"></script>


        <script  async  src='https://www.google.com/recaptcha/api.js'></script>

        <?php foreach ($this->styles as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    </head>


    <script>
        var domReadyQueue = [];
    </script>
</head>
<body>
    <header class="naglowek-calosc">        
        <div class="naglowek">
            <div class="naglowek-gora">
                <div class="container"><span><?= (new CustomElementModel('1'))->getField('inflonia techniczna tekst'); ?></span> <?= (new CustomElementModel('1'))->getField('infolinia techniczna telefon'); ?></div> 
            </div>
            <div class="naglowek-poz">         
                <div class="container container-nag">
                    <a href="<?= site_url(); ?>"> 
                        <img class="naglowek-logo" src="<?= (new CustomElementModel('1'))->getField('Logo'); ?>" alt="" title=""> 
                    </a>
                    <div class="naglowek-ikony-szukaj">
                        <?php if (empty($this->session->userdata['login'])) { ?>
                            <a href="<?= site_url('rejestracja'); ?>" class="naglowek-ikony-rejestracja"><?= (new CustomElementModel('1'))->getField('Rejestracja'); ?></a>
                        <?php } ?>
                        <a href="<?= site_url('account'); ?>" class="naglowek-ikony-konto"><?= (new CustomElementModel('10'))->getField('Moje konto'); ?></a>
                        <a href="<?= site_url('koszyk'); ?>" class="naglowek-ikony-koszyk"><?= (new CustomElementModel('1'))->getField('twoj koszyk'); ?>: 
                            <?php
                            $quantity = basket('quantity');
                            $price = basket('price');
                            if ($quantity > 0) {
                                ?>
                                ( <span><?= $quantity; ?></span>  <?= number_format($price, 2, ',', ' '); ?> <?= (new CustomElementModel('16'))->getField('waluta'); ?> )
                                <?php
                            } else {
                                echo (new CustomElementModel('1'))->getField('jest pusty');
                            }
                            ?></a>
                        <div class="naglowek-szukaj">
                            <form action="<?= site_url('oferta/main_search'); ?>" method="POST" id="szukajka-form">
                                <div class="naglowek-szukaj-input">
                                    <input type="text" name="search" id="szukajka" 
                                           value="<?= !empty($this->session->userdata('main_search')) ? $this->session->userdata('main_search')['search'] : '';?>"
                                           placeholder="<?= (new CustomElementModel('1'))->getField('szukany produkt'); ?>">
                                </div>
                                <div class="naglowek-szukaj-submit">
                                    <input type="submit" name="send" id="szukaj" value="">
                                </div>
                                <div class="kasuj"></div>                     
                            </form>
                        </div>
                    </div>
                    <div class="menu-strony-przycisk-mobilny">
                    </div>           
                </div>
                <div class="naglowek-hide">
                    <div class="naglowek-menu">
                        <div class="container">
                            <nav class="menu-strony-nav">         
                                <?= get_menu(!empty($is_home), 1, "menu-strony", ""); ?>             
                            </nav>
                        </div>  
                    </div>
                    <div class="naglowek-wysz">
                        <div class="container">
                            <div class="naglowek-wysz-poz-jeden"><?= (new CustomElementModel('1'))->getField('sprawdz czy mamy czesci do twojego auta'); ?></div>
<?php $car_search_sess_data = $this->session->userdata('samochody_search');
if(!empty($car_search_sess_data)){
    $session_brand = $car_search_sess_data['marka'];
    $session_model = $car_search_sess_data['model'];
}?>
                            <div class="naglowek-wysz-poz-dwa">
                                <form action="<?= site_url('oferta/samochody_search'); ?>" method="POST" id="szukajka-form2">
                                    <div class="naglowek-wysz-poz-dwa-select">
                                        <select name="marka" id="marka">
                                            <option value="">Marka</option>
                                            <?php
                                            $car_brands = get_car_brands_list();
                                            foreach ($car_brands as $cb):
                                                ?>
                                                <option value="<?= $cb ?>" <?= (!empty($session_brand) && $session_brand == $cb) ? 'selected' : '';?>><?= $cb ?></option>
                                                <?php
                                            endforeach;
                                            ?>

                                        </select>
                                    </div>
                                    <div class="naglowek-wysz-poz-dwa-label">
                                        <label for="model">Model:</label>
                                    </div>                      
                                    <div class="naglowek-wysz-poz-dwa-input">

                                        <input type="text" name="model" id="model" 
                                               value="<?= (!empty($session_model)) ? $session_model : '';?>"
                                               placeholder="<?= (new CustomElementModel('1'))->getField('Wpisz poszukiwany produkt'); ?>">

                                    </div> 
                                    <div class="naglowek-wysz-poz-dwa-input-submit">
                                        <input type="submit" name="szukaj" id="szukaj" value="<?= (new CustomElementModel('1'))->getField('szukaj'); ?>">
                                    </div>                                        
                                </form>
                            </div>
                        </div>  
                    </div>
                </div>                            
            </div>
        </div>     
    </div>




    <?php
    if (!empty($is_home)) {
        $this->load->view('partials/wizerunek');
    }
    ?>
</header> 




<?php $this->load->view($this->view_file); ?>
<section class="sekcja-linki">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12"> 
                <h5 class="sekcja-linki-nag"><?= (new CustomElementModel('3'))->getField('informacje'); ?></h5>
<?= get_menu(!empty($is_home), 2, "", ""); ?>               
            </div> 
            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12"> 
                <h5 class="sekcja-linki-nag"><?= (new CustomElementModel('3'))->getField('pomoc'); ?></h5>
<?= get_menu(!empty($is_home), 3, "", ""); ?>               
            </div>         
            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12"> 
                <h5 class="sekcja-linki-nag"><?= (new CustomElementModel('3'))->getField('moje konto'); ?></h5>
<?= get_menu(!empty($is_home), 4, "", ""); ?>               
            </div> 
            <div class="col-lg-3 col-md-3 col-sm-3 col-xs-12"> 
                <h5 class="sekcja-linki-nag"><?= (new CustomElementModel('3'))->getField('kontakt'); ?></h5>
                <?= (new CustomElementModel('3'))->getField('kontakt adres'); ?>
                <?= (new CustomElementModel('3'))->getField('kontakt dane'); ?>
<?= (new CustomElementModel('3'))->getField('kontakt otwarte'); ?>   
            </div>                  
        </div>         
    </div>            
</section>
<footer>
    <div class="stopka-projekt">
        Projekt i wykonanie strony www: DUONET
    </div>      
</footer>    
<!-- AURANET_GA4_BLOCK 2026-05-29 — UA-79935351-1 (zombie od 2024-07-01) zastąpiony GA4 -->
<!-- TODO: wymienić G-QCP0Y0SQ3S na realny GA4 Measurement ID po założeniu property -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-QCP0Y0SQ3S"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-QCP0Y0SQ3S');
</script>


<script    type="text/javascript" src="<?php echo assets('js/jquery.bxslider.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo assets('js/chosen.jquery.js'); ?>"></script>
<script    type="text/javascript" src="<?php echo assets('js/bootstrap.min.js'); ?>"></script>
<script    type="text/javascript" src="<?php echo assets('js/placeholders.min.js'); ?>"></script>
<script type="text/javascript" src="<?php echo assets('js/jquery.hoverIntent.js'); ?>"></script>
<script    src="<?php echo assets('plugins/lightbox2-master/dist/js/lightbox.min.js'); ?>"></script>
<script>
        var polityka_komunikat = '<?= trim(nl2br((new CustomElementModel('20'))->getField('informacja')->value)); ?>';
</script>
<script    type="text/javascript" src="<?php echo assets('plugins/cookie-policy/js/cookie-policy.js'); ?>"></script>
<script    type="text/javascript" src="<?php echo assets('plugins/EasyAutocomplete-1.3.5/jquery.easy-autocomplete.min.js'); ?>"></script>
<script    type="text/javascript" src="<?php echo assets('plugins/slick/slick.min.js'); ?>"></script>
<script>
<?php
getAlerts();
?>
//przekaz urli
        var base_url = '<?= site_url(); ?>';
</script>
<script type="text/javascript" src="<?php echo assets('js/script.js'); ?>"></script>
<script type="text/javascript" src="<?php echo assets('js/script_dev.js'); ?>"></script>


</body>
</html>



