<?php
// customer/get_directions.php
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

$stmt = $conn->prepare("SELECT name, latitude, longitude FROM facilities WHERE facility_id = ? AND verified = 1");
$stmt->bind_param('i', $facility_id);
$stmt->execute();
$facility = $stmt->get_result()->fetch_assoc();

if (!$facility) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Directions - PharmaLocator</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <style>
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        #map { height: 100vh; width: 100%; }
        .back-btn { position: absolute; top: 20px; left: 20px; z-index: 1000; background: white; padding: 10px 20px; border-radius: 30px; text-decoration: none; color: #2c7da0; font-weight: bold; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .info { position: absolute; top: 20px; right: 20px; background: white; padding: 10px 20px; border-radius: 30px; font-size: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); z-index: 1000; }
    </style>
</head>
<body>
    <a href="javascript:history.back()" class="back-btn"><i class="fas fa-arrow-left"></i> Back</a>
    <div class="info">📍 Getting directions to <?php echo htmlspecialchars($facility['name']); ?></div>
    <div id="map"></div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script>
        const destLat = <?php echo $facility['latitude']; ?>;
        const destLng = <?php echo $facility['longitude']; ?>;
        let map, routingControl, userLocation;

        map = L.map('map').setView([destLat, destLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap contributors' }).addTo(map);
        L.marker([destLat, destLng]).addTo(map).bindPopup('<?php echo addslashes($facility['name']); ?>').openPopup();

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                position => {
                    userLocation = { lat: position.coords.latitude, lng: position.coords.longitude };
                    L.marker([userLocation.lat, userLocation.lng], {
                        icon: L.icon({ iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png', shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png', iconSize: [25,41], iconAnchor: [12,41] })
                    }).addTo(map).bindPopup('Your location');
                    showRoute();
                },
                error => { alert('Could not get your location. Please enable location services.'); },
                { enableHighAccuracy: true }
            );
        } else {
            alert('Geolocation not supported by your browser.');
        }

        function showRoute() {
            if (!userLocation) return;
            if (routingControl) map.removeControl(routingControl);
            routingControl = L.Routing.control({
                router: L.Routing.osrmv1({ serviceUrl: 'https://router.project-osrm.org/route/v1' }),
                waypoints: [ L.latLng(userLocation.lat, userLocation.lng), L.latLng(destLat, destLng) ],
                routeWhileDragging: false,
                showAlternatives: true,
                lineOptions: { styles: [{ color: '#2c7da0', opacity: 0.8, weight: 6 }] }
            }).addTo(map);
            const bounds = L.latLngBounds([ [userLocation.lat, userLocation.lng], [destLat, destLng] ]);
            map.fitBounds(bounds, { padding: [50,50] });
        }
    </script>
</body>
</html>