<?php
// customer/favorites.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

redirectIfNotLoggedIn();
if (!isCustomer()) {
    header("Location: " . SITE_URL . "/login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get favorites
$sql = "SELECT f.*, fac.* FROM favorites f 
        JOIN facilities fac ON f.facility_id = fac.facility_id 
        WHERE f.user_id = $user_id";
$favorites = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - PharmaLocator</title>
    
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
            max-width: 1300px;
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
            max-width: 1300px;
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
        }

        .page-header h1 {
            font-size: 32px;
            color: #1e293b;
            position: relative;
            padding-bottom: 10px;
        }

        .page-header h1:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #2c7da0, #2a9d8f);
            border-radius: 2px;
        }

        .back-link {
            text-decoration: none;
            padding: 10px 20px;
            background: #f1f5f9;
            color: #475569;
            border-radius: 40px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }

        .back-link:hover {
            background: #e2e8f0;
            transform: translateX(-5px);
        }

        /* ===== FAVORITES GRID (KEEPING YOUR STRUCTURE) ===== */
        .favorites-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
            gap: 25px; 
            margin-top: 30px; 
        }
        
        .fav-card { 
            background: white; 
            padding: 25px; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
            transition: all 0.3s;
            border: 1px solid #f0f0f0;
            position: relative;
            overflow: hidden;
        }

        .fav-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
        }

        .fav-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2c7da0, #2a9d8f);
        }
        
        .fav-card h3 {
            font-size: 20px;
            margin-bottom: 10px;
            color: #1e293b;
            padding-right: 30px;
        }

        .fav-card .type-badge {
            margin-bottom: 15px;
        }
        
        .badge { 
            display: inline-block; 
            padding: 6px 12px; 
            border-radius: 30px; 
            color: white; 
            font-size: 12px; 
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-pharmacy { 
            background: linear-gradient(135deg, #2a9d8f, #1e7a6a); 
        }
        
        .badge-hospital { 
            background: linear-gradient(135deg, #e76f51, #c44536); 
        }
        
        .badge-clinic { 
            background: linear-gradient(135deg, #2c7da0, #1e5f7a); 
        }

        .fav-card p {
            color: #64748b;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .fav-card p i {
            color: #2c7da0;
            width: 20px;
        }

        .fav-card .address {
            font-size: 14px;
            line-height: 1.5;
        }

        .fav-card .phone {
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            border-top: 1px solid #f1f5f9;
            padding-top: 20px;
        }

        .btn-small {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 40px;
            font-size: 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.3s;
            flex: 1;
            border: none;
            cursor: pointer;
        }

        .btn-view {
            background: linear-gradient(135deg, #2c7da0, #1e5f7a);
            color: white;
            box-shadow: 0 4px 10px rgba(44,125,160,0.2);
        }

        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44,125,160,0.3);
        }

        .btn-remove {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-remove:hover {
            background: #fecaca;
            transform: translateY(-2px);
        }

        /* Empty State Card */
        .empty-state {
            background: white;
            border-radius: 24px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: 1px solid #f0f0f0;
            max-width: 500px;
            margin: 50px auto;
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

        .empty-state .btn-primary {
            text-decoration: none;
            padding: 14px 30px;
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

        .empty-state .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(44,125,160,0.4);
        }

        /* Stats Summary */
        .stats-summary {
            background: white;
            border-radius: 16px;
            padding: 20px 25px;
            margin-bottom: 30px;
            border: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .stats-summary i {
            font-size: 24px;
            color: #2c7da0;
            background: #f0f9ff;
            padding: 12px;
            border-radius: 50%;
        }

        .stats-summary .stats-text {
            flex: 1;
        }

        .stats-summary .stats-text strong {
            font-size: 18px;
            color: #1e293b;
        }

        .stats-summary .stats-text span {
            color: #64748b;
            display: block;
            font-size: 14px;
        }

        /* Footer */
        footer {
            background: white;
            padding: 20px 5%;
            margin-top: auto;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
        }

        .footer-container {
            max-width: 1300px;
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

            .favorites-grid {
                grid-template-columns: 1fr;
            }

            .footer-container {
                flex-direction: column;
                text-align: center;
            }

            .stats-summary {
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
                    <div class="logo-icon">💊</div>
                    <div class="logo-text">
                        PharmaLocator
                        <span>Find Care, Fast</span>
                    </div>
                </a>
            </div>

            <!-- CENTER: Navigation (KEEPING YOUR LINKS) -->
            <nav class="nav-center">
                <a href="dashboard.php">Home</a>
                <a href="favorites.php" class="active">Favorites</a>
            </nav>

            <!-- RIGHT: User Menu -->
            <div class="user-menu">
                <span class="user-name">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?>
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
            <h1><i class="fas fa-heart" style="color: #ef4444; margin-right: 10px;"></i> My Favorite Facilities</h1>
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Stats Summary (if there are favorites) -->
        <?php if ($favorites->num_rows > 0): ?>
        <div class="stats-summary">
            <i class="fas fa-heart" style="color: #ef4444;"></i>
            <div class="stats-text">
                <strong>You have <?php echo $favorites->num_rows; ?> saved favorite <?php echo $favorites->num_rows == 1 ? 'facility' : 'facilities'; ?></strong>
                <span>Quick access to your preferred healthcare facilities</span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Favorites Grid (KEEPING YOUR PHP STRUCTURE) -->
        <?php if ($favorites->num_rows == 0): ?>
            <div class="empty-state">
                <i class="fas fa-heart-broken"></i>
                <h3>No favorites yet</h3>
                <p>Start adding facilities to your favorites from the map! Click the heart icon on any facility to save it here.</p>
                <a href="dashboard.php" class="btn-primary">
                    <i class="fas fa-map-marked-alt"></i> Go to Map
                </a>
            </div>
        <?php else: ?>
            <div class="favorites-grid">
                <?php while($fav = $favorites->fetch_assoc()): ?>
                <div class="fav-card">
                    <h3><?php echo htmlspecialchars($fav['name']); ?></h3>
                    <div class="type-badge">
                        <span class="badge badge-<?php echo $fav['facility_type']; ?>">
                            <i class="fas fa-<?php echo $fav['facility_type'] == 'pharmacy' ? 'prescription-bottle' : ($fav['facility_type'] == 'hospital' ? 'hospital' : 'clinic'); ?>"></i>
                            <?php echo ucfirst($fav['facility_type']); ?>
                        </span>
                    </div>
                    <p class="address">
                        <i class="fas fa-map-marker-alt"></i>
                        <?php echo htmlspecialchars($fav['address']); ?>
                    </p>
                    <p class="phone">
                        <i class="fas fa-phone-alt"></i>
                        <?php echo htmlspecialchars($fav['phone'] ?? 'Phone not available'); ?>
                    </p>
                    <?php if (!empty($fav['opening_hours'])): ?>
                    <p>
                        <i class="fas fa-clock"></i>
                        <?php echo htmlspecialchars($fav['opening_hours']); ?>
                    </p>
                    <?php endif; ?>
                    
                    <div class="action-buttons">
                        <a href="dashboard.php?focus=<?php echo $fav['facility_id']; ?>" class="btn-small btn-view">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="remove_favorite.php?id=<?php echo $fav['facility_id']; ?>" class="btn-small btn-remove" onclick="return confirm('Remove this facility from your favorites?')">
                            <i class="fas fa-trash-alt"></i> Remove
                        </a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- ===== FOOTER ===== -->
    <footer>
        <div class="footer-container">
            <div class="footer-copyright">
                &copy; 2026 PharmaLocator. All rights reserved.
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