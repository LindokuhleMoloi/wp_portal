<?php
// move_ticket.php

// Include database connection details
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'tarryn_workplaceportal';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if ticket ID is provided
if(isset($_POST['ticketId'])) {
    $ticketId = $_POST['ticketId'];

    // Example query: Move the ticket to a new status, such as "In Progress"
    $query = "UPDATE `helpdesk_support_incoming` SET `status` = 'In Progress' WHERE `id` = ?";
    
    // Prepare and execute the query
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("i", $ticketId);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo "Ticket moved successfully.";
        } else {
            echo "No changes made. The ticket might have already been moved or does not exist.";
        }
        
        $stmt->close();
    } else {
        echo "Error preparing the query: " . $conn->error;
    }
} else {
    echo "Ticket ID is missing.";
}

// Close the database connection
$conn->close();
?>
