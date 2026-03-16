<?php
// admin/approve_facility.php
require_once __DIR__ . '/../includes/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

$facility_id = $_GET['id'] ?? 0;

if ($facility_id) {
    // Approve the facility
    $sql = "UPDATE facilities SET verified = 1 WHERE facility_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $facility_id);
    
    if ($stmt->execute()) {
        // Also get the owner and approve them if needed
        $get_owner = "SELECT owner_id FROM facilities WHERE facility_id = ?";
        $stmt2 = $conn->prepare($get_owner);
        $stmt2->bind_param("i", $facility_id);
        $stmt2->execute();
        $owner = $stmt2->get_result()->fetch_assoc();
        
        if ($owner) {
            // Approve the business owner too
            $update_owner = "UPDATE business_owners SET verification_status = 'approved' WHERE owner_id = ?";
            $stmt3 = $conn->prepare($update_owner);
            $stmt3->bind_param("i", $owner['owner_id']);
            $stmt3->execute();
        }
        
        header("Location: dashboard.php?msg=" . urlencode("Facility approved successfully"));
        exit();
    }
}

header("Location: dashboard.php?error=" . urlencode("Failed to approve facility"));
?>