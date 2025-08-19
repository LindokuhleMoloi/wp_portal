<?php
// mentor_dashboard.php
session_start();

// Check if mentor is logged in
if (!isset($_SESSION['mentor_logged_in']) || $_SESSION['mentor_logged_in'] !== true) {
    header("Location: mentor_login.php");
    exit();
}

// Get mentor's details from session
$mentor_fullname = $_SESSION['mentor_fullname'] ?? 'Mentor';
$mentor_employee_id = $_SESSION['mentor_employee_id'] ?? 'N/A';
$mentor_employee_code = $_SESSION['mentor_employee_code'] ?? 'N/A';
$mentor_designation = $_SESSION['mentor_designation'] ?? 'Mentor';

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    error_log("Database connection failed for mentor_dashboard: " . $conn->connect_error);
    $conn = null;
}

// Check for success/error messages from other pages (e.g., mentor_apply_leave.php)
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message']); // Clear message after displaying
unset($_SESSION['error_message']); // Clear message after displaying


// --- Fetch Pending Leave Count for Mentees ---
$pending_leaves_count = 0;
if ($conn) {
    $sql_pending_count = "SELECT COUNT(*) as count
                          FROM leave_applications la
                          JOIN employee_list el ON la.employee_id = el.id
                          WHERE el.mentor_id = ? AND la.mentor_status = 0";

    $stmt_pending_count = $conn->prepare($sql_pending_count);
    if ($stmt_pending_count) {
        $stmt_pending_count->bind_param("i", $mentor_employee_id);
        $stmt_pending_count->execute();
        $result_pending_count = $stmt_pending_count->get_result();
        if ($result_pending_count->num_rows > 0) {
            $row = $result_pending_count->fetch_assoc();
            $pending_leaves_count = $row['count'];
        }
        $stmt_pending_count->close();
    }

    // --- Fetch Mentee Count ---
    $mentee_count = 0;
    $sql_mentees = "SELECT COUNT(*) as count FROM employee_list WHERE mentor_id = ?";
    $stmt_mentees = $conn->prepare($sql_mentees);
    if ($stmt_mentees) {
        $stmt_mentees->bind_param("i", $mentor_employee_id);
        $stmt_mentees->execute();
        $result_mentees = $stmt_mentees->get_result();
        if ($result_mentees->num_rows > 0) {
            $row = $result_mentees->fetch_assoc();
            $mentee_count = $row['count'];
        }
        $stmt_mentees->close();
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mentor Dashboard</title>
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
            flex-wrap: wrap; /* Added for responsiveness */
            gap: 15px; /* Added for responsiveness */
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
            flex-grow: 1; /* Allows it to take available space */
            text-align: center; /* Center horizontally */
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
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            text-decoration: none; /* Ensure link cards don't have underlines */
            color: inherit; /* Ensure text color is inherited */
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
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
            flex-grow: 1;
            align-items: flex-start;
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

        /* Pending Leaves Button */
        .pending-leaves-btn {
            background-color: var(--warning-color);
            color: var(--dark-color);
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
            width: 100%;
            justify-content: center;
        }

        .pending-leaves-btn:hover {
            background-color: #ffb007;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
            min-width: 300px;
        }

        .message.success {
            background-color: var(--success-color);
            color: white;
        }

        .message.error {
            background-color: var(--danger-color);
            color: white;
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
            <i class="fas fa-chalkboard-teacher"></i> MENTOR DASHBOARD
        </h1>
        <div class="welcome-message">
            Welcome, <span><?php echo htmlspecialchars($mentor_fullname); ?></span>
        </div>
        <div class="header-buttons">
            <a href="mentor_profile.php" class="header-btn">
                <i class="fas fa-user-cog"></i> Profile
            </a>
            <a href="logout.php" class="header-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="message success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="message error">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-grid">
        <a href="mentee_list.php" class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-users-class"></i> My Mentees
                </h2>
                <span class="card-badge"><?php echo $mentee_count; ?> mentees</span>
            </div>
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="metric-value"><?php echo $mentee_count; ?></div>
                <div class="metric-label">Total Mentees</div>
            </div>
        </a>

        <a href="mentor_pending_leave.php" class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-hourglass-half"></i> Pending Leave
                </h2>
                <span class="card-badge"><?php echo $pending_leaves_count; ?> pending</span>
            </div>
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <div class="metric-value"><?php echo $pending_leaves_count; ?></div>
                <div class="metric-label">Applications to Review</div>
                <?php if ($pending_leaves_count > 0): ?>
                    <a href="mentor_pending_leave.php" class="pending-leaves-btn">
                        <i class="fas fa-eye"></i> View Applications
                    </a>
                <?php endif; ?>
            </div>
        </a>

        <a href="mentor_apply_leave.php" class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-calendar-plus"></i> Apply for Leave
                </h2>
                <span class="card-badge">Your Leave</span>
            </div>
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-plane-departure"></i>
                </div>
                <div class="metric-value">Go</div>
                <div class="metric-label">Apply for Your Leave</div>
            </div>
        </a>

        <a href="mentor_leave_history.php" class="dashboard-card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-history"></i> My Leave History
                </h2>
                <span class="card-badge">View Status</span>
            </div>
            <div class="metric-card">
                <div class="metric-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="metric-value"><i class="fas fa-arrow-right"></i></div>
                <div class="metric-label">Review Past Applications</div>
            </div>
        </a>

        <div class="dashboard-card" style="grid-column: 1 / -1;">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-bolt"></i> Quick Actions
                </h2>
            </div>
            <div class="quick-actions">
                <a href="manage_mentees.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    <span>Manage Mentees</span>
                </a>
                <a href="mentee_performance.php" class="action-btn">
                    <i class="fas fa-chart-line"></i>
                    <span>Mentee Performance</span>
                </a>
                <a href="leave_calendar.php" class="action-btn">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Leave Calendar</span>
                </a>
                <a href="send_announcement.php" class="action-btn">
                    <i class="fas fa-bullhorn"></i>
                    <span>Send Announcement</span>
                </a>
                <a href="training_resources.php" class="action-btn">
                    <i class="fas fa-book-open"></i>
                    <span>Training Resources</span>
                </a>
                <a href="reports.php" class="action-btn">
                    <i class="fas fa-file-alt"></i>
                    <span>Reports</span>
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>