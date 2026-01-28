<?php
/**
 * Helper Functions
 */

/**
 * Get the base URL for assets
 * Optimized for PHP built-in server with -t public
 * @return string
 */
function getBaseUrl() {
    // If BASE_URL is manually defined, use it
    if (defined('BASE_URL') && BASE_URL !== 'http://localhost') {
        return BASE_URL;
    }
    
    // Get the protocol (http or https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    
    // Get the host (e.g., localhost:8000)
    $host = $_SERVER['HTTP_HOST'];
    
    // With PHP built-in server using -t public, document root is 'public'
    // So base URL is just protocol + host (no path component)
    return $protocol . $host;
}

/**
 * Get asset URL (for CSS, JS, images)
 * Routes through assets.php when using PHP built-in server with -t public
 * @param string $path - Path relative to assets folder (e.g., 'css/style.css')
 * @return string
 */
function asset($path) {
    $path = ltrim($path, '/');
    return getBaseUrl() . '/assets.php?path=' . urlencode($path);
}

/**
 * Get URL for application pages
 * Optimized for PHP built-in server with -t public
 * @param string $path - Path relative to public folder (e.g., 'login.php' or 'public/login.php')
 * @return string
 */
function url($path = '') {
    $base = getBaseUrl();
    $path = ltrim($path, '/');
    
    // Since we're using -t public, document root is 'public'
    // So we need to strip 'public/' prefix if present
    if (strpos($path, 'public/') === 0) {
        $path = substr($path, 7); // Remove 'public/' (7 characters)
    }
    
    // For app/views paths, route them through router.php
    // This allows accessing files outside the public folder
    if (strpos($path, 'app/views/') === 0) {
        // Route through router.php with the view path as a parameter
        $path = 'router.php?view=' . urlencode($path);
    }
    
    // Ensure no double slashes
    $base = rtrim($base, '/');
    $path = ltrim($path, '/');
    
    return $base . '/' . $path;
}

