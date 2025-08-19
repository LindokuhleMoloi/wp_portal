<?php
// Database connection
$conn = new mysqli("localhost", "tarryn_Lindokuhle", "L1nd0kuhle", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// For testing purposes, we just return a simple message
header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Hi']);
?>
