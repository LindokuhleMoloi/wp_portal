<?php
// mentor_login.php (This file should ONLY display the form and handle client-side/session-based lockout display)
session_start();

// Redirect if already logged in (This logic is correct for this file)
if (isset($_SESSION['mentor_logged_in']) && $_SESSION['mentor_logged_in'] === true) {
    header("Location: mentor_dashboard.php");
    exit();
}

// Initialize variables (These are fine for displaying the form)
$employee_code = $_GET['employee_code'] ?? ''; // Get employee_code from URL if redirected with error
$error = $_GET['error'] ?? '';                 // Get error message from URL if redirected from process_mentor_login.php
$password_changed_success = isset($_GET['password_changed']) && $_GET['password_changed'] == 1; // Check for success flag

$login_attempts = $_SESSION['mentor_login_attempts'] ?? 0;
$last_attempt = $_SESSION['mentor_last_attempt'] ?? 0;
$lockout_time = 300; // 5 minutes in seconds

// Check if account is temporarily locked (This logic is also correct for this file)
if ($login_attempts >= 3 && (time() - $last_attempt) < $lockout_time) {
    $remaining_time = $lockout_time - (time() - $last_attempt);
    $error = "Too many failed attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
} elseif ($login_attempts >= 3 && (time() - $last_attempt) >= $lockout_time) {
    // Reset attempts after lockout period (Only if the page is reloaded after lockout expires)
    $_SESSION['mentor_login_attempts'] = 0;
    // Clear any lingering error message related to lockout after reset
    if (strpos($error, 'Too many failed attempts') !== false) {
        $error = '';
    }
}

// No database connection or form processing here anymore
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS (copy-paste directly from project_manager_login.php) */
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

        .login-container {
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

        .login-container::before {
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

        .login-container h2 {
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

        input[type="text"],
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
            max-width: 900px;
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
        }
        .success-message { /* New style for success message */
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

        .attempts-warning {
            color: #ffcccb;
            font-size: 0.9rem;
            margin-top: 5px;
            text-align: center;
        }

        .login-instructions {
            color: white;
            text-align: center;
            margin-bottom: 20px;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .login-container {
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

    <div class="login-container">
        <h2><i class="fas fa-user-tie"></i> MENTOR LOGIN</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                <?php if (strpos($error, 'attempts') === false && $login_attempts > 0): ?>
                    <div class="attempts-warning">Attempts: <?php echo $login_attempts; ?>/3</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($password_changed_success): // Display success message if password was changed ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Your password has been successfully changed. Please log in with your new password.
            </div>
        <?php endif; ?>

        <p class="login-instructions">
            First-time users: Use your employee code as both username and password
        </p>

        <form id="mentorLoginForm" action="process_mentor_login.php" method="POST">
            <div class="form-group">
                <input type="text" name="employee_code" id="employee_code"
                       placeholder="Employee Code"
                       value="<?php echo htmlspecialchars($employee_code); ?>"
                       <?php echo ($login_attempts >= 3 && (time() - $last_attempt) < $lockout_time) ? 'disabled' : ''; ?>
                       required>
            </div>

            <div class="form-group">
                <input type="password" name="password" id="password"
                       placeholder="Password"
                       <?php echo ($login_attempts >= 3 && (time() - $last_attempt) < $lockout_time) ? 'disabled' : ''; ?>
                       required>
                <i class="fas fa-eye password-toggle" id="togglePassword"></i>
            </div>

            <button type="submit" class="btn"
                    <?php echo ($login_attempts >= 3 && (time() - $last_attempt) < $lockout_time) ? 'disabled' : ''; ?>>
                <i class="fas fa-sign-in-alt"></i> LOGIN
            </button>
        </form>
    </div>

    <script>
        // Password toggle functionality
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });

        // Auto-focus on first input field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('employee_code').focus();
        });

        // Basic client-side validation (PHP will do server-side validation)
        document.getElementById('mentorLoginForm').addEventListener('submit', function(e) {
            const code = document.getElementById('employee_code').value.trim();
            const password = document.getElementById('password').value.trim();

            if (!code || !password) {
                e.preventDefault();
                alert('Please enter both employee code and password');
            }
        });
    </script>
</body>
</html>