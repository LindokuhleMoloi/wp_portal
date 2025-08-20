<?php
// employee_dashboard.php

// Require the config file to handle session, database connection, etc.
require_once(__DIR__ . '/../../config.php');

// Redirect if not logged in
if (!isset($_SESSION['employee_id'])) {
    header("Location: leave_login.php");
    exit();
}

// The database connection is now managed by config.php
// The global $conn object is available for use.

$employee_id = $_SESSION['employee_id'];
$employee_fullname = $_SESSION['fullname'] ?? 'Employee';

// --- Fetch Leave Types ---
$leave_types = [];
$sql_leave_types = "SELECT id, name, calculation_method, days_per_year FROM leave_types WHERE status = 1 ORDER BY name ASC";
$result_leave_types = $conn->query($sql_leave_types);
if ($result_leave_types->num_rows > 0) {
    while ($row = $result_leave_types->fetch_assoc()) {
        $leave_types[] = $row;
    }
}

// --- Fetch Employee's Leave Balances (Optimized) ---
$employee_leave_balances = [];
$sql_employee_balances = "
    SELECT
        lt.id AS leave_type_id,
        lt.name AS leave_type_name,
        lt.days_per_year,
        lt.calculation_method,
        COALESCE(elb.days_accumulated, 0) AS days_accumulated,
        COALESCE(elb.days_taken, 0) AS days_taken,
        CASE
            WHEN lt.calculation_method = 'fixed_annual' THEN
                COALESCE(lt.days_per_year - elb.days_taken, lt.days_per_year)
            WHEN lt.calculation_method = 'monthly_accrual' THEN
                COALESCE(elb.days_accumulated - elb.days_taken, 0)
            ELSE 0
        END AS balance
    FROM leave_types lt
    LEFT JOIN employee_leave_balances elb ON
        lt.id = elb.leave_type_id
        AND elb.employee_id = ?
    WHERE lt.status = 1
    ORDER BY lt.name ASC
";

$stmt_employee_balances = $conn->prepare($sql_employee_balances);
$stmt_employee_balances->bind_param("i", $employee_id);
$stmt_employee_balances->execute();
$result_employee_balances = $stmt_employee_balances->get_result();

while ($row = $result_employee_balances->fetch_assoc()) {
    $employee_leave_balances[$row['leave_type_id']] = [
        'name' => $row['leave_type_name'],
        'available_days' => floatval($row['balance']),
        'days_accumulated' => floatval($row['days_accumulated']),
        'days_taken' => floatval($row['days_taken']),
        'calculation_method' => $row['calculation_method'],
        'days_per_year' => $row['days_per_year']
    ];
}
$stmt_employee_balances->close();

// --- Handle Success Messages from URL parameter ---
$success_message = '';
if (isset($_GET['success'])) {
    $success_message = urldecode($_GET['success']);
}
// --- Handle Error Messages from URL parameter ---
$error_message = '';
if (isset($_GET['error'])) {
    $error_message = urldecode($_GET['error']);
}

// The connection is now managed by config.php, which closes it automatically
// at the end of the script's execution.
// $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Employee Leave Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.6.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" integrity="sha512-1ycn6IcaQQ40NZMfz47K9FkGBdt+UjCofOQY/s0vWj7CqIq/c6zM+O5o95a9l6c6qM/jW5L6fW7O2O6zW2Jg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <style>
        /* General Styles */
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
            background-color: #000; /* Fallback color */
            display: flex;
            flex-direction: column;
            padding: 15px;
            position: relative;
        }

        /* Logo */
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

        /* Header Strip */
        .header-strip {
            background: linear-gradient(to right, #0b3e4d, #0e6574, #003e4d);
            padding: 40px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -15px -15px 30px -15px; /* Adjust to full width minus body padding */
            width: calc(100% + 30px);
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
        .welcome-message {
            color: white;
            text-align: center; /* Ensures the text inside is centered */
            margin-bottom: 8px;
            font-size: 1.9rem;
            font-weight: 600;
            /* Remove these two lines to allow centering */
            /* margin-left: auto; */
            /* padding-right: 50px; */
            
            /* Add properties to center the block element */
            margin-left: auto; /* Centers horizontally with auto margins */
            margin-right: auto; /* Centers horizontally with auto margins */
            width: fit-content; /* Ensures the div only takes up the width of its content */
            /* Alternatively, if the parent is a flex container and you want to center it among other flex items: */
            /* align-self: center; */
        }

        .header-buttons {
            display: flex;
            gap: 15px;
        }

        .history-btn, .logout-btn {
            background-color: white;
            color: black;
            border: none;
            padding: 8px 18px;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none; /* For anchor tags */
            display: inline-block; /* For anchor tags */
            line-height: normal; /* Align text vertically */
        }

        .history-btn:hover, .logout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Main Dashboard Container */
        .dashboard-container {
            background: linear-gradient(to right, rgba(0, 0, 0, 0.38), transparent);
            padding: 25px;
            border-radius: 12px;
            width: calc(100% - 900px); /* Adjust width for content */
            height: calc(100% - 200px); /* Adjust height for content */
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.3);
            margin: 0 auto;
            position: relative;
            display: flex; /* Use flexbox for internal layout */
            gap: 25px; /* Space between form and right side */
        }

        /* Form Container */
        #leaveForm {
            flex-grow: 1; /* Allow form to take available space */
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 20px;
            width: 100%;
            text-align: left;
        }

        .form-label {
            display: block;
            color: white;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 0.8rem;
        }

       .form-select, .form-control {
    width: 100%;
    max-width: 1000px; /* Increased from 300px to 400px */
    padding: 10px;
    border: none;
    border-radius: 8px;
    background-color: white;
    font-size: 0.8rem;
    color: #333;
    height: 38px;
}

        /* 🚀 OUT OF THIS WORLD CALENDAR STYLES 🚀 */

        /* Calendar Container (for From/To dates) */
       .calendar-container {
    display: flex;
    justify-content: flex-start;
    gap: 20px;
    margin-top: 20px;
    width: 100%;
}
        .calendar-box {
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.05));
    border-radius: 15px;
    padding: 20px; /* Increased from 15px */
    color: #fff;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4), 0 0 20px rgba(0, 200, 255, 0.3) inset;
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    flex: 1;
    display: flex;
    flex-direction: column;
    min-width: 220px; /* Added minimum width */
}

        .calendar-box .form-group {
            margin-bottom: 0; /* Remove default margin from inner form-group */
        }

        .calendar-box .form-label {
            color: #e0f7fa; /* Lighter blue for labels */
            font-size: 0.9rem; /* Slightly larger label */
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
            text-align: center; /* Center the FROM/TO labels */
        }
        
        .calendar-header { /* This class is no longer used for the FROM/TO headers, replaced by .calendar-box .form-label */
            display: none; /* Hide if still present from old structure */
        }

        /* Style for date input field */
       input[type="date"] {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-color: rgba(255, 255, 255, 0.9);
    border: 2px solid #0e6574;
    border-radius: 8px;
    padding: 12px 20px; /* Increased from 10px 15px */
    font-size: 1.1rem; /* Increased from 1rem */
    color: #1a1a1a;
    cursor: pointer;
    outline: none;
    transition: all 0.3s ease;
    text-align: center;
    position: relative;
    padding-right: 45px; /* Increased from 40px */
    width: 100%; /* Ensure it takes full width of container */
    height: 50px; /* Increased height */
}

        input[type="date"]::-webkit-calendar-picker-indicator {
            opacity: 0; /* Hide default calendar icon */
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
            cursor: pointer;
        }

        /* Custom calendar icon using Font Awesome */
        input[type="date"] + .fa-calendar-alt {
            position: absolute;
            right: 15px; /* Position icon inside input */
            top: 50%;
            transform: translateY(-50%);
            color: #0e6574; /* Icon color */
            pointer-events: none; /* Make icon unclickable, so input's calendar picker works */
            font-size: 1.2rem; /* Larger icon */
        }

        input[type="date"]:hover {
            border-color: #FF8C00; /* Orange on hover */
            box-shadow: 0 0 10px rgba(255, 140, 0, 0.5); /* Subtle glow */
        }

        input[type="date"]:focus {
            border-color: #FF8C00;
            box-shadow: 0 0 15px rgba(255, 140, 0, 0.8), 0 0 20px rgba(255, 140, 0, 0.4) inset; /* More pronounced glow */
        }

        /* --- END CALENDAR STYLES --- */
        
        /* Right Side Container */
        .right-side-container {
            width: 35%; /* Fixed width for the right panel */
            min-width: 300px; /* Ensure it doesn't get too small */
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Leave Balances Box */
        .leave-balances-box {
            background-color: white;
            border-radius: 10px;
            padding: 8px 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .leave-balances-title {
            font-weight: 700;
            font-size: 1rem;
            text-transform: uppercase;
            color: #0b3e4d;
            margin-bottom: 6px;
            text-align: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .leave-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
            font-size: 0.9rem;
            padding: 3px 8px;
            line-height: 1.2;
        }

        .leave-type {
            font-weight: 600;
            color: #333;
            min-width: 120px;
        }

        .leave-days {
            font-weight: 700;
            color: #0e6574;
            text-align: right;
        }

        /* Application Summary Box */
        .application-summary {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .application-summary-title {
            font-weight: 700;
            font-size: 1.1rem;
            text-transform: uppercase;
            color: #0b3e4d;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .application-summary-title strong {
            font-weight: 800;
        }

        .days-applied {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }

        .days-label {
            font-weight: 600;
            color: #333;
        }

        .days-value {
            font-weight: 700;
            color: #0e6574;
        }

        .submit-btn {
            background: #FF8C00;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 1.4rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        /* Alert Messages */
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            text-align: center;
            color: #fff;
            width: fit-content;
            margin-left: auto;
            margin-right: auto;
        }

        .alert-success {
            background-color: #28a745; /* Green */
        }
        .alert-danger {
            background-color: #dc3545; /* Red */
        }


        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1050; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
        }

        .modal.show { /* For Bootstrap .show class */
            display: block;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto; /* 15% from the top and centered */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* Could be more or less, depending on screen size */
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2),0 6px 20px 0 rgba(0,0,0,0.19);
            animation-name: animatetop;
            animation-duration: 0.4s;
        }

        /* Add Animation */
        @keyframes animatetop {
            from {top: -300px; opacity: 0}
            to {top: 0; opacity: 1}
        }

        .modal-header {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 15px;
            text-align: center;
            position: relative; /* For close button positioning */
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #0b3e4d;
        }
        
        .modal-header .close {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 1.8rem;
            color: #aaa;
            text-decoration: none;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
        }

        .modal-header .close:hover {
            color: #333;
        }


        .modal-body {
            padding: 10px 0;
            text-align: center;
            font-size: 1.1rem;
            color: #333;
        }

        .modal-footer {
            padding: 15px 0 0;
            border-top: 1px solid #eee;
            margin-top: 20px;
            text-align: center;
        }

        .modal-button {
            background-color: #0e6574;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin: 0 5px;
            transition: background-color 0.3s ease;
        }

        .modal-button.cancel {
            background-color: #dc3545;
        }

        .modal-button:hover {
            opacity: 0.9;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .dashboard-container {
                flex-direction: column; /* Stack elements vertically */
                width: calc(100% - 40px); /* Wider on smaller screens */
                height: auto; /* Auto height to fit content */
            }
            
            #leaveForm {
                width: 100%;
                order: 2; /* Move form below right-side container */
            }
            
            .right-side-container {
                position: static; /* Remove absolute positioning */
                width: 100%;
                margin-bottom: 20px;
                order: 1; /* Place right-side container first */
            }
            
            .calendar-container {
                justify-content: center;
            }

            .header-strip {
                flex-direction: column;
                align-items: flex-start;
                padding: 25px 15px;
            }
            .header-title {
                margin-bottom: 15px;
                font-size: 2rem;
            }
            .welcome-message {
                margin-left: 0;
                padding-right: 0;
                margin-bottom: 15px;
                font-size: 1.5rem;
            }
            .header-buttons {
                width: 100%;
                justify-content: space-around;
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
            }

            .history-btn, .logout-btn {
                padding: 6px 12px;
                font-size: 0.75rem;
            }

            .dashboard-container {
                padding: 20px;
                height: auto; /* Adjust to content */
                width: calc(100% - 20px); /* More padding */
            }

            .form-select, .form-control {
                max-width: 100%;
            }

            .calendar-container {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }
            
            .calendar-box {
                height: auto; /* Allow content to dictate height */
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
        <h1 class="header-title"><span>EMPLOYEE </span>LEAVE DASHBOARD</h1>
        <div class="welcome-message">
            Welcome, <?php echo htmlspecialchars($employee_fullname); ?>!
        </div>
        <div class="header-buttons">
            <a href="view_Leave_history.php" class="history-btn">VIEW LEAVE HISTORY</a>
            <a href="leavelogout.php" class="logout-btn">LOGOUT</a>
        </div>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <form id="leaveForm">
            <div class="form-group">
                <label for="leaveType" class="form-label">Type of Leave</label>
                <select id="leaveType" name="leave_type_id" class="form-select" required>
                    <option value="">Select Leave Type</option>
                    <?php foreach ($leave_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['id']); ?>">
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="calendar-container">
                <div class="calendar-box">
                    <div class="form-group">
                        <label for="startDate" class="form-label">FROM</label>
                        <div style="position: relative;">
                            <input type="date" id="startDate" name="start_date" class="form-control" required>
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
                
                <div class="calendar-box">
                    <div class="form-group">
                        <label for="endDate" class="form-label">TO</label>
                        <div style="position: relative;">
                            <input type="date" id="endDate" name="end_date" class="form-control" required>
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="reason" class="form-label">Reason for Leave</label>
                <textarea id="reason" name="reason" class="form-control" rows="3"></textarea>
            </div>
        </form>

        <div class="right-side-container">
            <div class="leave-balances-box">
                <div class="leave-balances-title">LEAVE BALANCES</div>
                <?php if (!empty($employee_leave_balances)): ?>
                    <?php foreach ($employee_leave_balances as $id => $balance): ?>
                        <div class="leave-item" data-leave-type-id="<?php echo htmlspecialchars($id); ?>">
                            <span class="leave-type"><?php echo htmlspecialchars($balance['name']); ?></span>
                            <span class="leave-days" id="leaveBalance_<?php echo htmlspecialchars($id); ?>">
                                <?php echo number_format($balance['available_days'], 2); ?> days
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="leave-item">
                        <span class="leave-type">No balances found.</span>
                        <span class="leave-days">0.00 days</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="application-summary">
                <div class="application-summary-title"><strong>APPLICATION SUMMARY</strong></div>
                <div class="days-applied">
                    <span class="days-label">NUMBER OF DAYS APPLIED FOR</span>
                    <span class="days-value" id="daysAppliedValue">0</span>
                </div>
                <button type="button" class="submit-btn" id="submitLeaveBtn">SUBMIT APPLICATION</button>
            </div>
        </div>
    </div>

    <div id="negativeBalanceModal" class="modal" tabindex="-1" role="dialog" aria-labelledby="negativeBalanceModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title" id="negativeBalanceModalLabel">Confirm Leave Application</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>You are attempting to apply for <strong id="modalLeaveType"></strong> leave for <span id="modalNegativeDays">0</span> days more than your available balance.</p>
                    <p>Do you wish to proceed with this application? (It may require special approval)</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="modal-button cancel" data-dismiss="modal">Cancel</button>
                    <button type="button" class="modal-button" id="confirmSubmitBtn">Proceed Anyway</button>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

    <script>
        // Store leave balances from PHP in JavaScript
        const employeeLeaveBalances = <?php echo json_encode($employee_leave_balances); ?>;

        // Function to calculate working days (excluding weekends)
        function getWorkingDays(startDate, endDate) {
            let start = new Date(startDate);
            let end = new Date(endDate);
            let count = 0;
            // Loop while the current day is less than or equal to the end date
            for (let day = start; day <= end; day.setDate(day.getDate() + 1)) {
                let dayOfWeek = day.getDay();
                // 0 = Sunday, 6 = Saturday
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    count++;
                }
            }
            return count;
        }

        // Update days applied when dates or leave type change
        $('#startDate, #endDate').on('change', function() {
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            let daysApplied = 0;

            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);

                if (start > end) {
                    alert('Start date cannot be after end date.');
                    $('#daysAppliedValue').text(0);
                    return;
                }
                daysApplied = getWorkingDays(startDate, endDate);
            }

            $('#daysAppliedValue').text(daysApplied);
        });

        // Handle form submission (initial click)
        $('#submitLeaveBtn').on('click', function() {
            const leaveTypeId = $('#leaveType').val();
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            const reason = $('#reason').val();
            const daysApplied = parseFloat($('#daysAppliedValue').text());

            // Basic client-side validation
            if (!leaveTypeId || !startDate || !endDate || daysApplied <= 0) {
                alert('Please select a leave type, valid dates, and ensure the number of days is greater than zero.');
                return;
            }

            const start = new Date(startDate);
            const end = new Date(endDate);
            if (start > end) {
                alert('Start date cannot be after end date.');
                return;
            }

            // Get leave balance for selected type
            const selectedBalance = employeeLeaveBalances[leaveTypeId];
            const availableDays = selectedBalance ? selectedBalance.available_days : 0;

            if (availableDays < daysApplied) {
                // Show modal for negative balance confirmation
                const negativeDays = daysApplied - availableDays;
                $('#modalNegativeDays').text(negativeDays.toFixed(2));
                $('#modalLeaveType').text(selectedBalance ? selectedBalance.name : 'Unknown Leave Type');
                $('#negativeBalanceModal').modal('show');
                return; // Stop default submission
            }

            // If balance is sufficient, submit directly
            submitLeaveApplication(leaveTypeId, startDate, endDate, daysApplied, reason, false);
        });

        // Handle forced submission from modal
        $('#confirmSubmitBtn').on('click', function() {
            $('#negativeBalanceModal').modal('hide'); // Hide the modal
            const leaveTypeId = $('#leaveType').val();
            const startDate = $('#startDate').val();
            const endDate = $('#endDate').val();
            const reason = $('#reason').val();
            const daysApplied = parseFloat($('#daysAppliedValue').text());

            // Call submit function with force_submit = true
            submitLeaveApplication(leaveTypeId, startDate, endDate, daysApplied, reason, true);
        });


        function submitLeaveApplication(leaveTypeId, startDate, endDate, daysApplied, reason, forceSubmit = false) {
            $.ajax({
                url: 'process_leave_application.php',
                method: 'POST',
                data: {
                    employee_id: <?php echo $employee_id; ?>,
                    leave_type_id: leaveTypeId,
                    start_date: startDate,
                    end_date: endDate,
                    num_days: daysApplied,
                    reason: reason,
                    force_submit: forceSubmit ? 'true' : 'false' // Ensure string 'true' or 'false'
                },
                dataType: 'json', // Expect JSON response
                success: function(response) {
                    if (response.status === 'success') {
                        // Redirect with success message
                        window.location.href = 'employee_dashboard.php?success=' +
                            encodeURIComponent(response.message || 'Leave application submitted successfully.');
                    } else if (response.status === 'warning') {
                        // This block should ideally not be hit if modal handles first,
                        // but kept for robustness if backend sends warning for other reasons.
                        // Or if force_submit was true, but it still detected a warning (less likely)
                        alert('Warning: ' + (response.message || 'An issue occurred with your application.'));
                        // You might want to refresh balances here if the warning implied a server-side state change
                        // location.reload();
                    } else { // status === 'error'
                        // Redirect with error message
                        window.location.href = 'employee_dashboard.php?error=' +
                            encodeURIComponent(response.message || 'An error occurred during submission.');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMessage = 'An unknown error occurred.';
                    try {
                        const jsonResponse = JSON.parse(xhr.responseText);
                        errorMessage = jsonResponse.message || xhr.responseText;
                    } catch (e) {
                        errorMessage = xhr.responseText || error;
                    }
                    // Redirect with error message
                    window.location.href = 'employee_dashboard.php?error=' +
                        encodeURIComponent('AJAX Error: ' + errorMessage);
                }
            });
        }

        // Set min date to today for date pickers
        $(document).ready(function() {
            const today = new Date().toISOString().split('T')[0];
            $('#startDate').attr('min', today);
            $('#endDate').attr('min', today);

            // Ensure endDate is not earlier than startDate
            $('#startDate').on('change', function() {
                const startVal = $(this).val();
                $('#endDate').attr('min', startVal);
                if (new Date($('#endDate').val()) < new Date(startVal)) {
                    $('#endDate').val(startVal); // Set end date to start date if it's earlier
                }
                // Trigger change on end date to re-calculate days applied immediately
                $('#endDate').trigger('change');
            });

            // Trigger initial calculation in case dates are pre-filled or defaults apply
            $('#startDate').trigger('change');
            $('#leaveType').trigger('change'); // To ensure days applied is 0 or correct if pre-selected
        });
    </script>
</body>
</html>