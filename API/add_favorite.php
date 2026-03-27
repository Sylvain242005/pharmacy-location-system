<?php
// api/add_favorite.php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only logged‑in users can add favorites
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['facility_id']) || !is_numeric($input['facility_id'])) {
    echo json_encode(['success' => false, 'message' => 'Facility ID is required']);
    exit();
}

$user_id = $_SESSION['user_id'];
$facility_id = (int)$input['facility_id'];

// Check if the facility exists and is verified
$check_fac = $conn->prepare("SELECT facility_id FROM facilities WHERE facility_id = ? AND verified = 1");
$check_fac->bind_param('i', $facility_id);
$check_fac->execute();
$check_fac->store_result();

if ($check_fac->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Facility not found or not verified']);
    exit();
}
$check_fac->close();

// Check if already favorited
$check_fav = $conn->prepare("SELECT favorite_id FROM favorites WHERE user_id = ? AND facility_id = ?");
$check_fav->bind_param('ii', $user_id, $facility_id);
$check_fav->execute();
$check_fav->store_result();

if ($check_fav->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Already in favorites']);
    exit();
}
$check_fav->close();

// Insert favorite
$stmt = $conn->prepare("INSERT INTO favorites (user_id, facility_id, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param('ii', $user_id, $facility_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Added to favorites']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>