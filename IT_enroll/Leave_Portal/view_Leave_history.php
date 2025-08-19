<?php
// view_Leave_history.php

session_start(); // Start the session at the very beginning

// --- Database Connection Details ---
$servername = "localhost";
$username = "root"; // Your provided username
$password = "";         // Your provided password
$dbname = "tarryn_workplaceportal"; // Your provided database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect if employee_id is not set in session (user is not logged in)
if (!isset($_SESSION['employee_id'])) {
    header("Location: leave_login.php");
    exit();
}

// Retrieve employee details
$employee_id = $_SESSION['employee_id'];
$employee_fullname = $_SESSION['fullname'] ?? 'Employee';

// --- Fetch Employee Details ---
$employee_details = [];
$sql_employee = "SELECT * FROM employee_list WHERE id = ?";
$stmt_employee = $conn->prepare($sql_employee);
$stmt_employee->bind_param("i", $employee_id);
$stmt_employee->execute();
$result_employee = $stmt_employee->get_result();

if ($result_employee->num_rows > 0) {
    $employee_details = $result_employee->fetch_assoc();
}
$stmt_employee->close();

// Determine employee status text
$employee_status_text = 'Active';
if (isset($employee_details['status'])) {
    $employee_status_text = ($employee_details['status'] == 1) ? 'Active' : 'Inactive';
}

// --- Fetch Leave History from Database ---
$leave_history = [];
$sql_history = "SELECT 
                    la.id, 
                    lt.name AS leave_type_name, 
                    la.start_date, 
                    la.end_date, 
                    la.reason, 
                    la.status, 
                    la.date_applied,
                    la.mentor_status,
                    la.mentor_approval_date,
                    la.pm_status,
                    la.pm_approval_date
                FROM leave_applications la
                JOIN leave_types lt ON la.leave_type_id = lt.id
                WHERE la.employee_id = ?
                ORDER BY la.date_applied DESC";

$stmt_history = $conn->prepare($sql_history);
$stmt_history->bind_param("i", $employee_id);
$stmt_history->execute();
$result_history = $stmt_history->get_result();

if ($result_history === false) {
    error_log("Error fetching leave history: " . $conn->error);
} else if ($result_history->num_rows > 0) {
    while($row = $result_history->fetch_assoc()) {
        // Calculate overall status
        $row['overall_status'] = 'Pending';
        if ($row['mentor_status'] == 1 && $row['pm_status'] == 1) {
            $row['overall_status'] = 'Approved';
        } elseif ($row['mentor_status'] == 2 || $row['pm_status'] == 2) {
            $row['overall_status'] = 'Rejected';
        }
        $leave_history[] = $row;
    }
}
$stmt_history->close();

// --- Fetch Leave Balances ---
$leave_balances = [];
$sql_balances = "SELECT 
                    lt.name AS type,
                    COALESCE(elb.days_accumulated, 0) - COALESCE(elb.days_taken, 0) AS balance
                FROM leave_types lt
                LEFT JOIN employee_leave_balances elb ON lt.id = elb.leave_type_id AND elb.employee_id = ?
                WHERE lt.status = 1";

$stmt_balances = $conn->prepare($sql_balances);
$stmt_balances->bind_param("i", $employee_id);
$stmt_balances->execute();
$result_balances = $stmt_balances->get_result();

if ($result_balances->num_rows > 0) {
    while ($row = $result_balances->fetch_assoc()) {
        $leave_balances[] = $row;
    }
}
$stmt_balances->close();

// Fetch related names (employer, department, etc.)
$employer_name = 'Not specified';
$department_name = 'Not specified';
$pm_name = 'Not specified';
$designation_name = 'Not specified';
$mentor_name = 'Not specified';

// Add your queries here to fetch these values based on the employee_details
// For example:
if (!empty($employee_details['mentor_id'])) {
    $sql_mentor = "SELECT mentor_name FROM mentor WHERE mentor_id = ?";
    $stmt_mentor = $conn->prepare($sql_mentor);
    $stmt_mentor->bind_param("s", $employee_details['mentor_id']);
    $stmt_mentor->execute();
    $result_mentor = $stmt_mentor->get_result();
    if ($result_mentor->num_rows > 0) {
        $mentor = $result_mentor->fetch_assoc();
        $mentor_name = htmlspecialchars($mentor['mentor_name']);
    }
    $stmt_mentor->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Employee Leave History</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
     <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Montserrat', sans-serif;
            overflow-x: hidden;
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
            background: linear-gradient(to right, #0b3e4d, #0e6574, #003e4d);
            padding: 40px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -15px -15px 30px -15px;
            width: calc(100% + 30px);
        }

        .header-buttons {
            display: flex;
            gap: 15px;
            margin-left: auto;
            margin-right: 80px;
        }

        .header-title {
            color: white;
            font-size: 2.4rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .header-title span {
            font-weight: 300;
        }

        .dashboard-btn, .logout-btn {
            background-color: white;
            color: black;
            border: none;
            padding: 8px 18px;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            line-height: normal;
        }

        .dashboard-btn:hover, .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .history-container {
            background-color: rgba(0, 0, 0, 0.80);
            padding: 25px;
            border-radius: 12px;
            width: calc(100% - 100px);
            height: auto;
            min-height: auto;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.3);
            margin: 0 auto 30px;
            position: relative;
            color: white;
            overflow: visible;
        }

        .content-wrapper {
            display: flex;
            flex-direction: column;
            height: auto;
            overflow: visible;
            gap: 20px;
        }

        .table-container {
            overflow: visible;
            margin-bottom: 20px;
        }

        .history-title {
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 25px;
            color: #FF8C00;
            text-transform: uppercase;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 30px;
        }

        .history-table th, .history-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            font-size: 0.9rem;
        }

        .history-table th {
            background: linear-gradient(to right, #0b3e4d, #0e6574, #003e4d);
            font-weight: 600;
            text-transform: uppercase;
            color: white;
        }

        .history-table tr:hover {
            background-color: rgba(14, 101, 116, 0.5);
        }

        .history-table tbody tr:last-child {
            border-bottom: none;
        }

        .status-pending {
            background-color: #FFC300;
            color: #333;
            padding: 4px 8px;
            border-radius: 5px;
            font-weight: 600;
        }

        .status-approved {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
            font-weight: 600;
        }

        .status-rejected {
            background-color: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 5px;
            font-weight: 600;
        }

        .rejection-reason {
            margin-top: 5px;
            font-size: 0.8rem;
            color: #ffcccc;
            line-height: 1.4;
        }

        /* Centering the welcome message */
        .welcome-message {
            color: white;
            text-align: center;
            margin-bottom: 8px;
            font-size: 1.9rem;
            font-weight: 600;
            margin-left: auto;
            margin-right: auto;
            width: fit-content;
        }

        .details-balances-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            justify-content: space-between;
            margin-top: 30px;
            width: 100%;
            overflow: visible;
        }

        .employee-details-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 65%;
            color: #333;
            overflow: visible;
        }

        .employee-details-container h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: #0b3e4d;
            text-align: center;
            text-transform: uppercase;
            position: relative;
            padding-bottom: 15px;
        }

        .employee-details-container h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 25%;
            width: 50%;
            height: 3px;
            background: linear-gradient(to right, #0b3e4d, #0e6574);
            border-radius: 3px;
        }

        .employee-details-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            overflow: visible;
        }

        .detail-item {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
        }

        .detail-label {
            font-weight: 600;
            color: #0e6574;
            min-width: 160px;
            margin-right: 15px;
            font-size: 1rem;
        }

        .detail-value {
            color: #444;
            font-weight: 500;
            flex: 1;
            font-size: 1rem;
            word-break: break-word;
            padding: 6px 0;
        }

        .detail-item-full {
            grid-column: span 2;
            align-items: flex-start;
        }

        .detail-item-full .detail-value {
            line-height: 1.5;
        }

        .leave-balances-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 30%;
            color: #333;
            overflow: visible;
        }

        .leave-balances-container h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 25px;
            color: #0b3e4d;
            text-align: center;
            text-transform: uppercase;
            position: relative;
            padding-bottom: 15px;
        }

        .leave-balances-container h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 25%;
            width: 50%;
            height: 3px;
            background: linear-gradient(to right, #0b3e4d, #0e6574);
            border-radius: 3px;
        }

        .balance-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .balance-type {
            font-weight: 600;
            color: #0e6574;
        }

        .balance-days {
            font-weight: 700;
            color: #0b3e4d;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
            gap: 5px;
        }

        .pagination-link {
            padding: 8px 12px;
            background-color: #0e6574;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .pagination-link:hover {
            background-color: #0b3e4d;
            transform: translateY(-2px);
        }

        .pagination-link.active {
            background-color: #FF8C00;
            font-weight: bold;
        }

        @media (max-width: 1200px) {
            .history-container {
                width: 95%;
            }
            
            .details-balances-wrapper {
                flex-direction: column;
            }
            
            .employee-details-container,
            .leave-balances-container {
                width: 100%;
            }
            
            .leave-balances-container {
                margin-top: 20px;
            }
        }

        @media (max-width: 992px) {
            .history-container {
                padding: 20px;
                width: 100%;
            }
            
            .history-table th, .history-table td {
                padding: 10px;
                font-size: 0.85rem;
            }
            
            .employee-details-container,
            .leave-balances-container {
                padding: 20px;
            }
            
            .employee-details-container h3,
            .leave-balances-container h3 {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 768px) {
            .logo {
                top: 15px;
                right: 15px;
                width: 60px;
                height: 50px;
            }

            .logo-letter {
                font-size: 2.2rem;
            }

            .header-title {
                font-size: 1.8rem;
            }

            .header-strip {
                padding: 25px 15px;
                margin-bottom: 20px;
                flex-direction: column;
                text-align: center;
            }

            .header-buttons {
                margin: 15px auto 0;
            }

            .welcome-message {
                margin: 15px auto;
                padding-right: 0;
            }

            .dashboard-btn, .logout-btn {
                padding: 6px 12px;
                font-size: 0.75rem;
            }
            
            .history-title {
                font-size: 1.4rem;
            }
            
            .employee-details-grid {
                grid-template-columns: 1fr;
            }
            
            .detail-item-full {
                grid-column: span 1;
            }
            
            .detail-label {
                min-width: 120px;
                font-size: 0.95rem;
            }
            
            .detail-value {
                font-size: 0.95rem;
            }
            
            .history-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
            
            .history-table thead, .history-table tbody, .history-table th, .history-table td, .history-table tr {
                display: block;
            }
            
            .history-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            .history-table tr {
                border: 1px solid rgba(255, 255, 255, 0.2);
                margin-bottom: 15px;
                border-radius: 8px;
            }
            
            .history-table td {
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            
            .history-table td::before {
                content: attr(data-label);
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: rgba(255, 255, 255, 0.7);
            }

            .pagination {
                gap: 3px;
            }
            
            .pagination-link {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 480px) {
            .detail-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .detail-label {
                margin-bottom: 5px;
                min-width: unset;
            }
            
            .balance-item {
                flex-direction: column;
            }
            
            .balance-days {
                margin-top: 5px;
            }
            
            .employee-details-container h3,
            .leave-balances-container h3 {
                font-size: 1.3rem;
                padding-bottom: 10px;
            }
            
            .employee-details-container h3::after,
            .leave-balances-container h3::after {
                left: 20%;
                width: 60%;
            }
            
            .history-container {
                padding: 15px;
                width: 100%;
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
        <h1 class="header-title"><span>EMPLOYEE </span>LEAVE HISTORY</h1>
        <div class="welcome-message">
            Welcome, <?php echo htmlspecialchars($employee_fullname); ?>!
        </div>
        <div class="header-buttons">
            <a href="employee_dashboard.php" class="dashboard-btn">BACK TO DASHBOARD</a>
            <a href="leavelogout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <div class="history-container">
        <h2 class="history-title">Your Leave Applications</h2>
        <?php if (empty($leave_history)): ?>
            <p style="text-align: center; color: white;">No leave applications found.</p>
        <?php else: ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Days Applied</th>
                        <th>Reason</th>
                        <th>Mentor Approval</th>
                        <th>PM Approval</th>
                        <th>Overall Status</th>
                        <th>Date Applied</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leave_history as $application): ?>
                        <tr>
                            <td data-label="Leave Type"><?php echo htmlspecialchars($application['leave_type_name']); ?></td>
                            <td data-label="Start Date"><?php echo htmlspecialchars(date('M d, Y', strtotime($application['start_date']))); ?></td>
                            <td data-label="End Date"><?php echo htmlspecialchars(date('M d, Y', strtotime($application['end_date']))); ?></td>
                            <td data-label="Days Applied">
                                <?php
                                    $start = new DateTime($application['start_date']);
                                    $end = new DateTime($application['end_date']);
                                    $interval = $start->diff($end);
                                    echo $interval->days + 1;
                                ?>
                            </td>
                            <td data-label="Reason"><?php echo htmlspecialchars($application['reason']); ?></td>
                            <td data-label="Mentor Approval">
                                <?php
                                    $mentor_status_class = '';
                                    $mentor_status_text = '';
                                    switch ($application['mentor_status']) {
                                        case 0:
                                            $mentor_status_class = 'status-pending';
                                            $mentor_status_text = 'Pending';
                                            break;
                                        case 1:
                                            $mentor_status_class = 'status-approved';
                                            $mentor_status_text = 'Approved';
                                            if (!empty($application['mentor_approval_date'])) {
                                                $mentor_status_text .= '<br><small>' . date('M d, Y', strtotime($application['mentor_approval_date'])) . '</small>';
                                            }
                                            break;
                                        case 2:
                                            $mentor_status_class = 'status-rejected';
                                            $mentor_status_text = 'Rejected';
                                            break;
                                        default:
                                            $mentor_status_class = '';
                                            $mentor_status_text = 'Unknown';
                                    }
                                ?>
                                <span class="<?php echo $mentor_status_class; ?>"><?php echo $mentor_status_text; ?></span>
                            </td>
                            <td data-label="PM Approval">
                                <?php
                                    $pm_status_class = '';
                                    $pm_status_text = '';
                                    switch ($application['pm_status']) {
                                        case 0:
                                            $pm_status_class = 'status-pending';
                                            $pm_status_text = 'Pending';
                                            break;
                                        case 1:
                                            $pm_status_class = 'status-approved';
                                            $pm_status_text = 'Approved';
                                            if (!empty($application['pm_approval_date'])) {
                                                $pm_status_text .= '<br><small>' . date('M d, Y', strtotime($application['pm_approval_date'])) . '</small>';
                                            }
                                            break;
                                        case 2:
                                            $pm_status_class = 'status-rejected';
                                            $pm_status_text = 'Rejected';
                                            break;
                                        default:
                                            $pm_status_class = '';
                                            $pm_status_text = 'Unknown';
                                    }
                                ?>
                                <span class="<?php echo $pm_status_class; ?>"><?php echo $pm_status_text; ?></span>
                            </td>
                            <td data-label="Overall Status">
                                <?php
                                    $overall_status_class = '';
                                    $overall_status_text = '';
                                    if ($application['mentor_status'] == 1 && $application['pm_status'] == 1) {
                                        $overall_status_class = 'status-approved';
                                        $overall_status_text = 'Approved';
                                    } elseif ($application['mentor_status'] == 2 || $application['pm_status'] == 2) {
                                        $overall_status_class = 'status-rejected';
                                        $overall_status_text = 'Rejected';
                                    } else {
                                        $overall_status_class = 'status-pending';
                                        $overall_status_text = 'Pending';
                                    }
                                ?>
                                <span class="<?php echo $overall_status_class; ?>"><?php echo $overall_status_text; ?></span>
                            </td>
                            <td data-label="Date Applied"><?php echo htmlspecialchars(date('M d, Y h:i A', strtotime($application['date_applied']))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <hr style="border-top: 1px dashed rgba(255, 255, 255, 0.3); margin: 40px 0;">

    <div class="details-balances-wrapper">
        <div class="employee-details-container">
            <h3>PERSONAL DETAILS FOR <?php echo htmlspecialchars(strtoupper($employee_fullname)); ?></h3>
            
            <div class="employee-details-grid">
                <div class="detail-item">
                    <span class="detail-label">Employee Code:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($employee_details['employee_code'] ?? 'Not specified'); ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($employee_status_text); ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Gender:</span>
                    <span class="detail-value">
                        <?php echo !empty($employee_details['gender']) ? htmlspecialchars($employee_details['gender']) : 'Not specified'; ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value">
                        <?php
                        if (!empty($employee_details['date_of_birth']) && $employee_details['date_of_birth'] != '0000-00-00') {
                            echo htmlspecialchars(date('F j, Y', strtotime($employee_details['date_of_birth'])));
                        } else {
                            echo 'Not specified';
                        }
                        ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($employee_details['email'] ?? 'Not specified'); ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Contact:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($employee_details['contact'] ?? 'Not specified'); ?></span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Employer:</span>
                    <span class="detail-value">
                        <?php echo htmlspecialchars($employer_name); ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Department:</span>
                    <span class="detail-value">
                        <?php echo htmlspecialchars($department_name); ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Project Manager:</span>
                    <span class="detail-value">
                        <?php echo htmlspecialchars($pm_name); ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Designation:</span>
                    <span class="detail-value">
                        <?php echo htmlspecialchars($designation_name); ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Mentor:</span>
                    <span class="detail-value">
                        <?php echo htmlspecialchars($mentor_name); ?>
                    </span>
                </div>
                
                <div class="detail-item">
                    <span class="detail-label">Contract Start:</span>
                    <span class="detail-value">
                        <?php
                        if (!empty($employee_details['contract_start_date']) && $employee_details['contract_start_date'] != '0000-00-00') {
                            echo htmlspecialchars(date('F j, Y', strtotime($employee_details['contract_start_date'])));
                        } else {
                            echo 'Not specified';
                        }
                        ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Contract End:</span>
                    <span class="detail-value">
                        <?php
                        if (!empty($employee_details['contract_end_date']) && $employee_details['contract_end_date'] != '0000-00-00') {
                            echo htmlspecialchars(date('F j, Y', strtotime($employee_details['contract_end_date'])));
                        } else {
                            echo 'Not specified';
                        }
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="leave-balances-container">
            <h3>YOUR LEAVE BALANCES</h3>
            <?php if (empty($leave_balances)): ?>
                <p style="text-align: center; color: white;">No data found</p>
            <?php else: ?>
                <?php foreach ($leave_balances as $balance): ?>
                    <div class="balance-item">
                        <span class="balance-type"><?php echo htmlspecialchars($balance['type']); ?>:</span>
                        <span class="balance-days"><?php echo htmlspecialchars(number_format($balance['balance'], 2)); ?> days</span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>