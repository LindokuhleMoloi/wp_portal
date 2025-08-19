<?php
session_start();

// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

// Redirect if not logged in as mentor
if (!isset($_SESSION['mentor_logged_in'])) {
    $_SESSION['error_message'] = "Please log in to perform this action.";
    header("Location: mentor_login.php");
    exit();
}

// Ensure it's a POST request
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $_SESSION['error_message'] = "Invalid request method.";
    header("Location: view_mentee_list.php"); // Redirect to a safe page
    exit();
}

// Ensure necessary parameters are provided
if (!isset($_POST['application_id']) || !isset($_POST['action']) || !is_numeric($_POST['application_id'])) {
    $_SESSION['error_message'] = "Invalid leave application or action.";
    header("Location: view_mentee_list.php"); // Redirect to a safe page
    exit();
}

$application_id = filter_var($_POST['application_id'], FILTER_SANITIZE_NUMBER_INT);
$action = $_POST['action']; // 'approve' or 'reject'
$mentor_id = $_SESSION['mentor_employee_id']; // Get the logged-in mentor's ID
$rejection_reason = null; // Initialize to null

// Determine the new mentor_status
$new_status = null;
if ($action == 'approve') {
    $new_status = 1; // Approved
} elseif ($action == 'reject') {
    $new_status = 2; // Rejected
    $rejection_reason = filter_var($_POST['rejection_reason'] ?? '', FILTER_SANITIZE_STRING); // Get rejection reason if available

    if (empty($rejection_reason) && $new_status == 2) {
        $_SESSION['error_message'] = "Rejection reason is required for rejection.";
        // Redirect back to the mentee_leaves page with the specific employee_id
        $redirect_employee_id = filter_var($_POST['mentee_employee_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
        header("Location: mentee_leaves.php?employee_id=" . $redirect_employee_id);
        exit();
    }
} else {
    $_SESSION['error_message'] = "Invalid action specified.";
    // Redirect back to the mentee_leaves page with the specific employee_id
    $redirect_employee_id = filter_var($_POST['mentee_employee_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
    header("Location: mentee_leaves.php?employee_id=" . $redirect_employee_id);
    exit();
}

// Database connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    $_SESSION['error_message'] = "Database connection failed: " . $conn->connect_error;
    // Redirect back to the mentee_leaves page with the specific employee_id
    $redirect_employee_id = filter_var($_POST['mentee_employee_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
    header("Location: view_mentee_leaves.php?employee_id=" . $redirect_employee_id);
    exit();
}

// First, verify that the leave application belongs to a mentee of this mentor
// and that its current mentor_status is 0 (pending)
$sql_verify = "SELECT employee_id FROM leave_applications WHERE id = ? AND mentor_status = 0";
$stmt_verify = $conn->prepare($sql_verify);
if ($stmt_verify) {
    $stmt_verify->bind_param("i", $application_id);
    $stmt_verify->execute();
    $result_verify = $stmt_verify->get_result();
    $leave_data = $result_verify->fetch_assoc();
    $stmt_verify->close();

    if (!$leave_data) {
        $_SESSION['error_message'] = "Leave application not found or not pending your approval.";
        $conn->close();
        // Redirect back to the mentee_leaves page with the specific employee_id
        $redirect_employee_id = filter_var($_POST['mentee_employee_id'] ?? '', FILTER_SANITIZE_NUMBER_INT);
        header("Location: mentee_leaves.php?employee_id=" . $redirect_employee_id);
        exit();
    }
    $mentee_employee_id_from_db = $leave_data['employee_id'];

    // Now verify the mentee_employee_id belongs to the logged-in mentor
    $sql_check_mentor_mentee = "SELECT id FROM employee_list WHERE id = ? AND mentor_id = ?";
    $stmt_check_mentor_mentee = $conn->prepare($sql_check_mentor_mentee);
    if ($stmt_check_mentor_mentee) {
        $stmt_check_mentor_mentee->bind_param("ii", $mentee_employee_id_from_db, $mentor_id);
        $stmt_check_mentor_mentee->execute();
        $result_check = $stmt_check_mentor_mentee->get_result();
        if ($result_check->num_rows == 0) {
            $_SESSION['error_message'] = "You are not authorized to manage this mentee's leave applications.";
            $conn->close();
            header("Location: mentee_list.php"); // Redirect to general mentee list if unauthorized
            exit();
        }
        $stmt_check_mentor_mentee->close();
    } else {
        $_SESSION['error_message'] = "Error preparing mentor-mentee verification statement.";
        $conn->close();
        header("Location: view_mentee_list.php");
        exit();
    }

} else {
    $_SESSION['error_message'] = "Error preparing leave verification statement: " . $conn->error;
    $conn->close();
    header("Location: view_mentee_list.php");
    exit();
}


// Update mentor_status for the leave application
$sql_update_leave = "UPDATE leave_applications SET mentor_status = ?, rejection_reason = ? WHERE id = ?";
$stmt_update_leave = $conn->prepare($sql_update_leave);

if ($stmt_update_leave) {
    $stmt_update_leave->bind_param("isi", $new_status, $rejection_reason, $application_id);
    if ($stmt_update_leave->execute()) {
        $_SESSION['success_message'] = "Leave application " . ($action == 'approve' ? "approved" : "rejected") . " successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating leave application: " . $stmt_update_leave->error;
    }
    $stmt_update_leave->close();
} else {
    $_SESSION['error_message'] = "Error preparing update statement: " . $conn->error;
}

$conn->close();

// Redirect back to the mentee_leaves page for the specific mentee
// Use the mentee_employee_id verified from the database query
header("Location: view_mentee_leaves.php?employee_id=" . $mentee_employee_id_from_db);
exit();
?>