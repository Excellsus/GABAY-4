// leafletGeofencing.js
// This file stores the geofence center and radii used by the admin UI and mobile verify flow.
// The admin UI will replace the `center` and `radius` values via file edits.

const geofenceConfig = {
  // center: [lat, lng] - this will be overwritten by the admin page when updated
  center: [10.66561, 122.95784],
  zones: [
    { name: "Main Palace Building", radius: 150 },
    { name: "Palace Complex", radius: 150 },
    { name: "Government Building Grounds", radius: 150 },
  ],
};

// Initialize a small map for debugging or mobile display when included
function initGeofenceMap(containerId = "geofence-map") {
  if (typeof L === "undefined") return null;
  const map = L.map(containerId).setView(geofenceConfig.center, 18);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "© OpenStreetMap contributors",
  }).addTo(map);

  // draw zones
  geofenceConfig.zones.forEach((z, i) => {
    const colors = ["#ff4444", "#4CAF50", "#2196F3"];
    L.circle(geofenceConfig.center, {
      color: colors[i] || "#666",
      fillColor: colors[i] || "#666",
      fillOpacity: 0.1,
      radius: z.radius,
      weight: 2,
    })
      .addTo(map)
      .bindPopup(`<strong>${z.name}</strong><br>Radius: ${z.radius}m`);
  });

  L.marker(geofenceConfig.center)
    .addTo(map)
    .bindPopup("<strong>Geofence Center</strong>");
  return map;
}

// Utility to check distance (meters) from center using Haversine
function distanceFromCenter(lat, lng) {
  const toRad = (v) => (v * Math.PI) / 180;
  const R = 6371000; // meters
  const dLat = toRad(lat - geofenceConfig.center[0]);
  const dLng = toRad(lng - geofenceConfig.center[1]);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(toRad(geofenceConfig.center[0])) *
      Math.cos(toRad(lat)) *
      Math.sin(dLng / 2) *
      Math.sin(dLng / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  return R * c;
}

// Export for usage in browsers
if (typeof window !== "undefined") {
  window.geofenceConfig = geofenceConfig;
  window.initGeofenceMap = initGeofenceMap;
  window.distanceFromCenter = distanceFromCenter;
}
