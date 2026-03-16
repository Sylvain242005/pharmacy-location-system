<?php
// api/save_location.php
header('Content-Type: application/json');
include '../db.php';

$data = json_decode(file_get_contents('php://input'), true);

$user_id = $data['user_id'] ?? 0;
$latitude = $data['latitude'] ?? 0;
$longitude = $data['longitude'] ?? 0;

if ($user_id && $latitude && $longitude) {
    $sql = "INSERT INTO location_history (user_id, latitude, longitude) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idd", $user_id, $latitude, $longitude);
    $stmt->execute();
}

echo json_encode(['success' => true]);

$conn->close();
?>