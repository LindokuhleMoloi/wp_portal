<?php
// Require the config file to handle session, database connection, etc.
require_once(__DIR__ . '/../../config.php');

// Redirect if not logged in as project manager
if (!isset($_SESSION['pm_logged_in']) || !isset($_SESSION['pm_employee_id'])) {
    header("Location: project_manager_login.php");
    exit();
}

// The database connection is now managed by config.php
// The global $conn object is available for use.

$pm_employee_id = $_SESSION['pm_employee_id'];
$error = '';
$success = '';

// Process password change form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        // Validate inputs
        if (empty($new_password) || empty($confirm_password)) {
            throw new Exception("Please fill in all fields");
        }
        
        if (strlen($new_password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }

        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in database
        $update_stmt = $conn->prepare("
            UPDATE employee_access 
            SET password = ?, 
                force_password_change = 0, 
                password_changed_at = NOW() 
            WHERE employee_id = ?
        ");
        $update_stmt->bind_param("si", $hashed_password, $pm_employee_id);

        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update password. Please try again.");
        }

        // Clear force password change flag from session
        unset($_SESSION['pm_force_password_change']);
        
        // Set success message in session
        $_SESSION['pm_password_change_success'] = "Password changed successfully!";
        
        // Redirect to dashboard
        header("Location: project_manager_dashboard.php");
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// The connection is now managed by config.php, which closes it automatically
// at the end of the script's execution.
// $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Project Manager</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            position: relative;
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
        
        input::placeholder {
            text-align: center;
            color: #aaa;
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
            background-color: rgba(255, 0, 0, 0.1);
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid #ff5252;
        }
        
        .success-message {
            color: #c9f7c9;
            margin-bottom: 20px;
            font-weight: 600;
            background-color: rgba(0, 109, 119, 0.2);
            padding: 10px;
            border-radius: 8px;
            border-left: 4px solid #0e6574;
        }
        
        .password-strength {
            height: 5px;
            background: #ddd;
            margin: 10px 0;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .password-strength span {
            display: block;
            height: 100%;
            width: 0;
            transition: width 0.3s, background 0.3s;
        }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .info-message {
            color: #a0d8f0;
            margin-bottom: 20px;
            font-size: 0.9rem;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change Your Password</h2>
        
        <?php if (isset($_GET['first_login'])): ?>
            <div class="info-message">
                <i class="fas fa-info-circle"></i> This is your first login. Please create a new password.
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="passwordForm">
            <div class="form-group">
                <input type="password" name="new_password" id="new_password" 
                       placeholder="New Password" required
                       oninput="checkPasswordStrength(this.value)">
                <div class="password-strength">
                    <span id="password-strength-bar"></span>
                </div>
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_password" 
                       placeholder="Confirm Password" required>
            </div>
            
            <button type="submit" class="btn" id="submitBtn">
                <span id="btnText">UPDATE PASSWORD</span>
                <span id="btnSpinner" class="spinner" style="display: none;"></span>
            </button>
        </form>
    </div>

    <script>
        // Password strength indicator
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('password-strength-bar');
            let strength = 0;
            
            // Length check
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
            // Complexity checks
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;
            
            // Update strength bar
            const width = (strength / 6) * 100;
            strengthBar.style.width = width + '%';
            
            // Update color
            if (strength <= 2) {
                strengthBar.style.backgroundColor = '#ff5252'; // Red
            } else if (strength <= 4) {
                strengthBar.style.backgroundColor = '#ffc107'; // Yellow
            } else {
                strengthBar.style.backgroundColor = '#4caf50'; // Green
            }
        }

        // Form submission handler
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert("Passwords do not match!");
                return;
            }
            
            if (newPassword.length < 8) {
                e.preventDefault();
                alert("Password must be at least 8 characters!");
                return;
            }
            
            // Show loading state
            const btn = document.getElementById("submitBtn");
            const btnText = document.getElementById("btnText");
            const btnSpinner = document.getElementById("btnSpinner");
            
            btn.disabled = true;
            btnText.textContent = "Processing...";
            btnSpinner.style.display = "inline-block";
        });
    </script>
</body>
</html>