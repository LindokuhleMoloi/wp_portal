<?php
// processmentor_leave_action.php (Specifically for Mentor Actions)
session_start();

// Redirect if not logged in as mentor
if (!isset($_SESSION['mentor_logged_in'])) {
    // It's good practice to send a JSON response even on redirect for AJAX calls
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Only mentors can process these leaves.']);
    exit();
}

// Database connection (Consider placing this in a separate config/connection file for better practice)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Check if required parameters are set and valid
if (!isset($_POST['action']) || !isset($_POST['leave_id']) || !isset($_POST['approver_role'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid leave parameters. Missing action, leave_id, or approver_role.']);
    $conn->close();
    exit();
}

$action = $_POST['action'];
$leaveId = intval($_POST['leave_id']); // Ensure it's an integer for security
$approverRole = $_POST['approver_role']; // This should be 'mentor' from pending_leaves.php

// Basic validation of leaveId
if ($leaveId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid leave ID.']);
    $conn->close();
    exit();
}

// Ensure the request is coming from a mentor context, matching the session and the expected role
if ($approverRole !== 'mentor') {
    echo json_encode(['success' => false, 'message' => 'Role mismatch. This endpoint is for mentor actions.']);
    $conn->close();
    exit();
}

// Fetch the employee_id associated with this leave_id to verify it belongs to the mentor's mentee
// This adds an extra layer of security to ensure a mentor can only approve/reject leaves for their own mentees.
$mentor_employee_id = $_SESSION['mentor_employee_id'];
$sql_check_mentee = "SELECT el.mentor_id FROM leave_applications la JOIN employee_list el ON la.employee_id = el.id WHERE la.id = ?";
$stmt_check = $conn->prepare($sql_check_mentee);
if ($stmt_check) {
    $stmt_check->bind_param("i", $leaveId);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();
    if ($result_check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Leave application not found.']);
        $stmt_check->close();
        $conn->close();
        exit();
    }
    $row_check = $result_check->fetch_assoc();
    if ($row_check['mentor_id'] != $mentor_employee_id) {
        echo json_encode(['success' => false, 'message' => 'You are not authorized to process this leave request.']);
        $stmt_check->close();
        $conn->close();
        exit();
    }
    $stmt_check->close();
} else {
    echo json_encode(['success' => false, 'message' => 'SQL prepare error during mentee check: ' . $conn->error]);
    $conn->close();
    exit();
}


// Determine which status column to update (for mentor actions, it's mentor_status)
$statusColumn = 'mentor_status';

if ($action === 'approve') {
    // Update mentor_status to 1 (Approved by Mentor)
    // You might also need logic here to update the overall 'status' (e.g., to 1 for Approved)
    // if only mentor approval is needed, or if it triggers the next approval step.
    // For now, it only updates mentor_status.
    $sql = "UPDATE leave_applications SET {$statusColumn} = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $leaveId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Leave approved by mentor successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve leave: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $conn->error]);
    }
} elseif ($action === 'reject') {
    // Update mentor_status to 2 (Rejected by Mentor)
    // A rejection from the mentor usually means the entire leave request is denied.
    // So, we also set the overall 'status' to 2 (Rejected) and store the reason.
    if (!isset($_POST['reason']) || empty(trim($_POST['reason']))) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required.']);
        $conn->close();
        exit();
    }
    $reason = $_POST['reason'];

    $sql = "UPDATE leave_applications SET {$statusColumn} = 2, status = 2, rejection_reason = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $reason, $leaveId); // 's' for string reason, 'i' for integer leaveId
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Leave rejected by mentor successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to reject leave: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $conn->error]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
}

$conn->close();

?>