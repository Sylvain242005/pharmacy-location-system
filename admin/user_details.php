<?php
// admin/user_details.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch user details
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: dashboard.php");
    exit();
}

// Fetch business if user is a business owner
$business = null;
if ($user['role'] == 'business_owner') {
    $business_query = "SELECT * FROM business_owners WHERE user_id = ?";
    $business_stmt = $conn->prepare($business_query);
    $business_stmt->bind_param("i", $user_id);
    $business_stmt->execute();
    $business_result = $business_stmt->get_result();
    $business = $business_result->fetch_assoc();
}

// Fetch facilities owned by user
$facilities = null;
if ($user['role'] == 'business_owner' && $business) {
    $facilities_query = "SELECT * FROM facilities WHERE owner_id = ?";
    $facilities_stmt = $conn->prepare($facilities_query);
    $facilities_stmt->bind_param("i", $business['owner_id']);
    $facilities_stmt->execute();
    $facilities = $facilities_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - PharmaLocator</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* ===== GLOBAL STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ===== HEADER DESIGN ===== */
        .new-header {
            background: white;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            align-items: center;
        }

        .logo-link {
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo-icon {
            font-size: 24px;
            background: linear-gradient(135deg, #2c7da0, #2a9d8f);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            color: white;
        }

        .logo-text {
            font-weight: 700;
            font-size: 18px;
            color: #1e293b;
        }

        .logo-text span {
            color: #64748b;
            font-weight: 400;
            font-size: 12px;
            display: block;
            line-height: 1.2;
        }

        /* Navigation */
        .nav-center {
            display: flex;
            gap: 32px;
        }

        .nav-center a {
            text-decoration: none;
            color: #475569;
            font-weight: 500;
            font-size: 15px;
            padding: 8px 0;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-center a:hover {
            color: #2c7da0;
            border-bottom-color: #2c7da0;
        }

        .nav-center a.active {
            color: #2c7da0;
            border-bottom-color: #2c7da0;
            font-weight: 600;
        }

        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-name {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 40px;
            color: #1e293b;
            font-weight: 500;
            font-size: 14px;
        }

        .user-name i {
            color: #2c7da0;
        }

        .logout-btn {
            text-decoration: none;
            padding: 8px 16px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 40px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        /* ===== MAIN CONTAINER ===== */
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 30px;
            flex: 1;
            width: 100%;
        }

        /* Back Button */
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: #475569;
            background: #f1f5f9;
            padding: 10px 20px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 25px;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }

        .btn-back:hover {
            background: #e2e8f0;
            transform: translateX(-5px);
        }

        /* Detail Card */
        .detail-card {
            background: white;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border: 1px solid #f0f0f0;
        }

        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 25px;
        }

        .detail-header h2 {
            font-size: 28px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-header h2 i {
            color: #2c7da0;
        }

        /* Badges */
        .badge {
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .badge.admin {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.business_owner {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.customer {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 25px 0;
        }

        .info-item {
            padding: 15px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #f1f5f9;
        }

        .info-label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .info-label i {
            color: #2c7da0;
        }

        .info-value {
            font-size: 16px;
            font-weight: 500;
            color: #1e293b;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 40px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-verify {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-unverify {
            background: #fff3cd;
            color: #856404;
        }

        .btn-suspend {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-activate {
            background: #d1fae5;
            color: #065f46;
        }

        .btn:hover {
            transform: translateY(-2px);
            filter: brightness(0.95);
        }

        /* Facilities Table */
        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #475569;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        table tr:hover {
            background: #f8fafc;
        }

        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            text-decoration: none;
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-small:hover {
            transform: translateY(-2px);
            filter: brightness(0.95);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .nav-center {
                flex-wrap: wrap;
                justify-content: center;
                gap: 15px;
            }

            .user-menu {
                width: 100%;
                justify-content: center;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- ===== HEADER ===== -->
    <header class="new-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="../home.html" class="logo-link">
                    <div class="logo-icon"></div>
                    <div class="logo-text">
                        PharmaLocator
                        <span>Admin Portal</span>
                    </div>
                </a>
            </div>

            <nav class="nav-center">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_users.php">Users</a>
                <a href="verify_businesses.php">Verifications</a>
                <a href="reports.php">Reports</a>
            </nav>

            <div class="user-menu">
                <span class="user-name">
                    <i class="fas fa-user-shield"></i>
                    <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Admin'; ?>
                </span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="container">
        <!-- Back Button -->
        <a href="manage_users.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>

        <!-- User Details Card -->
        <div class="detail-card">
            <div class="detail-header">
                <h2>
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($user['full_name']); ?>
                </h2>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <span class="badge <?php echo $user['role']; ?>">
                        <i class="fas fa-<?php echo $user['role'] == 'admin' ? 'crown' : ($user['role'] == 'business_owner' ? 'store' : 'user'); ?>"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                    </span>
                    <?php if ($user['verified']): ?>
                        <span class="badge approved">
                            <i class="fas fa-check-circle"></i> Verified
                        </span>
                    <?php else: ?>
                        <span class="badge pending">
                            <i class="fas fa-clock"></i> Pending
                        </span>
                    <?php endif; ?>
                    <span class="badge <?php echo $user['is_active'] ? 'approved' : 'pending'; ?>">
                        <i class="fas fa-<?php echo $user['is_active'] ? 'check' : 'ban'; ?>"></i>
                        <?php echo $user['is_active'] ? 'Active' : 'Suspended'; ?>
                    </span>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-phone"></i> Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-calendar"></i> Registered on</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-clock"></i> Last Updated</div>
                    <div class="info-value">
                        <?php 
                        if (isset($user['updated_at']) && !empty($user['updated_at']) && $user['updated_at'] != '0000-00-00 00:00:00') {
                            echo date("F j, Y", strtotime($user['updated_at']));
                        } elseif (isset($user['created_at']) && !empty($user['created_at']) && $user['created_at'] != '0000-00-00 00:00:00') {
                            echo date("F j, Y", strtotime($user['created_at'])) . " (Created)";
                        } else {
                            echo "<span style='color: #94a3b8;'>Not available</span>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="action-buttons">
                <?php if (!$user['verified']): ?>
                    <a href="manage_users.php?action=verify&id=<?php echo $user['user_id']; ?>" class="btn btn-verify">
                        <i class="fas fa-check-circle"></i> Verify User
                    </a>
                <?php else: ?>
                    <a href="manage_users.php?action=unverify&id=<?php echo $user['user_id']; ?>" class="btn btn-unverify">
                        <i class="fas fa-times-circle"></i> Unverify User
                    </a>
                <?php endif; ?>

                <?php if ($user['is_active']): ?>
                    <a href="manage_users.php?action=suspend&id=<?php echo $user['user_id']; ?>" class="btn btn-suspend">
                        <i class="fas fa-ban"></i> Suspend User
                    </a>
                <?php else: ?>
                    <a href="manage_users.php?action=activate&id=<?php echo $user['user_id']; ?>" class="btn btn-activate">
                        <i class="fas fa-play"></i> Activate User
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Business Information -->
        <?php if ($business): ?>
        <div class="detail-card">
            <h3><i class="fas fa-store"></i> Business Information</h3>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-building"></i> Business Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($business['business_name']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-tag"></i> Business Type</div>
                    <div class="info-value"><?php echo ucfirst($business['business_type']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-map-marker-alt"></i> Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($business['address'] ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-id-card"></i> Registration Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($business['registration_number'] ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-file-invoice"></i> Tax ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($business['tax_id'] ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-check-circle"></i> Verification Status</div>
                    <div class="info-value">
                        <span class="badge <?php echo $business['verification_status']; ?>">
                            <?php echo ucfirst($business['verification_status']); ?>
                        </span>
                    </div>
                </div>
            </div>

            <?php if ($facilities && $facilities->num_rows > 0): ?>
            <div style="margin-top: 20px;">
                <h4><i class="fas fa-building"></i> Owned Facilities</h4>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($facility = $facilities->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($facility['name']); ?></strong></td>
                                <td><span class="badge <?php echo $facility['facility_type']; ?>"><?php echo ucfirst($facility['facility_type']); ?></span></td>
                                <td><?php echo htmlspecialchars($facility['address']); ?></td>
                                <td>
                                    <?php if ($facility['verified']): ?>
                                        <span class="badge approved">Verified</span>
                                    <?php else: ?>
                                        <span class="badge pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="facility_details.php?id=<?php echo $facility['facility_id']; ?>" class="btn-small">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="footer-container">
            <div class="footer-copyright">
                &copy; 2026 PharmaLocator - Admin Portal. All rights reserved.
            </div>
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