<?php
// SOURCE: /home6/desal/public_html/application/config/routes.php
// FETCHED: 2026-05-27 ~10:16 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2021-08-11 05:56:19, size 4924 B

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$route['default_controller'] = "home";
$route['404_override'] = '';

// Jeżeli są wersje językowe
$route['(\w{2})/duocms'] = 'duocms/login';
$route['(\w{2})/o-nas'] = 'page/index/1';
$route['(\w{2})/wspolpraca'] = 'page/index/27';
$route['(\w{2})/platnosci'] = 'page/index/28';
$route['(\w{2})/wysylka'] = 'page/index/29';
$route['(\w{2})/wymiany-i-zwroty'] = 'page/index/33';
$route['(\w{2})/reklamacje'] = 'page/index/34';
$route['(\w{2})/cennik'] = 'merchant/links';
$route['(\w{2})/mapa-strony'] = 'merchant/links';
$route['(\w{2})/zlomowanie'] = 'page/index/36';
$route['(\w{2})/transport'] = 'page/index/37';

$route['(\w{2})/samochody'] = 'oferta/samochody';
$route['(\w{2})/produkty'] = 'oferta/index';
$route['(\w{2})/logowanie'] = 'account/login';
$route['(\w{2})/rejestracja'] = 'account/register';
$route['(\w{2})/wyszukiwarka'] = 'oferta/search';
$route['(\w{2})/wyszukiwarka/(:num)'] = 'oferta/search//$2';
$route['(\w{2})/Wyszukiwarka/ajax_search_prompt/(:any)'] = 'oferta/ajax_search_prompt/$2';
$route['(\w{2})/wyszukiwarka/ajax_search_prompt2'] = 'oferta/ajax_search_prompt2';

$route['(\w{2})/nowosci'] = 'oferta/show_special/new';
$route['(\w{2})/promocje'] = 'oferta/show_special/promo';
$route['(\w{2})/bestseller'] = 'oferta/show_special/bestseller';

$route['(\w{2})/aktualnosci/(:num)-(:any)'] = 'aktualnosci/pokaz/$2';
$route['(\w{2})/aktualnosci'] = 'aktualnosci/index';

$route['(\w{2})/koszyk'] = 'zamowienie/basket';
$route['(\w{2})/zamowienia'] = 'oferta/index';

$route['(\w{2})/oferta/category/(:num)'] = 'oferta/category/$2';
$route['(\w{2})/oferta/category/(:num)-(:any)/(:num)'] = 'oferta/category/$2/$4';
$route['(\w{2})/oferta/category/(:num)-(:any)'] = 'oferta/category/$2';
$route['(\w{2})/oferta/(:num)-(:any)'] = 'oferta/category/$2';
$route['(\w{2})/oferta/kategoria/(:num)'] = 'oferta/category/$2';
$route['(\w{2})/oferta/kategoria/(:num)-(:any)/(:num)'] = 'oferta/category/$2/$4';
$route['(\w{2})/oferta/kategoria/(:num)-(:any)'] = 'oferta/category/$2';
$route['(\w{2})/oferta/(:num)-(:any)'] = 'oferta/category/$2';

$route['(\w{2})/wyprzedaz'] = 'oferta/wyprzedaz';
$route['(\w{2})/wyprzedaz/(:num)'] = 'oferta/wyprzedaz/$2';
$route['(\w{2})/page/(:num)'] = 'page/index/$2';
$route['(\w{2})/page/(:num)-(:any)'] = 'page/index/$2';

$route['(\w{2})/polityka-prywatnosci'] = 'page/index/5';
$route['(\w{2})/regulamin'] = 'page/index/35';

$route['(\w{2})/product/(:num)'] = 'oferta/product/$2';
$route['(\w{2})/product/(:num)-(:any)'] = 'oferta/product/$2';
$route['(\w{2})/produkt/(:num)'] = 'oferta/product/$2';
$route['(\w{2})/produkt/(:num)-(:any)'] = 'oferta/product/$2';

$route['(\w{2})/duocms/products/(:num)'] = 'duocms/products/index/$2';
$route['(\w{2})/duocms/products'] = 'duocms/products/index/1';

$route['(\w{2})/duocms/orders/(:num)'] = 'duocms/orders/index/$2';
// Poniższe odkomentować wyłącznie w przypadku wersji językowych
$route['(\w{2})/(.*)'] = '$2';
$route['(\w{2})'] = $route['default_controller'];

/* End of file routes.php */
/* Location: ./application/config/routes.php */
