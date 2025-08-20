<?php
// Database connection
$servername = "localhost"; // Your server name
$username = "root"; // Your username
$password = ""; // Your password
$dbname = "tarryn_workplaceportal"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if logId and memberId are set
if (isset($_POST['logId']) && isset($_POST['memberId'])) {
    $logId = $_POST['logId'];
    $memberId = $_POST['memberId'];

    // Prepare and bind the SQL statement to update the assigned member
    $stmt = $conn->prepare("UPDATE helpdesk_support_incoming SET assigned_to = ? WHERE id = ?");
    $stmt->bind_param("si", $memberId, $logId); // "si" means string for memberId and integer for logId

    // Execute the statement
    if ($stmt->execute()) {
        // Check if any rows were affected
        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Issue assigned successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No issue found with that ID or already assigned.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error executing query.']);
    }

    // Close the statement
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
}

// Close the database connection
$conn->close();
?>
