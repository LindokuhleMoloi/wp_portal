<?php
// forgot_password.php
session_start();

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("System error. Please try again later.");
}

// Initialize variables
$error = '';
$success = '';
$employee_code = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_code = trim($_POST['employee_code'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    try {
        // Validate inputs
        if (empty($employee_code) {
            throw new Exception("Employee code is required");
        }
        if (empty($new_password) {
            throw new Exception("New password is required");
        }
        if (empty($confirm_password)) {
            throw new Exception("Please confirm your new password");
        }
        if ($new_password !== $confirm_password) {
            throw new Exception("Passwords do not match");
        }
        if (strlen($new_password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }

        // Check if employee exists and is active
        $stmt = $conn->prepare("
            SELECT el.id, el.designation_id 
            FROM employee_list el 
            WHERE el.employee_code = ? AND el.status = 1
        ");
        $stmt->bind_param("s", $employee_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Employee not found or account inactive");
        }

        $employee = $result->fetch_assoc();

        // Check if employee is mentor/project manager (designation_id 49 or 50)
        if (in_array($employee['designation_id'], [49, 50])) {
            $role = $employee['designation_id'] == 49 ? 'Mentor' : 'Project Manager';
            throw new Exception("$role accounts must use their dedicated portal");
        }

        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in database
        $update_stmt = $conn->prepare("
            UPDATE employee_access 
            SET password = ?, force_password_change = 0, password_changed_at = NOW() 
            WHERE employee_id = ?
        ");
        $update_stmt->bind_param("si", $hashed_password, $employee['id']);

        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update password. Please try again.");
        }

        // If no record exists in employee_access, create one
        if ($update_stmt->affected_rows === 0) {
            $insert_stmt = $conn->prepare("
                INSERT INTO employee_access (employee_id, employee_code, password)
                VALUES (?, ?, ?)
            ");
            $insert_stmt->bind_param("iss", $employee['id'], $employee_code, $hashed_password);
            
            if (!$insert_stmt->execute()) {
                throw new Exception("Failed to create access record. Please contact support.");
            }
        }

        $success = "Password updated successfully! You can now login with your new password.";
        $employee_code = ''; // Clear the field after success

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Set CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Forgot Password - Leave Portal</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
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
      text-align: center;
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

    h1 {
      font-size: 2.5rem;
      text-transform: uppercase;
      margin-bottom: 30px;
      color: #ffffff;
      margin-top: 40px;
    }

    h1 span {
      font-weight: normal;
    }

    h1 strong {
      font-weight: 800;
    }

    .password-container {
      position: relative;
      background: linear-gradient(to right, #0b3e4d, #0e6574, #003e4d);
      padding: 40px;
      border-radius: 25px;
      width: 100%;
      max-width: 900px;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.3);
      margin-bottom: 20px;
      overflow: hidden;
    }

    .password-container::before {
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

    .password-container h2 {
      color: white;
      font-size: 1.5rem;
      margin-bottom: 25px;
      position: relative;
      z-index: 2;
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
      background-color: #e84e17;
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
      background-color: rgba(255, 0, 0, 0.1);
      padding: 10px;
      border-radius: 8px;
      border-left: 4px solid #ff5252;
    }

    .success-message {
      color: #c9f7c9;
      margin-top: 15px;
      font-weight: 600;
      font-size: 1.1rem;
      position: relative;
      z-index: 2;
      background-color: rgba(0, 109, 119, 0.2);
      padding: 10px;
      border-radius: 8px;
      border-left: 4px solid #0e6574;
    }

    .back-to-login {
      color: #a0d8f0;
      text-decoration: none;
      font-size: 0.9rem;
      margin-top: 10px;
      display: inline-block;
    }

    .back-to-login:hover {
      text-decoration: underline;
    }

    /* Password strength meter */
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
      background: transparent;
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
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    @media (max-width: 768px) {
      h1 {
        font-size: 2rem;
        margin-top: 60px;
      }

      .logo {
        top: 20px;
        right: 20px;
        width: 80px;
        height: 60px;
      }

      .logo-letter {
        font-size: 2.8rem;
      }

      .password-container {
        padding: 30px;
        max-width: 95%;
      }
    }
  </style>
</head>

<body>
  <div class="logo">
    <div class="logo-letter w">W</div>
    <div class="logo-letter p">P</div>
  </div>

  <h1><span>RESET YOUR </span> <strong>PASSWORD</strong></h1>

  <div class="password-container">
    <h2>Enter your employee code and new password</h2>
    
    <?php if (!empty($error)): ?>
      <div class="error-message">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
      <div class="success-message">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <a href="leave_login.php" class="btn">GO TO LOGIN</a>
    <?php else: ?>
      <form id="passwordResetForm" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="form-group">
          <input type="text" name="employee_code" id="employee_code" 
                 placeholder="2023-0000" 
                 value="<?php echo htmlspecialchars($employee_code, ENT_QUOTES, 'UTF-8'); ?>" 
                 required
                 autocomplete="username" />
        </div>
        
        <div class="form-group">
          <input type="password" name="new_password" id="new_password" 
                 placeholder="New Password" 
                 required
                 autocomplete="new-password"
                 oninput="checkPasswordStrength(this.value)" />
          <div class="password-strength">
            <span id="password-strength-bar"></span>
          </div>
        </div>
        
        <div class="form-group">
          <input type="password" name="confirm_password" id="confirm_password" 
                 placeholder="Confirm New Password" 
                 required
                 autocomplete="new-password" />
        </div>
        
        <button type="submit" class="btn" id="resetBtn">
          <span id="btnText">RESET PASSWORD</span>
          <span id="btnSpinner" class="spinner" style="display: none;"></span>
        </button>
      </form>
      
      <a href="leave_login.php" class="back-to-login">
        <i class="fas fa-arrow-left"></i> Back to login
      </a>
    <?php endif; ?>
  </div>

  <script>
    // Password strength checker
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
    document.getElementById('passwordResetForm').addEventListener('submit', function(e) {
      const password = document.getElementById('new_password').value;
      const confirm = document.getElementById('confirm_password').value;
      
      if (password !== confirm) {
        e.preventDefault();
        alert("Passwords don't match!");
        return;
      }
      
      if (password.length < 8) {
        e.preventDefault();
        alert("Password must be at least 8 characters!");
        return;
      }
      
      // Show loading state
      const btn = document.getElementById("resetBtn");
      const btnText = document.getElementById("btnText");
      const btnSpinner = document.getElementById("btnSpinner");
      
      btn.disabled = true;
      btnText.textContent = "Processing...";
      btnSpinner.style.display = "inline-block";
    });

    // Prevent form resubmission on page refresh
    if (window.history.replaceState) {
      window.history.replaceState(null, null, window.location.href);
    }
  </script>
</body>
</html>
<?php
// Close connection
$conn->close();
?>