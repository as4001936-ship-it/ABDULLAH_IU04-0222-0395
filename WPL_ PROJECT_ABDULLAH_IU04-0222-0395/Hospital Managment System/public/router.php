<?php
/**
 * Router for accessing app/views files
 * Required when using PHP built-in server with -t public
 */

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/auth/auth_guard.php';

// Get the view path from query parameter
$viewPath = $_GET['view'] ?? '';

// Validate that it's an app/views path
if (empty($viewPath) || strpos($viewPath, 'app/views/') !== 0) {
    http_response_code(404);
    die('Invalid view path');
}

// Security: Only allow .php files
if (substr($viewPath, -4) !== '.php') {
    http_response_code(403);
    die('Access denied');
}

// Convert path to file system path
$filePath = __DIR__ . '/../' . $viewPath;

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    die('View not found');
}

// Check if file is within app/views directory (prevent directory traversal)
$realFilePath = realpath($filePath);
$realViewsPath = realpath(__DIR__ . '/../app/views');
if (strpos($realFilePath, $realViewsPath) !== 0) {
    http_response_code(403);
    die('Access denied');
}

// Require authentication (views are protected)
requireAuth();

// Include the view file
require $filePath;

