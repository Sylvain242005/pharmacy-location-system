<?php
// admin/dashboard.php - Debug Version with Professional Design

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Include database connection
include '../db.php';

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

// Initialize stats array with default values
$stats = [
    'total_users' => 0,
    'total_facilities' => 0,
    'pending_verifications' => 0,
    'total_products' => 0,
    'total_services' => 0
];

// Test database queries one by one with error checking
echo "<!-- Debug Info Start -->\n";

// Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows == 0) {
    die("Error: 'users' table does not exist in database");
}

// Total users
$users_result = $conn->query("SELECT COUNT(*) as count FROM users");
if ($users_result) {
    $stats['total_users'] = $users_result->fetch_assoc()['count'];
    echo "<!-- Users count: " . $stats['total_users'] . " -->\n";
} else {
    echo "<!-- Error in users query: " . $conn->error . " -->\n";
}

// Check if facilities table exists
$table_check = $conn->query("SHOW TABLES LIKE 'facilities'");
if ($table_check->num_rows > 0) {
    $facilities_result = $conn->query("SELECT COUNT(*) as count FROM facilities");
    if ($facilities_result) {
        $stats['total_facilities'] = $facilities_result->fetch_assoc()['count'];
        echo "<!-- Facilities count: " . $stats['total_facilities'] . " -->\n";
    }
}

// Check if business_owners table exists
$table_check = $conn->query("SHOW TABLES LIKE 'business_owners'");
if ($table_check->num_rows > 0) {
    $pending_result = $conn->query("SELECT COUNT(*) as count FROM business_owners WHERE verification_status = 'pending'");
    if ($pending_result) {
        $stats['pending_verifications'] = $pending_result->fetch_assoc()['count'];
        echo "<!-- Pending verifications: " . $stats['pending_verifications'] . " -->\n";
    }
}

// Check if products table exists
$table_check = $conn->query("SHOW TABLES LIKE 'products'");
if ($table_check->num_rows > 0) {
    $products_result = $conn->query("SELECT COUNT(*) as count FROM products");
    if ($products_result) {
        $stats['total_products'] = $products_result->fetch_assoc()['count'];
    }
}

// Check if services table exists
$table_check = $conn->query("SHOW TABLES LIKE 'services'");
if ($table_check->num_rows > 0) {
    $services_result = $conn->query("SELECT COUNT(*) as count FROM services");
    if ($services_result) {
        $stats['total_services'] = $services_result->fetch_assoc()['count'];
    }
}

// Get pending businesses with error handling
$pending_businesses = [];
$pending_businesses_result = false;

if ($conn->query("SHOW TABLES LIKE 'business_owners'")->num_rows > 0 && 
    $conn->query("SHOW TABLES LIKE 'users'")->num_rows > 0) {
    
    $query = "
        SELECT bo.*, u.full_name, u.email, u.phone 
        FROM business_owners bo
        LEFT JOIN users u ON bo.user_id = u.user_id
        WHERE bo.verification_status = 'pending'
        ORDER BY bo.created_at DESC
    ";
    
    $pending_businesses_result = $conn->query($query);
    
    if ($pending_businesses_result) {
        $pending_businesses = $pending_businesses_result->fetch_all(MYSQLI_ASSOC);
        echo "<!-- Found " . count($pending_businesses) . " pending businesses -->\n";
    } else {
        echo "<!-- Error in pending businesses query: " . $conn->error . " -->\n";
    }
}

// Get recent facilities with error handling
$recent_facilities = [];
$facilities_query = "
    SELECT f.*, u.full_name as owner_name 
    FROM facilities f
    LEFT JOIN business_owners bo ON f.owner_id = bo.owner_id
    LEFT JOIN users u ON bo.user_id = u.user_id
    ORDER BY f.created_at DESC
    LIMIT 10
";
$recent_facilities_result = $conn->query($facilities_query);
if ($recent_facilities_result) {
    $recent_facilities = $recent_facilities_result->fetch_all(MYSQLI_ASSOC);
}

// Get recent users
$recent_users = [];
$recent_users_result = $conn->query("
    SELECT user_id, full_name, email, role, created_at, verified 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 10
");
if ($recent_users_result) {
    $recent_users = $recent_users_result->fetch_all(MYSQLI_ASSOC);
}

echo "<!-- Debug Info End -->\n";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PharmaLocator</title>
    
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
        .admin-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 30px;
            flex: 1;
            width: 100%;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .welcome-section h2 {
            font-size: 24px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .welcome-section h2 i {
            color: #fbbf24;
        }

        .welcome-section p {
            color: #cbd5e1;
        }

        .last-login {
            background: rgba(255,255,255,0.1);
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .last-login i {
            color: #94a3b8;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            text-align: center;
            transition: all 0.3s;
            border: 1px solid #f0f0f0;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2c7da0, #2a9d8f);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .stat-icon {
            font-size: 40px;
            color: #2c7da0;
            margin-bottom: 15px;
            background: #f0f9ff;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            margin: 0 auto 15px;
        }

        .stat-card .number {
            font-size: 42px;
            font-weight: bold;
            color: #1e293b;
            margin: 10px 0;
        }

        .stat-card .label {
            color: #64748b;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 500;
        }

        .stat-card.pending::before {
            background: linear-gradient(90deg, #f4a261, #e76f51);
        }

        .stat-card.pending .stat-icon {
            color: #f4a261;
        }

        .stat-card.pending .number {
            color: #f4a261;
        }

        /* ===== SECTIONS ===== */
        .section {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            border: 1px solid #f0f0f0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-header h3 {
            font-size: 20px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h3 i {
            color: #2c7da0;
        }

        .view-all {
            text-decoration: none;
            color: #2c7da0;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            background: #f1f5f9;
            padding: 8px 16px;
            border-radius: 30px;
        }

        .view-all:hover {
            background: #e2e8f0;
            transform: translateX(5px);
        }

        /* ===== TABLES ===== */
        .table-responsive {
            overflow-x: auto;
            border-radius: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 14px;
            white-space: nowrap;
        }

        table td {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            white-space: nowrap;
        }

        table tbody tr:hover {
            background: #f8fafc;
        }

        /* ===== BADGES ===== */
        .badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.pharmacy {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.hospital {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge.clinic {
            background: #dbeafe;
            color: #1e40af;
        }

        /* ===== ACTION BUTTONS ===== */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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
            white-space: nowrap;
        }

        .btn-approve {
            background: #d1fae5;
            color: #065f46;
        }

        .btn-approve:hover {
            background: #a7f3d0;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-reject:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        .btn-view {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-view:hover {
            background: #bfdbfe;
            transform: translateY(-2px);
        }

        /* Debug Info (hidden but accessible) */
        .debug-info {
            display: none;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 10px;
        }

        /* Footer */
        footer {
            background: white;
            padding: 20px 30px;
            margin-top: 40px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .footer-copyright {
            color: #64748b;
            font-size: 14px;
        }

        .footer-social {
            display: flex;
            gap: 20px;
        }

        .footer-social a {
            color: #94a3b8;
            font-size: 18px;
            transition: all 0.3s;
        }

        .footer-social a:hover {
            color: #2c7da0;
            transform: translateY(-3px);
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

            .welcome-section {
                flex-direction: column;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-container {
                flex-direction: column;
                text-align: center;
            }

            table {
                font-size: 14px;
            }

            td, th {
                padding: 10px 8px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-small {
                width: 100%;
                justify-content: center;
            }
        }

        /* Print styles for debugging */
        @media print {
            .debug-info {
                display: block;
                white-space: pre-wrap;
                font-family: monospace;
                background: #f1f5f9;
                padding: 20px;
                margin: 20px;
                border: 1px solid #cbd5e1;
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
                <a href="dashboard.php" class="active">Dashboard</a>
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
    <div class="admin-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div>
                <h2>
                    <i class="fas fa-crown"></i> 
                    Welcome back, <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Administrator'; ?>!
                </h2>
                <p>Here's what's happening with your platform today</p>
            </div>
            <div class="last-login">
                <i class="fas fa-clock"></i> Last login: <?php echo date('Y-m-d H:i:s'); ?>
            </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="number"><?php echo $stats['total_users']; ?></div>
                <div class="label">Total Users</div>
                <div style="color: #64748b; font-size: 13px; margin-top: 5px;">Registered accounts</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="number"><?php echo $stats['total_facilities']; ?></div>
                <div class="label">Facilities</div>
                <div style="color: #64748b; font-size: 13px; margin-top: 5px;">Pharmacies, Hospitals, Clinics</div>
            </div>

            <div class="stat-card pending">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="number"><?php echo $stats['pending_verifications']; ?></div>
                <div class="label">Pending Verifications</div>
                <div style="color: #64748b; font-size: 13px; margin-top: 5px;">Awaiting approval</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-pills"></i>
                </div>
                <div class="number"><?php echo $stats['total_products']; ?></div>
                <div class="label">Products</div>
                <div style="color: #64748b; font-size: 13px; margin-top: 5px;">From pharmacies</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="number"><?php echo $stats['total_services']; ?></div>
                <div class="label">Services</div>
                <div style="color: #64748b; font-size: 13px; margin-top: 5px;">From hospitals/clinics</div>
            </div>
        </div>

        <!-- Pending Verifications -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-clock"></i> Pending Business Verifications</h3>
                <a href="verify_businesses.php" class="view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (!empty($pending_businesses)): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Business Name</th>
                                <th>Type</th>
                                <th>Owner</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_businesses as $business): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($business['business_name'] ?? 'N/A'); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $business['business_type'] ?? 'pending'; ?>">
                                        <?php echo ucfirst($business['business_type'] ?? 'Unknown'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($business['full_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($business['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($business['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge pending">Pending</span>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-small btn-approve" 
                                            onclick="verifyBusiness(<?php echo $business['owner_id'] ?? 0; ?>, 'approve')">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="btn-small btn-reject" 
                                            onclick="verifyBusiness(<?php echo $business['owner_id'] ?? 0; ?>, 'reject')">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                    <button class="btn-small btn-view" 
                                            onclick="viewDetails(<?php echo $business['owner_id'] ?? 0; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle" style="color: #10b981;"></i>
                    <p>No pending verifications. All caught up!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Facilities -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-building"></i> Recent Facilities Added</h3>
                <a href="manage_facilities.php" class="view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (!empty($recent_facilities)): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Facility Name</th>
                                <th>Type</th>
                                <th>Owner</th>
                                <th>Address</th>
                                <th>Status</th>
                                <th>Added</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_facilities as $facility): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($facility['name'] ?? 'N/A'); ?></strong></td>
                                <td>
                                    <span class="badge <?php echo $facility['facility_type'] ?? 'pending'; ?>">
                                        <?php echo ucfirst($facility['facility_type'] ?? 'Unknown'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($facility['owner_name'] ?? 'Unknown'); ?></td>
                                <td><?php echo htmlspecialchars(substr($facility['address'] ?? '', 0, 30)) . '...'; ?></td>
                                <td>
                                    <?php if (isset($facility['verified']) && $facility['verified']): ?>
                                        <span class="badge approved">Verified</span>
                                    <?php else: ?>
                                        <span class="badge pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($facility['created_at']) ? date('Y-m-d', strtotime($facility['created_at'])) : 'N/A'; ?></td>
                                <td>
                                    <button class="btn-small btn-view" 
                                            onclick="viewFacility(<?php echo $facility['facility_id'] ?? 0; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <p>No facilities yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Users -->
        <div class="section">
            <div class="section-header">
                <h3><i class="fas fa-users"></i> Recent User Registrations</h3>
                <a href="manage_users.php" class="view-all">
                    View All <i class="fas fa-arrow-right"></i>
                </a>
            </div>

            <?php if (!empty($recent_users)): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['full_name'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role'] ?? 'customer'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'] ?? 'customer')); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (isset($user['verified']) && $user['verified']): ?>
                                        <span class="badge approved">Verified</span>
                                    <?php else: ?>
                                        <span class="badge pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($user['created_at']) ? date('Y-m-d', strtotime($user['created_at'])) : 'N/A'; ?></td>
                                <td>
                                    <button class="btn-small btn-view" 
                                            onclick="viewUser(<?php echo $user['user_id'] ?? 0; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-user"></i>
                    <p>No users yet</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Debug Info (Hidden) -->
        <div class="debug-info">
            <?php
            echo "=== DEBUG INFORMATION ===\n";
            echo "Total Users: " . $stats['total_users'] . "\n";
            echo "Total Facilities: " . $stats['total_facilities'] . "\n";
            echo "Pending Verifications: " . $stats['pending_verifications'] . "\n";
            echo "Total Products: " . $stats['total_products'] . "\n";
            echo "Total Services: " . $stats['total_services'] . "\n";
            echo "Pending Businesses Found: " . count($pending_businesses) . "\n";
            echo "Recent Facilities Found: " . count($recent_facilities) . "\n";
            echo "Recent Users Found: " . count($recent_users) . "\n";
            ?>
        </div>
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

    <script>
    function verifyBusiness(ownerId, action) {
        if (!ownerId || ownerId === 0) {
            alert('Invalid business ID');
            return;
        }

        if (!confirm('Are you sure you want to ' + action + ' this business?')) {
            return;
        }

        fetch('../api/verify_businesses.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ owner_id: ownerId, action: action })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Business ' + action + 'ed successfully!');
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }

    function viewDetails(ownerId) {
        if (ownerId && ownerId !== 0) {
            window.location.href = 'business_details.php?id=' + ownerId;
        } else {
            alert('Invalid business ID');
        }
    }

    function viewFacility(facilityId) {
        if (facilityId && facilityId !== 0) {
            window.location.href = 'facility_details.php?id=' + facilityId;
        } else {
            alert('Invalid facility ID');
        }
    }

    function viewUser(userId) {
        if (userId && userId !== 0) {
            window.location.href = 'user_details.php?id=' + userId;
        } else {
            alert('Invalid user ID');
        }
    }

    // Optional: Show debug info in console
    console.log('=== DEBUG INFO ===');
    console.log('Total Users:', <?php echo $stats['total_users']; ?>);
    console.log('Total Facilities:', <?php echo $stats['total_facilities']; ?>);
    console.log('Pending Verifications:', <?php echo $stats['pending_verifications']; ?>);
    console.log('Total Products:', <?php echo $stats['total_products']; ?>);
    console.log('Total Services:', <?php echo $stats['total_services']; ?>);
    console.log('Pending Businesses:', <?php echo count($pending_businesses); ?>);
    console.log('Recent Facilities:', <?php echo count($recent_facilities); ?>);
    console.log('Recent Users:', <?php echo count($recent_users); ?>);
    </script>
</body>
</html>