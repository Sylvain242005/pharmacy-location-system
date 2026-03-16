<?php
// admin/business_details.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login.html");
    exit();
}

$owner_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($owner_id == 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch business details
$query = "SELECT bo.*, u.full_name, u.email, u.phone, u.created_at as user_since 
          FROM business_owners bo
          JOIN users u ON bo.user_id = u.user_id
          WHERE bo.owner_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$business = $result->fetch_assoc();

if (!$business) {
    header("Location: dashboard.php");
    exit();
}

// Fetch facilities owned by this business
$facilities_query = "SELECT * FROM facilities WHERE owner_id = ?";
$facilities_stmt = $conn->prepare($facilities_query);
$facilities_stmt->bind_param("i", $owner_id);
$facilities_stmt->execute();
$facilities = $facilities_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Details - PharmaLocator</title>
    
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

        .status-badge {
            padding: 8px 16px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .badge.approved {
            background: #d1fae5;
            color: #065f46;
        }

        .badge.pending {
            background: #fff3cd;
            color: #856404;
        }

        .badge.rejected {
            background: #fee2e2;
            color: #991b1b;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
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

        /* Document Section */
        .document-section {
            margin: 25px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }

        .document-section h3 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #1e293b;
        }

        .document-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
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

        .btn-approve {
            background: linear-gradient(135deg, #2a9d8f, #1e7a6a);
            color: white;
            box-shadow: 0 4px 15px rgba(42,157,143,0.3);
        }

        .btn-approve:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(42,157,143,0.4);
        }

        .btn-reject {
            background: linear-gradient(135deg, #e76f51, #c44536);
            color: white;
            box-shadow: 0 4px 15px rgba(231,111,81,0.3);
        }

        .btn-reject:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(231,111,81,0.4);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        /* Facilities Table */
        .facilities-table {
            width: 100%;
            border-collapse: collapse;
        }

        .facilities-table th {
            background: #f8fafc;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #475569;
        }

        .facilities-table td {
            padding: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .facilities-table tr:hover {
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
        <a href="verify_businesses.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Verifications
        </a>

        <!-- Business Details Card -->
        <div class="detail-card">
            <div class="detail-header">
                <h2>
                    <i class="fas fa-store"></i>
                    <?php echo htmlspecialchars($business['business_name']); ?>
                </h2>
                <span class="status-badge badge <?php echo $business['verification_status']; ?>">
                    <i class="fas fa-<?php echo $business['verification_status'] == 'approved' ? 'check-circle' : ($business['verification_status'] == 'pending' ? 'clock' : 'times-circle'); ?>"></i>
                    <?php echo ucfirst($business['verification_status']); ?>
                </span>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-tag"></i> Business Type</div>
                    <div class="info-value"><?php echo ucfirst($business['business_type']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-user"></i> Owner Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($business['full_name']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-envelope"></i> Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($business['email']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label"><i class="fas fa-phone"></i> Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($business['phone']); ?></div>
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
                    <div class="info-label"><i class="fas fa-calendar"></i> Submitted on</div>
                    <div class="info-value"><?php echo date('F j, Y', strtotime($business['created_at'])); ?></div>
                </div>
            </div>

            <?php if (!empty($business['document_path'])): ?>
            <div class="document-section">
                <h3><i class="fas fa-file-pdf"></i> Business Document</h3>
                <img src="../<?php echo htmlspecialchars($business['document_path']); ?>" 
                     alt="Business Document" class="document-image">
                <div style="margin-top: 10px;">
                    <a href="../<?php echo htmlspecialchars($business['document_path']); ?>" target="_blank" class="btn btn-secondary">
                        <i class="fas fa-external-link-alt"></i> View Full Size
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <div class="action-buttons">
                <button class="btn btn-approve" onclick="verifyBusiness(<?php echo $owner_id; ?>, 'approve')">
                    <i class="fas fa-check-circle"></i> Approve Business
                </button>
                <button class="btn btn-reject" onclick="verifyBusiness(<?php echo $owner_id; ?>, 'reject')">
                    <i class="fas fa-times-circle"></i> Reject Business
                </button>
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-tachometer-alt"></i> Go to Dashboard
                </a>
            </div>
        </div>

        <!-- Owned Facilities -->
        <?php if ($facilities && $facilities->num_rows > 0): ?>
        <div class="detail-card">
            <h3><i class="fas fa-building"></i> Owned Facilities</h3>
            <div class="table-responsive">
                <table class="facilities-table">
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
                            <td>
                                <span class="badge <?php echo $facility['facility_type']; ?>">
                                    <?php echo ucfirst($facility['facility_type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($facility['address']); ?></td>
                            <td>
                                <?php if ($facility['verified']): ?>
                                    <span class="badge approved">Verified</span>
                                <?php else: ?>
                                    <span class="badge pending">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="facility_details.php?id=<?php echo $facility['facility_id']; ?>" class="btn-small btn-view">
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
        if (!confirm('Are you sure you want to ' + action + ' this business?')) {
            return;
        }

        fetch('../api/verify_business.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ owner_id: ownerId, action: action })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Business ' + action + 'd successfully!');
                window.location.href = 'verify_businesses.php';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
    </script>
</body>
</html>