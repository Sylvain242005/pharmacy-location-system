<?php
// business/add_product.php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Only business owners can access
if (!isBusinessOwner()) {
    header('Location: ' . SITE_URL . '/login.html');
    exit();
}

// Get facility ID from URL
$facility_id = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;
if (!$facility_id) {
    die("No facility selected. Please go back and choose a facility.");
}

// Verify that the facility belongs to this owner
$check = $conn->prepare("SELECT f.* FROM facilities f
                         JOIN business_owners bo ON f.owner_id = bo.owner_id
                         WHERE f.facility_id = ? AND bo.user_id = ?");
$check->bind_param('ii', $facility_id, $_SESSION['user_id']);
$check->execute();
$facility = $check->get_result()->fetch_assoc();

if (!$facility) {
    die("Unauthorized access or facility not found.");
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = sanitize($_POST['name']);
    $description = sanitize($_POST['description']);
    $price       = floatval($_POST['price']);
    $category    = sanitize($_POST['category']);
    $stock_status = sanitize($_POST['stock_status']);
    $quantity    = intval($_POST['quantity']);
    $expiry_date = $_POST['expiry_date'] ?: null;
    $manufacturer = sanitize($_POST['manufacturer']);
    $prescription = isset($_POST['prescription_required']) ? 1 : 0;

    $sql = "INSERT INTO products (facility_id, name, description, price, category, stock_status, quantity, expiry_date, manufacturer, prescription_required)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('issdssisss', $facility_id, $name, $description, $price, $category, $stock_status, $quantity, $expiry_date, $manufacturer, $prescription);

    if ($stmt->execute()) {
        $message = "Product added successfully!";
        $messageType = "success";
        // Optionally reset the form or keep it filled
    } else {
        $message = "Error: " . $conn->error;
        $messageType = "danger";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - PharmaLocator</title>
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
            color: #2c7da0;
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

        .alert a {
            color: inherit;
            font-weight: 600;
            margin-left: 10px;
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
            background: linear-gradient(135deg, #2c7da0, #1e5f7a);
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
            box-shadow: 0 4px 15px rgba(44,125,160,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(44,125,160,0.4);
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
        <header class="new-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="../home.html" class="logo-link">
                    <div class="logo-icon"></div>
                    <div class="logo-text">
                        PharmaLocator
                        <span>Find Care, Fast</span>
                    </div>
                </a>
            </div>

            <nav class="nav-center">
                <a href="dashboard.php">Dashboard</a>
                <a href="manage_products.php?facility_id=<?php echo $facility_id; ?>">Products</a>
                <a href="add_products.php?facility_id=<?php echo $facility_id; ?>">Add Product</a>
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

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-plus-circle"></i> Add Product</h1>
            <div class="facility-info">
                <i class="fas fa-building"></i> <?php echo htmlspecialchars($facility['name']); ?>
                <span class="badge <?php echo $facility['facility_type']; ?>"><?php echo ucfirst($facility['facility_type']); ?></span>
            </div>
        </div>

        <div class="form-card">
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group full-width">
                    <label><i class="fas fa-tag"></i> Product Name </label>
                    <input type="text" name="name" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill"></i> Price (XAF) </label>
                        <input type="number" name="price" min="0" step="50" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-folder"></i> Category</label>
                        <select name="category">
                            <option value="">Select Category</option>
                            <option value="Pain Relief">Pain Relief</option>
                            <option value="Antibiotics">Antibiotics</option>
                            <option value="Vitamins">Vitamins</option>
                            <option value="Cold & Flu">Cold & Flu</option>
                            <option value="Allergy">Allergy</option>
                            <option value="Digestive">Digestive Health</option>
                            <option value="First Aid">First Aid</option>
                            <option value="Other">Other</option>
                        </select>
                          
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-chart-line"></i> Stock Status *</label>
                        <select name="stock_status" required>
                            <option value="in_stock">In Stock</option>
                            <option value="low_stock">Low Stock</option>
                            <option value="out_of_stock">Out of Stock</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-boxes"></i> Quantity</label>
                        <input type="number" name="quantity" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Expiry Date</label>
                        <input type="date" name="expiry_date">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-industry"></i> Manufacturer</label>
                        <input type="text" name="manufacturer">
                    </div>
                </div>

                <div class="form-group full-width">
                    <label><i class="fas fa-align-left"></i> Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" name="prescription_required" id="prescription">
                    <label for="prescription"><i class="fas fa-prescription-bottle"></i> Prescription Required</label>
                </div>

                <div class="action-buttons">
                    <a href="manage_products.php?facility_id=<?php echo $facility_id; ?>" class="btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <footer>...</footer> <!-- same footer -->
</body>
</html>