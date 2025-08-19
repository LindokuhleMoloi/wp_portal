<?php
require_once('../config.php'); // Include configuration for database connection

// Retrieve values from POST request
$employee_code = $_POST['employee_code'];
$device_id = $_POST['device_id'];
$type = $_POST['type'];

// Fetch the employee ID
$query = "SELECT id FROM employee_list WHERE employee_code = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $employee_code);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $employee = $result->fetch_assoc();
    $employee_id = $employee['id'];

    if ($type == 1) { // Check In
        // Check if the device is currently checked out
        $query = "SELECT * FROM Laptop_Logs WHERE SN_ID = ? AND employee_id = ? AND type = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('si', $device_id, $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "Device is already checked in by you.";
        } else {
            // Update the device status to "Checked Out"
            $query = "UPDATE Laptops SET Status = 'Checked Out' WHERE SN = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $device_id);
            $stmt->execute();

            // Log the check-in in Laptop_Logs
            $query = "INSERT INTO Laptop_Logs (employee_id, type, date_created, SN_ID) VALUES (?, ?, NOW(), ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iis', $employee_id, $type, $device_id);
            $stmt->execute();
            
            echo "Device checked in successfully.";
        }
    } elseif ($type == 2) { // Check Out
        // Check if the device is already checked out by the employee
        $query = "SELECT * FROM Laptop_Logs WHERE SN_ID = ? AND employee_id = ? AND type = 1";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('si', $device_id, $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo "You cannot check out this device as it is not checked in.";
        } else {
            // Update the device status to "Available"
            $query = "UPDATE Laptops SET Status = 'Available' WHERE SN = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $device_id);
            $stmt->execute();

            // Log the check-out in Laptop_Logs and capture the return time in Return_time column
            $query = "UPDATE Laptop_Logs SET type = ?, Return_time = NOW() WHERE employee_id = ? AND SN_ID = ? AND type = 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iis', $type, $employee_id, $device_id);
            $stmt->execute();

            echo "Device checked out successfully.";
        }
    }
} else {
    echo "Employee code not found.";
}
?>
