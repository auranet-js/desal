<?php
// SOURCE: /home6/desal/public_html/application/config/database.php
// FETCHED: 2026-05-27 ~10:16 UTC
// VIA: MCP desal-duocms read_file
// READ-ONLY MIRROR — nie edytuj bez zsynchronizowania z produkcją
// Oryginalne mtime: 2021-08-11 06:01:46
// SEKRETY: prawdziwe wartości username/password w ~/secrets/desal/db.env
// Tutaj placeholdery (__DB_*__) — NIE WGRYWAJ TEGO PLIKU NA PRODUKCJĘ BEZ PODMIANY

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$active_group = 'default';
$active_record = TRUE;

$db['default']['hostname'] = 'localhost';
$db['default']['username'] = '__DB_USER__';
$db['default']['password'] = '__DB_PASSWORD__';
$db['default']['database'] = 'desal_duonet';
$db['default']['dbdriver'] = 'mysqli';
$db['default']['dbprefix'] = 'duo_';
$db['default']['pconnect'] = TRUE;
$db['default']['db_debug'] = TRUE;
$db['default']['cache_on'] = FALSE;
$db['default']['cachedir'] = '';
$db['default']['char_set'] = 'utf8';
$db['default']['dbcollat'] = 'utf8_unicode_ci';
$db['default']['swap_pre'] = '';
$db['default']['autoinit'] = TRUE;
$db['default']['stricton'] = FALSE;

/* End of file database.php */
/* Location: ./application/config/database.php */
