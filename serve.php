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

// Read and output the file
echo file_get_contents($filePath);