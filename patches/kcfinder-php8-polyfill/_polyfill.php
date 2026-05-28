<?php
/**
 * KCFinder 2.51 PHP 8 polyfill — hot-fix
 *
 * Przywraca funkcje usunięte w PHP 8.x które są używane przez KCFinder 2.51.
 * Tymczasowe rozwiązanie do czasu wymiany KCFindera na nowoczesny uploader (elFinder).
 *
 * @see ~/projekty/_duocms-playbook/KCFINDER_REPLACEMENT.md
 * @see ~/projekty/desal/docs/KCFINDER_TEST_RESULT_2026-05-28.md
 *
 * @package    KCFinder polyfill
 * @author     Auranet
 * @date       2026-05-28
 */

// each() — usunięte w PHP 8.0
// Używane w lib/class_gd.php:55-56 przy resize obrazu
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

// Polyfill nie wymaga zmian w innych miejscach.
// __autoload() w core/autoload.php:48 jest deklarowane jako globalna funkcja
// — PHP 8.3 nadal akceptuje (z Deprecated warning), więc działa. Usunąć dopiero w PHP 9.0.
