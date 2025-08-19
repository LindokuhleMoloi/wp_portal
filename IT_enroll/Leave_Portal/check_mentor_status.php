<?php
session_start();
require_once 'db_connection.php'; // Your DB connection file

$leave_id = $_GET['leave_id'];

$sql = "SELECT mentor_status FROM leave_applications WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $leave_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        'mentor_status' => $row['mentor_status']
    ]);
} else {
    echo json_encode([
        'error' => 'Leave not found'
    ]);
}
$stmt->close();
$conn->close();
?>