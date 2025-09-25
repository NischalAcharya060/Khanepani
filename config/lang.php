<?php

// Default language
$default_lang = 'en';

// If not set, store in session
if (!isset($_SESSION['site_lang'])) {
    $_SESSION['site_lang'] = $default_lang;
}

// Switch language if requested
if (isset($_GET['lang'])) {
    $_SESSION['site_lang'] = $_GET['lang'];
}

$current_lang = $_SESSION['site_lang'];

// Check both possible paths
$lang_file_config = __DIR__ . "/lang_{$current_lang}.php"; // config/lang_en.php
$lang_file_folder = __DIR__ . "/../lang/{$current_lang}.php"; // lang/en.php

if (file_exists($lang_file_config)) {
    include $lang_file_config;
} elseif (file_exists($lang_file_folder)) {
    include $lang_file_folder;
} else {
    // fallback to English
    include __DIR__ . "/lang_en.php";
}
