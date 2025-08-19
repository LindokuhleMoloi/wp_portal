<?php
session_start();

// Redirect if not logged in as project manager
if (!isset($_SESSION['pm_logged_in'])) {
    header("Location: project_manager_login.php");
    exit();
}

// Check if password change is required
if (isset($_SESSION['pm_force_password_change']) && $_SESSION['pm_force_password_change']) {
    header("Location: pm_change_password.php");
    exit();
}

// Check for password change success message
$success_message = '';
if (isset($_SESSION['pm_password_change_success'])) {
    $success_message = $_SESSION['pm_password_change_success'];
    unset($_SESSION['pm_password_change_success']);
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

// Get current PM details
$pm_id = $_SESSION['pm_employee_id'];
$pm_fullname = $_SESSION['pm_fullname'];

// --- Fetch Team Member Count ---
$team_count = 0;
$sql_team = "SELECT COUNT(*) as count FROM employee_list WHERE project_manager_id = ?";
$stmt_team = $conn->prepare($sql_team);
if ($stmt_team) {
    $stmt_team->bind_param("i", $pm_id);
    $stmt_team->execute();
    $result_team = $stmt_team->get_result();
    if ($result_team->num_rows > 0) {
        $row = $result_team->fetch_assoc();
        $team_count = $row['count'];
    }
    $stmt_team->close();
}

// --- Fetch Pending Leave Count ---
$pending_count = 0;
$sql_leaves = "SELECT COUNT(*) as count FROM leave_applications la
               JOIN employee_list el ON la.employee_id = el.id
               WHERE el.project_manager_id = ? AND la.status = 'Pending'";
$stmt_leaves = $conn->prepare($sql_leaves);
if ($stmt_leaves) {
    $stmt_leaves->bind_param("i", $pm_id);
    $stmt_leaves->execute();
    $result_leaves = $stmt_leaves->get_result();
    if ($result_leaves->num_rows > 0) {
        $row = $result_leaves->fetch_assoc();
        $pending_count = $row['count'];
    }
    $stmt_leaves->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Project Manager Dashboard</title>
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
      --warning-color: #ffc107;
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
      padding: 15px;
      position: relative;
    }

    .logo {
      position: absolute;
      top: 20px;
      right: 20px;
      width: 80px;
      height: 60px;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 6px;
    }

    .logo-letter {
      font-family: 'Montserrat', sans-serif;
      font-weight: 800;
      font-size: 2.8rem;
      color: white;
      transition: transform 0.3s ease;
    }

    .logo-letter.w {
      transform: translateY(-2px);
    }

    .logo-letter.p {
      transform: translateY(2px);
    }

    .logo:hover .logo-letter.w {
      transform: translateY(-4px);
    }

    .logo:hover .logo-letter.p {
      transform: translateY(4px);
    }

    .header-strip {
      background: linear-gradient(to right, var(--dark-color), var(--primary-color));
      padding: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: -15px -15px 30px -15px;
      width: calc(100% + 30px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .header-title {
      color: white;
      font-size: 2.2rem;
      font-weight: 700;
      text-transform: uppercase;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .header-title i {
      font-size: 1.8rem;
    }

    .welcome-message {
      color: white;
      font-size: 1.5rem;
      font-weight: 600;
      text-align: right;
    }

    .welcome-message span {
      color: var(--accent-color);
    }

    .header-buttons {
      display: flex;
      gap: 15px;
    }

    .header-btn {
      background-color: white;
      color: var(--dark-color);
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .header-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .header-btn i {
      font-size: 1rem;
    }

    /* Dashboard Grid Layout */
    .dashboard-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
      gap: 25px;
      width: 100%;
      max-width: 1800px;
      margin: 0 auto;
    }

    /* Dashboard Cards */
    .dashboard-card {
      background-color: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      padding: 25px;
      transition: transform 0.3s ease;
      cursor: pointer;
    }

    .dashboard-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--light-color);
    }

    .card-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--dark-color);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .card-title i {
      color: var(--primary-color);
    }

    .card-badge {
      background-color: var(--secondary-color);
      color: white;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.9rem;
      font-weight: 600;
    }

    /* Metric Cards */
    .metric-card {
      text-align: center;
      padding: 20px 0;
    }

    .metric-value {
      font-size: 3rem;
      font-weight: 700;
      color: var(--primary-color);
      margin: 15px 0;
    }

    .metric-label {
      font-size: 1.1rem;
      color: var(--dark-color);
      font-weight: 600;
    }

    .metric-icon {
      font-size: 2.5rem;
      color: var(--accent-color);
      margin-bottom: 10px;
    }

    /* Quick Actions */
    .quick-actions {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 15px;
      margin-top: 15px;
    }

    .action-btn {
      background-color: var(--light-color);
      border: none;
      border-radius: 8px;
      padding: 15px;
      text-align: center;
      transition: all 0.3s ease;
      text-decoration: none;
      color: var(--dark-color);
    }

    .action-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      background-color: var(--accent-color);
      color: white;
    }

    .action-btn i {
      font-size: 1.5rem;
      margin-bottom: 10px;
      display: block;
    }

    .action-btn span {
      font-weight: 600;
      font-size: 0.9rem;
    }

    /* No Data Message */
    .no-data {
      text-align: center;
      padding: 30px;
      color: #666;
      font-style: italic;
    }

    /* Responsive Adjustments */
    @media (max-width: 1200px) {
      .dashboard-grid {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 768px) {
      .header-strip {
        flex-direction: column;
        gap: 15px;
        padding: 20px;
        text-align: center;
      }
      
      .welcome-message {
        text-align: center;
      }
      
      .header-buttons {
        justify-content: center;
      }
      
      .dashboard-grid {
        grid-template-columns: 1fr;
      }
      
      .logo {
        top: 15px;
        right: 15px;
        width: 60px;
        height: 50px;
      }
      
      .logo-letter {
        font-size: 2.2rem;
      }
      
      .quick-actions {
        grid-template-columns: 1fr 1fr;
      }
    }

    @media (max-width: 480px) {
      .quick-actions {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="logo">
    <div class="logo-letter w">W</div>
    <div class="logo-letter p">P</div>
  </div>

  <div class="header-strip">
    <h1 class="header-title">
      <i class="fas fa-tasks"></i> PROJECT MANAGER DASHBOARD
    </h1>
    <div class="welcome-message">
      Welcome, <span><?php echo htmlspecialchars($pm_fullname); ?></span>
    </div>
    <div class="header-buttons">
      <a href="project_manager_profile.php" class="header-btn">
        <i class="fas fa-user-cog"></i> Profile
      </a>
      <a href="pm_logout.php" class="header-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>

  <?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
      <?php echo htmlspecialchars($success_message); ?>
    </div>
  <?php endif; ?>

  <div class="dashboard-grid">
    <!-- Team Members Card -->
    <a href="team_list.php" class="dashboard-card">
      <div class="card-header">
        <h2 class="card-title">
          <i class="fas fa-users"></i> My Team
        </h2>
        <span class="card-badge"><?php echo $team_count; ?> members</span>
      </div>
      <div class="metric-card">
        <div class="metric-icon">
          <i class="fas fa-users"></i>
        </div>
        <div class="metric-value"><?php echo $team_count; ?></div>
        <div class="metric-label">Team Members</div>
      </div>
    </a>

    <!-- Pending Approvals Card -->
    <a href="pending_approvals.php" class="dashboard-card">
      <div class="card-header">
        <h2 class="card-title">
          <i class="fas fa-calendar-check"></i> Pending Approvals
        </h2>
        <span class="card-badge"><?php echo $pending_count; ?> requests</span>
      </div>
      <div class="metric-card">
        <div class="metric-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="metric-value"><?php echo $pending_count; ?></div>
        <div class="metric-label">Pending Requests</div>
      </div>
    </a>

    <!-- Quick Actions Card -->
    <div class="dashboard-card">
      <div class="card-header">
        <h2 class="card-title">
          <i class="fas fa-bolt"></i> Quick Actions
        </h2>
      </div>
      <div class="quick-actions">
        <a href="new_project.php" class="action-btn">
          <i class="fas fa-plus"></i>
          <span>New Project</span>
        </a>
        <a href="team_report.php" class="action-btn">
          <i class="fas fa-chart-bar"></i>
          <span>Team Report</span>
        </a>
        <a href="leave_calendar.php" class="action-btn">
          <i class="fas fa-calendar-alt"></i>
          <span>Leave Calendar</span>
        </a>
        <a href="message_team.php" class="action-btn">
          <i class="fas fa-envelope"></i>
          <span>Message Team</span>
        </a>
      </div>
    </div>
  </div>

  <script>
    // Simple animation on page load
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.dashboard-card');
      cards.forEach((card, index) => {
        setTimeout(() => {
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, 100 * index);
      });
    });
  </script>
</body>
</html>