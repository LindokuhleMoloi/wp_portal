<?php
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ticket_id = $_POST['ticket_id'];
    $assigned_to = $_POST['assigned_to'];

    // Fetch support member name
    $stmt = $conn->prepare("SELECT name FROM support_team WHERE id = ?");
    $stmt->bind_param("i", $assigned_to);
    $stmt->execute();
    $result = $stmt->get_result();
    $support_member = $result->fetch_assoc();
    $assigned_name = $support_member['name'];
    $stmt->close();

    // Update the administered_tickets table
    $update_stmt = $conn->prepare("INSERT INTO administered_tickets (ticket_id, assigned_to, assigned_name) VALUES (?, ?, ?)");
    $update_stmt->bind_param("iis", $ticket_id, $assigned_to, $assigned_name);

    if ($update_stmt->execute()) {
        echo "Support member successfully assigned.";
    } else {
        echo "Error: " . $update_stmt->error;
    }

    $update_stmt->close();
    $conn->close();
}
?>
