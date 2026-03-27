<?php
// business/add_facility.php
require_once '../includes/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a business owner
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'business_owner') {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get owner_id for the current user
$get_owner = $conn->query("SELECT owner_id FROM business_owners WHERE user_id = $user_id");
if ($get_owner->num_rows == 0) {
    // If no owner record exists, redirect to complete profile
    header("Location: complete_profile.php");
    exit();
}
$owner = $get_owner->fetch_assoc();
$owner_id = $owner['owner_id'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize inputs
    $name = trim($_POST['name']);
    $type = trim($_POST['type']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $lat = floatval($_POST['latitude']);
    $lng = floatval($_POST['longitude']);
    $description = trim($_POST['description'] ?? '');
    
    // Basic validation
    $errors = [];
    if (empty($name)) $errors[] = "Facility name is required";
    if (empty($type)) $errors[] = "Facility type is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    if ($lat == 0 || $lng == 0) $errors[] = "Please select a location on the map";
    
    if (empty($errors)) {
        // Insert facility with verified = 1 (auto-approved)
        $sql = "INSERT INTO facilities (owner_id, name, facility_type, address, phone, latitude, longitude, description, verified, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'approved', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssdds", $owner_id, $name, $type, $address, $phone, $lat, $lng, $description);
        
        if ($stmt->execute()) {
            $facility_id = $conn->insert_id;
            $_SESSION['success'] = "Facility added successfully!";
            header("Location: dashboard.php?msg=Facility+added+successfully");
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Get facility types for dropdown
$types_result = $conn->query("SELECT DISTINCT facility_type FROM facilities ORDER BY facility_type");
$facility_types = [];
if ($types_result) {
    while ($row = $types_result->fetch_assoc()) {
        $facility_types[] = $row['facility_type'];
    }
}

// Default types if none exist
if (empty($facility_types)) {
    $facility_types = ['pharmacy', 'hospital', 'clinic', 'health center', 'laboratory', 'optician', 'dentist', 'other'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Facility - PharmaLocator</title>
    
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
            max-width: 900px;
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
            margin-top: 5px;
        }

        /* Info Note */
        .info-note {
            background: #e3f2fd;
            border-left: 4px solid #2c7da0;
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #1e5f7a;
        }

        .info-note i {
            font-size: 20px;
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

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
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

        /* Map Section */
        .map-section {
            margin: 30px 0 20px;
        }

        .map-label {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .map-label i {
            color: #2c7da0;
        }

        .map-container {
            border-radius: 16px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
            margin-bottom: 10px;
        }

        #map {
            height: 300px;
            width: 100%;
        }

        .map-help {
            font-size: 13px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-top: 8px;
        }

        .coords-display {
            background: #f8fafc;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            text-align: center;
            margin: 10px 0;
            border: 1px solid #e2e8f0;
            color: #475569;
        }

        .coords-display i {
            color: #2c7da0;
            margin-right: 5px;
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
                <a href="add_facility.php" class="active">Add Facility</a>
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
        <div class="page-header">
            <h1>
                <i class="fas fa-plus-circle"></i>
                Add New Facility
            </h1>
            <p>Fill in the details below to register your facility</p>
        </div>

        <!-- Info note showing auto-verification -->
        <div class="info-note">
            <i class="fas fa-info-circle"></i>
            <span>Facilities are automatically verified and will appear immediately in the system.</span>
        </div>

        <div class="form-card">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['msg']); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="facilityForm">
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label><i class="fas fa-store"></i> Facility Name *</label>
                        <input type="text" id="name" name="name" required 
                               placeholder="e.g., Pharmacie Universel" 
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Facility Type *</label>
                       <select id="type" name="type" required>
    <option value="">Select Type</option>
    <?php
    $types = ['pharmacy', 'hospital', 'clinic', 'health center', 'laboratory', 'optician', 'dentist', 'other'];
    foreach ($types as $t) {
        echo "<option value=\"$t\">" . ucfirst($t) . "</option>";
    }
    ?>
</select>
                            </select>
                            
                            <?php foreach ($facility_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" 
                                    <?php echo (isset($_POST['type']) && $_POST['type'] == $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required 
                               placeholder="e.g., 612345678" 
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-map-marker-alt"></i> Street Address *</label>
                        <input type="text" id="address" name="address" required 
                               placeholder="e.g., Carrefour Obili, Yaoundé" 
                               value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                    </div>

                    <div class="form-group full-width">
                        <label><i class="fas fa-align-left"></i> Description (Optional)</label>
                        <textarea id="description" name="description" rows="3" 
                                  placeholder="Brief description of your facility..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>
                </div>

                <!-- Map Section -->
                <div class="map-section">
                    <div class="map-label">
                        <i class="fas fa-map-pin"></i>
                        <span>Location on Map *</span>
                    </div>
                    
                    <p class="map-help">
                        <i class="fas fa-info-circle"></i>
                        Click on the map to set your facility's exact location
                    </p>
                    
                    <div class="map-container">
                        <div id="map"></div>
                    </div>
                    
                    <div class="coords-display" id="coords">
                        <i class="fas fa-location-dot"></i>
                        No location selected yet
                    </div>
                </div>

                <input type="hidden" id="latitude" name="latitude" value="<?php echo isset($_POST['latitude']) ? $_POST['latitude'] : ''; ?>">
                <input type="hidden" id="longitude" name="longitude" value="<?php echo isset($_POST['longitude']) ? $_POST['longitude'] : ''; ?>">

                <div class="action-buttons">
                    <a href="dashboard.php" class="btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus-circle"></i> Add Facility
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

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map
        var map = L.map('map').setView([3.8480, 11.5021], 6); // Default to Cameroon center
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        var marker;
        
        // Function to update marker position
        function placeMarker(lat, lng) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }
            
            document.getElementById('latitude').value = lat;
            document.getElementById('longitude').value = lng;
            document.getElementById('coords').innerHTML = 
                `<i class="fas fa-check-circle" style="color: #2c7da0;"></i> Selected Location: Latitude: ${lat.toFixed(6)}, Longitude: ${lng.toFixed(6)}`;
        }
        
        // Handle map clicks
        map.on('click', function(e) {
            placeMarker(e.latlng.lat, e.latlng.lng);
        });
        
        // If coordinates were previously submitted, restore marker
        <?php if (isset($_POST['latitude']) && isset($_POST['longitude']) && $_POST['latitude'] != '' && $_POST['longitude'] != ''): ?>
            placeMarker(<?php echo $_POST['latitude']; ?>, <?php echo $_POST['longitude']; ?>);
        <?php endif; ?>
        
        // Form validation
        document.getElementById('facilityForm').onsubmit = function(e) {
            var lat = document.getElementById('latitude').value;
            var lng = document.getElementById('longitude').value;
            
            if (!lat || !lng || lat == '' || lng == '') {
                e.preventDefault();
                alert('Please click on the map to select your facility location.');
                return false;
            }
            
            return true;
        };
    </script>
</body>
</html>