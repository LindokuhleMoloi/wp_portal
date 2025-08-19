<?php
    $mysqli = new mysqli("localhost", "tarryn_Lindokuhle", "L1nd0kuhle", "tarryn_workplaceportal");

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    $employeeCode = $_POST['employee_code'];
    $newEmail = $_POST['new_email'];

    // Step 1: Get the employee_id from employee_list table
    $sql1 = "SELECT id FROM employee_list WHERE employee_code = ?";
    $stmt1 = $mysqli->prepare($sql1);
    $stmt1->bind_param("s", $employeeCode);
    $stmt1->execute();
    $result1 = $stmt1->get_result();

    if ($result1->num_rows > 0) {
        $row = $result1->fetch_assoc();
        $employeeId = $row['id'];

        // Step 2: Update the employee email in employee_meta table
        // Corrected SQL query: update meta_value where meta_field is 'email'
        $sql2 = "UPDATE employee_meta SET meta_value = ? WHERE employee_id = ? AND meta_field = 'email'";
        $stmt2 = $mysqli->prepare($sql2);
        $stmt2->bind_param("si", $newEmail, $employeeId);

        if ($stmt2->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt2->error]);
        }
        $stmt2->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Employee not found.']);
    }

    $stmt1->close();
    $mysqli->close();
?>
