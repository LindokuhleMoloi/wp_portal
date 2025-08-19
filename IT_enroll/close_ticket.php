<?php
session_start(); // Start the session

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the ticket_id is provided
if (isset($_POST['ticket_id']) && !empty($_POST['ticket_id'])) {
    $ticket_id = $_POST['ticket_id'];

    // Prepare the SQL query to update the ticket status to "Closed"
    $sql = "UPDATE administered_tickets SET status = 'closed', closed_at = CURRENT_TIMESTAMP WHERE ticket_id = ?";

    // Prepare and bind
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $ticket_id);

    // Execute the statement
    if ($stmt->execute()) {
        // If the ticket is successfully closed, redirect to the assigned tickets page with a success message
        $_SESSION['message'] = 'Ticket closed successfully!';
        header("Location: assigned_tickets.php");
        exit();
    } else {
        // If there's an error, store the error message in session and redirect back
        $_SESSION['error'] = 'Error closing ticket: ' . $stmt->error;
        header("Location: assigned_tickets.php");
        exit();
    }

    // Close the statement
    $stmt->close();
} else {
    // If ticket_id is not provided, show an error
    $_SESSION['error'] = 'No ticket ID provided or the ticket ID is invalid.';
    header("Location: assigned_tickets.php");
    exit();
}

// Close the connection
$conn->close();
?>
