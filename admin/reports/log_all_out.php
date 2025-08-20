<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ensure logout request is valid
if (isset($_POST['logout_employee_id'])) {
    $employee_id = $_POST['logout_employee_id'];

    // SQL query to update logs for the given employee_id to "logged out"
    $update_query = "UPDATE `logs` 
                     SET `type` = 2, `date_created` = NOW() 
                     WHERE `employee_id` = ? AND `type` = 1"; // 1 represents 'logged in'

    if ($stmt = $conn->prepare($update_query)) {
        // Bind the employee_id parameter
        $stmt->bind_param("i", $employee_id);

        // Execute the query
        if ($stmt->execute()) {
            echo "All employees logged out successfully.";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
} else {
    echo "No employee_id provided.";
}

$conn->close();
?>
