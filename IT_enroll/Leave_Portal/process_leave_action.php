<?php
// process_leave_action.php
session_start();

// Redirect if not logged in as project manager
if (!isset($_SESSION['pm_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Get the PM's ID from session
$pm_id = $_SESSION['pm_employee_id'] ?? null;
if (!$pm_id) {
    echo json_encode(['success' => false, 'message' => 'PM ID not found in session.']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "tarryn_Lindokuhle";
$password = "L1nd0kuhle";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

// Check if required parameters are set and valid
if (!isset($_POST['action']) || !isset($_POST['leave_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid leave parameters.']);
    $conn->close();
    exit();
}

$action = $_POST['action'];
$leaveId = intval($_POST['leave_id']);

// Basic validation of leaveId
if ($leaveId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid leave ID.']);
    $conn->close();
    exit();
}

// First verify the leave exists and get mentor status
$check_sql = "SELECT mentor_status FROM leave_applications WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $leaveId);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Leave application not found.']);
    $check_stmt->close();
    $conn->close();
    exit();
}

$leave_data = $check_result->fetch_assoc();
$mentor_status = $leave_data['mentor_status'];
$check_stmt->close();

$currentDateTime = date('Y-m-d H:i:s');

if ($action === 'approve') {
    // Only allow approval if mentor has approved (status=1)
    if ($mentor_status != 1) {
        $message = $mentor_status == 0 
            ? 'Cannot approve - Mentor has not approved this leave yet' 
            : 'Cannot approve - Mentor has rejected this leave';
        echo json_encode(['success' => false, 'message' => $message]);
        $conn->close();
        exit();
    }

    // Update pm_status to 1 and set approval details
    $sql = "UPDATE leave_applications 
            SET pm_status = 1, 
                pm_approved_by = ?,
                pm_approval_date = ?,
                status = 1 /* Set overall status to approved */
            WHERE id = ? AND mentor_status = 1"; // Additional safety check
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("isi", $pm_id, $currentDateTime, $leaveId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Leave approved successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to approve leave: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'SQL prepare failed: ' . $conn->error]);
    }
} elseif ($action === 'reject') {
    // Only allow rejection if mentor has approved (status=1)
    if ($mentor_status != 1) {
        $message = $mentor_status == 0 
            ? 'Cannot reject - Mentor has not decided on this leave yet' 
            : 'Leave already rejected by mentor';
        echo json_encode(['success' => false, 'message' => $message]);
        $conn->close();
        exit();
    }

    // Logic to reject leave: update pm_status to 2 and save reason
    if (!isset($_POST['reason'])) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason is required.']);
        $conn->close();
        exit();
    }
    
    $reason = trim($_POST['reason']);
    if (empty($reason)) {
        echo json_encode(['success' => false, 'message' => 'Rejection reason cannot be empty.']);
        $conn->close();
        exit();
    }

    $sql = "UPDATE leave_applications 
            SET pm_status = 2, 
                pm_rejected_by = ?,
                pm_rejection_date = ?,
                pm_rejection_reason = ?,
                status = 2 /* Set overall status to rejected */
            WHERE id = ? AND mentor_status = 1"; // Additional safety check
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("issi", $pm_id, $currentDateTime, $reason, $leaveId);
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Leave rejected successfully!']);
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