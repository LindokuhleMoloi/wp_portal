<?php
session_start();

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die("Security violation detected");
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("System error. Please try again later.");
}

try {
    $employee_code = trim($_POST['employee_code'] ?? '');
    $input_password = $_POST['password'] ?? '';

    // Validate inputs
    if (empty($employee_code) || empty($input_password)) {
        throw new Exception("Both fields are required");
    }

    // Check employee exists
    $stmt = $conn->prepare("
        SELECT el.id, el.designation_id, el.fullname, el.status,
               ea.password, ea.force_password_change, ea.account_locked
        FROM employee_list el
        LEFT JOIN employee_access ea ON el.id = ea.employee_id
        WHERE el.employee_code = ?
    ");
    
    if (!$stmt) {
        throw new Exception("System error. Please try again.");
    }
    
    $stmt->bind_param("s", $employee_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Invalid credentials");
    }

    $user = $result->fetch_assoc();

    // Check account status
    if ($user['status'] != 1) {
        throw new Exception("Account inactive. Contact HR.");
    }

    // Check if account is locked
    if ($user['account_locked'] == 1) {
        throw new Exception("Account locked. Contact HR.");
    }

    // First-time login handling
    $force_password_change = false;

    // Case 1: No password exists
    if ($user['password'] === null) {
        $force_password_change = true;
        $hashed_temp = password_hash($employee_code, PASSWORD_DEFAULT);
        $insert_stmt = $conn->prepare("
            INSERT INTO employee_access (employee_id, employee_code, password, force_password_change)
            VALUES (?, ?, ?, 1)
        ");
        $insert_stmt->bind_param("iss", $user['id'], $employee_code, $hashed_temp);
        $insert_stmt->execute();
    } 
    // Case 2: Using temporary password
    elseif (password_verify($employee_code, $user['password'])) {
        $force_password_change = true;
    }
    // Case 3: Normal password check
    elseif (!password_verify($input_password, $user['password'])) {
        throw new Exception("Invalid credentials");
    }

    // Set session
    $_SESSION['employee_id'] = $user['id'];
    $_SESSION['employee_code'] = $employee_code;
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['loggedin'] = true;

    // Update last login
    $update_stmt = $conn->prepare("UPDATE employee_access SET last_login = NOW() WHERE employee_id = ?");
    $update_stmt->bind_param("i", $user['id']);
    $update_stmt->execute();

    // Redirect
    if ($force_password_change || $user['force_password_change'] == 1) {
        header("Location: change_password.php?first_login=1");
        exit();
    }
    
    header("Location: employee_dashboard.php");
    exit();

} catch (Exception $e) {
    $error = urlencode($e->getMessage());
    header("Location: leave_login.php?error=$error&employee_code=" . urlencode($employee_code));
    exit();
} finally {
    $conn->close();
}
?>