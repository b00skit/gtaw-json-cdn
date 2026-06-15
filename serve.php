<?php
// Place this file in the /cdn/ directory
// Access via: https://sys.booskit.dev/cdn/serve.php?file=gtaw_locations.json

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
header("Content-Type: application/json");

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get file parameter
$file = isset($_GET['file']) ? $_GET['file'] : '';

// Basic security - prevent directory traversal
$file = basename($file);

// Path to JSON files directory
$jsonDir = __DIR__ . '/json/';

// Full path to requested file
$filePath = $jsonDir . $file;

// Check if file exists and ends with .json
if (empty($file) || !file_exists($filePath) || !preg_match('/\.json$/', $file)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

// Include bandwidth manager
require_once __DIR__ . '/bandwidth.php';
$ip = BandwidthManager::getClientIp();
$bandwidthManager = new BandwidthManager();

// Get usage and size
$fileSize = filesize($filePath);
$usage = $bandwidthManager->getUsage($ip);

// Check daily limit
if (($usage['daily_bytes'] + $fileSize) > BandwidthManager::DAILY_LIMIT) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Daily bandwidth limit exceeded. IPs are limited to 500MB per day.',
        'ip' => $ip,
        'current_usage_daily' => formatBandwidthBytes($usage['daily_bytes']),
        'limit_daily' => formatBandwidthBytes(BandwidthManager::DAILY_LIMIT),
        'file_size' => formatBandwidthBytes($fileSize)
    ]);
    exit;
}

// Check weekly limit
if (($usage['weekly_bytes'] + $fileSize) > BandwidthManager::WEEKLY_LIMIT) {
    http_response_code(429);
    echo json_encode([
        'error' => 'Weekly bandwidth limit exceeded. IPs are limited to 1GB per week.',
        'ip' => $ip,
        'current_usage_weekly' => formatBandwidthBytes($usage['weekly_bytes']),
        'limit_weekly' => formatBandwidthBytes(BandwidthManager::WEEKLY_LIMIT),
        'file_size' => formatBandwidthBytes($fileSize)
    ]);
    exit;
}

// Log request and served bytes
$bandwidthManager->logRequest($ip, $file, $fileSize);

// Read and output the file
echo file_get_contents($filePath);