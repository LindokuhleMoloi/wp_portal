<?php
// Require the config file to handle session, database connection, etc.
require_once(__DIR__ . '/../../config.php');

// The database connection is now managed by config.php
// The global $conn object is available for use.

$leave_id = $_GET['leave_id'] ?? null;

if (!$leave_id) {
    echo json_encode([
        'error' => 'Leave ID not provided'
    ]);
    exit();
}

$sql = "SELECT mentor_status FROM leave_applications WHERE id = ?";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode([
        'error' => 'Database error: ' . $conn->error
    ]);
    exit();
}

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

// The connection is now managed by config.php, which closes it automatically
// at the end of the script's execution.
// $conn->close();
?>