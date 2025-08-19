<?php
header('Content-Type: application/json');

// Database connection
$conn = new mysqli("localhost", "tarryn_Lindokuhle", "L1nd0kuhle", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input data
if (empty($data)) {
    echo json_encode(['success' => false, 'message' => 'No input data provided']);
    exit;
}

$employeeId = $data['employeeId'] ?? null;
$date = $data['date'] ?? null;
$newTime = $data['newTime'] ?? null;
$timeType = $data['timeType'] ?? null;
$notes = $data['notes'] ?? null;

// Validate required fields
if (empty($employeeId) || empty($date) || empty($newTime) || empty($timeType)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data: Missing required fields']);
    exit;
}

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

// Validate time format (ISO 8601)
if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $newTime)) {
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    exit;
}

// Validate time type (In or Out)
if (!in_array($timeType, ['In', 'Out'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid time type']);
    exit;
}

// Convert time type to database value
$type = ($timeType === 'In') ? 1 : 2;

// Prepare SQL statement
$sql = "UPDATE logs 
        SET date_created = ?, notes = ?
        WHERE employee_id = ? 
          AND DATE(date_created) = ? 
          AND type = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

// Bind parameters
$stmt->bind_param("ssisi", $newTime, $notes, $employeeId, $date, $type);

// Execute the statement
if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Time updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No matching record found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update time: ' . $stmt->error]);
}

// Close the statement and connection
$stmt->close();
$conn->close();
?>