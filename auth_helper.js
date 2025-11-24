/**
 * =====================================================
 * GABAY ADMIN AUTHENTICATION HELPER
 * =====================================================
 * 
 * Client-side authentication utilities for admin pages.
 * Provides CSRF token management and AJAX authentication handling.
 * 
 * USAGE:
 * Include this script in all admin pages after jQuery:
 * <script src="auth_helper.js"></script>
 * 
 * Then use AdminAuth.ajax() for authenticated AJAX requests:
 * AdminAuth.ajax({
 *     url: 'endpoint.php',
 *     method: 'POST',
 *     data: { action: 'saveData', value: 123 },
 *     success: function(response) { ... }
 * });
 */

var AdminAuth = (function() {
    'use strict';
    
    /**
     * Get CSRF token from page meta tag or hidden input
     * @returns {string} CSRF token
     */
    function getCSRFToken() {
        // Try to get from meta tag first
        var metaToken = document.querySelector('meta[name="csrf-token"]');
        if (metaToken) {
            return metaToken.getAttribute('content');
        }
        
        // Try to get from hidden input
        var inputToken = document.querySelector('input[name="csrf_token"]');
        if (inputToken) {
            return inputToken.value;
        }
        
        // Try to get from global variable (set by PHP)
        if (typeof window.CSRF_TOKEN !== 'undefined') {
            return window.CSRF_TOKEN;
        }
        
        console.warn('CSRF token not found on page');
        return '';
    }
    
    /**
     * Handle authentication errors from AJAX responses
     * @param {object} response - Server response
     */
    function handleAuthError(response) {
        if (response && response.error === 'authentication_required') {
            // Session expired - show message and redirect to login
            var message = response.message || 'Your session has expired. Please log in again.';
            
            // Show alert
            if (typeof window.showNotification === 'function') {
                window.showNotification(message, 'error');
            } else {
                alert(message);
            }
            
            // Redirect to login after short delay
            setTimeout(function() {
                var returnUrl = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = 'login.php?return=' + returnUrl;
            }, 1500);
            
            return true;
        }
        return false;
    }
    
    /**
     * Enhanced AJAX function with CSRF token and auth error handling
     * @param {object} options - jQuery AJAX options
     */
    function secureAjax(options) {
        // Get CSRF token
        var csrfToken = getCSRFToken();
        
        // Add CSRF token to request data
        if (options.method === 'POST' || options.type === 'POST') {
            if (typeof options.data === 'object' && !(options.data instanceof FormData)) {
                options.data.csrf_token = csrfToken;
            } else if (typeof options.data === 'string') {
                options.data += '&csrf_token=' + encodeURIComponent(csrfToken);
            } else if (options.data instanceof FormData) {
                options.data.append('csrf_token', csrfToken);
            } else {
                options.data = { csrf_token: csrfToken };
            }
        }
        
        // Wrap success callback to check for auth errors
        var originalSuccess = options.success;
        options.success = function(response, textStatus, jqXHR) {
            // Check if response indicates auth error
            if (handleAuthError(response)) {
                return; // Don't call original success if auth failed
            }
            
            // Call original success callback
            if (originalSuccess) {
                originalSuccess(response, textStatus, jqXHR);
            }
        };
        
        // Wrap error callback to handle 401 Unauthorized
        var originalError = options.error;
        options.error = function(jqXHR, textStatus, errorThrown) {
            // Check for 401 Unauthorized
            if (jqXHR.status === 401) {
                try {
                    var response = JSON.parse(jqXHR.responseText);
                    handleAuthError(response);
                    return; // Don't call original error if auth failed
                } catch (e) {
                    // Response not JSON, handle as regular error
                }
            }
            
            // Call original error callback
            if (originalError) {
                originalError(jqXHR, textStatus, errorThrown);
            }
        };
        
        // Make the AJAX request
        return $.ajax(options);
    }
    
    /**
     * Handle logout click
     */
    function handleLogout() {
        if (confirm('Are you sure you want to log out?')) {
            window.location.href = 'logout.php';
        }
    }
    
    /**
     * Setup logout buttons on page load
     */
    function setupLogoutButtons() {
        // Find all logout buttons/links
        var logoutElements = document.querySelectorAll('a[href*="logout"], button[data-action="logout"], .logout-button');
        
        logoutElements.forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                handleLogout();
            });
        });
    }
    
    /**
     * Initialize authentication helpers
     */
    function init() {
        // Setup logout buttons when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupLogoutButtons);
        } else {
            setupLogoutButtons();
        }
        
        // Add CSRF token to all forms automatically
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                addCSRFToForms();
            });
        } else {
            addCSRFToForms();
        }
    }
    
    /**
     * Add CSRF token to all forms on the page
     */
    function addCSRFToForms() {
        var token = getCSRFToken();
        if (!token) return;
        
        var forms = document.querySelectorAll('form');
        forms.forEach(function(form) {
            // Check if form already has CSRF token
            var existingToken = form.querySelector('input[name="csrf_token"]');
            if (existingToken) {
                existingToken.value = token; // Update existing token
            } else {
                // Add new hidden input with CSRF token
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'csrf_token';
                input.value = token;
                form.appendChild(input);
            }
        });
    }
    
    /**
     * Refresh CSRF token (call after long idle or before important operation)
     */
    function refreshCSRFToken(callback) {
        $.ajax({
            url: 'auth_refresh.php',
            method: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.csrf_token) {
                    // Update token in meta tag
                    var metaToken = document.querySelector('meta[name="csrf-token"]');
                    if (metaToken) {
                        metaToken.setAttribute('content', response.csrf_token);
                    }
                    
                    // Update global variable
                    window.CSRF_TOKEN = response.csrf_token;
                    
                    // Update all forms
                    addCSRFToForms();
                    
                    if (callback) callback(true);
                } else {
                    if (callback) callback(false);
                }
            },
            error: function() {
                if (callback) callback(false);
            }
        });
    }
    
    // Auto-initialize
    init();
    
    // Public API
    return {
        ajax: secureAjax,
        getToken: getCSRFToken,
        refreshToken: refreshCSRFToken,
        logout: handleLogout
    };
})();
