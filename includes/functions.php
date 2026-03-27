<?php
/**
 * functions.php – Core helper functions
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Authentication helpers
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_role'] === 'admin';
}

function isCustomer() {
    return isLoggedIn() && $_SESSION['user_role'] === 'customer';
}

function isBusinessOwner() {
    return isLoggedIn() && $_SESSION['user_role'] === 'business_owner';
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.html');
        exit();
    }
}

// Sanitization
function sanitize($input) {
    global $conn;
    return $conn->real_escape_string(htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8'));
}

// CSRF protection
function generateCsrfToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCsrfToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Formatting
function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' XAF';
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minute' . (floor($diff / 60) > 1 ? 's' : '') . ' ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hour' . (floor($diff / 3600) > 1 ? 's' : '') . ' ago';
    if ($diff < 2592000) return floor($diff / 86400) . ' day' . (floor($diff / 86400) > 1 ? 's' : '') . ' ago';
    return date('M j, Y', $time);
}

// Data helpers
function getFacilityTypes($conn) {
    $result = $conn->query("SELECT DISTINCT facility_type FROM facilities ORDER BY facility_type");
    $types = [];
    if ($result) while ($row = $result->fetch_assoc()) $types[] = $row['facility_type'];
    return $types ?: ['pharmacy', 'hospital', 'clinic', 'other'];
}

function getStockStatuses() {
    return ['in_stock' => 'In Stock', 'low_stock' => 'Low Stock', 'out_of_stock' => 'Out of Stock'];
}

function getVerificationStatuses() {
    return ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
}

function getRoles() {
    return ['customer' => 'Customer', 'business_owner' => 'Business Owner', 'admin' => 'Admin'];
}

function getProductsCategories() {
    return ['Pain Relief', 'Antibiotics', 'Vitamins', 'Cold & Flu', 'Allergy', 'Digestive', 'First Aid', 'Other'];
}

function getServicesCategories() {
    return ['Consultation', 'Emergency', 'Surgery', 'Maternity', 'Pediatrics', 'Cardiology', 'Radiology', 'Laboratory', 'Dental', 'Other'];
}

// File upload
function uploadFile($file, $targetDir, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'], $maxSize = MAX_UPLOAD_SIZE) {
    if ($file['error'] !== UPLOAD_ERR_OK) return ['success' => false, 'error' => 'Upload error'];
    if ($file['size'] > $maxSize) return ['success' => false, 'error' => 'File too large'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, $allowedTypes)) return ['success' => false, 'error' => 'Type not allowed'];

    if (!is_dir($targetDir)) mkdir($targetDir, 0755, true);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid() . '_' . time() . '.' . $ext;
    $targetPath = rtrim($targetDir, '/') . '/' . $newName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => $targetPath, 'filename' => $newName];
    }
    return ['success' => false, 'error' => 'Failed to move file'];
}

// Logging
function logActivity($action, $details = '') {
    $logFile = __DIR__ . '/../logs/activity.log';
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user = $_SESSION['user_id'] ?? 'guest';
    file_put_contents($logFile, "[$timestamp] User: $user | IP: $ip | Action: $action | Details: $details" . PHP_EOL, FILE_APPEND | LOCK_EX);
}