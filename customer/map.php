<?php
// customer/map.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isCustomer()) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

$facilities = $conn->query("SELECT * FROM facilities WHERE verified = 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map View - PharmaLocator</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body { margin: 0; padding: 0; }
        #map { height: 100vh; width: 100%; }
        .header { position: absolute; top: 10px; left: 10px; z-index: 1000; background: white; padding: 8px 16px; border-radius: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; gap: 10px; }
        .header a { text-decoration: none; color: #2c7da0; font-weight: bold; }
        .header a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="header">
        <a href="dashboard.php">← Back to Dashboard</a>
    </div>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const facilities = <?php echo json_encode($facilities->fetch_all(MYSQLI_ASSOC)); ?>;
        const map = L.map('map').setView([3.8480, 11.5021], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);

        const greenIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png', shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png', iconSize: [25,41], iconAnchor: [12,41] });
        const redIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png', shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png', iconSize: [25,41], iconAnchor: [12,41] });
        const blueIcon = L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png', shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png', iconSize: [25,41], iconAnchor: [12,41] });

        facilities.forEach(f => {
            let icon;
            if (f.facility_type === 'pharmacy') icon = greenIcon;
            else if (f.facility_type === 'hospital') icon = redIcon;
            else icon = blueIcon;
            const marker = L.marker([f.latitude, f.longitude], { icon }).addTo(map);
            marker.bindPopup(`<strong>${f.name}</strong><br>${f.address}<br><a href="facility_details.php?id=${f.facility_id}">View Details</a>`);
        });

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(pos => {
                const userLoc = [pos.coords.latitude, pos.coords.longitude];
                L.marker(userLoc, { icon: L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png', shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png', iconSize: [25,41], iconAnchor: [12,41] }) }).addTo(map).bindPopup('You are here').openPopup();
                map.setView(userLoc, 13);
            });
        }
    </script>
</body>
</html>