<?php
/**
 * Common header for view pages
 * Include this at the top of view files to ensure helpers are loaded
 */

// Load helpers if not already loaded
if (!function_exists('asset')) {
    require_once __DIR__ . '/helpers.php';
}

