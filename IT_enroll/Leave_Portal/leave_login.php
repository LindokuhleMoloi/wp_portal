<?php
// leave_login.php

// Require the config file to handle session, database connection, etc.
require_once(__DIR__ . '/../../config.php');

// Security headers (already included in the provided code)
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");

// Redirect if already logged in. This check should come after session_start(),
// which is now handled by config.php.
if (isset($_SESSION['employee_id'])) {
    header("Location: employee_dashboard.php");
    exit();
}

// The database connection is now handled by config.php
// The $conn object is available globally.

// Initialize variables
$employee_code = '';
$error = '';
$success = '';

// Check for password change success message
if (isset($_SESSION['password_change_success'])) {
    $success = $_SESSION['password_change_success'];
    unset($_SESSION['password_change_success']);
}

// Sanitize input
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8');
}

if (isset($_GET['employee_code'])) {
    $employee_code = htmlspecialchars($_GET['employee_code'], ENT_QUOTES, 'UTF-8');
}

// Set CSRF token if not already set (this is a good practice)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
  <title>Leave Application Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* ... (CSS code is unchanged) ... */
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

    .success-message {
      color: #c9f7c9;
      margin: 15px 0;
      font-weight: 600;
      font-size: 1.1rem;
      position: relative;
      z-index: 2;
      background-color: rgba(0, 109, 119, 0.3);
      padding: 10px;
      border-radius: 8px;
      border-left: 4px solid #0e6574;
      width: 100%;
      max-width: 900px;
    }

    .forgot-password {
      color: #a0d8f0;
      text-decoration: none;
      font-size: 0.9rem;
      margin-top: 10px;
      display: inline-block;
    }
    .forgot-password:hover {
      text-decoration: underline;
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

    h1 {
      font-size: 3rem;
      text-transform: uppercase;
      margin-bottom: 40px;
      color: #ffffff;
      margin-top: 40px;
    }

    h1 span {
      font-weight: normal;
    }

    h1 strong {
      font-weight: 800;
    }

    .login-container {
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

    .login-notice {
      color: #a0d8f0;
      margin: 15px 0;
      font-size: 0.9rem;
      background-color: rgba(0, 109, 119, 0.2);
      padding: 10px;
      border-radius: 8px;
      border-left: 4px solid #0e6574;
    }

    .dashboard-links {
      display: flex;
      justify-content: space-between;
      width: 100%;
      max-width: 900px;
      margin-top: 25px;
      gap: 20px;
    }

    .dashboard-btn {
      padding: 25px 30px;
      border-radius: 18px;
      color: white;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.3s ease;
      text-align: center;
      flex: 1;
      font-size: 1.2rem;
      min-height: 100px;
      display: flex;
      align-items: center;
      justify-content: center;
      line-height: 1.4;
    }

    .dashboard-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .mentor-btn {
      background-color: #2fa8e0;
    }

    .project-btn {
      background-color: #303086;
    }

    @media (max-width: 768px) {
      h1 {
        font-size: 2.4rem;
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

      .login-container, .btn, .dashboard-links {
        max-width: 95%;
      }

      .dashboard-links {
        flex-direction: column;
        gap: 15px;
      }

      .dashboard-btn {
        padding: 20px;
        min-height: 70px;
      }
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
  </style>
</head>

<body>
  <div class="logo">
    <div class="logo-letter w">W</div>
    <div class="logo-letter p">P</div>
  </div>

  <h1><span>EMPLOYEE </span> <strong>LEAVE PORTAL</strong></h1>

  <?php if (!empty($success)): ?>
    <div class="success-message">
      <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
    </div>
  <?php endif; ?>

  <div class="login-container">
    <h2>Enter your employee code and password</h2>
    
    <form id="leaveLoginForm" action="process_leave_login.php" method="POST">
      <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
      
      <div class="form-group">
        <input type="text" name="employee_code" id="employee_code" 
               placeholder="2023-0000" 
               value="<?php echo $employee_code; ?>" 
               required
               autocomplete="username" />
      </div>
      
      <div class="form-group">
        <input type="password" name="password" id="password" 
               placeholder="Password" 
               required
               autocomplete="current-password" />
        <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
      </div>
      
      <?php if (!empty($error)): ?>
        <div class="error-message">
          <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
      <?php endif; ?>
      
      <div class="login-notice">
        <i class="fas fa-info-circle"></i> Mentors and Project Managers should use their dedicated login portals below.
      </div>
    </form>
  </div>

  <button type="submit" form="leaveLoginForm" class="btn" id="loginBtn">
    <span id="btnText">LOGIN</span>
    <span id="btnSpinner" class="spinner" style="display: none;"></span>
  </button>

  <div class="dashboard-links">
    <a href="mentor_login.php" class="dashboard-btn mentor-btn">
      <i class="fas fa-user-tie"></i> Mentor/Manager Dashboard
    </a>
    <a href="http://localhost/employee_gatepass/IT_enroll/Leave_Portal/project_manager_login.php" class="dashboard-btn project-btn">
      <i class="fas fa-tasks"></i> Project Manager Dashboard
    </a>
  </div>

  <script>
    document.getElementById('leaveLoginForm').addEventListener('submit', function(e) {
      const code = document.getElementById("employee_code").value.trim();
      const password = document.getElementById("password").value.trim();
      
      if (!code || !password) {
        e.preventDefault();
        alert("Please enter both employee code and password.");
        return;
      }

      // Show loading state
      const btn = document.getElementById("loginBtn");
      const btnText = document.getElementById("btnText");
      const btnSpinner = document.getElementById("btnSpinner");
      
      btn.disabled = true;
      btnText.textContent = "Authenticating...";
      btnSpinner.style.display = "inline-block";
    });

    // Disable browser autofill for password field
    document.getElementById('password').addEventListener('input', function() {
      if (this.value.match(/^\s*$/)) {
        this.value = '';
      }
    });
  </script>
</body>
</html>
<?php
// The connection is now closed by the config file.
// $conn->close();
?>