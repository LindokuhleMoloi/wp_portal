<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
    header("Location: leave_login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($new_password) || empty($confirm_password)) {
            throw new Exception("Both fields are required");
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception("Passwords don't match");
        }
        
        if (strlen($new_password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("
            UPDATE employee_access 
            SET password = ?, force_password_change = 0 
            WHERE employee_id = ?
        ");
        $stmt->bind_param("si", $hashed_password, $_SESSION['employee_id']);
        $stmt->execute();
        
        $_SESSION['password_change_success'] = "Password changed successfully!";
        header("Location: employee_dashboard.php");
        exit();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 500px; margin: 50px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        input { width: 100%; padding: 10px; }
        button { background: #4CAF50; color: white; padding: 10px; border: none; cursor: pointer; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change Password</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <input type="password" name="new_password" placeholder="New Password" required>
            </div>
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button type="submit">Change Password</button>
        </form>
    </div>
</body>
</html>
<?php $conn->close(); ?>