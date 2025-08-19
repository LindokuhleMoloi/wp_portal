<?php
// Database connection
$servername = "localhost"; // Your server name
$username = "tarryn_Lindokuhle"; // Your username
$password = "L1nd0kuhle"; // Your password
$dbname = "tarryn_workplaceportal"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the required POST parameters are set
if(isset($_POST['ticket_id']) && isset($_POST['support_member_id'])) {
    $ticket_id = $_POST['ticket_id'];
    $support_member_id = $_POST['support_member_id'];

    // Update the ticket's support member in the database
    $sql = "UPDATE helpdesk_support_incoming 
            SET assigned_to = ? 
            WHERE id = ?";

    if($stmt = $conn->prepare($sql)) {
        // Bind the parameters to the SQL query
        $stmt->bind_param("ii", $support_member_id, $ticket_id);
        
        // Execute the query
        if($stmt->execute()) {
            // Return success response
            echo "success";
        } else {
            // Return error response if query fails
            echo "error";
        }

        // Close the statement
        $stmt->close();
    } else {
        // Return error if SQL preparation fails
        echo "error";
    }
} else {
    // Return error if POST parameters are not set
    echo "error";
}

// Close the database connection
$conn->close();
?>
