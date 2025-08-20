<?php
// Require the config file to handle session, database connection, etc.
require_once(__DIR__ . '/../../config.php');

// Redirect if employee is not logged in
if (!isset($_SESSION['employee_id'])) {
    header("Location: leave_login.php");
    exit();
}

// The database connection is now managed by config.php
// The global $conn object is available for use.

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

// The connection is now managed by config.php, which closes it automatically
// at the end of the script's execution.
// $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0e6574;
            --secondary-color: #e84e17;
            --accent-color: #2fa8e0;
            --dark-color: #0b3e4d;
            --light-color: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #dc3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('system-image.png') no-repeat center center fixed;
            background-size: cover;
            background-color: #000;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
        }

        .logo {
            position: absolute;
            top: 30px;
            right: 30px;
            width: 100px;
            height: 80px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .logo-letter {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 3.5rem;
            color: white;
            transition: transform 0.3s ease;
        }

        .logo-letter.w {
            transform: translateY(-3px);
        }

        .logo-letter.p {
            transform: translateY(3px);
        }

        .logo:hover .logo-letter.w {
            transform: translateY(-5px);
        }

        .logo:hover .logo-letter.p {
            transform: translateY(5px);
        }

        .container {
            position: relative;
            background: linear-gradient(to right, var(--dark-color), var(--primary-color));
            padding: 40px;
            border-radius: 25px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            overflow: hidden;
            color: white;
        }

        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 40%;
            background: linear-gradient(to right, rgba(0, 0, 0, 0.25), transparent);
            z-index: 1;
            border-radius: 25px 0 0 25px;
        }

        .container h2 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 25px;
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
            z-index: 2;
        }

        input[type="password"] {
            width: 100%;
            padding: 16px 45px 16px 16px;
            border: none;
            border-radius: 18px;
            background-color: white;
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        input::placeholder {
            text-align: center;
        }

        .btn {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 20px;
            width: 100%;
            font-size: 1.3rem;
            font-weight: 700;
            border-radius: 18px;
            margin-bottom: 20px;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.3s ease;
            letter-spacing: 1.5px;
        }

        .btn:hover {
            background-color: #d64510;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .error-message {
            color: #ffcccb;
            margin-top: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            position: relative;
            z-index: 2;
            padding: 10px;
            background-color: rgba(220, 53, 69, 0.2);
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .success-message {
            color: var(--light-color);
            background-color: rgba(40, 167, 69, 0.2);
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 16px;
            cursor: pointer;
            color: #666;
            z-index: 3;
            background: none;
            border: none;
            font-size: 1.2rem;
            padding: 0;
            height: 20px;
            width: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .instructions {
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9em;
            opacity: 0.8;
            position: relative;
            z-index: 2;
        }

        @media (max-width: 768px) {
            .container {
                padding: 30px 20px;
            }
            
            .logo {
                top: 20px;
                right: 20px;
                width: 60px;
                height: 50px;
            }
            
            .logo-letter {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="logo">
        <div class="logo-letter w">W</div>
        <div class="logo-letter p">P</div>
    </div>

    <div class="container">
        <h2><i class="fas fa-key"></i> Change Password</h2>
        
        <?php if (isset($error) && !empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="passwordForm">
            <div class="form-group">
                <input type="password" name="new_password" id="new_password" 
                       placeholder="New Password" required>
                <button type="button" class="password-toggle" id="toggleNewPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_password" id="confirm_password" 
                       placeholder="Confirm Password" required>
                <button type="button" class="password-toggle" id="toggleConfirmPassword">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sync-alt"></i> Change Password
            </button>
        </form>
    </div>

    <script>
        // Password toggle functionality
        function setupPasswordToggle(inputId, toggleId) {
            const toggleBtn = document.getElementById(toggleId);
            const passwordInput = document.getElementById(inputId);
            const icon = toggleBtn.querySelector('i');
            
            toggleBtn.addEventListener('click', function() {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
                // Keep focus on the input field
                passwordInput.focus();
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            setupPasswordToggle('new_password', 'toggleNewPassword');
            setupPasswordToggle('confirm_password', 'toggleConfirmPassword');

            // Auto-focus on first input field
            document.getElementById('new_password').focus();
        });

        // Prevent form submission if fields are empty (basic client-side check)
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPass = document.getElementById('new_password').value.trim();
            const confirmPass = document.getElementById('confirm_password').value.trim();
            
            if (!newPass || !confirmPass) {
                e.preventDefault();
                alert('All password fields are required.');
            } else if (newPass !== confirmPass) {
                e.preventDefault();
                alert('New password and confirm password do not match.');
            } else if (newPass.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
            }
        });
    </script>
</body>
</html>