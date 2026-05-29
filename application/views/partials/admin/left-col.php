<!-- /. NAV TOP  -->
<nav class="navbar-default navbar-side" role="navigation">
    <div class="sidebar-collapse">
        <ul class="nav" id="side-menu">

            <li class="<?php echo $activePage == 'dashboard' ? 'active-link' : null; ?>">
                <a href="<?php echo site_url('duocms/dashboard'); ?>">
                    <i class="fa fa-desktop "></i> Pulpit
                </a>
            </li>
            <?php if (get_option('admin_site')) { ?>
                <li class="">
                    <a href="#" >
                        <i class="fa fa-globe" aria-hidden="true"></i> Strona <span class="caret"></span></a>
                    <ul class="nav nav-second-level collapse submenu <?php
                    if (in_array($activePage, array('wizerunek', 'pages', 'partnerzy', 'menu', 'custom_elements'))) {
                        echo 'in';
                    }
                    ?>" aria-expanded="true">
                            <?php if (get_option('admin_slider')) { ?>
                            <li class="<?php echo $activePage == 'wizerunek' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/wizerunek'); ?>">
                                    <i class="fa fa-sliders" aria-hidden="true"></i> Slider
                                </a>
                            </li>
                        <?php } ?> 
                        <?php if (get_option('admin_pages')) { ?>
                            <li class="<?php echo $activePage == 'pages' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/pages/get'); ?>">
                                    <i class="fa fa-clipboard" aria-hidden="true"></i> Podstrony
                                </a>
                            </li>
                        <?php } ?>  

                        <?php if (get_option('admin_partners')) { ?>
                            <li class="<?php echo $activePage == 'partnerzy' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/partnerzy/index'); ?>">
                                    <i class="fa fa-magnet" aria-hidden="true"></i> Partnerzy
                                </a>
                            </li>
                        <?php } ?>  
                        <?php if (get_option('admin_menu')) { ?>
                            <li class="<?php echo $activePage == 'menu' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/menu'); ?>">
                                    <i class="fa fa-cog" aria-hidden="true"></i>  Menu
                                </a>
                            </li>
                        <?php } ?>  
                        <?php if (get_option('admin_custom_elements')) { ?>
                            <li class="<?php echo $activePage == 'custom_elements' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/custom_elements'); ?>">
                                    <i class="fa fa-suitcase" aria-hidden="true"></i> Inne elementy
                                </a>
                            </li>
                        <?php } ?>  
                    </ul>
                </li>
            <?php } ?>
            <?php if (get_option('admin_gallery')) { ?>
                <li class="<?php echo $activePage == 'gallery' ? 'active-link' : null; ?>">
                    <a href="<?php echo site_url('duocms/gallery'); ?>">
                        <i class="fa fa-picture-o" aria-hidden="true"></i> Galeria
                    </a>
                </li>
            <?php } ?>         
                <li class="<?php echo $activePage == 'logs' ? 'active-link' : null; ?>">
                    <a href="<?php echo site_url('duocms/logs'); ?>">
                        <i class="fa fa-picture-o" aria-hidden="true"></i> Logi
                    </a>
                </li>
            <?php if (get_option('admin_shop')) { ?>       
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fa fa-shopping-cart" aria-hidden="true"></i> Sklep <span class="caret"></span></a>
                    <ul  class="nav nav-second-level collapse submenu" aria-expanded="true"> 
                        <?php if (get_option('admin_offer_categories')) { ?>
                            <li class="<?php echo $activePage == 'offer_categories' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/offer_categories'); ?>">
                                    <i class="fa fa-folder-open" aria-hidden="true"></i> Kategorie
                                </a>
                            </li>
                        <?php } ?>  
                        <?php if (get_option('admin_products')) { ?>
                            <li class="<?php echo $activePage == 'products' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/products'); ?>">
                                    <i class="fa fa-list-alt" aria-hidden="true"></i> Produkty
                                </a>
                            </li>
                        <?php } ?>  
                        <?php if (get_option('admin_orders')) { ?>
                            <li class="<?php echo $activePage == 'orders' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/orders'); ?>">
                                    <i class="fa fa-shopping-cart" aria-hidden="true"></i> Zamówienia
                                </a>
                            </li>
                        <?php } ?>  
                        <?php if (get_option('admin_delivery')) { ?>
                            <li class="<?php echo $activePage == 'delivery' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/delivery'); ?>">
                                    <i class="fa fa-truck" aria-hidden="true"></i> Dostawa
                                </a>
                            </li>
                        <?php } ?>  
                            <li class="<?php echo $activePage == 'cars' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/cars'); ?>">
                                    <i class="fa fa-eye" aria-hidden="true"></i> Samochody
                                </a>
                            </li>
                            <li class="<?php echo $activePage == 'otomoto' ? 'active-link' : null; ?>">
                                <a href="<?php echo site_url('duocms/Otomoto'); ?>">
                                    <i class="fa fa-bullhorn" aria-hidden="true"></i> Otomoto
                                </a>
                            </li>
                    </ul>
                </li>

            <?php } ?>

            <li class="<?php echo $activePage == 'templates' ? 'active-link' : null; ?>">
                <a href="<?php echo site_url('duocms/templates'); ?>">
                    <i class="fa fa-columns" aria-hidden="true"></i> Szablony
                </a>
            </li>





            <?php if (get_option('admin_news')) { ?>
                <li class="<?php echo $activePage == 'news' ? 'active-link' : null; ?>">
                    <a href="<?php echo site_url('duocms/news'); ?>">
                        <i class="fa fa-newspaper-o" aria-hidden="true"></i> Aktualności
                    </a>
                </li>
            <?php } ?>  
            <?php if (get_option('admin_newsletter')) { ?>
                <li class="<?php echo $activePage == 'newsletter' ? 'active-link' : null; ?>">
                    <a href="<?php echo site_url('duocms/newsletter'); ?>">
                        <i class="fa fa-envelope" aria-hidden="true"></i> Newsletter
                    </a>
                </li>
            <?php } ?> 

            <?php if (get_option('admin_users')) { ?>
                <li class="<?php echo $activePage == 'users' ? 'active-link' : null; ?>">
                    <a href="<?php echo site_url('duocms/users'); ?>">
                        <i class="fa fa-users" aria-hidden="true"></i> Użytkownicy
                    </a>
                </li>
            <?php } ?> 
            <?php if (get_option('admin_recruitment')) { ?>
                <li class="<?php echo $activePage == 'recruitment' ? 'active-link' : null; ?>">
                    <a href="<?php echo site_url('duocms/recruitment'); ?>">
                        <i class="fa fa-male" aria-hidden="true"></i> Rekrutacja
                    </a>
                </li>
            <?php } ?>
            <?php if (get_option('admin_agents')) { ?>
                <li class="<?php echo $activePage == 'agents' ? 'active-link' : null; ?>">
                    <a href="<?php echo site_url('duocms/agents'); ?>">
                        <i class="fa fa-male" aria-hidden="true"></i> Agenci
                    </a>
                </li>
            <?php } ?>  
            <?php if (get_option('admin_configuration')) { ?>
                <li class="<?php echo $activePage == 'configuration' ? 'active-link' : null; ?>">
                    <a href="<?php echo site_url('duocms/configuration'); ?>">
                        <i class="fa fa-cogs" aria-hidden="true"></i> Ustawienia
                    </a>
                </li>
            <?php } ?>  

            <li class="<?php echo $activePage == 'change-password' ? 'active-link' : null; ?>">
                <a href="<?php echo site_url('duocms/change_password'); ?>">
                    <i class="fa fa-lock" aria-hidden="true"></i> Zmiana hasła
                </a>
            </li>
        </ul>
    </div>

</nav>

