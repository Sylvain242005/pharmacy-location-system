<?php
// admin/reports.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: " . SITE_URL . "/login.html");
    exit();
}

// Get reports data
$users_by_role = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$facilities_by_type = $conn->query("SELECT facility_type, COUNT(*) as count FROM facilities GROUP BY facility_type");
$verification_stats = $conn->query("SELECT verification_status, COUNT(*) as count FROM business_owners GROUP BY verification_status");
$top_facilities = $conn->query("SELECT name, facility_type, views_count FROM facilities ORDER BY views_count DESC LIMIT 10");
$recent_searches = $conn->query("SELECT s.*, u.full_name FROM searches s LEFT JOIN users u ON s.user_id = u.user_id ORDER BY s.timestamp DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - PharmaLocator</title>
    
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

        /* Page Header */
        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 28px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header h1 i {
            color: #2c7da0;
        }

        .page-header p {
            color: #64748b;
        }

        /* Report Grid */
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .report-card {
            background: white;
            padding: 25px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            transition: transform 0.3s;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .report-card h3 {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
            color: #1e293b;
            font-size: 18px;
        }

        .report-card h3 i {
            color: #2c7da0;
        }

        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
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
            font-size: 13px;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        table tr:last-child td {
            border-bottom: none;
        }

        .number {
            font-size: 20px;
            font-weight: bold;
            color: #2c7da0;
        }

        .rank-badge {
            display: inline-block;
            width: 24px;
            height: 24px;
            background: #f1f5f9;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }

        .badge {
            padding: 4px 10px;
            border-radius: 30px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
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

            .report-grid {
                grid-template-columns: 1fr;
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
                <a href="reports.php" class="active">Reports</a>
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
        <!-- Page Header -->
        <div class="page-header">
            <h1>
                <i class="fas fa-chart-pie"></i>
                System Reports
            </h1>
            <p>Analytics and insights about your platform</p>
        </div>

        <!-- Reports Grid -->
        <div class="report-grid">
            <!-- Users by Role -->
            <div class="report-card">
                <h3><i class="fas fa-users"></i> Users by Role</h3>
                <div class="table-responsive">
                    <table>
                        <?php 
                        $users_by_role_data = [];
                        if ($users_by_role && $users_by_role->num_rows > 0) {
                            while ($row = $users_by_role->fetch_assoc()) {
                                $users_by_role_data[] = $row;
                            }
                        }
                        ?>
                        <?php if (!empty($users_by_role_data)): ?>
                            <?php foreach ($users_by_role_data as $row): ?>
                            <tr>
                                <td><strong><?php echo ucfirst(str_replace('_', ' ', $row['role'])); ?></strong></td>
                                <td><span class="number"><?php echo $row['count']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; color: #94a3b8;">No data available</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Facilities by Type -->
            <div class="report-card">
                <h3><i class="fas fa-building"></i> Facilities by Type</h3>
                <div class="table-responsive">
                    <table>
                        <?php 
                        $facilities_by_type_data = [];
                        if ($facilities_by_type && $facilities_by_type->num_rows > 0) {
                            while ($row = $facilities_by_type->fetch_assoc()) {
                                $facilities_by_type_data[] = $row;
                            }
                        }
                        ?>
                        <?php if (!empty($facilities_by_type_data)): ?>
                            <?php foreach ($facilities_by_type_data as $row): ?>
                            <tr>
                                <td><strong><?php echo ucfirst($row['facility_type']); ?></strong></td>
                                <td><span class="number"><?php echo $row['count']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; color: #94a3b8;">No data available</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Verification Status -->
            <div class="report-card">
                <h3><i class="fas fa-check-circle"></i> Business Verification</h3>
                <div class="table-responsive">
                    <table>
                        <?php 
                        $verification_stats_data = [];
                        if ($verification_stats && $verification_stats->num_rows > 0) {
                            while ($row = $verification_stats->fetch_assoc()) {
                                $verification_stats_data[] = $row;
                            }
                        }
                        ?>
                        <?php if (!empty($verification_stats_data)): ?>
                            <?php foreach ($verification_stats_data as $row): ?>
                            <tr>
                                <td><span class="badge <?php echo $row['verification_status']; ?>"><?php echo ucfirst($row['verification_status']); ?></span></td>
                                <td><span class="number"><?php echo $row['count']; ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" style="text-align: center; color: #94a3b8;">No data available</td></tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Top Facilities -->
            <div class="report-card">
                <h3><i class="fas fa-star"></i> Most Viewed Facilities</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Facility</th>
                                <th>Type</th>
                                <th>Views</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $top_facilities_data = [];
                            if ($top_facilities && $top_facilities->num_rows > 0) {
                                while ($row = $top_facilities->fetch_assoc()) {
                                    $top_facilities_data[] = $row;
                                }
                            }
                            ?>
                            <?php if (!empty($top_facilities_data)): ?>
                                <?php foreach ($top_facilities_data as $index => $row): ?>
                                <tr>
                                    <td><span class="rank-badge"><?php echo $index + 1; ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                    <td><span class="badge <?php echo $row['facility_type']; ?>"><?php echo ucfirst($row['facility_type']); ?></span></td>
                                    <td><span class="number"><?php echo $row['views_count']; ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; color: #94a3b8;">No data available</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Searches -->
        <div class="report-card" style="margin-top: 0;">
            <h3><i class="fas fa-search"></i> Recent Searches</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Search Term</th>
                            <th>Type</th>
                            <th>Radius</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $recent_searches_data = [];
                        if ($recent_searches && $recent_searches->num_rows > 0) {
                            while ($row = $recent_searches->fetch_assoc()) {
                                $recent_searches_data[] = $row;
                            }
                        }
                        ?>
                        <?php if (!empty($recent_searches_data)): ?>
                            <?php foreach ($recent_searches_data as $row): ?>
                            <tr>
                                <td><?php echo $row['full_name'] ?? '<em>Guest</em>'; ?></td>
                                <td><?php echo $row['search_term'] ?? '-'; ?></td>
                                <td><span class="badge <?php echo $row['search_type']; ?>"><?php echo ucfirst($row['search_type']); ?></span></td>
                                <td><?php echo $row['radius']; ?> km</td>
                                <td><?php echo date('Y-m-d H:i', strtotime($row['timestamp'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" style="text-align: center; color: #94a3b8;">No search data available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
</body>
</html>