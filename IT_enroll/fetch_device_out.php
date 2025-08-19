<?php
require_once('../config.php'); // Include configuration for database connection

$employee_code = $_POST['employee_code'];

// Fetch employee details using employee_code
$query = "SELECT id FROM employee_list WHERE employee_code = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $employee_code); // Bind the employee_code to the query
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Employee found
    $employee = $result->fetch_assoc();
    $employee_id = $employee['id']; // Store employee id for further use

    // Fetch devices that the employee has checked out
    $query = "
        SELECT L.SN, L.`ROTA OR Training`
        FROM Laptops L
        JOIN Laptop_Logs LL ON L.SN = LL.SN_ID
        WHERE LL.employee_id = ? AND LL.type = 1
        AND L.Status = 'Checked Out'
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $employee_id); // Bind the employee_id to the query
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Devices found, output them as options
        while ($row = $result->fetch_assoc()) {
            echo "<option value='{$row['SN']}'>{$row['ROTA OR Training']}</option>";
        }
    } else {
        // No devices available
        echo "<option value=''>No devices available</option>";
    }
} else {
    // Employee not found
    echo "<option value=''>Employee code not found</option>";
}
?>
