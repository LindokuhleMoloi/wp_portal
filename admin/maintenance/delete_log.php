<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Check if an ID is provided
if (isset($_POST['id'])) {
    $id = intval($_POST['id']); // Sanitize input

    // Prepare the delete statement
    $stmt = $conn->prepare("DELETE FROM helpdesk_support_incoming WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Success response
        echo json_encode(['status' => 'success']);
    } else {
        // Error response
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete log.']);
    }

    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'No ID provided.']);
}

$conn->close();
?>
