<?php
// mentor_leave_history.php
session_start();

// Check if mentor is logged in
if (!isset($_SESSION['mentor_logged_in']) || $_SESSION['mentor_logged_in'] !== true) {
    header("Location: mentor_login.php");
    exit();
}

// Get mentor's details from session
$mentor_employee_id = $_SESSION['mentor_employee_id'] ?? null;
$mentor_fullname = $_SESSION['mentor_fullname'] ?? 'Mentor';

if (!$mentor_employee_id) {
    error_log("mentor_leave_history.php: Mentor Employee ID not found in session.");
    header("Location: logout.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "tarryn_Lindokuhle";
$password = "L1nd0kuhle";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Database connection failed for mentor_leave_history: " . $conn->connect_error);
    $_SESSION['error_message'] = "Database connection failed. Please try again later.";
    header("Location: mentor_dashboard.php");
    exit();
}

// Function to get status text
function getStatusText($status_code) {
    switch ($status_code) {
        case 0: return '<span class="status-pending"><i class="fas fa-hourglass-half"></i> Pending</span>';
        case 1: return '<span class="status-approved"><i class="fas fa-check-circle"></i> Approved</span>';
        case 2: return '<span class="status-rejected"><i class="fas fa-times-circle"></i> Rejected</span>';
        default: return 'Unknown';
    }
}

// Fetch mentor's leave applications
$leave_applications = [];
$sql_applications = "SELECT
                        la.id,
                        lt.name AS leave_type_name,
                        la.start_date,
                        la.end_date,
                        la.number_of_days,
                        la.reason,
                        la.status AS overall_status,
                        la.date_applied,
                        la.mentor_status,
                        la.pm_status
                     FROM
                        leave_applications la
                     JOIN
                        leave_types lt ON la.leave_type_id = lt.id
                     WHERE
                        la.employee_id = ?
                     ORDER BY
                        la.date_applied DESC";

$stmt_applications = $conn->prepare($sql_applications);
if ($stmt_applications) {
    $stmt_applications->bind_param("i", $mentor_employee_id);
    $stmt_applications->execute();
    $result_applications = $stmt_applications->get_result();
    if ($result_applications->num_rows > 0) {
        while ($row = $result_applications->fetch_assoc()) {
            $leave_applications[] = $row;
        }
    }
    $stmt_applications->close();
} else {
    error_log("mentor_leave_history.php: Failed to prepare statement: " . $conn->error);
    $_SESSION['error_message'] = "Could not fetch leave history. Please try again.";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Leave History - Mentor</title>
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
            --info-color: #17a2b8;
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
            flex-wrap: wrap;
            gap: 15px;
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
            flex-grow: 1;
            text-align: center;
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

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 1200px;
            margin: 30px auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
            overflow-x: auto;
        }

        .table-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-color);
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--light-color);
            padding-bottom: 15px;
        }

        .message {
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: 600;
        }

        .message.success {
            background-color: var(--success-color);
            color: white;
        }

        .message.error {
            background-color: var(--danger-color);
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
        }

        table thead th {
            background-color: var(--primary-color);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
            white-space: nowrap;
        }

        table tbody tr {
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }

        table tbody tr:last-child {
            border-bottom: none;
        }

        table tbody tr:hover {
            background-color: var(--light-color);
        }

        table tbody td {
            padding: 12px 15px;
            vertical-align: top;
            font-size: 0.9rem;
            color: var(--dark-color);
        }

        .status-pending { color: var(--warning-color); font-weight: 600; }
        .status-approved { color: var(--success-color); font-weight: 600; }
        .status-rejected { color: var(--danger-color); font-weight: 600; }
        .status-pending i, .status-approved i, .status-rejected i { margin-right: 5px; }

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
            .logo {
                top: 15px;
                right: 15px;
                width: 60px;
                height: 50px;
            }
            .logo-letter {
                font-size: 2.2rem;
            }
            .container {
                margin: 20px auto;
                padding: 20px;
            }
            .table-title {
                font-size: 1.5rem;
            }
            table, thead, tbody, th, td, tr {
                display: block;
            }
            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            tr { border: 1px solid #ccc; margin-bottom: 10px; border-radius: 8px; }
            td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            td:before {
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: var(--primary-color);
            }
            td:nth-of-type(1):before { content: "ID"; }
            td:nth-of-type(2):before { content: "Leave Type"; }
            td:nth-of-type(3):before { content: "Start Date"; }
            td:nth-of-type(4):before { content: "End Date"; }
            td:nth-of-type(5):before { content: "Days"; }
            td:nth-of-type(6):before { content: "Reason"; }
            td:nth-of-type(7):before { content: "Applied On"; }
            td:nth-of-type(8):before { content: "Mentor Status"; }
            td:nth-of-type(9):before { content: "PM Status"; }
            td:nth-of-type(10):before { content: "Overall Status"; }
            .container {
                overflow-x: hidden;
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
            <i class="fas fa-history"></i> MY LEAVE HISTORY
        </h1>
        <div class="welcome-message">
            Welcome, <span><?php echo htmlspecialchars($mentor_fullname); ?></span>
        </div>
        <div class="header-buttons">
            <a href="mentor_dashboard.php" class="header-btn">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="mentor_apply_leave.php" class="header-btn">
                <i class="fas fa-calendar-plus"></i> Apply Leave
            </a>
            <a href="logout.php" class="header-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="container">
        <h2 class="table-title">My Leave Applications</h2>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($leave_applications)): ?>
            <div class="message info">
                You have not submitted any leave applications yet.
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Applied On</th>
                        <th>Mentor Status</th>
                       
                        <th>PM Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leave_applications as $app): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($app['id']); ?></td>
                        <td><?php echo htmlspecialchars($app['leave_type_name']); ?></td>
                        <td><?php echo htmlspecialchars($app['start_date']); ?></td>
                        <td><?php echo htmlspecialchars($app['end_date']); ?></td>
                        <td><?php echo htmlspecialchars($app['number_of_days']); ?></td>
                        <td><?php echo htmlspecialchars(substr($app['reason'], 0, 50)) . (strlen($app['reason']) > 50 ? '...' : ''); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($app['date_applied'])); ?></td>
                        <td><?php echo getStatusText($app['mentor_status']); ?></td>
                     
                        <td><?php echo getStatusText($app['overall_status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>