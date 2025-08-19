<?php
$servername = "localhost";
$username = "tarryn_Lindokuhle";
$password = "L1nd0kuhle";
$database = "tarryn_workplaceportal";

// Create connection
$mysqli = new mysqli($servername, $username, $password, $database);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$data = json_decode(file_get_contents('php://input'), true);
$message = $data['message'];
$employee_code = $_SESSION['employee_code']; // Assuming session contains employee code

$query = "INSERT INTO helpdesk_messages (employee_code, message) VALUES (?, ?)";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('ss', $employee_code, $message);

if ($stmt->execute()) {
    echo "Message sent successfully!";
} else {
    echo "Error sending message: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
