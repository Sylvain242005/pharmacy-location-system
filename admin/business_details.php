<?php
// admin/business_details.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isAdmin()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

$owner_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($owner_id == 0) {
    header('Location: verify_businesses.php');
    exit();
}

// Fetch business details
$query = "SELECT bo.*, u.full_name, u.email, u.phone, u.created_at as user_since
          FROM business_owners bo
          JOIN users u ON bo.user_id = u.user_id
          WHERE bo.owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$business = $result->fetch_assoc();

if (!$business) {
    header('Location: verify_businesses.php');
    exit();
}

// Handle approve/reject actions
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        $update = $conn->prepare("UPDATE business_owners SET verification_status = 'approved', verified_at = NOW() WHERE owner_id = ?");
        $update->bind_param('i', $owner_id);
        if ($update->execute()) {
            // Approve all facilities of this owner
            $updateFac = $conn->prepare("UPDATE facilities SET verified = 1 WHERE owner_id = ?");
            $updateFac->bind_param('i', $owner_id);
            $updateFac->execute();
            $message = "Business approved successfully!";
            $messageType = 'success';
        } else {
            $message = "Error approving business.";
            $messageType = 'danger';
        }
        $update->close();
    } elseif ($action === 'reject') {
        $update = $conn->prepare("UPDATE business_owners SET verification_status = 'rejected' WHERE owner_id = ?");
        $update->bind_param('i', $owner_id);
        if ($update->execute()) {
            $message = "Business rejected.";
            $messageType = 'success';
        } else {
            $message = "Error rejecting business.";
            $messageType = 'danger';
        }
        $update->close();
    }

    // Refresh business data after update
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $business = $result->fetch_assoc();
}

// Fetch facilities owned by this business
$facilities = $conn->query("SELECT * FROM facilities WHERE owner_id = $owner_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Details - PharmaLocator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column; }
        .new-header { background: white; box-shadow: 0 2px 15px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; }
        .logo-link { text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .logo-icon { font-size: 24px; background: linear-gradient(135deg, #2c7da0, #2a9d8f); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; color: white; }
        .logo-text { font-weight: 700; font-size: 18px; color: #1e293b; }
        .logo-text span { color: #64748b; font-weight: 400; font-size: 12px; display: block; line-height: 1.2; }
        .nav-center { display: flex; gap: 32px; }
        .nav-center a { text-decoration: none; color: #475569; font-weight: 500; font-size: 15px; padding: 8px 0; border-bottom: 2px solid transparent; transition: all 0.3s ease; }
        .nav-center a:hover { color: #2c7da0; border-bottom-color: #2c7da0; }
        .nav-center a.active { color: #2c7da0; border-bottom-color: #2c7da0; font-weight: 600; }
        .user-menu { display: flex; align-items: center; gap: 15px; }
        .user-name { display: flex; align-items: center; gap: 8px; background: #f1f5f9; padding: 8px 16px; border-radius: 40px; color: #1e293b; font-weight: 500; font-size: 14px; }
        .logout-btn { text-decoration: none; padding: 8px 16px; background: #fee2e2; color: #dc2626; border-radius: 40px; font-weight: 500; font-size: 14px; transition: all 0.3s; }
        .logout-btn:hover { background: #fecaca; transform: translateY(-2px); }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 30px; flex: 1; width: 100%; }
        .btn-back { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #475569; background: #f1f5f9; padding: 10px 20px; border-radius: 40px; font-size: 14px; font-weight: 500; margin-bottom: 25px; transition: all 0.3s; border: 1px solid #e2e8f0; }
        .btn-back:hover { background: #e2e8f0; transform: translateX(-5px); }
        .detail-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-bottom: 30px; border: 1px solid #f0f0f0; }
        .detail-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; }
        .detail-header h2 { font-size: 28px; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        .detail-header h2 i { color: #2c7da0; }
        .status-badge { padding: 8px 16px; border-radius: 40px; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; }
        .badge.approved { background: #d1fae5; color: #065f46; }
        .badge.pending { background: #fff3cd; color: #856404; }
        .badge.rejected { background: #fee2e2; color: #991b1b; }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 25px 0; }
        .info-item { padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
        .info-label { font-size: 13px; color: #64748b; margin-bottom: 5px; display: flex; align-items: center; gap: 5px; }
        .info-label i { color: #2c7da0; }
        .info-value { font-size: 16px; font-weight: 500; color: #1e293b; }
        .action-buttons { display: flex; gap: 15px; margin-top: 25px; flex-wrap: wrap; }
        .btn { padding: 12px 24px; border: none; border-radius: 40px; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; text-decoration: none; }
        .btn-approve { background: linear-gradient(135deg, #2a9d8f, #1e7a6a); color: white; box-shadow: 0 4px 15px rgba(42,157,143,0.3); }
        .btn-approve:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(42,157,143,0.4); }
        .btn-reject { background: linear-gradient(135deg, #e76f51, #c44536); color: white; box-shadow: 0 4px 15px rgba(231,111,81,0.3); }
        .btn-reject:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(231,111,81,0.4); }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; transform: translateY(-2px); }
        .alert { padding: 16px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-danger { background: #fee2e2; color: #991b1b; border-left: 4px solid #ef4444; }
        .facilities-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .facilities-table th, .facilities-table td { padding: 12px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        .facilities-table th { background: #f8fafc; font-weight: 600; color: #475569; }
        .facilities-table tr:hover { background: #f8fafc; }
        .btn-small { padding: 6px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; text-decoration: none; background: #dbeafe; color: #1e40af; }
        .btn-small:hover { transform: translateY(-2px); filter: brightness(0.95); }
        footer { background: white; padding: 20px 30px; margin-top: 40px; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); }
        .footer-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .footer-copyright { color: #64748b; font-size: 14px; }
        .footer-social { display: flex; gap: 20px; }
        .footer-social a { color: #94a3b8; font-size: 18px; transition: all 0.3s; }
        .footer-social a:hover { color: #2c7da0; transform: translateY(-3px); }
        @media (max-width: 768px) {
            .header-container { flex-direction: column; gap: 15px; padding: 15px; }
            .nav-center { flex-wrap: wrap; justify-content: center; gap: 15px; }
            .user-menu { width: 100%; justify-content: center; }
            .info-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <header class="new-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="../home.html" class="logo-link">
                    <div class="logo-icon"></div>
                    <div class="logo-text">PharmaLocator<span>Admin Portal</span></div>
                </a>
            </div>
            <nav class="nav-center">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_users.php">Users</a>
                <a href="verify_businesses.php">Verifications</a>
                <a href="reports.php">Reports</a>
            </nav>
            <div class="user-menu">
                <span class="user-name"><i class="fas fa-user-shield"></i> <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="verify_businesses.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Verifications</a>

        <div class="detail-card">
            <div class="detail-header">
                <h2><i class="fas fa-store"></i> <?php echo htmlspecialchars($business['business_name']); ?></h2>
                <span class="status-badge badge <?php echo $business['verification_status']; ?>">
                    <i class="fas fa-<?php echo $business['verification_status'] == 'approved' ? 'check-circle' : ($business['verification_status'] == 'pending' ? 'clock' : 'times-circle'); ?>"></i>
                    <?php echo ucfirst($business['verification_status']); ?>
                </span>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="info-grid">
                <div class="info-item"><div class="info-label"><i class="fas fa-tag"></i> Business Type</div><div class="info-value"><?php echo ucfirst($business['business_type']); ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-user"></i> Owner Name</div><div class="info-value"><?php echo htmlspecialchars($business['full_name']); ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-envelope"></i> Email</div><div class="info-value"><?php echo htmlspecialchars($business['email']); ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-phone"></i> Phone</div><div class="info-value"><?php echo htmlspecialchars($business['phone']); ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-map-marker-alt"></i> Address</div><div class="info-value"><?php echo htmlspecialchars($business['address'] ?? 'N/A'); ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-id-card"></i> Registration Number</div><div class="info-value"><?php echo htmlspecialchars($business['registration_number'] ?? 'N/A'); ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-file-invoice"></i> Tax ID</div><div class="info-value"><?php echo htmlspecialchars($business['tax_id'] ?? 'N/A'); ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-calendar"></i> Submitted on</div><div class="info-value"><?php echo date('F j, Y', strtotime($business['created_at'])); ?></div></div>
            </div>

            <?php if (!empty($business['document_path'])): ?>
            <div class="document-section" style="margin: 20px 0; padding: 15px; background: #f8fafc; border-radius: 12px;">
                <h3><i class="fas fa-file-pdf"></i> Business Document</h3>
                <a href="../<?php echo htmlspecialchars($business['document_path']); ?>" target="_blank" class="btn-small">View Document</a>
            </div>
            <?php endif; ?>

            <div class="action-buttons">
                <?php if ($business['verification_status'] === 'pending'): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="action" value="approve" class="btn btn-approve" onclick="return confirm('Approve this business?')">
                            <i class="fas fa-check-circle"></i> Approve Business
                        </button>
                    </form>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="action" value="reject" class="btn btn-reject" onclick="return confirm('Reject this business?')">
                            <i class="fas fa-times-circle"></i> Reject Business
                        </button>
                    </form>
                <?php endif; ?>
                <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-tachometer-alt"></i> Go to Dashboard</a>
            </div>
        </div>

        <?php if ($facilities && $facilities->num_rows > 0): ?>
        <div class="detail-card">
            <h3><i class="fas fa-building"></i> Owned Facilities</h3>
            <table class="facilities-table">
                <thead><tr><th>Name</th><th>Type</th><th>Address</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php while ($fac = $facilities->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($fac['name']); ?></strong></td>
                        <td><span class="badge <?php echo $fac['facility_type']; ?>"><?php echo ucfirst($fac['facility_type']); ?></span></td>
                        <td><?php echo htmlspecialchars($fac['address']); ?></td>
                        <td><?php echo $fac['verified'] ? '<span class="badge approved">Verified</span>' : '<span class="badge pending">Pending</span>'; ?></td>
                        <td><a href="facility_details.php?id=<?php echo $fac['facility_id']; ?>" class="btn-small"><i class="fas fa-eye"></i> View</a></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-container">
            <div class="footer-copyright">&copy; 2026 PharmaLocator - Admin Portal. All rights reserved.</div>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>
</body>
</html>