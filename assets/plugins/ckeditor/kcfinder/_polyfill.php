<?php
/**
 * KCFinder 2.51 PHP 8 polyfill — hot-fix Auranet 2026-05-28
 *
 * Naprawia:
 *  1) chdir — KCFinder polega na cwd = katalog kcfinder dla relative requires
 *  2) each() — usunięte w PHP 8.0 (używane w lib/class_gd.php:55-56)
 *  3) Magic __autoload() — usunięte w PHP 8.0 (rejestrujemy SPL handler)
 *
 * Wymagane też: zakomentowanie `function __autoload()` w core/autoload.php
 * (PHP 8.0+ odmawia kompilacji deklaracji globalnej __autoload — E_COMPILE_ERROR).
 *
 * Plan długoterminowy: wymiana KCFindera na elFinder (patrz
 * ~/projekty/_duocms-playbook/KCFINDER_REPLACEMENT.md).
 */

chdir(__DIR__);

if (!function_exists('each')) {
    function each(&$array) {
        $key = key($array);
        if ($key === null || $key === false) {
            return false;
        }
        $value = current($array);
        next($array);
        return [
            0 => $key,
            'key' => $key,
            1 => $value,
            'value' => $value,
        ];
    }
}

spl_autoload_register(function ($class) {
    $kc = __DIR__;
    if ($class === 'uploader') {
        require $kc . '/core/uploader.php';
    } elseif ($class === 'browser') {
        require $kc . '/core/browser.php';
    } elseif (file_exists($kc . "/core/types/{$class}.php")) {
        require $kc . "/core/types/{$class}.php";
    } elseif (file_exists($kc . "/lib/class_{$class}.php")) {
        require $kc . "/lib/class_{$class}.php";
    } elseif (file_exists($kc . "/lib/helper_{$class}.php")) {
        require $kc . "/lib/helper_{$class}.php";
    }
});
