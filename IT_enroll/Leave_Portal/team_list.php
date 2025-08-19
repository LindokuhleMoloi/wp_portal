<?php
// team_list.php
session_start();

// Redirect if not logged in as project manager
if (!isset($_SESSION['pm_logged_in'])) {
    header("Location: project_manager_login.php");
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

// Get current PM details
$pm_id = $_SESSION['pm_employee_id'];
$pm_fullname = $_SESSION['pm_fullname'];

// --- Fetch Team Members ---
$team_members = [];
$sql_team = "SELECT 
                el.id, el.employee_code, el.fullname, 
                el.email, el.contact, dl.name AS designation,
                COUNT(la.id) AS pending_leaves
             FROM employee_list el
             LEFT JOIN designation_list dl ON el.designation_id = dl.id
             LEFT JOIN leave_applications la ON el.id = la.employee_id AND la.status = 'Pending'
             WHERE el.project_manager_id = ?
             GROUP BY el.id
             ORDER BY el.fullname ASC";
$stmt_team = $conn->prepare($sql_team);
if ($stmt_team) {
    $stmt_team->bind_param("i", $pm_id);
    $stmt_team->execute();
    $result_team = $stmt_team->get_result();
    if ($result_team->num_rows > 0) {
        while($row = $result_team->fetch_assoc()) {
            $team_members[] = $row;
        }
    }
    $stmt_team->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Team List</title>
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

    .content-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      padding-bottom: 15px;
      border-bottom: 2px solid var(--light-color);
    }

    .content-title {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--dark-color);
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .content-title i {
      color: var(--primary-color);
    }

    .team-count {
      background-color: var(--secondary-color);
      color: white;
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 1rem;
      font-weight: 600;
    }

    /* Team Table */
    .team-table {
      width: 100%;
      border-collapse: collapse;
    }

    .team-table th {
      background-color: var(--light-color);
      color: var(--dark-color);
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 0.95rem;
    }

    .team-table td {
      padding: 15px;
      border-bottom: 1px solid #eee;
      font-size: 0.9rem;
    }

    .team-table tr:last-child td {
      border-bottom: none;
    }

    .team-table tr:hover td {
      background-color: rgba(15, 101, 116, 0.05);
    }

    .member-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .member-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: var(--primary-color);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: 1.1rem;
    }

    .member-details {
      line-height: 1.4;
    }

    .member-name {
      font-weight: 600;
      color: var(--dark-color);
    }

    .member-code {
      font-size: 0.8rem;
      color: #666;
    }

    .member-contact {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .member-email {
      color: var(--primary-color);
      text-decoration: none;
    }

    .member-email:hover {
      text-decoration: underline;
    }

    .pending-leaves {
      background-color: var(--warning-color);
      color: #333;
      padding: 3px 10px;
      border-radius: 10px;
      font-size: 0.8rem;
      font-weight: 700;
      display: inline-block;
    }

    .view-btn {
      background-color: var(--accent-color);
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      text-decoration: none;
      display: inline-block;
    }

    .view-btn:hover {
      background-color: #1e96c8;
      transform: translateY(-2px);
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

    /* No Team Members */
    .no-team {
      text-align: center;
      padding: 50px 20px;
      color: #666;
    }

    .no-team i {
      font-size: 3rem;
      color: var(--accent-color);
      margin-bottom: 20px;
    }

    .no-team p {
      font-size: 1.2rem;
      margin-bottom: 20px;
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
      
      .content-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
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
      
      .team-table {
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
      <i class="fas fa-users"></i> MY TEAM
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
    <div class="content-header">
      <h2 class="content-title">
        <i class="fas fa-users-cog"></i> Team Members
      </h2>
      <span class="team-count"><?php echo count($team_members); ?> members</span>
    </div>

    <?php if (!empty($team_members)): ?>
      <table class="team-table">
        <thead>
          <tr>
            <th>Member</th>
            <th>Designation</th>
            <th>Contact</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($team_members as $member): ?>
            <tr>
              <td>
                <div class="member-info">
                  <div class="member-avatar">
                    <?php echo strtoupper(substr($member['fullname'], 0, 1)); ?>
                  </div>
                  <div class="member-details">
                    <div class="member-name"><?php echo htmlspecialchars($member['fullname']); ?></div>
                    <div class="member-code"><?php echo htmlspecialchars($member['employee_code']); ?></div>
                  </div>
                </div>
              </td>
              <td><?php echo htmlspecialchars($member['designation']); ?></td>
              <td class="member-contact">
                <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>" class="member-email">
                  <?php echo htmlspecialchars($member['email']); ?>
                </a>
                <span><?php echo htmlspecialchars($member['contact']); ?></span>
              </td>
              <td>
                <?php if ($member['pending_leaves'] > 0): ?>
                  <span class="pending-leaves">
                    <?php echo $member['pending_leaves']; ?> pending leave(s)
                  </span>
                <?php else: ?>
                  <span style="color: var(--success-color);">Active</span>
                <?php endif; ?>
              </td>
              <td>
                <a href="view_employee.php?id=<?php echo $member['id']; ?>" class="view-btn">
                  <i class="fas fa-eye"></i> View
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php else: ?>
      <div class="no-team">
        <i class="fas fa-user-slash"></i>
        <p>No team members assigned to you</p>
        <a href="project_manager_dashboard.php" class="back-btn">
          <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
      </div>
    <?php endif; ?>

    <a href="project_manager_dashboard.php" class="back-btn">
      <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
  </div>

  <script>
    // Simple animation on page load
    document.addEventListener('DOMContentLoaded', function() {
      const rows = document.querySelectorAll('.team-table tbody tr');
      rows.forEach((row, index) => {
        setTimeout(() => {
          row.style.opacity = '1';
          row.style.transform = 'translateY(0)';
        }, 100 * index);
      });
    });
  </script>
</body>
</html>