<?php
// business/manage_products.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isLoggedIn() || !isBusinessOwner()) {
    header("Location: " . SITE_URL . "/login.html");
    exit();
}

$facility_id = intval($_GET['facility_id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Verify this facility belongs to this owner
$check = $conn->query("SELECT f.* FROM facilities f 
                      JOIN business_owners bo ON f.owner_id = bo.owner_id 
                      WHERE f.facility_id = $facility_id AND bo.user_id = $user_id");

if ($check->num_rows == 0) {
    die("Unauthorized access");
}

$facility = $check->fetch_assoc();

// Handle delete
if (isset($_GET['delete'])) {
    $product_id = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE product_id = $product_id AND facility_id = $facility_id");
    header("Location: manage_products.php?facility_id=$facility_id");
    exit();
}

// Get products
$products = $conn->query("SELECT * FROM products WHERE facility_id = $facility_id ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - PharmaLocator</title>
    
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

        /* Page Header */
        .page-header {
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

        .header-left h1 {
            font-size: 24px;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 5px;
        }

        .header-left h1 i {
            color: #2c7da0;
        }

        .facility-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #64748b;
        }

        .facility-info i {
            color: #2c7da0;
        }

        .btn-add {
            text-decoration: none;
            padding: 12px 24px;
            background: linear-gradient(135deg, #2a9d8f, #1e7a6a);
            color: white;
            border-radius: 40px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(42,157,143,0.3);
        }

        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(42,157,143,0.4);
        }

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

        /* Products Table */
        .table-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background: #f8fafc;
            border-bottom: 2px solid #e2e8f0;
        }

        th {
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 14px;
        }

        td {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        tbody tr:hover {
            background: #f8fafc;
        }

        .stock-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .in_stock {
            background: #d1fae5;
            color: #065f46;
        }
        
        .low_stock {
            background: #fff3cd;
            color: #856404;
        }
        
        .out_of_stock {
            background: #fee2e2;
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-small {
            text-decoration: none;
            padding: 8px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .btn-edit:hover {
            background: #ffe7a3;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .btn-delete:hover {
            background: #fecaca;
            transform: translateY(-2px);
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

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            table {
                font-size: 14px;
            }

            .action-buttons {
                flex-direction: column;
            }

            td, th {
                padding: 12px 8px;
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
                    <div class="logo-icon"></div>
                    <div class="logo-text">
                        PharmaLocator
                        <span>Find Care, Fast</span>
                    </div>
                </a>
            </div>

            <nav class="nav-center">
                <a href="dashboard.php">Dashboard</a>
                <a href="add_product.php?facility_id=<?php echo $facility_id; ?>" class="active">Products</a>
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
        <a href="dashboard.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <!-- Page Header -->
        <div class="page-header">
            <div class="header-left">
                <h1>
                    <i class="fas fa-pills"></i> 
                    Manage Products
                </h1>
                <div class="facility-info">
                    <i class="fas fa-building"></i>
                    <?php echo htmlspecialchars($facility['name']); ?>
                    <span class="badge pharmacy" style="padding: 4px 12px; background: #2a9d8f; color: white; border-radius: 30px; font-size: 12px;">
                        <?php echo ucfirst($facility['facility_type']); ?>
                    </span>
                </div>
            </div>
            <a href="add_product.php?facility_id=<?php echo $facility_id; ?>" class="btn-add">
                <i class="fas fa-plus-circle"></i> Add New Product
            </a>
        </div>

        <?php if ($products->num_rows == 0): ?>
            <div class="empty-state">
                <i class="fas fa-pills"></i>
                <h3>No products yet</h3>
                <p>Start adding products to your inventory. They will appear here and on your public facility page.</p>
                <a href="add_product.php?facility_id=<?php echo $facility_id; ?>" class="btn-add">
                    <i class="fas fa-plus-circle"></i> Add Your First Product
                </a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Stock Status</th>
                            <th>Quantity</th>
                            <th>Expiry Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($p = $products->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($p['name']); ?></strong>
                                <?php if ($p['prescription_required']): ?>
                                    <span style="margin-left: 8px; font-size: 11px; background: #f1f5f9; padding: 2px 8px; border-radius: 30px;">Rx</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo formatPrice($p['price']); ?></strong></td>
                            <td>
                                <span class="stock-badge <?php echo $p['stock_status']; ?>">
                                    <?php echo str_replace('_', ' ', $p['stock_status']); ?>
                                </span>
                            </td>
                            <td><?php echo $p['quantity']; ?></td>
                            <td>
                                <?php if ($p['expiry_date']): ?>
                                    <?php echo date('d/m/Y', strtotime($p['expiry_date'])); ?>
                                <?php else: ?>
                                    <span style="color: #94a3b8;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_product.php?id=<?php echo $p['product_id']; ?>" class="btn-small btn-edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <a href="?facility_id=<?php echo $facility_id; ?>&delete=<?php echo $p['product_id']; ?>" 
                                       class="btn-small btn-delete" 
                                       onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
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
</body>
</html>