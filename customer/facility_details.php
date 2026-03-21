<?php
// customer/facility_details.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isCustomer()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

$facility_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$facility_id) {
    header('Location: dashboard.php');
    exit();
}

$stmt = $conn->prepare("SELECT * FROM facilities WHERE facility_id = ? AND verified = 1");
$stmt->bind_param('i', $facility_id);
$stmt->execute();
$facility = $stmt->get_result()->fetch_assoc();

if (!$facility) {
    header('Location: dashboard.php');
    exit();
}

// Fetch products if pharmacy
$products = null;
if ($facility['facility_type'] === 'pharmacy') {
    $prod_stmt = $conn->prepare("SELECT * FROM products WHERE facility_id = ? ORDER BY name");
    $prod_stmt->bind_param('i', $facility_id);
    $prod_stmt->execute();
    $products = $prod_stmt->get_result();
}

// Fetch services if hospital/clinic
$services = null;
if (in_array($facility['facility_type'], ['hospital', 'clinic'])) {
    $serv_stmt = $conn->prepare("SELECT * FROM services WHERE facility_id = ? ORDER BY name");
    $serv_stmt->bind_param('i', $facility_id);
    $serv_stmt->execute();
    $services = $serv_stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($facility['name']); ?> - PharmaLocator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; color: #1e293b; line-height: 1.6; min-height: 100vh; display: flex; flex-direction: column; }
        .new-header { background: white; box-shadow: 0 2px 15px rgba(0,0,0,0.08); position: sticky; top: 0; z-index: 1000; }
        .header-container { max-width: 1400px; margin: 0 auto; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; }
        .logo-link { text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .logo-icon { font-size: 24px; background: linear-gradient(135deg, #2c7da0, #2a9d8f); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 10px; color: white; }
        .logo-text { font-weight: 700; font-size: 18px; color: #1e293b; }
        .logo-text span { color: #64748b; font-weight: 400; font-size: 12px; display: block; line-height: 1.2; }
        .nav-center { display: flex; gap: 32px; }
        .nav-center a { text-decoration: none; color: #475569; font-weight: 500; font-size: 15px; padding: 8px 0; border-bottom: 2px solid transparent; transition: all 0.3s; }
        .nav-center a:hover { color: #2c7da0; border-bottom-color: #2c7da0; }
        .user-menu { display: flex; align-items: center; gap: 15px; }
        .user-name { display: flex; align-items: center; gap: 8px; background: #f1f5f9; padding: 8px 16px; border-radius: 40px; color: #1e293b; font-weight: 500; font-size: 14px; }
        .user-name i { color: #2c7da0; }
        .logout-btn { text-decoration: none; padding: 8px 16px; background: #fee2e2; color: #dc2626; border-radius: 40px; font-weight: 500; font-size: 14px; transition: all 0.3s; }
        .logout-btn:hover { background: #fecaca; transform: translateY(-2px); }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 30px; flex: 1; width: 100%; }
        .btn-back { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; color: #475569; background: #f1f5f9; padding: 10px 20px; border-radius: 40px; font-size: 14px; font-weight: 500; margin-bottom: 25px; transition: all 0.3s; border: 1px solid #e2e8f0; }
        .btn-back:hover { background: #e2e8f0; transform: translateX(-5px); }
        .detail-card { background: white; padding: 30px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); margin-bottom: 30px; border: 1px solid #f0f0f0; }
        .detail-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; margin-bottom: 25px; }
        .detail-header h2 { font-size: 28px; color: #1e293b; display: flex; align-items: center; gap: 10px; }
        .detail-header h2 i { color: #2c7da0; }
        .badge { padding: 6px 12px; border-radius: 30px; font-size: 12px; font-weight: 600; color: white; }
        .badge-pharmacy { background: linear-gradient(135deg, #2a9d8f, #1e7a6a); }
        .badge-hospital { background: linear-gradient(135deg, #e76f51, #c44536); }
        .badge-clinic { background: linear-gradient(135deg, #2c7da0, #1e5f7a); }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 25px 0; }
        .info-item { padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; }
        .info-label { font-size: 13px; color: #64748b; margin-bottom: 5px; display: flex; align-items: center; gap: 5px; }
        .info-label i { color: #2c7da0; }
        .info-value { font-size: 16px; font-weight: 500; color: #1e293b; }
        .map-container { height: 300px; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0; margin: 20px 0; }
        .section-title { font-size: 20px; margin: 20px 0 15px; display: flex; align-items: center; gap: 8px; color: #1e293b; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-top: 15px; }
        .product-card { border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; text-align: center; transition: all 0.2s; background: white; }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .product-price { font-size: 18px; font-weight: bold; color: #2c7da0; margin: 10px 0; }
        .stock-badge { display: inline-block; padding: 4px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .stock-in_stock { background: #d1fae5; color: #065f46; }
        .stock-low_stock { background: #fff3cd; color: #856404; }
        .stock-out_of_stock { background: #fee2e2; color: #991b1b; }
        .services-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .services-table th, .services-table td { padding: 12px; text-align: left; border-bottom: 1px solid #f1f5f9; }
        .services-table th { background: #f8fafc; font-weight: 600; color: #475569; }
        .services-table tr:hover { background: #f8fafc; }
        .action-buttons { display: flex; gap: 15px; margin-top: 20px; }
        .btn { padding: 12px 24px; border: none; border-radius: 40px; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #2c7da0, #1e5f7a); color: white; box-shadow: 0 4px 15px rgba(44,125,160,0.3); }
        .btn-primary:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(44,125,160,0.4); }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; transform: translateY(-2px); }
        footer { background: white; padding: 20px 30px; margin-top: 40px; box-shadow: 0 -2px 10px rgba(0,0,0,0.05); }
        .footer-container { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .footer-copyright { color: #64748b; font-size: 14px; }
        .footer-social { display: flex; gap: 20px; }
        .footer-social a { color: #94a3b8; font-size: 18px; transition: all 0.3s; }
        .footer-social a:hover { color: #2c7da0; transform: translateY(-3px); }
        @media (max-width: 768px) {
            .header-container { flex-direction: column; gap: 15px; padding: 15px; }
            .nav-center { flex-wrap: wrap; justify-content: center; gap: 15px; }
            .user-menu { width: 100%; justify-content: center; }
            .info-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <header class="new-header">
        <div class="header-container">
            <div class="logo-section">
                <a href="../home.html" class="logo-link">
                    <div class="logo-icon">💊</div>
                    <div class="logo-text">PharmaLocator<span>Find Care, Fast</span></div>
                </a>
            </div>
            <nav class="nav-center">
                <a href="dashboard.php">Home</a>
                <a href="favorites.php">Favorites</a>
            </nav>
            <div class="user-menu">
                <span class="user-name"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

        <div class="detail-card">
            <div class="detail-header">
                <h2><i class="fas fa-building"></i> <?php echo htmlspecialchars($facility['name']); ?></h2>
                <span class="badge badge-<?php echo $facility['facility_type']; ?>"><?php echo ucfirst($facility['facility_type']); ?></span>
            </div>

            <div class="info-grid">
                <div class="info-item"><div class="info-label"><i class="fas fa-map-marker-alt"></i> Address</div><div class="info-value"><?php echo htmlspecialchars($facility['address']); ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-phone-alt"></i> Phone</div><div class="info-value"><?php echo htmlspecialchars($facility['phone'] ?? 'N/A'); ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-envelope"></i> Email</div><div class="info-value"><?php echo htmlspecialchars($facility['email'] ?? 'N/A'); ?></div></div>
                <div class="info-item"><div class="info-label"><i class="fas fa-clock"></i> Hours</div><div class="info-value"><?php echo nl2br(htmlspecialchars($facility['opening_hours'] ?? 'N/A')); ?></div></div>
                <?php if (!empty($facility['description'])): ?>
                <div class="info-item" style="grid-column: span 2;"><div class="info-label"><i class="fas fa-align-left"></i> Description</div><div class="info-value"><?php echo nl2br(htmlspecialchars($facility['description'])); ?></div></div>
                <?php endif; ?>
            </div>

            <?php if (!empty($facility['latitude']) && !empty($facility['longitude'])): ?>
            <div class="map-container" id="map"></div>
            <?php endif; ?>

            <div class="action-buttons">
                <button id="directionsBtn" class="btn btn-primary" onclick="getDirections()"><i class="fas fa-directions"></i> Get Directions</button>
                <button onclick="addToFavorites(<?php echo $facility['facility_id']; ?>)" class="btn btn-secondary"><i class="fas fa-heart"></i> Add to Favorites</button>
            </div>
        </div>

        <?php if ($products && $products->num_rows > 0): ?>
        <div class="detail-card">
            <h3 class="section-title"><i class="fas fa-pills"></i> Available Products</h3>
            <div class="product-grid">
                <?php while ($prod = $products->fetch_assoc()): ?>
                <div class="product-card">
                    <strong><?php echo htmlspecialchars($prod['name']); ?></strong>
                    <div class="product-price"><?php echo number_format($prod['price'], 0); ?> XAF</div>
                    <div><span class="stock-badge stock-<?php echo $prod['stock_status']; ?>"><?php echo str_replace('_', ' ', $prod['stock_status']); ?></span></div>
                    <?php if ($prod['quantity']): ?><div style="font-size: 12px;">Qty: <?php echo $prod['quantity']; ?></div><?php endif; ?>
                    <?php if ($prod['expiry_date']): ?><div style="font-size: 12px;">Exp: <?php echo date('d/m/Y', strtotime($prod['expiry_date'])); ?></div><?php endif; ?>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($services && $services->num_rows > 0): ?>
        <div class="detail-card">
            <h3 class="section-title"><i class="fas fa-stethoscope"></i> Medical Services</h3>
            <table class="services-table">
                <thead><tr><th>Service</th><th>Specialist</th><th>Cost Estimate</th><th>Duration</th></tr></thead>
                <tbody>
                <?php while ($serv = $services->fetch_assoc()): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($serv['name']); ?></strong><br><small><?php echo htmlspecialchars($serv['description']); ?></small></td>
                    <td><?php echo htmlspecialchars($serv['specialist'] ?? 'N/A'); ?></td>
                    <td><?php echo $serv['cost_estimate'] ? number_format($serv['cost_estimate'], 0) . ' XAF' : 'N/A'; ?></td>
                    <td><?php echo htmlspecialchars($serv['duration'] ?? 'N/A'); ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <footer>
        <div class="footer-container">
            <div class="footer-copyright">&copy; 2026 PharmaLocator. All rights reserved.</div>
            <div class="footer-social">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-linkedin-in"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
            </div>
        </div>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script>
        const facilityLat = <?php echo $facility['latitude']; ?>;
        const facilityLng = <?php echo $facility['longitude']; ?>;
        let map, routeControl, userLocation;

        // Initialize map if coordinates exist
        <?php if (!empty($facility['latitude']) && !empty($facility['longitude'])): ?>
        map = L.map('map').setView([facilityLat, facilityLng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);
        L.marker([facilityLat, facilityLng]).addTo(map).bindPopup("<strong><?php echo addslashes($facility['name']); ?></strong>").openPopup();
        <?php endif; ?>

        // Get user location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                userLocation = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                if (map && !map.getCenter().equals([facilityLat, facilityLng])) {
                    L.marker([userLocation.lat, userLocation.lng], { icon: L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png', shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png', iconSize: [25,41], iconAnchor: [12,41] }) }).addTo(map).bindPopup('Your location');
                }
            });
        }

        function getDirections() {
            if (!userLocation) { alert('Please enable location access to get directions.'); return; }
            if (routeControl) map.removeControl(routeControl);
            routeControl = L.Routing.control({
                router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                waypoints: [ L.latLng(userLocation.lat, userLocation.lng), L.latLng(facilityLat, facilityLng) ],
                routeWhileDragging: false,
                showAlternatives: true,
                lineOptions: { styles: [{ color: '#2c7da0', opacity: 0.8, weight: 6 }] }
            }).addTo(map);
            const bounds = L.latLngBounds([ [userLocation.lat, userLocation.lng], [facilityLat, facilityLng] ]);
            map.fitBounds(bounds, { padding: [50,50] });
        }

        function addToFavorites(facilityId) {
            const user = JSON.parse(localStorage.getItem('user'));
            if (!user) { alert('Please login first'); return; }
            fetch('../api/add_favorite.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: user.id, facility_id: facilityId })
            })
            .then(r => r.json())
            .then(data => alert(data.success ? 'Added to favorites!' : (data.message || 'Already in favorites')))
            .catch(() => alert('Failed to add to favorites'));
        }
    </script>
</body>
</html>