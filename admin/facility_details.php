<?php
// admin/facility_details.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

$facility_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($facility_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch facility details
$query = "SELECT f.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone
          FROM facilities f
          LEFT JOIN business_owners bo ON f.owner_id = bo.owner_id
          LEFT JOIN users u ON bo.user_id = u.user_id
          WHERE f.facility_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $facility_id);
$stmt->execute();
$result = $stmt->get_result();
$facility = $result->fetch_assoc();

if (!$facility) {
    header("Location: dashboard.php");
    exit();
}

// Fetch products if this is a pharmacy
$products = null;
if ($facility['facility_type'] == 'pharmacy') {
    $products_query = "SELECT * FROM products WHERE facility_id = ?";
    $products_stmt = $conn->prepare($products_query);
    $products_stmt->bind_param("i", $facility_id);
    $products_stmt->execute();
    $products = $products_stmt->get_result();
}

// Fetch services if this is a hospital/clinic
$services = null;
if ($facility['facility_type'] == 'hospital' || $facility['facility_type'] == 'clinic') {
    $services_query = "SELECT * FROM services WHERE facility_id = ?";
    $services_stmt = $conn->prepare($services_query);
    $services_stmt->bind_param("i", $facility_id);
    $services_stmt->execute();
    $services = $services_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facility Details - PharmaLocator</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
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
            margin-bottom: 25px;
        }

        .detail-header h2 {
            font-size: 28px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .detail-header h2 i {
            color: #2c7da0;
        }

        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 600;
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

        /* Map Container */
        .map-container {
            height: 300px;
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            margin-top: 15px;
        }

        /* Table Styles */
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
        }
    </style>
</head>
<body>
    <!-- ===== HEADER ===== -->
    <header class="new-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="../home.html" class="logo-link">
                    <div class="logo-icon">💊</div>
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
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <!-- Facility Details Card -->
        <div class="detail-card">
            <div class="detail-header">
                <h2>
                    <i class="fas fa-building"></i>
                    <?php echo htmlspecialchars($facility['name']); ?>
                </h2>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <span class="type-badge badge <?php echo $facility['facility_type']; ?>">
                        <i class="fas fa-<?php echo $facility['facility_type'] == 'pharmacy' ? 'prescription-bottle' : ($facility['facility_type'] == 'hospital' ? 'hospital' : 'clinic'); ?>"></i>
                        <?php echo ucfirst($facility['facility_type']); ?>
                    </span>
                    <?php if ($facility['verified']): ?>
                        <span class="type-badge badge approved">
                            <i class="fas fa-check-circle"></i> Verified
                        </span>
                    <?php else: ?>
                        <span class="type-badge badge pending">
                            <i class="fas fa-clock"></i> Pending Verification
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-user"></i> Owner</div>
                    <div class="info-value"><?php echo htmlspecialchars($facility['owner_name'] ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-envelope"></i> Owner Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($facility['owner_email'] ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-phone"></i> Owner Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($facility['owner_phone'] ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-map-marker-alt"></i> Address</div>
                    <div class="info-value"><?php echo htmlspecialchars($facility['address']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-phone-alt"></i> Facility Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($facility['phone'] ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-envelope"></i> Facility Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($facility['email'] ?? 'N/A'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-clock"></i> Hours</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($facility['hours'] ?? 'N/A')); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-calendar"></i> Added on</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($facility['created_at'])); ?></div>
                </div>

                <?php if (!empty($facility['description'])): ?>
                <div class="info-item" style="grid-column: span 2;">
                    <div class="info-label"><i class="fas fa-align-left"></i> Description</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($facility['description'])); ?></div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($facility['latitude']) && !empty($facility['longitude'])): ?>
            <div class="info-item" style="padding: 0; background: none; border: none;">
                <div class="info-label" style="margin-bottom: 10px;"><i class="fas fa-map-pin"></i> Location</div>
                <div id="map" class="map-container"></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Products Section -->
        <?php if ($products && $products->num_rows > 0): ?>
        <div class="detail-card">
            <h3><i class="fas fa-pills"></i> Products Available</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock Status</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($product = $products->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($product['price'], 0); ?> CFA</td>
                            <td>
                                <span class="type-badge badge <?php echo $product['stock_status']; ?>">
                                    <?php echo str_replace('_', ' ', $product['stock_status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars(substr($product['description'] ?? '', 0, 50)) . '...'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Services Section -->
        <?php if ($services && $services->num_rows > 0): ?>
        <div class="detail-card">
            <h3><i class="fas fa-stethoscope"></i> Services Offered</h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Service Name</th>
                            <th>Category</th>
                            <th>Specialist</th>
                            <th>Cost Estimate</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($service = $services->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($service['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($service['category'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($service['specialist'] ?? 'N/A'); ?></td>
                            <td><?php echo $service['cost_estimate'] ? number_format($service['cost_estimate'], 0) . ' CFA' : 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($service['duration'] ?? 'N/A'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
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

    <!-- Leaflet JavaScript -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <?php if (!empty($facility['latitude']) && !empty($facility['longitude'])): ?>
    <script>
        // Initialize map
        var map = L.map('map').setView([<?php echo $facility['latitude']; ?>, <?php echo $facility['longitude']; ?>], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        var marker = L.marker([<?php echo $facility['latitude']; ?>, <?php echo $facility['longitude']; ?>]).addTo(map);
        marker.bindPopup("<strong><?php echo htmlspecialchars($facility['name']); ?></strong>").openPopup();
    </script>
    <?php endif; ?>
</body>
</html>