<?php
session_start();
require_once(__DIR__ . '/../../classes/DBConnection.php');

try {
    $db = new DBConnection();
    $conn = $db->conn;

    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        throw new Exception("Invalid CSRF token");
    }

    $employee_code = $_POST['employee_code'] ?? '';
    $input_password = $_POST['password'] ?? '';

    if (empty($employee_code) || empty($input_password)) {
        throw new Exception("Employee code and password are required");
    }

    // Check if employee exists and is a Project Manager (designation_id = 49)
    $stmt = $conn->prepare("
        SELECT 
            el.id, 
            el.fullname, 
            el.designation_id,
            ea.password, 
            ea.force_password_change, 
            ea.account_locked
        FROM employee_list el
        LEFT JOIN employee_access ea ON el.id = ea.employee_id
        WHERE el.employee_code = ? 
        AND el.status = 1
        AND el.designation_id = 49
    ");
    $stmt->bind_param("s", $employee_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Invalid credentials or you are not a Project Manager");
    }

    $user = $result->fetch_assoc();

    // Check if account is locked
    if ($user['account_locked'] == 1) {
        throw new Exception("Your account is locked. Please contact HR.");
    }

    // FIRST-TIME LOGIN HANDLING
    $is_first_login = false;
    
    // Case 1: No password record exists in employee_access
    if ($user['password'] === null) {
        $is_first_login = true;
        // Create initial record with employee_code as temporary password
        $hashed_temp = password_hash($employee_code, PASSWORD_DEFAULT);
        $insert_stmt = $conn->prepare("
            INSERT INTO employee_access 
                (employee_id, employee_code, password, force_password_change) 
            VALUES (?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                password = VALUES(password),
                force_password_change = VALUES(force_password_change)
        ");
        $insert_stmt->bind_param("iss", $user['id'], $employee_code, $hashed_temp);
        if (!$insert_stmt->execute()) {
            throw new Exception("System error during first-time login setup");
        }
    }
    // Case 2: Using temporary password (employee_code)
    elseif ($input_password === $employee_code && password_verify($employee_code, $user['password'])) {
        $is_first_login = true;
    }
    // Case 3: Normal password check
    elseif (!password_verify($input_password, $user['password'])) {
        throw new Exception("Invalid password");
    }

    // Set session variables
    $_SESSION['pm_logged_in'] = true;
    $_SESSION['pm_employee_id'] = $user['id'];
    $_SESSION['pm_employee_code'] = $employee_code;
    $_SESSION['pm_fullname'] = $user['fullname'];

    // Update last login
    $update_stmt = $conn->prepare("UPDATE employee_access SET last_login = NOW() WHERE employee_id = ?");
    $update_stmt->bind_param("i", $user['id']);
    $update_stmt->execute();

    // REDIRECT LOGIC
    if ($is_first_login || $user['force_password_change'] == 1) {
        $_SESSION['pm_force_password_change'] = true;
        header("Location: change_pm_password.php?first_login=1");
        exit();
    }
    
    header("Location: project_manager_dashboard.php");
    exit();

} catch (Exception $e) {
    $error = urlencode($e->getMessage());
    header("Location: project_manager_login.php?error=$error&employee_code=" . urlencode($employee_code));
    exit();
}
?>