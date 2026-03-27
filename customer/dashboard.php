<?php
// customer/dashboard.php
session_start();
include '../db.php';

// Check if user is logged in and is customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'customer') {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's favorites count
$fav_sql = "SELECT COUNT(*) as count FROM favorites WHERE user_id = ?";
$fav_stmt = $conn->prepare($fav_sql);
$fav_stmt->bind_param("i", $user_id);
$fav_stmt->execute();
$favorites_count = $fav_stmt->get_result()->fetch_assoc()['count'];

// Get all verified facilities for map
$facilities_sql = "SELECT * FROM facilities WHERE verified = 1 ORDER BY name";
$facilities_result = $conn->query($facilities_sql);
$facilities = [];
while ($row = $facilities_result->fetch_assoc()) {
    $facilities[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PharmaLocator</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css">
    
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
        .dashboard-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 30px;
            flex: 1;
            width: 100%;
        }

        /* Welcome Section */
        .welcome-section {
            background: linear-gradient(135deg, #2c7da0, #1e5f7a);
            color: white;
            padding: 40px;
            border-radius: 24px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(44,125,160,0.2);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.1"><path d="M20 20 L80 20 L80 80 L20 80 Z" fill="none" stroke="white" stroke-width="2"/><circle cx="50" cy="50" r="15" fill="none" stroke="white" stroke-width="2"/></svg>') repeat;
            opacity: 0.1;
        }

        .welcome-section h2 {
            font-size: 32px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }

        .welcome-section p {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }

        /* Stats Grid - KEEPING YOUR EXACT STRUCTURE */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #f0f0f0;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        }
        
        .stat-card .number {
            font-size: 42px;
            font-weight: bold;
            color: #2c7da0;
            margin: 10px 0;
            line-height: 1.2;
        }

        .stat-card i {
            font-size: 32px;
            color: #94a3b8;
            margin-bottom: 10px;
        }
        
        /* Search Panel - KEEPING YOUR EXACT STRUCTURE */
        .search-panel {
            background: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #f0f0f0;
        }
        
        .search-panel h3 {
            margin-bottom: 20px;
            color: #1e293b;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-panel h3 i {
            color: #2c7da0;
        }
        
        .search-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .search-controls select,
        .search-controls button {
            padding: 14px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s;
            background: white;
        }

        .search-controls select:focus {
            border-color: #2c7da0;
            outline: none;
            box-shadow: 0 0 0 4px rgba(44,125,160,0.1);
        }
        
        .search-controls button {
            background: linear-gradient(135deg, #2c7da0, #1e5f7a);
            color: white;
            border: none;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .search-controls button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(44,125,160,0.3);
        }
        
        /* Map Container - KEEPING YOUR EXACT STRUCTURE */
        .map-container {
            position: relative;
            margin-bottom: 20px;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        #map {
            height: 500px;
            border-radius: 20px;
            z-index: 1;
        }
        
        /* Results Sidebar - KEEPING YOUR EXACT STRUCTURE */
        .results-sidebar {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 320px;
            max-height: 460px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            z-index: 1000;
            overflow: hidden;
            display: none;
            border: 1px solid #f0f0f0;
        }
        
        .results-header {
            padding: 18px;
            background: linear-gradient(135deg, #2c7da0, #1e5f7a);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .results-header h3 {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .results-list {
            max-height: 400px;
            overflow-y: auto;
            padding: 10px;
        }
        
        .result-item {
            padding: 15px;
            border-bottom: 1px solid #f1f5f9;
            cursor: pointer;
            transition: background 0.2s;
            border-radius: 8px;
        }
        
        .result-item:hover {
            background: #f8fafc;
        }
        
        .result-item .name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #1e293b;
        }
        
        .result-item .type {
            font-size: 12px;
            color: #64748b;
            background: #f1f5f9;
            padding: 2px 8px;
            border-radius: 12px;
        }
        
        .result-item .distance {
            font-size: 12px;
            color: #2c7da0;
            font-weight: 600;
        }
        
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 0;
            width: auto;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .close-btn:hover {
            opacity: 1;
        }
        
        /* Modal Styles - KEEPING YOUR EXACT STRUCTURE */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            width: 90%;
            max-width: 800px;
            border-radius: 24px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        }
        
        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.3s;
        }
        
        .close:hover {
            color: #1e293b;
        }
        
        /* Tab Styles - KEEPING YOUR EXACT STRUCTURE */
        .tab {
            overflow: hidden;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            border-radius: 12px 12px 0 0;
            margin: 20px 0;
        }
        
        .tab button {
            background-color: inherit;
            float: left;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 14px 20px;
            transition: 0.3s;
            font-size: 15px;
            font-weight: 500;
            color: #64748b;
            width: 50%;
        }
        
        .tab button:hover {
            background-color: #e2e8f0;
            color: #1e293b;
        }
        
        .tab button.active {
            background-color: #2c7da0;
            color: white;
        }
        
        .tabcontent {
            display: none;
            padding: 25px;
            border: 1px solid #e2e8f0;
            border-top: none;
            border-radius: 0 0 12px 12px;
        }
        
        /* Product Grid - KEEPING YOUR EXACT STRUCTURE */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .product-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
            background: white;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .product-price {
            font-size: 20px;
            font-weight: bold;
            color: #2c7da0;
            margin: 10px 0;
        }
        
        /* Badge Styles */
        .badge-customer {
            background: #f1f5f9;
            color: #475569;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 13px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge-pharmacy { background: #d1fae5; color: #065f46; padding: 4px 12px; border-radius: 30px; font-size: 12px; }
        .badge-hospital { background: #fee2e2; color: #991b1b; padding: 4px 12px; border-radius: 30px; font-size: 12px; }
        .badge-clinic { background: #dbeafe; color: #1e40af; padding: 4px 12px; border-radius: 30px; font-size: 12px; }
        
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
            
            .results-sidebar {
                position: fixed;
                top: auto;
                bottom: 0;
                right: 0;
                left: 0;
                width: 100%;
                max-height: 50vh;
                border-radius: 20px 20px 0 0;
            }
            
            .welcome-section {
                padding: 30px 20px;
            }
            
            .welcome-section h2 {
                font-size: 24px;
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
                <a href="dashboard.php" class="active">Home</a>
                <a href="favorites.php">Favorites (<?php echo $favorites_count; ?>)</a>
                
            </nav>

            <!-- RIGHT: User Menu -->
            <div class="user-menu">
                <span class="user-name">
                    <i class="fas fa-user-circle"></i>
    
                </span>
                <a href="../logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- ===== MAIN DASHBOARD CONTENT ===== -->
    <div class="dashboard-container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Welcome back</h2>
            <p>Find healthcare facilities near you, check medicine availability, and get directions instantly.</p>
        </div>

        <!-- Statistics (KEEPING YOUR EXACT STRUCTURE) -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-building"></i>
                <div class="number"><?php echo count($facilities); ?></div>
                <div>Total Facilities</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-heart"></i>
                <div class="number"><?php echo $favorites_count; ?></div>
                <div>Your Favorites</div>
            </div>
            
            <div class="stat-card">
                <i class="fas fa-location-dot"></i>
                <div class="number"><span id="nearbyCount">0</span></div>
                <div>Nearby Found</div>
            </div>

            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <div class="number">24/7</div>
                <div>Emergency Access</div>
            </div>
        </div>

        <!-- Search Panel (KEEPING YOUR EXACT STRUCTURE) -->
        <div class="search-panel">
            <h3>
                <i class="fas fa-search"></i>
                Find Nearby Facilities
            </h3>
            <div class="search-controls">
                <select id="facilityType">
                    <option value="all">All Facilities</option>
                    <option value="pharmacy">Pharmacies Only</option>
                    <option value="hospital">Hospitals Only</option>
                    <option value="clinic">Clinics Only</option>
                </select>
                
                <select id="radiusSelect">
                    <option value="5">Within 5 km</option>
                    <option value="10" selected>Within 10 km</option>
                    <option value="20">Within 20 km</option>
                    <option value="50">Within 50 km</option>
                </select>
                
                <button onclick="searchNearby()">
                    <i class="fas fa-search"></i> Search Nearby
                </button>
            </div>
        </div>

        <!-- Map Container (KEEPING YOUR EXACT STRUCTURE) -->
        <div class="map-container">
            <div id="map"></div>
            
            <!-- Results Sidebar (KEEPING YOUR EXACT STRUCTURE) -->
            <div id="resultsSidebar" class="results-sidebar">
                <div class="results-header">
                    <h3>
                        <i class="fas fa-list"></i>
                        Nearby Facilities <span id="resultCount"></span>
                    </h3>
                    <button class="close-btn" onclick="toggleSidebar()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="resultsList" class="results-list">
                    <!-- Results will be loaded here -->
                </div>
            </div>
        </div>
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

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    
    <script>
    // ===== YOUR EXISTING JAVASCRIPT - 100% INTACT =====
    const allFacilities = <?php echo json_encode($facilities); ?>;
    let map;
    let userMarker;
    let markers = [];
    let routeControl;
    let userLocation = null;
    
    // Icon definitions
    const redIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    const pharmacyIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    const hospitalIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    const clinicIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    // Initialize map
    function initMap(lat, lon) {
        userLocation = { lat, lon };
        
        map = L.map('map').setView([lat, lon], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        
        // Add user marker
        userMarker = L.marker([lat, lon], { icon: redIcon })
            .addTo(map)
            .bindPopup('You are here')
            .openPopup();
    }
    
    // Get user location
    navigator.geolocation.getCurrentPosition(
        position => {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            if (!map) {
                initMap(lat, lon);
            } else {
                userMarker.setLatLng([lat, lon]);
            }
            
            userLocation = { lat, lon };
        },
        error => {
            console.error('Geolocation error:', error);
            alert('Please enable location access to find nearby facilities.');
            // Default to Yaoundé
            initMap(3.8480, 11.5021);
        },
        { enableHighAccuracy: true }
    );
    
    // Calculate distance using Haversine formula
    function calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    // Search nearby facilities
    function searchNearby() {
        if (!userLocation) {
            alert('Waiting for your location...');
            return;
        }
        
        const type = document.getElementById('facilityType').value;
        const radius = parseInt(document.getElementById('radiusSelect').value);
        
        // Clear existing markers
        markers.forEach(m => map.removeLayer(m));
        markers = [];
        
        // Filter facilities
        let filtered = allFacilities.filter(f => {
            if (type !== 'all' && f.facility_type !== type) return false;
            
            const distance = calculateDistance(
                userLocation.lat, userLocation.lon,
                parseFloat(f.latitude), parseFloat(f.longitude)
            );
            f.distance = distance;
            return distance <= radius;
        });
        
        // Sort by distance
        filtered.sort((a, b) => a.distance - b.distance);
        
        // Update count
        document.getElementById('nearbyCount').textContent = filtered.length;
        
        // Display on map
        displayFacilities(filtered);
        
        // Show sidebar with results
        displayResultsList(filtered);
        toggleSidebar(true);
    }
    
    // Display facilities on map
    function displayFacilities(facilities) {
        facilities.forEach(f => {
            let icon;
            switch(f.facility_type) {
                case 'pharmacy': icon = pharmacyIcon; break;
                case 'hospital': icon = hospitalIcon; break;
                case 'clinic': icon = clinicIcon; break;
                default: icon = pharmacyIcon;
            }
            
            const marker = L.marker([f.latitude, f.longitude], { icon }).addTo(map);
            
            marker.bindPopup(`
                <div style="min-width: 200px;">
                    <h3 style="margin: 0 0 10px 0;">${f.name}</h3>
                    <p><strong>Type:</strong> ${f.facility_type}</p>
                    <p><strong>Distance:</strong> ${f.distance.toFixed(2)} km</p>
                    <p>${f.address.substring(0, 50)}...</p>
                    <p>${f.phone || 'N/A'}</p>
                    <button onclick="showFacilityDetails(${f.facility_id})" 
                            style="width: 100%; margin: 5px 0; background: #007BFF;">
                        View Details
                    </button>
                    <button onclick="showRoute(${f.latitude}, ${f.longitude})" 
                            style="width: 100%; margin: 5px 0; background: #28a745;">
                        Get Directions
                    </button>
                </div>
            `);
            
            markers.push(marker);
        });
        
        // Fit bounds
        if (facilities.length > 0) {
            const bounds = L.latLngBounds(facilities.map(f => [f.latitude, f.longitude]));
            bounds.extend([userLocation.lat, userLocation.lon]);
            map.fitBounds(bounds, { padding: [50, 50] });
        }
    }
    
    // Display results list in sidebar
    function displayResultsList(facilities) {
        const list = document.getElementById('resultsList');
        const countSpan = document.getElementById('resultCount');
        
        countSpan.textContent = `(${facilities.length})`;
        
        if (facilities.length === 0) {
            list.innerHTML = '<p style="text-align: center; padding: 20px; color: #666;">No facilities found nearby</p>';
            return;
        }
        
        list.innerHTML = facilities.map(f => `
            <div class="result-item" onclick="focusFacility(${f.facility_id})">
                <div class="name">${f.name}</div>
                <div style="display: flex; justify-content: space-between; margin-top: 5px;">
                    <span class="type">${f.facility_type}</span>
                    <span class="distance">${f.distance.toFixed(1)} km</span>
                </div>
                <div style="font-size: 12px; color: #666; margin-top: 5px;">${f.address.substring(0, 60)}...</div>
            </div>
        `).join('');
    }
    
    // Focus on a facility
    function focusFacility(facilityId) {
        const facility = allFacilities.find(f => f.facility_id == facilityId);
        if (facility) {
            map.setView([facility.latitude, facility.longitude], 16);
            showFacilityDetails(facilityId);
        }
    }
    
    // Show facility details in modal
    function showFacilityDetails(facilityId) {
        fetch(`../api/get_facility_details.php?id=${facilityId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showDetailsModal(data.facility);
                }
            })
            .catch(error => console.error('Error:', error));
    }
    
    // Show details modal
    function showDetailsModal(facility) {
        // Remove existing modal if any
        const existingModal = document.getElementById('detailsModal');
        if (existingModal) existingModal.remove();
        
        // Create modal
        const modal = document.createElement('div');
        modal.id = 'detailsModal';
        modal.className = 'modal';
        modal.style.display = 'block';
        
        let content = `
            <div class="modal-content">
                <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
                <h2>${facility.name}</h2>
                <div style="margin-bottom: 20px;">
                    <span class="badge badge-${facility.facility_type}">${facility.facility_type}</span>
                </div>
                
                <div class="tab">
                    <button class="tablinks active" onclick="openTab(event, 'info')">Information</button>
                    <button class="tablinks" onclick="openTab(event, 'items')">
                        ${facility.facility_type === 'pharmacy' ? 'Products' : 'Services'}
                    </button>
                </div>
                
                <div id="info" class="tabcontent" style="display: block;">
                    <p><strong>Address:</strong> ${facility.address}</p>
                    <p><strong>Phone:</strong> ${facility.phone || 'N/A'}</p>
                    <p><strong>Email:</strong> ${facility.email || 'N/A'}</p>
                    <p><strong>Hours:</strong> ${facility.opening_hours || 'N/A'}</p>
                    ${facility.description ? `<p><strong>Description:</strong> ${facility.description}</p>` : ''}
                    <button onclick="addToFavorites(${facility.facility_id})" style="background: #ffc107; color: black; border: none; padding: 10px; border-radius: 8px; cursor: pointer; width: 100%;">
                        <i class="fas fa-heart"></i> Add to Favorites
                    </button>
                </div>
                
                <div id="items" class="tabcontent">
        `;
        
        if (facility.facility_type === 'pharmacy' && facility.products) {
            if (facility.products.length > 0) {
                content += '<div class="product-grid">';
                facility.products.forEach(p => {
                    const stockColor = p.stock_status === 'in_stock' ? 'green' : 
                                      p.stock_status === 'low_stock' ? 'orange' : 'red';
                    content += `
                        <div class="product-card">
                            <strong>${p.name}</strong>
                            <div class="product-price">${Number(p.price).toLocaleString()} XAF</div>
                            <div style="color: ${stockColor};">${p.stock_status.replace('_', ' ')}</div>
                            ${p.expiry_date ? `<div style="font-size: 12px;">Exp: ${p.expiry_date}</div>` : ''}
                        </div>
                    `;
                });
                content += '</div>';
            } else {
                content += '<p>No products listed yet.</p>';
            }
        } else if (facility.services && facility.services.length > 0) {
            content += '<table style="width: 100%; border-collapse: collapse;"><tr style="background: #f8fafc;"><th style="padding: 10px; text-align: left;">Service</th><th style="padding: 10px; text-align: left;">Specialist</th><th style="padding: 10px; text-align: left;">Cost</th></tr>';
            facility.services.forEach(s => {
                content += `
                    <tr style="border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 10px;"><strong>${s.name}</strong><br>${s.description || ''}</td>
                        <td style="padding: 10px;">${s.specialist || 'N/A'}</td>
                        <td style="padding: 10px;">${s.cost_estimate ? Number(s.cost_estimate).toLocaleString() + ' XAF' : 'N/A'}</td>
                    </tr>
                `;
            });
            content += '</table>';
        } else {
            content += '<p>No items listed yet.</p>';
        }
        
        content += '</div></div></div>';
        modal.innerHTML = content;
        document.body.appendChild(modal);
    }
    
    // Open tab in modal
    function openTab(evt, tabName) {
        const tabcontents = document.getElementsByClassName('tabcontent');
        for (let i = 0; i < tabcontents.length; i++) {
            tabcontents[i].style.display = 'none';
        }
        
        const tablinks = document.getElementsByClassName('tablinks');
        for (let i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(' active', '');
        }
        
        document.getElementById(tabName).style.display = 'block';
        evt.currentTarget.className += ' active';
    }
    
    // Show route to destination
    function showRoute(destLat, destLon) {
        if (!userLocation) {
            alert('Location not available');
            return;
        }
        
        if (routeControl) {
            map.removeControl(routeControl);
        }
        
        routeControl = L.Routing.control({
            router: L.Routing.osrmv1({
                serviceUrl: 'https://router.project-osrm.org/route/v1'
            }),
            waypoints: [
                L.latLng(userLocation.lat, userLocation.lon),
                L.latLng(destLat, destLon)
            ],
            routeWhileDragging: false,
            showAlternatives: true
        }).addTo(map);
    }
    
    // Add to favorites
    function addToFavorites(facilityId) {
        const user = JSON.parse(localStorage.getItem('user'));
        if (!user) {
            alert('Please login first');
            return;
        }
        
        fetch('../api/add_favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                user_id: user.id,
                facility_id: facilityId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Added to favorites!');
                // Optionally refresh favorites count
                location.reload();
            } else {
                alert(data.message || 'Already in favorites');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to add to favorites');
        });
    }
    
    // Toggle sidebar
    function toggleSidebar(show) {
        const sidebar = document.getElementById('resultsSidebar');
        if (show === true) {
            sidebar.style.display = 'block';
        } else if (show === false) {
            sidebar.style.display = 'none';
        } else {
            sidebar.style.display = sidebar.style.display === 'none' ? 'block' : 'none';
        }
    }
    </script>
</body>
</html>