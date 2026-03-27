<?php
// includes/language.php
session_start();

// Available languages
$available_langs = ['en', 'fr'];

// Default language
$default_lang = 'en';

// Get current language from session or cookie
if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $available_langs)) {
    $current_lang = $_SESSION['lang'];
} elseif (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], $available_langs)) {
    $current_lang = $_COOKIE['lang'];
} else {
    // Try to detect browser language (optional)
    $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '', 0, 2);
    $current_lang = in_array($browser_lang, $available_langs) ? $browser_lang : $default_lang;
}

// Load the language file
$lang_file = __DIR__ . '/../lang/' . $current_lang . '.php';
if (file_exists($lang_file)) {
    require $lang_file;
} else {
    // Fallback to English
    require __DIR__ . '/../lang/en.php';
}

// Function to translate a key
function __($key, $default = '') {
    global $lang;
    return isset($lang[$key]) ? $lang[$key] : $default;
}