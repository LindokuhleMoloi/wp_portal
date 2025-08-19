<?php
header('Content-Type: application/json');

// Database connection details
$servername = "localhost";
$username = "tarryn_Lindokuhle";
$password = "L1nd0kuhle";
$database = "tarryn_workplaceportal";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check the connection
if ($conn->connect_error) {
    echo json_encode(["error" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Get the employee code from the GET request
$employee_code = isset($_GET['employee_code']) ? trim($_GET['employee_code']) : '';

// Basic validation for employee code
if (empty($employee_code)) {
    echo json_encode(["error" => "Employee code is required."]);
    exit();
}

// Prepare SQL query to fetch email and full name by employee code
$sql = "SELECT el.fullname, em.meta_value AS email 
        FROM employee_list AS el 
        JOIN employee_meta AS em ON em.employee_id = el.id 
        WHERE el.employee_code = ? AND em.meta_field = 'email'";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["error" => "SQL prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("s", $employee_code);
$stmt->execute();
$result = $stmt->get_result();

$employees = [];
while ($row = $result->fetch_assoc()) {
    $employees[] = [
        'fullname' => $row['fullname'], 
        'email' => $row['email']
    ];
}

// Check if no employees found
if (empty($employees)) {
    echo json_encode(["error" => "No employees found for the provided employee code."]);
} else {
    echo json_encode($employees);
}

// Close connections
$stmt->close();
$conn->close();
?>