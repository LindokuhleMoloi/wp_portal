<?php
// view_employee.php
session_start();

// Redirect if not logged in as project manager
if (!isset($_SESSION['pm_logged_in'])) {
    header("Location: project_manager_login.php");
    exit();
}

// Check if employee ID is provided
if (!isset($_GET['id'])) {
    header("Location: team_list.php");
    exit();
}

$employee_id = $_GET['id'];

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

// --- Fetch Employee Details ---
$employee = [];
$sql_employee = "SELECT 
                    el.*, 
                    dl.name AS designation_name,
                    dpt.name AS department_name,
                    empl.name AS employer_name
                 FROM employee_list el
                 LEFT JOIN designation_list dl ON el.designation_id = dl.id
                 LEFT JOIN department_list dpt ON el.department_id = dpt.id
                 LEFT JOIN employer_list empl ON el.employer_id = empl.id
                 WHERE el.id = ? AND el.project_manager_id = ?";
$stmt_employee = $conn->prepare($sql_employee);
if ($stmt_employee) {
    $stmt_employee->bind_param("ii", $employee_id, $pm_id);
    $stmt_employee->execute();
    $result_employee = $stmt_employee->get_result();
    if ($result_employee->num_rows > 0) {
        $employee = $result_employee->fetch_assoc();
    } else {
        // Employee not found or doesn't belong to this PM
        header("Location: team_list.php");
        exit();
    }
    $stmt_employee->close();
}

// --- Fetch Employee Leave History ---
$leave_history = [];
$sql_leaves = "SELECT 
                  la.*, 
                  lt.name AS leave_type,
                  DATEDIFF(la.end_date, la.start_date) + 1 AS days_taken
               FROM leave_applications la
               JOIN leave_types lt ON la.leave_type_id = lt.id
               WHERE la.employee_id = ?
               ORDER BY la.start_date DESC";
$stmt_leaves = $conn->prepare($sql_leaves);
if ($stmt_leaves) {
    $stmt_leaves->bind_param("i", $employee_id);
    $stmt_leaves->execute();
    $result_leaves = $stmt_leaves->get_result();
    if ($result_leaves->num_rows > 0) {
        while($row = $result_leaves->fetch_assoc()) {
            $leave_history[] = $row;
        }
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
  <title>Employee Details</title>
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

    /* Main Content */
    .content-container {
      background-color: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      padding: 25px;
      margin-bottom: 30px;
    }

    /* Employee Profile Header */
    .profile-header {
      display: flex;
      align-items: center;
      gap: 25px;
      margin-bottom: 30px;
      padding-bottom: 25px;
      border-bottom: 2px solid var(--light-color);
    }

    .profile-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background-color: var(--primary-color);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      font-weight: 700;
      flex-shrink: 0;
    }

    .profile-info h2 {
      font-size: 1.8rem;
      color: var(--dark-color);
      margin-bottom: 5px;
    }

    .profile-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 15px;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.9rem;
      color: #555;
    }

    .meta-item i {
      color: var(--accent-color);
    }

    .profile-status {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 10px;
    }

    .status-active {
      background-color: rgba(40, 167, 69, 0.2);
      color: var(--success-color);
    }

    .status-inactive {
      background-color: rgba(220, 53, 69, 0.2);
      color: var(--danger-color);
    }

    /* Employee Details Sections */
    .details-section {
      margin-bottom: 30px;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--light-color);
    }

    .section-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--dark-color);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .section-title i {
      color: var(--primary-color);
    }

    /* Details Grid */
    .details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }

    .detail-card {
      background-color: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .detail-label {
      font-size: 0.8rem;
      color: #666;
      margin-bottom: 5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .detail-value {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--dark-color);
    }

    /* Leave History Table */
    .leave-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    .leave-table th {
      background-color: var(--light-color);
      color: var(--dark-color);
      padding: 12px;
      text-align: left;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .leave-table td {
      padding: 12px;
      border-bottom: 1px solid #eee;
      font-size: 0.9rem;
    }

    .leave-table tr:last-child td {
      border-bottom: none;
    }

    .leave-status {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .status-pending {
      background-color: rgba(255, 193, 7, 0.2);
      color: var(--warning-color);
    }

    .status-approved {
      background-color: rgba(40, 167, 69, 0.2);
      color: var(--success-color);
    }

    .status-rejected {
      background-color: rgba(220, 53, 69, 0.2);
      color: var(--danger-color);
    }

    .no-leaves {
      text-align: center;
      padding: 30px;
      color: #666;
      font-style: italic;
    }

    /* Back Button */
    .back-btn {
      display: inline-block;
      margin-top: 20px;
      background-color: var(--light-color);
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

    .back-btn:hover {
      background-color: #e9ecef;
      transform: translateY(-2px);
    }

    /* Responsive Adjustments */
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
      
      .profile-header {
        flex-direction: column;
        text-align: center;
      }
      
      .profile-meta {
        justify-content: center;
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
      
      .leave-table {
        display: block;
        overflow-x: auto;
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
      <i class="fas fa-user-tie"></i> EMPLOYEE DETAILS
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

  <div class="content-container">
    <!-- Employee Profile Header -->
    <div class="profile-header">
      <div class="profile-avatar">
        <?php echo strtoupper(substr($employee['fullname'], 0, 1)); ?>
      </div>
      <div class="profile-info">
        <h2>
          <?php echo htmlspecialchars($employee['fullname']); ?>
          <span class="profile-status <?php echo ($employee['status'] == 1) ? 'status-active' : 'status-inactive'; ?>">
            <?php echo ($employee['status'] == 1) ? 'Active' : 'Inactive'; ?>
          </span>
        </h2>
        <p style="color: var(--primary-color); font-weight: 600;">
          <?php echo htmlspecialchars($employee['designation_name']); ?>
          <?php if (!empty($employee['department_name'])): ?>
            • <?php echo htmlspecialchars($employee['department_name']); ?>
          <?php endif; ?>
        </p>
        <div class="profile-meta">
          <div class="meta-item">
            <i class="fas fa-id-card"></i>
            <span><?php echo htmlspecialchars($employee['employee_code']); ?></span>
          </div>
          <div class="meta-item">
            <i class="fas fa-building"></i>
            <span><?php echo htmlspecialchars($employee['employer_name']); ?></span>
          </div>
          <div class="meta-item">
            <i class="fas fa-phone"></i>
            <span><?php echo htmlspecialchars($employee['contact']); ?></span>
          </div>
          <div class="meta-item">
            <i class="fas fa-envelope"></i>
            <span><?php echo htmlspecialchars($employee['email']); ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Employment Details Section -->
    <div class="details-section">
      <div class="section-header">
        <h3 class="section-title">
          <i class="fas fa-briefcase"></i> Employment Details
        </h3>
      </div>
      <div class="details-grid">
        <div class="detail-card">
          <div class="detail-label">Contract Start Date</div>
          <div class="detail-value">
            <?php echo !empty($employee['contract_start_date']) ? date('M j, Y', strtotime($employee['contract_start_date'])) : 'Not specified'; ?>
          </div>
        </div>
        <div class="detail-card">
          <div class="detail-label">Contract End Date</div>
          <div class="detail-value">
            <?php echo !empty($employee['contract_end_date']) ? date('M j, Y', strtotime($employee['contract_end_date'])) : 'Ongoing'; ?>
          </div>
        </div>
        <div class="detail-card">
          <div class="detail-label">Date of Birth</div>
          <div class="detail-value">
            <?php echo !empty($employee['date_of_birth']) ? date('M j, Y', strtotime($employee['date_of_birth'])) : 'Not specified'; ?>
          </div>
        </div>
        <div class="detail-card">
          <div class="detail-label">Gender</div>
          <div class="detail-value">
            <?php echo !empty($employee['gender']) ? htmlspecialchars($employee['gender']) : 'Not specified'; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact Information Section -->
    <div class="details-section">
      <div class="section-header">
        <h3 class="section-title">
          <i class="fas fa-address-book"></i> Contact Information
        </h3>
      </div>
      <div class="details-grid">
        <div class="detail-card">
          <div class="detail-label">Email Address</div>
          <div class="detail-value">
            <a href="mailto:<?php echo htmlspecialchars($employee['email']); ?>">
              <?php echo htmlspecialchars($employee['email']); ?>
            </a>
          </div>
        </div>
        <div class="detail-card">
          <div class="detail-label">Phone Number</div>
          <div class="detail-value">
            <?php echo !empty($employee['contact']) ? htmlspecialchars($employee['contact']) : 'Not specified'; ?>
          </div>
        </div>
        <div class="detail-card">
          <div class="detail-label">Address</div>
          <div class="detail-value">
            <?php echo !empty($employee['address']) ? htmlspecialchars($employee['address']) : 'Not specified'; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Leave History Section -->
    <div class="details-section">
      <div class="section-header">
        <h3 class="section-title">
          <i class="fas fa-calendar-alt"></i> Leave History
        </h3>
      </div>
      
      <?php if (!empty($leave_history)): ?>
        <table class="leave-table">
          <thead>
            <tr>
              <th>Leave Type</th>
              <th>Dates</th>
              <th>Days</th>
              <th>Reason</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($leave_history as $leave): ?>
              <tr>
                <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                <td>
                  <?php echo date('M j', strtotime($leave['start_date'])); ?> - 
                  <?php echo date('M j, Y', strtotime($leave['end_date'])); ?>
                </td>
                <td><?php echo $leave['days_taken']; ?></td>
                <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                <td>
                  <span class="leave-status status-<?php echo strtolower($leave['status']); ?>">
                    <?php echo htmlspecialchars($leave['status']); ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="no-leaves">
          <i class="fas fa-calendar-check" style="font-size: 2rem; color: var(--accent-color); margin-bottom: 10px;"></i>
          <p>No leave history found for this employee</p>
        </div>
      <?php endif; ?>
    </div>

    <a href="team_list.php" class="back-btn">
      <i class="fas fa-arrow-left"></i> Back to Team List
    </a>
  </div>

  <script>
    // Simple animation on page load
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.detail-card');
      cards.forEach((card, index) => {
        setTimeout(() => {
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, 100 * index);
      });
      
      const leaveRows = document.querySelectorAll('.leave-table tbody tr');
      leaveRows.forEach((row, index) => {
        setTimeout(() => {
          row.style.opacity = '1';
          row.style.transform = 'translateY(0)';
        }, 100 * (index + cards.length));
      });
    });
  </script>
</body>
</html>