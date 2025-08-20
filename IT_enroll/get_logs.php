<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "tarryn_workplaceportal";

// Create connection
$mysqli = new mysqli($servername, $username, $password, $database);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$employee_code = $_SESSION['employee_code']; // Assuming session contains employee code

// Fetch past logs
$query = "SELECT * FROM helpdesk_support_incoming WHERE employee_code = ? ORDER BY created_at DESC";
$stmt = $mysqli->prepare($query);
$stmt->bind_param('s', $employee_code);
$stmt->execute();
$result = $stmt->get_result();

$logs = [];
while ($row = $result->fetch_assoc()) {
    $logs[] = $row;
}

$stmt->close();
$mysqli->close();

// Return JSON data
header('Content-Type: application/json');
echo json_encode(['logs' => $logs]);
