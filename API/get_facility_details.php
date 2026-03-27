<?php
// api/get_facility_details.php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/db.php';

$facility_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($facility_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid facility ID']);
    exit();
}

// Fetch facility details
$stmt = $conn->prepare("SELECT * FROM facilities WHERE facility_id = ? AND verified = 1");
$stmt->bind_param('i', $facility_id);
$stmt->execute();
$result = $stmt->get_result();
$facility = $result->fetch_assoc();

if (!$facility) {
    echo json_encode(['success' => false, 'message' => 'Facility not found']);
    exit();
}

// Fetch products if pharmacy
if ($facility['facility_type'] === 'pharmacy') {
    $prod_stmt = $conn->prepare("SELECT product_id, name, price, stock_status, expiry_date, description FROM products WHERE facility_id = ? ORDER BY name");
    $prod_stmt->bind_param('i', $facility_id);
    $prod_stmt->execute();
    $products = $prod_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $facility['products'] = $products;
    $facility['services'] = []; // no services for pharmacy
} 
// Fetch services if hospital or clinic
elseif (in_array($facility['facility_type'], ['hospital', 'clinic'])) {
    $serv_stmt = $conn->prepare("SELECT service_id, name, description, specialist, cost_estimate, duration FROM services WHERE facility_id = ? ORDER BY name");
    $serv_stmt->bind_param('i', $facility_id);
    $serv_stmt->execute();
    $services = $serv_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $facility['services'] = $services;
    $facility['products'] = [];
} else {
    $facility['products'] = [];
    $facility['services'] = [];
}

// Remove sensitive data (none)
echo json_encode(['success' => true, 'facility' => $facility]);
?>