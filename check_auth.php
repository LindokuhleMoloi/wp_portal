<?php
require_once('./config.php');
session_start();

// Initialize response
$response = ['authenticated' => false];

// Check if user is logged in
if(isset($_SESSION['userdata']) && !empty($_SESSION['userdata'])) {
    $response['authenticated'] = true;
}

// Set JSON header and output response
header('Content-Type: application/json');
echo json_encode($response);
?>