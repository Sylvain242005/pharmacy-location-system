<?php
// api/get_facilities.php
header('Content-Type: application/json');
include '../db.php';

$lat = $_GET['lat'] ?? 0;
$lng = $_GET['lng'] ?? 0;
$type = $_GET['type'] ?? 'all';
$radius = $_GET['radius'] ?? 10; // km

// Haversine formula to calculate distance
$sql = "SELECT 
            facility_id,
            name,
            address,
            latitude,
            longitude,
            phone,
            facility_type,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * 
            cos(radians(longitude) - radians(?)) + sin(radians(?)) * 
            sin(radians(latitude)))) AS distance 
        FROM facilities 
        WHERE verified = 1";

if ($type != 'all') {
    $sql .= " AND facility_type = ?";
}

$sql .= " HAVING distance < ? ORDER BY distance LIMIT 50";

$stmt = $conn->prepare($sql);

if ($type != 'all') {
    $stmt->bind_param("dddsi", $lat, $lng, $lat, $type, $radius);
} else {
    $stmt->bind_param("dddi", $lat, $lng, $lat, $radius);
}

$stmt->execute();
$result = $stmt->get_result();

$facilities = [];
while ($row = $result->fetch_assoc()) {
    $facilities[] = $row;
}

echo json_encode(['success' => true, 'facilities' => $facilities]);

$stmt->close();
$conn->close();
?>