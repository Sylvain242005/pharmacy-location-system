<?php
// business/edit_service.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isBusinessOwner()) {
    header("Location: " . SITE_URL . "/login.html");
    exit();
}

$service_id = intval($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Get service and verify ownership
$sql = "SELECT s.*, f.facility_id, f.name as facility_name, f.facility_type FROM services s 
        JOIN facilities f ON s.facility_id = f.facility_id
        JOIN business_owners bo ON f.owner_id = bo.owner_id
        WHERE s.service_id = $service_id AND bo.user_id = $user_id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Service not found or unauthorized");
}

$service = $result->fetch_assoc();
$facility_id = $service['facility_id'];
$facility_name = $service['facility_name'];
$facility_type = $service['facility_type'];
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $category = sanitize($_POST['category']);
    $specialist = sanitize($_POST['specialist']);
    $cost_estimate = $_POST['cost_estimate'] ? floatval($_POST['cost_estimate']) : null;
    $duration = sanitize($_POST['duration']);
    $availability = sanitize($_POST['availability']);

    $update = "UPDATE services SET 
                name='$name', description='$description', category='$category',
                specialist='$specialist', cost_estimate=" . ($cost_estimate ? $cost_estimate : "NULL") . ",
                duration='$duration', availability='$availability'
                WHERE service_id = $service_id";
    
    if ($conn->query($update)) {
        $message = "Service updated successfully!";
        $messageType = "success";
        // Refresh service data
        $result = $conn->query($sql);
        $service = $result->fetch_assoc();
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service - PharmaLocator</title>
    
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
            max-width: 800px;
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
            margin-bottom: 8px;
        }

        .page-header h1 i {
            color: #f4a261;
        }

        .facility-info {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #64748b;
        }

        .facility-info i {
            color: #2c7da0;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .badge.hospital {
            background: linear-gradient(135deg, #e76f51, #c44536);
        }

        .badge.clinic {
            background: linear-gradient(135deg, #2c7da0, #1e5f7a);
        }

        /* Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            padding: 40px;
            border: 1px solid #f0f0f0;
        }

        /* Alert Messages */
        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert i {
            font-size: 18px;
        }

        /* Form Elements */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 5px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #475569;
            font-weight: 500;
            font-size: 14px;
        }

        label i {
            color: #2c7da0;
            margin-right: 5px;
        }

        input, select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
            background: #f8fafc;
        }

        input:focus, select:focus, textarea:focus {
            border-color: #2c7da0;
            outline: none;
            box-shadow: 0 0 0 4px rgba(44,125,160,0.1);
            background: white;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-primary {
            flex: 2;
            padding: 14px 30px;
            background: linear-gradient(135deg, #f4a261, #e76f51);
            color: white;
            border: none;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(244,162,97,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(244,162,97,0.4);
        }

        .btn-secondary {
            flex: 1;
            padding: 14px 30px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            border-radius: 40px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        /* Back Link */
        .btn-back {
            text-decoration: none;
            padding: 8px 16px;
            background: #f1f5f9;
            color: #475569;
            border-radius: 30px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }

        .btn-back:hover {
            background: #e2e8f0;
            transform: translateX(-5px);
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
            }

            .action-buttons {
                flex-direction: column;
            }

            .footer-container {
                flex-direction: column;
                text-align: center;
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
                        <span>Find Care, Fast</span>
                    </div>
                </a>
            </div>

            <nav class="nav-center">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_services.php?facility_id=<?php echo $facility_id; ?>">Services</a>
            </nav>

            <div class="user-menu">
                <span class="user-name">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Business Owner'); ?>
                </span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="container">
        <!-- Back Link -->
        <a href="manage_services.php?facility_id=<?php echo $facility_id; ?>" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Services
        </a>

        <div class="page-header">
            <h1>
                <i class="fas fa-edit"></i> 
                Edit Service
            </h1>
            <div class="facility-info">
                <i class="fas fa-building"></i>
                <?php echo htmlspecialchars($facility_name); ?>
                <span class="badge <?php echo $facility_type; ?>">
                    <?php echo ucfirst($facility_type); ?>
                </span>
            </div>
        </div>

        <div class="form-card">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType == 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group full-width">
                    <label><i class="fas fa-tag"></i> Service Name *</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($service['name']); ?>" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-folder"></i> Category</label>
                        <select name="category">
                            <option value="">Select Category</option>
                            <?php
                            $categories = ['Consultation','Emergency','Surgery','Maternity','Pediatrics','Cardiology','Radiology','Laboratory','Dental','Other'];
                            foreach ($categories as $cat) {
                                $selected = ($cat == $service['category']) ? 'selected' : '';
                                echo "<option value=\"$cat\" $selected>$cat</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-user-md"></i> Specialist/Department</label>
                        <input type="text" name="specialist" value="<?php echo htmlspecialchars($service['specialist']); ?>">
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill"></i> Cost Estimate (XAF)</label>
                        <input type="number" name="cost_estimate" min="0" step="1000" value="<?php echo $service['cost_estimate']; ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-clock"></i> Duration</label>
                        <input type="text" name="duration" value="<?php echo htmlspecialchars($service['duration']); ?>">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-align-left"></i> Description *</label>
                    <textarea name="description" rows="4" required><?php echo htmlspecialchars($service['description']); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-calendar-alt"></i> Availability</label>
                    <input type="text" name="availability" value="<?php echo htmlspecialchars($service['availability']); ?>">
                </div>

                <div class="action-buttons">
                    <a href="manage_services.php?facility_id=<?php echo $facility_id; ?>" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save"></i> Update Service
                    </button>
                </div>
            </form>
        </div>
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
</body>
</html>