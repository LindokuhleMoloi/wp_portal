<?php
// change_mentor_password.php
session_start();

// Redirect if mentor is not logged in
if (!isset($_SESSION['mentor_logged_in']) || $_SESSION['mentor_logged_in'] !== true) {
    header("Location: mentor_login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$employee_id = $_SESSION['mentor_employee_id'];
$error = '';
$success = '';

// Check if it's a "first time" change (e.g., after initial MD5 login)
$is_first_time_change = isset($_GET['first_time']) && $_GET['first_time'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_new_password = $_POST['confirm_new_password'] ?? '';

    // Validate inputs
    if (empty($new_password) || empty($confirm_new_password)) {
        $error = "New password and confirmation are required.";
    } elseif ($new_password !== $confirm_new_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8 || !preg_match("/[0-9]/", $new_password) || !preg_match("/[a-zA-Z]/", $new_password)) {
        $error = "New password must be at least 8 characters long and contain both letters and numbers.";
    } else {
        // Fetch current password hash from mentor_accounts table
        $sql_fetch_pass = "SELECT password FROM mentor_accounts WHERE employee_id = ?";
        $stmt_fetch_pass = $conn->prepare($sql_fetch_pass);
        if ($stmt_fetch_pass) {
            $stmt_fetch_pass->bind_param("i", $employee_id);
            $stmt_fetch_pass->execute();
            $result_fetch_pass = $stmt_fetch_pass->get_result();
            $user_data = $result_fetch_pass->fetch_assoc();
            $stmt_fetch_pass->close();

            if ($user_data) {
                $stored_hash = $user_data['password'];
                $is_current_password_valid = false;

                // Password verification logic
                if (password_verify($current_password, $stored_hash)) {
                    $is_current_password_valid = true;
                } else {
                    // Fallback for MD5 (less secure, but replicates existing logic)
                    if (md5($current_password) === $stored_hash) {
                         $is_current_password_valid = true;
                    }
                }
                
                // Special handling for first-time login where current_password might be the employee_code
                // This allows the initial employee_code to be accepted as the "current password"
                if (!$is_current_password_valid && $is_first_time_change && $current_password === $_SESSION['mentor_employee_code']) {
                     $is_current_password_valid = true;
                }

                if (!$is_current_password_valid) {
                    $error = "Incorrect current password. If this is your first time login, please use your Employee Code.";
                } else {
                    // Hash the new password securely
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                    // Update password and reset force_password_change flag in mentor_accounts
                    $sql_update_pass = "UPDATE mentor_accounts SET password = ?, force_password_change = 0, date_updated = NOW() WHERE employee_id = ?";
                    $stmt_update_pass = $conn->prepare($sql_update_pass);
                    if ($stmt_update_pass) {
                        $stmt_update_pass->bind_param("si", $new_hashed_password, $employee_id);
                        if ($stmt_update_pass->execute()) {
                            // Password changed successfully, destroy session and redirect to login
                            session_destroy(); // Destroy current session
                            header("Location: mentor_login.php?password_changed=1"); // Redirect with success flag
                            exit();
                        } else {
                            $error = "Error updating password: " . $stmt_update_pass->error;
                        }
                        $stmt_update_pass->close();
                    } else {
                        $error = "Database error preparing update statement: " . $conn->error;
                    }
                }
            } else {
                $error = "User not found in mentor access records.";
            }
        } else {
            $error = "Database error preparing fetch statement: " . $conn->error;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Mentor Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS (copy-paste directly from project_manager_login.php or change_pm_password.php) */
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
            background: url('system-image.png') no-repeat center center fixed;
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
            max-width: 900px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
            overflow: hidden;
            color: white; /* Added for text visibility */
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
            padding: 16px;
            border: none;
            border-radius: 18px;
            background-color: white;
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 15px;
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
            margin-bottom: 20px; /* Added margin */
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
            right: 20px;
            top: 16px;
            cursor: pointer;
            color: #666;
            z-index: 3;
        }
        
        .instructions {
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9em;
            opacity: 0.8;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: var(--accent-color);
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
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
        <h2><i class="fas fa-key"></i> Change Mentor Password</h2>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($is_first_time_change): ?>
            <p class="instructions">
                This is your first login. Please change your password for security.
            </p>
        <?php endif; ?>
        
        <form id="changePasswordForm" action="change_mentor_password.php" method="POST">
            <div class="form-group">
                <input type="password" name="current_password" id="current_password" 
                       placeholder="Current Password" required>
                <i class="fas fa-eye password-toggle" id="toggleCurrentPassword"></i>
            </div>
            
            <div class="form-group">
                <input type="password" name="new_password" id="new_password" 
                       placeholder="New Password" required>
                <i class="fas fa-eye password-toggle" id="toggleNewPassword"></i>
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_new_password" id="confirm_new_password" 
                       placeholder="Confirm New Password" required>
                <i class="fas fa-eye password-toggle" id="toggleConfirmPassword"></i>
            </div>
            
            <button type="submit" class="btn">
                <i class="fas fa-sync-alt"></i> Change Password
            </button>
        </form>
        <?php
        // This link is removed because we are doing a direct header redirect now.
        // if (!empty($success)): ?>
            <!-- <a href="mentor_dashboard.php" class="back-link">Go to Dashboard <i class="fas fa-arrow-right"></i></a> -->
        <?php // endif; ?>
    </div>

    <script>
        // Password toggle functionality for all fields
        function setupPasswordToggle(inputId, toggleId) {
            document.getElementById(toggleId).addEventListener('click', function() {
                const passwordInput = document.getElementById(inputId);
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    this.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    this.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            setupPasswordToggle('current_password', 'toggleCurrentPassword');
            setupPasswordToggle('new_password', 'toggleNewPassword');
            setupPasswordToggle('confirm_new_password', 'toggleConfirmPassword');

            // Auto-focus on first input field
            document.getElementById('current_password').focus();
        });

        // Prevent form submission if fields are empty (basic client-side check)
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            const currentPass = document.getElementById('current_password').value.trim();
            const newPass = document.getElementById('new_password').value.trim();
            const confirmPass = document.getElementById('confirm_new_password').value.trim();
            
            if (!currentPass || !newPass || !confirmPass) {
                e.preventDefault();
                alert('All password fields are required.');
            } else if (newPass !== confirmPass) {
                e.preventDefault();
                alert('New password and confirm password do not match.');
            }
        });
    </script>
</body>
</html>