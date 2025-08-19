<?php
// process_mentor_login.php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get form data
$employee_code = $_POST['employee_code'] ?? '';
$input_password = $_POST['password'] ?? '';

// Set the Mentor designation ID (confirmed as 50)
$mentor_designation_id = 50;

// Prepare statement to check if user is a Mentor
$stmt = $conn->prepare("
    SELECT el.id, el.employee_code, el.fullname, el.designation_id, dl.name AS designation_name
    FROM employee_list el
    JOIN designation_list dl ON el.designation_id = dl.id
    WHERE el.employee_code = ?
    AND el.designation_id = ?
    AND dl.name = 'Mentor'
");

if (!$stmt) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("si", $employee_code, $mentor_designation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found or not a Mentor
    header("Location: mentor_login.php?error=" . urlencode("This login is only for Mentors. You don't have access to this section.") . "&employee_code=" . urlencode($employee_code));
    exit();
}

$user = $result->fetch_assoc();

// Now check mentor_accounts table for login credentials
$stmt_account = $conn->prepare("
    SELECT password, force_password_change, account_locked 
    FROM mentor_accounts 
    WHERE employee_id = ?
");

if (!$stmt_account) {
    die("Error preparing account statement: " . $conn->error);
}

$stmt_account->bind_param("i", $user['id']);
$stmt_account->execute();
$account_result = $stmt_account->get_result();

if ($account_result->num_rows === 0) {
    // First-time login - check if using employee code as password
    if ($input_password === $user['employee_code']) {
        // Create account in mentor_accounts
        $hashed_password = password_hash($user['employee_code'], PASSWORD_DEFAULT);
        $insert_stmt = $conn->prepare("
            INSERT INTO mentor_accounts (employee_id, employee_code, password, force_password_change, account_locked)
            VALUES (?, ?, ?, 1, 0)
        ");
        $insert_stmt->bind_param("iss", $user['id'], $user['employee_code'], $hashed_password);
        
        if ($insert_stmt->execute()) {
            // Set session variables
            $_SESSION['mentor_logged_in'] = true;
            $_SESSION['mentor_employee_id'] = $user['id'];
            $_SESSION['mentor_employee_code'] = $user['employee_code'];
            $_SESSION['mentor_fullname'] = $user['fullname'];
            $_SESSION['mentor_designation'] = $user['designation_name'];
            
            // Redirect to password change
            header("Location: change_mentor_password.php?first_time=1");
            exit();
        } else {
            header("Location: mentor_login.php?error=" . urlencode("Account setup failed. Please contact support.") . "&employee_code=" . urlencode($employee_code));
            exit();
        }
    } else {
        header("Location: mentor_login.php?error=" . urlencode("Invalid initial password. Use your employee code.") . "&employee_code=" . urlencode($employee_code));
        exit();
    }
} else {
    // Existing mentor account - verify password
    $account = $account_result->fetch_assoc();
    
    if ($account['account_locked'] == 1) {
        header("Location: mentor_login.php?error=" . urlencode("Your account is locked. Please contact support.") . "&employee_code=" . urlencode($employee_code));
        exit();
    }
    
    // Password verification
    $is_password_valid = password_verify($input_password, $account['password']) || 
                        ($input_password === $user['employee_code'] && $account['password'] === $user['employee_code']);
    
    if (!$is_password_valid) {
        // Increment failed attempts
        $_SESSION['mentor_login_attempts'] = ($_SESSION['mentor_login_attempts'] ?? 0) + 1;
        $_SESSION['mentor_last_attempt'] = time();
        
        header("Location: mentor_login.php?error=" . urlencode("Invalid password.") . "&employee_code=" . urlencode($employee_code));
        exit();
    }
    
    // Successful login
    $_SESSION['mentor_logged_in'] = true;
    $_SESSION['mentor_employee_id'] = $user['id'];
    $_SESSION['mentor_employee_code'] = $user['employee_code'];
    $_SESSION['mentor_fullname'] = $user['fullname'];
    $_SESSION['mentor_designation'] = $user['designation_name'];
    
    // Reset login attempts
    $_SESSION['mentor_login_attempts'] = 0;
    
    // Update last login
    $conn->query("UPDATE mentor_accounts SET last_login = NOW() WHERE employee_id = {$user['id']}");
    
    // Redirect based on password change requirement
    if ($account['force_password_change'] == 1) {
        header("Location: change_mentor_password.php");
        exit();
    }
    
    header("Location: mentor_dashboard.php");
    exit();
}

$conn->close();
?>