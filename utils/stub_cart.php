<?php
// This file creates a simple fake cart system when the real one isn't working yet
// Can be used for testing until you have the full cart implementation working

// Set content type to JSON and prevent errors from displaying
header('Content-Type: application/json');
ini_set('display_errors', 0);

// Return a simple success response with count 0
$response = [
    'success' => true,
    'count' => 0,
    'message' => 'Stub cart response'
];

// Output the response
echo json_encode($response);
exit;
