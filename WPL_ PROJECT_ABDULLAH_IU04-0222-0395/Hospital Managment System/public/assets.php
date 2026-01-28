<?php
/**
 * Asset Router - Serves CSS, JS, and other assets from outside public folder
 * Required when using PHP built-in server with -t public
 */

// Get the asset path from query parameter
$assetPath = $_GET['path'] ?? '';

// Validate path
if (empty($assetPath)) {
    http_response_code(404);
    die('Asset not found');
}

// Security: Prevent directory traversal
if (strpos($assetPath, '..') !== false || strpos($assetPath, '/') === 0) {
    http_response_code(403);
    die('Access denied');
}

// Convert to file system path
$filePath = __DIR__ . '/../assets/' . $assetPath;

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    die('Asset not found');
}

// Check if file is within assets directory (prevent directory traversal)
$realFilePath = realpath($filePath);
$realAssetsPath = realpath(__DIR__ . '/../assets');
if (strpos($realFilePath, $realAssetsPath) !== 0) {
    http_response_code(403);
    die('Access denied');
}

// Determine content type based on file extension
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$contentTypes = [
    'css' => 'text/css',
    'js' => 'application/javascript',
    'json' => 'application/json',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
];

$contentType = $contentTypes[$extension] ?? 'application/octet-stream';

// Set headers
header('Content-Type: ' . $contentType);
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Output the file
readfile($filePath);

