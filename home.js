// home.js - Map Functions

let map;
let userMarker;
let markers = [];
let routeControl;
let userLocation = null;
let currentInfoWindow = null;

// Red icon for user location
const redIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

// Pharmacy icon
const pharmacyIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

// Hospital icon
const hospitalIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    iconSize: [25, 41],
    iconAnchor: [12, 41],
    popupAnchor: [1, -34],
    shadowSize: [41, 41]
});

// Clinic icon
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
    
    map = L.map('map').setView([lat, lon], 14);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add user marker
    userMarker = L.marker([lat, lon], { icon: redIcon })
        .addTo(map)
        .bindPopup('You are here')
        .openPopup();
    
    // Save location to database
    saveLocationToDatabase(lat, lon);
}

// Get user location
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            position => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                
                if (!map) {
                    initMap(lat, lon);
                } else {
                    updateUserLocation(lat, lon);
                }
            },
            error => {
                console.error('Geolocation error:', error);
                alert('Please enable location access to find nearby facilities.');
                // Default to Yaoundé
                initMap(3.8480, 11.5021);
            },
            { enableHighAccuracy: true }
        );
    } else {
        alert('Geolocation is not supported by your browser.');
        initMap(3.8480, 11.5021);
    }
}

// Update user location
function updateUserLocation(lat, lon) {
    if (userMarker) {
        userMarker.setLatLng([lat, lon]);
    }
    userLocation = { lat, lon };
    saveLocationToDatabase(lat, lon);
}

// Save location to database
function saveLocationToDatabase(lat, lon) {
    const user = JSON.parse(localStorage.getItem('user'));
    if (!user) return;
    
    fetch('api/save_location.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            user_id: user.id,
            latitude: lat,
            longitude: lon
        })
    }).catch(err => console.error('Error saving location:', err));
}

// Find nearby places
function findPlaces() {
    const type = document.getElementById('placeType').value;
    
    if (type === '') {
        alert('Please select Pharmacy or Hospital');
        return;
    }
    
    if (!map || !userLocation) {
        alert('Map not loaded or location not available');
        return;
    }
    
    showLoading(true);
    
    // Get facilities from database
    fetch(`api/get_facilities.php?lat=${userLocation.lat}&lng=${userLocation.lon}&type=${type}&radius=10`)
        .then(response => response.json())
        .then(data => {
            showLoading(false);
            if (data.success) {
                displayFacilities(data.facilities);
            } else {
                alert('Error loading facilities');
            }
        })
        .catch(error => {
            showLoading(false);
            console.error('Error:', error);
            handleApiError(error);
        });
}

// Display facilities on map
function displayFacilities(facilities) {
    // Clear existing markers
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
    
    if (facilities.length === 0) {
        alert('No facilities found nearby');
        return;
    }
    
    facilities.forEach(facility => {
        // Choose icon based on type
        let icon;
        switch(facility.facility_type) {
            case 'pharmacy':
                icon = pharmacyIcon;
                break;
            case 'hospital':
                icon = hospitalIcon;
                break;
            case 'clinic':
                icon = clinicIcon;
                break;
            default:
                icon = pharmacyIcon;
        }
        
        const marker = L.marker([facility.latitude, facility.longitude], { icon })
            .addTo(map);
        
        // Calculate distance
        const distance = calculateDistance(
            userLocation.lat, userLocation.lon,
            facility.latitude, facility.longitude
        );
        
        // Create popup content
        const popupContent = `
            <div class="facility-popup">
                <h3>${facility.name}</h3>
                <p><strong>Type:</strong> ${facility.facility_type}</p>
                <p><strong>Distance:</strong> ${distance.toFixed(2)} km</p>
                <p>${facility.address.substring(0, 50)}...</p>
                <p>📞 ${facility.phone || 'N/A'}</p>
                <button onclick="showFacilityDetails(${facility.facility_id})">
                    View Details
                </button>
                <button onclick="showRoute(${facility.latitude}, ${facility.longitude})">
                    Get Directions
                </button>
            </div>
        `;
        
        marker.bindPopup(popupContent);
        markers.push(marker);
    });
    
    // Fit bounds to show all markers
    if (facilities.length > 0) {
        const bounds = L.latLngBounds(facilities.map(f => [f.latitude, f.longitude]));
        bounds.extend([userLocation.lat, userLocation.lon]);
        map.fitBounds(bounds, { padding: [50, 50] });
    }
}

// Calculate distance using Haversine formula
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Show facility details
function showFacilityDetails(facilityId) {
    fetch(`api/get_facility_details.php?id=${facilityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayFacilityModal(data.facility);
            } else {
                alert('Error loading facility details');
            }
        })
        .catch(error => handleApiError(error));
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
        showAlternatives: true,
        lineOptions: {
            styles: [
                { color: 'blue', opacity: 0.6, weight: 5 }
            ]
        }
    }).addTo(map);
    
    // Fit map to show route
    setTimeout(() => {
        const bounds = L.latLngBounds([
            [userLocation.lat, userLocation.lon],
            [destLat, destLon]
        ]);
        map.fitBounds(bounds, { padding: [50, 50] });
    }, 500);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    getLocation();
});