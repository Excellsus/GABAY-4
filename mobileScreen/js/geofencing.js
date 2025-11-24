/**
 * GABAY Mobile Geofencing System
 * Shared geofencing functionality for all mobile screens
 */

class GeofenceManager {
  constructor(options = {}) {
    this.checkInterval = options.checkInterval || 30000; // Check every 30 seconds
    this.watchId = null;
    this.isInitialized = false;
    this.lastPosition = null;
    this.retryCount = 0;
    this.maxRetries = 3;

    // Configuration
    this.config = {
      enableHighAccuracy: true,
      timeout: 10000,
      maximumAge: 60000, // 1 minute
    };

    // Callbacks
    this.onAccessGranted = options.onAccessGranted || (() => {});
    this.onAccessDenied =
      options.onAccessDenied || this.defaultAccessDenied.bind(this);
    this.onLocationUpdate = options.onLocationUpdate || (() => {});
  }

  /**
   * Initialize geofencing for the current page
   */
  async initialize() {
    if (this.isInitialized) {
      console.log("Geofencing already initialized");
      return;
    }

    console.log("Initializing geofencing system...");

    // First, check if geofencing is enabled by admin
    try {
      const statusResp = await fetch("../check_geofence_status.php");
      const statusData = await statusResp.json();
      
      if (statusData.success && !statusData.enabled) {
        console.log("Geofencing is disabled by admin - skipping enforcement");
        return; // Exit without enforcing geofencing
      }
    } catch (err) {
      console.warn("Could not check geofence status, proceeding with enforcement as safety measure", err);
      // Continue with geofencing if check fails (fail-safe)
    }

    // Check if geolocation is supported
    if (!navigator.geolocation) {
      this.onAccessDenied("Geolocation is not supported by your browser.");
      return;
    }

    // Start initial location check
    await this.performLocationCheck();

    // Set up continuous monitoring
    this.startContinuousMonitoring();

    this.isInitialized = true;
    console.log("Geofencing system initialized successfully");
  }

  /**
   * Perform a single location check
   */
  async performLocationCheck() {
    return new Promise((resolve, reject) => {
      console.log("Checking current location...");

      navigator.geolocation.getCurrentPosition(
        async (position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          console.log(`Location obtained: ${lat}, ${lng}`);
          this.lastPosition = { lat, lng, timestamp: Date.now() };

          try {
            const isAllowed = await this.verifyLocationWithServer(lat, lng);

            if (isAllowed) {
              console.log("✅ Location verified - access granted");
              this.onAccessGranted();
              this.onLocationUpdate(lat, lng, true);
              this.retryCount = 0; // Reset retry count on success
              resolve(true);
            } else {
              console.log("❌ Location denied - outside geofence");
              this.onAccessDenied(
                "Access denied — your device appears to be outside the allowed area."
              );
              this.onLocationUpdate(lat, lng, false);
              resolve(false);
            }
          } catch (error) {
            console.error("Location verification failed:", error);
            this.handleLocationError(error);
            reject(error);
          }
        },
        (error) => {
          console.error("Geolocation error:", error);
          this.handleGeolocationError(error);
          reject(error);
        },
        this.config
      );
    });
  }

  /**
   * Start continuous location monitoring
   */
  startContinuousMonitoring() {
    console.log(
      `Starting continuous location monitoring (${
        this.checkInterval / 1000
      }s intervals)`
    );

    // Clear any existing interval
    if (this.watchId) {
      clearInterval(this.watchId);
    }

    // Set up periodic checks
    this.watchId = setInterval(async () => {
      try {
        await this.performLocationCheck();
      } catch (error) {
        console.error("Periodic location check failed:", error);
      }
    }, this.checkInterval);
  }

  /**
   * Stop geofencing monitoring
   */
  stop() {
    if (this.watchId) {
      clearInterval(this.watchId);
      this.watchId = null;
    }
    this.isInitialized = false;
    console.log("Geofencing monitoring stopped");
  }

  /**
   * Verify location with server
   */
  async verifyLocationWithServer(lat, lng) {
    const response = await fetch("verify_location.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        lat: lat,
        lng: lng,
        office_id: this.getOfficeId(),
        page: this.getCurrentPage(),
      }),
    });

    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.message || "Server verification failed");
    }

    const result = data.result;
    // Allow access if inside zone1 (strict) or zone2 (moderate)
    return result && (result.inside_zone1 || result.inside_zone2);
  }

  /**
   * Handle geolocation errors
   */
  handleGeolocationError(error) {
    let message = "Unable to access your location: ";

    switch (error.code) {
      case error.PERMISSION_DENIED:
        message +=
          "Location permission denied. Please enable location access for this site.";
        break;
      case error.POSITION_UNAVAILABLE:
        message += "Location information is unavailable.";
        break;
      case error.TIMEOUT:
        message += "Location request timed out.";
        break;
      default:
        message += error.message || "Unknown error occurred.";
        break;
    }

    this.onAccessDenied(message);
  }

  /**
   * Handle location verification errors
   */
  handleLocationError(error) {
    this.retryCount++;

    if (this.retryCount < this.maxRetries) {
      console.log(
        `Location check failed, retrying... (${this.retryCount}/${this.maxRetries})`
      );
      setTimeout(() => {
        this.performLocationCheck();
      }, 2000 * this.retryCount); // Exponential backoff
    } else {
      this.onAccessDenied(
        "Network error while verifying location. Please check your connection."
      );
    }
  }

  /**
   * Default access denied handler
   */
  defaultAccessDenied(message) {
    console.error("Access denied:", message);

    // Create or update denial message
    this.showAccessDeniedMessage(message);
  }

  /**
   * Show access denied message with retry options
   */
  showAccessDeniedMessage(message) {
    // Remove existing message if any
    const existing = document.getElementById("geofence-denied-message");
    if (existing) {
      existing.remove();
    }

    // Create denial message overlay
    const overlay = document.createElement("div");
    overlay.id = "geofence-denied-message";
    overlay.style.cssText = `
      position: fixed;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      z-index: 99999;
      background: rgba(0, 0, 0, 0.85);
      color: #fff;
      padding: 20px;
    `;

    overlay.innerHTML = `
      <div style="max-width: 420px; text-align: center;">
        <div style="font-size: 28px; margin-bottom: 8px;">🚫 Access Denied</div>
        <div style="margin-bottom: 12px; opacity: 0.95;">${message}</div>
        <div style="margin-bottom: 12px; opacity: 0.95;">You will not be able to use this app outside the allowed area.</div>
        <div style="margin-top: 8px;">
          <button id="geofence-retry-btn" style="
            padding: 10px 16px;
            border-radius: 8px;
            background: #fff;
            color: #111;
            border: none;
            cursor: pointer;
            font-weight: bold;
          ">Retry</button>
        </div>
      </div>
    `;

    document.body.appendChild(overlay);

    // Add event listener
    document
      .getElementById("geofence-retry-btn")
      .addEventListener("click", () => {
        overlay.remove();
        this.retryCount = 0;
        this.performLocationCheck();
      });
  }

  /**
   * Get current office ID from URL or global variable
   */
  getOfficeId() {
    // Try to get from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const officeId = urlParams.get("office_id");
    if (officeId) return parseInt(officeId);

    // Try to get from global variable
    if (typeof highlightOfficeIdFromPHP !== "undefined") {
      return highlightOfficeIdFromPHP;
    }

    return null;
  }

  /**
   * Get current page name
   */
  getCurrentPage() {
    const path = window.location.pathname;
    const page = path.split("/").pop() || "explore.php";
    return page;
  }

  /**
   * Update check interval
   */
  setCheckInterval(interval) {
    this.checkInterval = interval;
    if (this.isInitialized) {
      this.startContinuousMonitoring();
    }
  }

  /**
   * Get last known position
   */
  getLastPosition() {
    return this.lastPosition;
  }

  /**
   * Check if currently inside geofence (based on last check)
   */
  isInsideGeofence() {
    return this.lastPosition && this.lastPosition.allowed;
  }
}

// Global instance
window.GabayGeofence = null;

/**
 * Initialize geofencing for the current page
 */
function initializeGeofencing(options = {}) {
  if (window.GabayGeofence) {
    console.log("Geofencing already initialized");
    return window.GabayGeofence;
  }

  console.log("Creating GABAY geofencing instance...");
  window.GabayGeofence = new GeofenceManager(options);

  // Auto-initialize on DOM ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", () => {
      window.GabayGeofence.initialize();
    });
  } else {
    window.GabayGeofence.initialize();
  }

  return window.GabayGeofence;
}

// Auto-initialize with default settings if not already done
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    if (!window.GabayGeofence) {
      initializeGeofencing();
    }
  });
} else {
  if (!window.GabayGeofence) {
    initializeGeofencing();
  }
}
