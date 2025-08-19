<?php
// Database connection settings
$servername = "localhost";
$username = "tarryn_Lindokuhle"; 
$password = "L1nd0kuhle"; 
$database = "tarryn_workplaceportal"; 

// Create connection
$mysqli = new mysqli($servername, $username, $password, $database);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the ticket is assigned to a support member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_id']) && isset($_POST['support_member_id'])) {
    $ticket_id = $_POST['ticket_id'];
    $support_member_id = $_POST['support_member_id'];
    $message_text = "You have been assigned a ticket"; // The message text for the assigned ticket

    // Prepare and bind SQL statement
    $stmt = $mysqli->prepare("INSERT INTO messages (ticket_id, issue_description, support_member_id, message_text, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isss", $ticket_id, $_POST['issue_description'], $support_member_id, $message_text);

    // Execute statement
    if ($stmt->execute()) {
        echo "<script>showModal('Message has been sent to the support member');</script>";
    } else {
        echo "<script>showModal('Error: " . $stmt->error . "');</script>";
    }

    // Close the statement
    $stmt->close();
}

// Close the connection
$mysqli->close();
?>
