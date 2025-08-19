<?php
// process_leave_application.php
session_start();
/*
// Load PHPMailer classes
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
*/
// Set content type to JSON
header('Content-Type: application/json');

// Check if employee is logged in
if (!isset($_SESSION['employee_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit();
}

// Get POST data
$employee_id = $_POST['employee_id'] ?? null;
$leave_type_id = $_POST['leave_type_id'] ?? null;
$start_date = $_POST['start_date'] ?? null;
$end_date = $_POST['end_date'] ?? null;
$num_days = $_POST['num_days'] ?? 0;
$reason = $_POST['reason'] ?? '';
$force_submit = ($_POST['force_submit'] ?? 'false') === 'true';

// Validate required fields
if (!$employee_id || !$leave_type_id || !$start_date || !$end_date || $num_days <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid leave application data']);
    $conn->close();
    exit();
}

// Get employee and mentor details
$employee = [];
$sql_employee = "SELECT e.id, e.fullname, e.email, e.mentor_id, m.email AS mentor_email, 
                m.mentor_name AS mentor_name FROM employee_list e 
                LEFT JOIN mentor m ON e.mentor_id = m.mentor_id WHERE e.id = ?";
$stmt_employee = $conn->prepare($sql_employee);

if ($stmt_employee === false) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    $conn->close();
    exit();
}

$stmt_employee->bind_param("i", $employee_id);
$stmt_employee->execute();
$result_employee = $stmt_employee->get_result();

if ($result_employee->num_rows > 0) {
    $employee = $result_employee->fetch_assoc();
}
$stmt_employee->close();

// Get leave type details
$leave_type = [];
$sql_leave_type = "SELECT name FROM leave_types WHERE id = ?";
$stmt_leave_type = $conn->prepare($sql_leave_type);

if ($stmt_leave_type === false) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    $conn->close();
    exit();
}

$stmt_leave_type->bind_param("i", $leave_type_id);
$stmt_leave_type->execute();
$result_leave_type = $stmt_leave_type->get_result();

if ($result_leave_type->num_rows > 0) {
    $leave_type = $result_leave_type->fetch_assoc();
}
$stmt_leave_type->close();

// Insert leave application
$sql = "INSERT INTO leave_applications 
        (employee_id, leave_type_id, start_date, end_date, number_of_days, reason, status, mentor_status) 
        VALUES (?, ?, ?, ?, ?, ?, 0, 0)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
    $conn->close();
    exit();
}

$stmt->bind_param("iissds", $employee_id, $leave_type_id, $start_date, $end_date, $num_days, $reason);

if ($stmt->execute()) {
    $leave_id = $stmt->insert_id;
    
    // Send email notification to mentor if mentor exists
    if (!empty($employee['mentor_email']) && !empty($employee['mentor_id'])) {
        $email_sent = sendMentorNotificationEmail(
            $employee['mentor_email'],
            $employee['mentor_name'],
            $employee['fullname'],
            $leave_type['name'],
            $start_date,
            $end_date,
            $num_days,
            $reason,
            $leave_id
        );
        
        if (!$email_sent) {
            error_log("Failed to send email notification for leave application ID: $leave_id");
        }
    }
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Leave application submitted successfully'
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to submit leave application: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();

/**
 * Send email notification to mentor using PHPMailer
 */
function sendMentorNotificationEmail($mentor_email, $mentor_name, $employee_name, $leave_type, 
                                    $start_date, $end_date, $num_days, $reason, $leave_id) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'mail.pro-learn.co.za';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'workplace@pro-learn.co.za';
        $mail->Password   = 'WokPro@123';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        
        // Recipients
        $mail->setFrom('workplace@pro-learn.co.za', 'Workplace Portal');
        $mail->addAddress($mentor_email, $mentor_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Leave Application Requires Your Approval - ' . $employee_name;
        
        $formatted_start = date('M j, Y', strtotime($start_date));
        $formatted_end = date('M j, Y', strtotime($end_date));
        $approval_link = 'https://pro-learn.co.za/mentor_dashboard.php';
        
        // HTML email body
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Leave Application Notification</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0e6574; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; background-color: #f9f9f9; border-left: 1px solid #ddd; border-right: 1px solid #ddd; }
        .footer { padding: 15px; text-align: center; font-size: 0.9em; color: #666; background-color: #f0f0f0; border-radius: 0 0 5px 5px; }
        .button { 
            display: inline-block; 
            padding: 12px 24px; 
            background-color: #0e6574; 
            color: white; 
            text-decoration: none; 
            border-radius: 5px; 
            margin: 15px 0; 
            font-weight: bold;
        }
        .leave-details { 
            background: #fff; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            padding: 15px; 
            margin: 15px 0; 
        }
        .detail-row { margin-bottom: 10px; }
        .detail-label { font-weight: bold; display: inline-block; width: 120px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Leave Application Notification</h2>
        </div>
        <div class="content">
            <p>Dear $mentor_name,</p>
            <p>Your mentee <strong>$employee_name</strong> has submitted a leave application that requires your approval.</p>
            
            <div class="leave-details">
                <h3 style="margin-top: 0;">Leave Details</h3>
                
                <div class="detail-row">
                    <span class="detail-label">Type:</span>
                    <span>$leave_type</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Dates:</span>
                    <span>$formatted_start to $formatted_end</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Days Requested:</span>
                    <span>$num_days</span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Reason:</span>
                    <span>$reason</span>
                </div>
            </div>
            
            <p>Please review and take appropriate action:</p>
            <a href="$approval_link" class="button">Review Leave Application</a>
            
            <p>Application ID: $leave_id</p>
        </div>
        <div class="footer">
            <p>This is an automated notification. Please do not reply to this email.</p>
            <p>Workplace Portal | Pro-Learn</p>
        </div>
    </div>
</body>
</html>
HTML;

        // Plain text version
        $mail->AltBody = "Leave Application Notification\n\n" .
                         "Dear $mentor_name,\n\n" .
                         "Your mentee $employee_name has submitted a leave application that requires your approval.\n\n" .
                         "Leave Details:\n" .
                         "Type: $leave_type\n" .
                         "Dates: $formatted_start to $formatted_end\n" .
                         "Days Requested: $num_days\n" .
                         "Reason: $reason\n\n" .
                         "Please log in to the Workplace Portal to review this application:\n" .
                         "$approval_link\n\n" .
                         "Application ID: $leave_id\n\n" .
                         "This is an automated notification. Please do not reply to this email.\n" .
                         "Workplace Portal | Pro-Learn";

        return $mail->send();
    } catch (Exception $e) {
        error_log("Email could not be sent to $mentor_email. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}