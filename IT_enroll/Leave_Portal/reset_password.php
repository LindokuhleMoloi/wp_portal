<?php
// reset_password.php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = '';
$success_message = '';
$token = $_GET['token'] ?? '';
$new_password = ''; // Initialize variable

// Verify token
if (!empty($token)) {
    $stmt = $conn->prepare("
        SELECT prt.employee_id, el.employee_code 
        FROM password_reset_tokens prt
        JOIN employee_list el ON prt.employee_id = el.id
        WHERE prt.token = ? AND prt.expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $error_message = "Invalid or expired token.";
    } else {
        $user = $result->fetch_assoc();
        $employee_id = $user['employee_id'];
        $employee_code = $user['employee_code'];
        
        // Process password reset
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $new_password = $_POST['new_password'] ?? '';
            $confirm_new_password = $_POST['confirm_new_password'] ?? '';
            
            if ($new_password !== $confirm_new_password) {
                $error_message = "Passwords do not match.";
            } elseif (strlen($new_password) < 8) {
                $error_message = "Password must be at least 8 characters long.";
            } elseif ($new_password === $employee_code) {
                $error_message = "Password cannot be your employee code.";
            } else {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $conn->prepare("UPDATE employee_access SET password = ?, force_password_change = 0 WHERE employee_id = ?");
                $stmt->bind_param("si", $hashed_password, $employee_id);
                
                if ($stmt->execute()) {
                    // Delete used token
                    $stmt = $conn->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
                    $stmt->bind_param("s", $token);
                    $stmt->execute();
                    
                    // Show the new password on screen
                    $success_message = "Your password has been reset successfully.<br><br>";
                    $success_message .= "<strong>New Password:</strong> " . htmlspecialchars($new_password);
                    $success_message .= "<br><br><small>You will be redirected to login in 10 seconds...</small>";
                    // Redirect to login after longer delay
                    header("Refresh: 10; url=leave_login.php");
                } else {
                    $error_message = "Failed to update password.";
                }
            }
        }
    }
} else {
    $error_message = "No token provided.";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background: url('system-image.png') no-repeat center center fixed;
            background-size: cover;
            background-color: #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            color: white;
        }
        .container {
            background: linear-gradient(to right, #0b3e4d, #0e6574, #003e4d);
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        h2 {
            margin-bottom: 30px;
            font-weight: 600;
        }
        .form-group {
            margin-bottom: 20px;
        }
        input[type="password"] {
            width: calc(100% - 30px);
            padding: 15px;
            border: none;
            border-radius: 18px;
            background-color: white;
            font-size: 1.1rem;
            text-align: center;
            color: #333;
        }
        .btn {
            background-color: #e84e17;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 18px;
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.3s ease;
            width: 100%;
        }
        .btn:hover {
            background-color: #d64510;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .error-message {
            color: #ffcccb;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .success-message {
            color: #c9f7c9;
            margin-bottom: 20px;
            font-weight: 600;
            line-height: 1.6;
        }
        .success-message strong {
            color: #ffffff;
        }
        .success-message small {
            font-size: 0.8em;
            color: #a0d8f0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
            <?php if ($error_message == "Invalid or expired token." || $error_message == "No token provided."): ?>
                <a href="forgot_password.php" class="btn">Try Again</a>
            <?php endif; ?>
        <?php elseif (!empty($success_message)): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php else: ?>
            <form action="reset_password.php?token=<?php echo htmlspecialchars($token); ?>" method="POST">
                <div class="form-group">
                    <input type="password" name="new_password" placeholder="New Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_new_password" placeholder="Confirm New Password" required>
                </div>
                <button type="submit" class="btn">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>