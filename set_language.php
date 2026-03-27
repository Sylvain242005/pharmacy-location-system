<?php
// set_language.php
session_start();
if (isset($_GET['lang'])) {
    $lang = $_GET['lang'];
    setcookie('lang', $lang, time() + 30 * 24 * 3600, '/'); // 30 days
    $_SESSION['lang'] = $lang;
}
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
header('Location: ' . $redirect);
exit();