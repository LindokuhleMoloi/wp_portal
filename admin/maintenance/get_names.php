<?php
include 'config.php'; // Include your database connection file

if (isset($_GET['employee_code'])) {
    $employee_code = $_GET['employee_code'];

    // Query the database for the fullname of the given employee code
    $sql = "SELECT fullname FROM employee_list WHERE employee_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $employee_code); // Bind employee_code as a string
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the employee code exists
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Return the fullname in JSON format
        echo json_encode([$row]);
    } else {
        // No employee found with the given employee_code
        echo json_encode([]);
    }

    $stmt->close();
}
?>
