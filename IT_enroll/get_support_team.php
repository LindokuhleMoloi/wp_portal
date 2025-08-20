<?php
$mysqli = new mysqli("localhost", "root", "", "tarryn_workplaceportal");
if ($mysqli->connect_error) {
    die(json_encode([])); // Return empty array on error
}

$employeeCode = $_GET['employee_code'] ?? '';
if ($employeeCode) {
    $stmt = $mysqli->prepare("SELECT employee_code FROM support_team WHERE employee_code = ?");
    $stmt->bind_param("s", $employeeCode);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($data);
    $stmt->close();
} else {
    echo json_encode([]);
}

$mysqli->close();
?>