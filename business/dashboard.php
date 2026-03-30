<?php
// business/dashboard.php
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Business Owner';

// Check if user is business owner, if not fix it
$check_role = $conn->query("SELECT role FROM users WHERE user_id = $user_id");
$user_data = $check_role->fetch_assoc();

if ($user_data['role'] != 'business_owner') {
    // Try to find business owner record
    $biz_check = $conn->query("SELECT * FROM business_owners WHERE user_id = $user_id");
    
    if ($biz_check->num_rows > 0) {
        // Has business record but wrong role - fix it
        $conn->query("UPDATE users SET role = 'business_owner' WHERE user_id = $user_id");
        $_SESSION['user_role'] = 'business_owner';
    } else {
        // No business record - redirect to fix
        header("Location: fix_business.php?user_id=$user_id");
        exit();
    }
}

// Get business owner details
$biz_sql = "SELECT bo.*, u.full_name, u.email, u.phone 
            FROM business_owners bo 
            JOIN users u ON bo.user_id = u.user_id 
            WHERE bo.user_id = $user_id";
$biz_result = $conn->query($biz_sql);

if ($biz_result->num_rows == 0) {
    header("Location: fix_business.php?user_id=$user_id");
    exit();
}

$business = $biz_result->fetch_assoc();
$owner_id = $business['owner_id'];

// Get facilities for this owner
$facilities_sql = "SELECT * FROM facilities WHERE owner_id = $owner_id ORDER BY created_at DESC";
$facilities_result = $conn->query($facilities_sql);
$facilities = [];
$has_facilities = false;

while ($row = $facilities_result->fetch_assoc()) {
    $has_facilities = true;
    
    // Get products count for pharmacy
    if ($row['facility_type'] == 'pharmacy') {
        $prod_count = $conn->query("SELECT COUNT(*) as count FROM products WHERE facility_id = " . $row['facility_id'])->fetch_assoc()['count'];
        $row['products_count'] = $prod_count;
    }
    
    // Get services count for hospital/clinic
    if (in_array($row['facility_type'], ['hospital', 'clinic'])) {
        $serv_count = $conn->query("SELECT COUNT(*) as count FROM services WHERE facility_id = " . $row['facility_id'])->fetch_assoc()['count'];
        $row['services_count'] = $serv_count;
    }
    
    $facilities[] = $row;
}

// Calculate statistics
$total_facilities = count($facilities);
$total_products = 0;
$total_services = 0;
$total_views = 0;

foreach ($facilities as $fac) {
    $total_views += $fac['views_count'] ?? 0;
    if (isset($fac['products_count'])) $total_products += $fac['products_count'];
    if (isset($fac['services_count'])) $total_services += $fac['services_count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Dashboard - PharmaLocator</title>
    
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
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 30px;
            flex: 1;
            width: 100%;
        }

        /* ===== DASHBOARD HEADER (KEEPING YOUR STRUCTURE) ===== */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
            background: white;
            padding: 25px 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
        }
        
        .welcome-section h2 {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .welcome-section p {
            color: #64748b;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .welcome-section p i {
            color: #2c7da0;
            width: 20px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.verified {
            background: linear-gradient(135deg, #2a9d8f, #1e7a6a);
            color: white;
        }
        
        .badge.pending {
            background: linear-gradient(135deg, #f4a261, #e76f51);
            color: white;
        }

        .btn-add-facility {
            text-decoration: none;
            padding: 12px 24px;
            background: linear-gradient(135deg, #2c7da0, #1e5f7a);
            color: white;
            border-radius: 40px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(44,125,160,0.3);
        }

        .btn-add-facility:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(44,125,160,0.4);
        }

        /* ===== STATS GRID (KEEPING YOUR STRUCTURE) ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 30px;
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

        /* ===== FACILITIES SECTION ===== */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-header h3 {
            font-size: 24px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h3 i {
            color: #2c7da0;
        }
        
        /* ===== FACILITY CARD (KEEPING YOUR STRUCTURE) ===== */
        .facility-card {
            background: white;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            overflow: hidden;
            transition: all 0.3s;
        }

        .facility-card:hover {
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }
        
        .facility-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            padding: 20px 25px;
            background: #f8fafc;
            transition: background 0.3s;
        }
        
        .facility-header:hover {
            background: #f1f5f9;
        }

        .facility-title {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .facility-title strong {
            font-size: 20px;
            color: #1e293b;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }
        
        .badge.pharmacy {
            background: linear-gradient(135deg, #2a9d8f, #1e7a6a);
        }
        
        .badge.hospital {
            background: linear-gradient(135deg, #e76f51, #c44536);
        }
        
        .badge.clinic {
            background: linear-gradient(135deg, #2c7da0, #1e5f7a);
        }

        .verification-badge {
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }

        .verification-badge.verified {
            background: #d1fae5;
            color: #065f46;
        }

        .verification-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .facility-stats {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #64748b;
        }

        .facility-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .facility-stats i {
            color: #2c7da0;
        }
        
        .facility-details {
            display: none;
            padding: 25px;
            border-top: 1px solid #f1f5f9;
        }
        
        .facility-details.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 16px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #475569;
        }

        .info-item i {
            color: #2c7da0;
            width: 20px;
        }
        
        /* Action Bar */
        .action-bar {
            display: flex;
            gap: 12px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .btn-small {
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2c7da0, #1e5f7a);
            color: white;
            box-shadow: 0 4px 10px rgba(44,125,160,0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44,125,160,0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #2a9d8f, #1e7a6a);
            color: white;
            box-shadow: 0 4px 10px rgba(42,157,143,0.2);
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(42,157,143,0.3);
        }
        
        .btn-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        
        .btn-warning:hover {
            background: #ffe7a3;
            transform: translateY(-2px);
        }

        .btn-view {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-view:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }
        
        /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 12px;
            overflow: hidden;
        }
        
        table th {
            background: #f8fafc;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 14px;
        }
        
        table td {
            padding: 12px 15px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        table tr:hover {
            background: #f8fafc;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-in_stock {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-low_stock {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-out_of_stock {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 40px;
            background: white;
            border-radius: 20px;
            border: 1px solid #f0f0f0;
        }

        .empty-state i {
            font-size: 80px;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #64748b;
            margin-bottom: 30px;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }
        
        .quick-action-card {
            background: white;
            padding: 30px;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .quick-action-card:hover {
            border-color: #2c7da0;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .quick-action-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #2c7da0;
        }
        
        .quick-action-card h4 {
            margin-bottom: 10px;
            color: #1e293b;
        }
        
        .quick-action-card p {
            color: #64748b;
            font-size: 14px;
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

            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .facility-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .facility-stats {
                width: 100%;
                justify-content: space-between;
            }

            .footer-container {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- ===== NEW HEADER ===== -->
    <header class="new-header">
        <div class="header-container">
            <!-- LEFT: Logo -->
            <div class="logo-section">
                <a href="../home.html" class="logo-link">
                    <div class="logo-icon"></div>
                    <div class="logo-text">
                        PharmaLocator
                        <span>Find Care, Fast</span>
                    </div>
                </a>
            </div>

            <!-- CENTER: Navigation (KEEPING YOUR LINKS) -->
            <nav class="nav-center">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="add_facility.php">Add Facility</a>
                
            </nav>

            <!-- RIGHT: User Menu -->
            <div class="user-menu">
                <span class="user-name">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($user_name); ?>
                </span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>

<script>
function changeLanguage(lang) {
    // Save language preference via AJAX or simple redirect with query param
    window.location.href = 'set_language.php?lang=' + lang + '&redirect=' + encodeURIComponent(window.location.pathname);
}
</script>

            </div>
        </div>
    </header>

    <!-- ===== MAIN DASHBOARD CONTENT ===== -->
    <div class="container">
        <!-- Welcome Header (KEEPING YOUR STRUCTURE) -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <h2><i class="fas fa-store"></i> Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
                <p><i class="fas fa-building"></i> <strong>Business:</strong> <?php echo htmlspecialchars($business['business_name']); ?></p>
                <p><i class="fas fa-tag"></i> <strong>Type:</strong> <?php echo ucfirst($business['business_type']); ?></p>
                <p><i class="fas fa-shield-alt"></i> <strong>Status:</strong> 
                    <span class="verification-badge <?php echo $business['verification_status']; ?>">
                        <?php echo ucfirst($business['verification_status']); ?>
                    </span>
                </p>
            </div>
            <a href="add_facility.php" class="btn-add-facility">
                <i class="fas fa-plus-circle"></i> Add New Facility
            </a>
        </div>

        <!-- Statistics (KEEPING YOUR STRUCTURE) -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="number"><?php echo $total_facilities; ?></div>
                <div class="label">Total Facilities</div>
            </div>
            
            <?php if ($business['business_type'] == 'pharmacy'): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-pills"></i>
                </div>
                <div class="number"><?php echo $total_products; ?></div>
                <div class="label">Total Products</div>
            </div>
            <?php endif; ?>
            
            <?php if (in_array($business['business_type'], ['hospital', 'clinic'])): ?>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-stethoscope"></i>
                </div>
                <div class="number"><?php echo $total_services; ?></div>
                <div class="label">Total Services</div>
            </div>
            <?php endif; ?>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <div class="number"><?php echo $total_views; ?></div>
                <div class="label">Profile Views</div>
            </div>
        </div>

        <!-- Facilities List -->
        <div class="section-header">
            <h3><i class="fas fa-list"></i> Your Facilities</h3>
        </div>
        
        <?php if (!$has_facilities): ?>
            <div class="empty-state">
                <i class="fas fa-store-alt"></i>
                <h3>No facilities yet</h3>
                <p>You haven't added any facilities to your account. Get started by adding your first facility.</p>
                <a href="add_facility.php" class="btn-add-facility" style="display: inline-flex;">
                    <i class="fas fa-plus-circle"></i> Add Your First Facility
                </a>
                
                <div class="quick-actions">
                    <div class="quick-action-card" onclick="location.href='add_facility.php?type=pharmacy'">
                        <div class="icon"><i class="fas fa-prescription-bottle"></i></div>
                        <h4>Add Pharmacy</h4>
                        <p>List medications and products</p>
                    </div>
                    
                    <div class="quick-action-card" onclick="location.href='add_facility.php?type=hospital'">
                        <div class="icon"><i class="fas fa-hospital"></i></div>
                        <h4>Add Hospital</h4>
                        <p>List medical services</p>
                    </div>
                    
                    <div class="quick-action-card" onclick="location.href='add_facility.php?type=clinic'">
                        <div class="icon"><i class="fas fa-clinic-medical"></i></div>
                        <h4>Add Clinic</h4>
                        <p>List specialized services</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($facilities as $facility): ?>
            <div class="facility-card">
                <div class="facility-header" onclick="toggleFacility(<?php echo $facility['facility_id']; ?>)">
                    <div class="facility-title">
                        <strong><?php echo htmlspecialchars($facility['name']); ?></strong>
                        <span class="badge <?php echo $facility['facility_type']; ?>">
                            <i class="fas fa-<?php echo $facility['facility_type'] == 'pharmacy' ? 'prescription-bottle' : ($facility['facility_type'] == 'hospital' ? 'hospital' : 'clinic'); ?>"></i>
                            <?php echo ucfirst($facility['facility_type']); ?>
                        </span>
                        <?php if ($facility['verified']): ?>
                            <span class="verification-badge verified">
                                <i class="fas fa-check-circle"></i> Verified
                            </span>
                        <?php else: ?>
                            <span class="verification-badge pending">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="facility-stats">
                        <span><i class="fas fa-eye"></i> <?php echo $facility['views_count'] ?? 0; ?> views</span>
                        <span><i class="fas fa-chevron-down"></i></span>
                    </div>
                </div>
                
                <div id="facility-<?php echo $facility['facility_id']; ?>" class="facility-details">
                    <!-- Quick Stats -->
                    <div class="info-grid">
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?php echo htmlspecialchars($facility['address']); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone-alt"></i>
                            <span><?php echo htmlspecialchars($facility['phone'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <span><?php echo htmlspecialchars($facility['opening_hours'] ?? 'Hours not set'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons (KEEPING YOUR LINKS) -->
                   <div class="action-bar">
    <a href="edit_facility.php?id=<?php echo $facility['facility_id']; ?>" class="btn-small btn-warning">
        <i class="fas fa-edit"></i> Edit
    </a>

    <?php if ($facility['facility_type'] == 'pharmacy'): ?>
        <a href="manage_products.php?facility_id=<?php echo $facility['facility_id']; ?>" class="btn-small btn-primary">
            <i class="fas fa-pills"></i> Manage Products (<?php echo $facility['products_count'] ?? 0; ?>)
        </a>
        <a href="add_products.php?facility_id=<?php echo $facility['facility_id']; ?>" class="btn-small btn-success">
            <i class="fas fa-plus-circle"></i> Add Product
        </a>
    <?php endif; ?>

    <?php if (in_array($facility['facility_type'], ['hospital', 'clinic'])): ?>
        <a href="manage_services.php?facility_id=<?php echo $facility['facility_id']; ?>" class="btn-small btn-primary">
            <i class="fas fa-stethoscope"></i> Manage Services (<?php echo $facility['services_count'] ?? 0; ?>)
        </a>
        <a href="add_services.php?facility_id=<?php echo $facility['facility_id']; ?>" class="btn-small btn-success">
            <i class="fas fa-plus-circle"></i> Add Service
        </a>
    <?php endif; ?>

    <a href="view_facility.php?id=<?php echo $facility['facility_id']; ?>" class="btn-small btn-view">
        <i class="fas fa-external-link-alt"></i> View Public Page
    </a>
</div>
                    
                    <!-- Preview of Products/Services -->
                    <?php if ($facility['facility_type'] == 'pharmacy' && isset($facility['products_count']) && $facility['products_count'] > 0): ?>
                        <h4 style="margin: 20px 0 10px;"><i class="fas fa-pills"></i> Recent Products</h4>
                        <?php
                        $products = $conn->query("SELECT * FROM products WHERE facility_id = " . $facility['facility_id'] . " ORDER BY created_at DESC LIMIT 5");
                        if ($products->num_rows > 0):
                        ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $products->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                        <td><?php echo number_format($product['price'], 0); ?> XAF</td>
                                        <td>
                                            <span class="status-badge status-<?php echo $product['stock_status']; ?>">
                                                <?php echo str_replace('_', ' ', $product['stock_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit_product.php?id=<?php echo $product['product_id']; ?>" class="btn-small btn-warning" style="padding: 5px 10px;">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="footer-container">
            <div class="footer-copyright">
                &copy; 2026 PharmaLocator - Business Portal. All rights reserved.
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
    function toggleFacility(id) {
        const details = document.getElementById('facility-' + id);
        details.classList.toggle('show');
    }
    
    // Auto-expand if URL has hash
    if (window.location.hash) {
        const id = window.location.hash.replace('#', '');
        const element = document.getElementById('facility-' + id);
        if (element) {
            element.classList.add('show');
        }
    }
    </script>
</body>
</html>