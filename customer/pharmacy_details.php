<?php
// customer/pharmacy_details.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header('Location: ../login.html');
    exit();
}
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    header("Location: facility_details.php?id=$id");
} else {
    header('Location: dashboard.php');
}
exit();