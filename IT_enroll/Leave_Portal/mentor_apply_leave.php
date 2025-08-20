<?php
// mentor_apply_leave.php
session_start();

// Check if mentor is logged in
if (!isset($_SESSION['mentor_logged_in']) || $_SESSION['mentor_logged_in'] !== true) {
    header("Location: mentor_login.php");
    exit();
}

// Get mentor's details from session
$employee_id = $_SESSION['mentor_employee_id'] ?? null;
$mentor_fullname = $_SESSION['mentor_fullname'] ?? 'Mentor';

if (!$employee_id) {
    error_log("mentor_apply_leave.php: Mentor Employee ID not found in session.");
    header("Location: logout.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log("Database connection failed for mentor_apply_leave: " . $conn->connect_error);
    $_SESSION['error_message'] = "Database connection failed. Please try again later.";
    header("Location: mentor_dashboard.php");
    exit();
}

// Fetch leave types for the form
$leave_types = [];
$sql_leave_types = "SELECT id, name FROM leave_types ORDER BY name ASC";
$result_leave_types = $conn->query($sql_leave_types);
if ($result_leave_types && $result_leave_types->num_rows > 0) {
    while ($row = $result_leave_types->fetch_assoc()) {
        $leave_types[] = $row;
    }
} else {
    $_SESSION['error_message'] = "No leave types found. Please contact administrator.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $leave_type_id = $_POST['leave_type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';

    // Basic validation
    if (empty($leave_type_id) || empty($start_date) || empty($end_date) || empty($reason)) {
        $_SESSION['error_message'] = "All fields are required.";
    } else {
        // Convert dates to DateTime objects for proper comparison
        $start_date_obj = new DateTime($start_date);
        $end_date_obj = new DateTime($end_date);
        
        // Get today's date at midnight for comparison
        $today = new DateTime('today');
        
        if ($start_date_obj < $today) {
            $_SESSION['error_message'] = "Start date cannot be in the past.";
        } elseif ($end_date_obj < $start_date_obj) {
            $_SESSION['error_message'] = "End date cannot be before start date.";
        } else {
            // Calculate number of working days (excluding weekends)
            $interval = $start_date_obj->diff($end_date_obj);
            $total_days = $interval->days + 1; // +1 to include both start and end dates
            
            $working_days = 0;
            $current_date = clone $start_date_obj;
            
            for ($i = 0; $i < $total_days; $i++) {
                $day_of_week = $current_date->format('N'); // 1 (Monday) to 7 (Sunday)
                if ($day_of_week < 6) { // Monday to Friday
                    $working_days++;
                }
                $current_date->modify('+1 day');
            }
            
            // Prepare and execute the SQL INSERT statement
            $sql = "INSERT INTO leave_applications 
                    (employee_id, leave_type_id, start_date, end_date, number_of_days, reason, status, mentor_status, pm_status) 
                    VALUES (?, ?, ?, ?, ?, ?, 0, 0, 0)";

            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("iissis", $employee_id, $leave_type_id, $start_date, $end_date, $working_days, $reason);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Leave application submitted successfully for " . htmlspecialchars($working_days) . " working days.";
                    header("Location: mentor_leave_history.php");
                    exit();
                } else {
                    $_SESSION['error_message'] = "Error submitting leave application: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $_SESSION['error_message'] = "Database query preparation failed: " . $conn->error;
            }
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for Leave - Mentor</title>
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
            padding: 25px;
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(47, 168, 224, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn i {
            margin-right: 0.5rem;
        }

        .btn-submit {
            background-color: var(--primary-color);
            color: white;
        }

        .btn-submit:hover {
            background-color: var(--dark-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-cancel {
            background-color: #e1e5eb;
            color: #6c757d;
        }

        .btn-cancel:hover {
            background-color: #d1d7e0;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
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

        @media (max-width: 768px) {
            .header-strip {
                flex-direction: column;
                text-align: center;
            }
            
            .welcome-message {
                text-align: center;
            }
            
            .header-buttons {
                width: 100%;
                justify-content: center;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const today = new Date().toISOString().split('T')[0];
            
            startDateInput.min = today;
            endDateInput.min = today;
            
            startDateInput.addEventListener('change', function() {
                endDateInput.min = this.value;
            });
        });
    </script>
</head>
<body>
    <div class="logo">
        <div class="logo-letter w">W</div>
        <div class="logo-letter p">P</div>
    </div>

    <div class="header-strip">
        <h1 class="header-title"><i class="fas fa-calendar-plus"></i> Apply for Leave</h1>
        <p class="welcome-message">Welcome, <span><?php echo htmlspecialchars($mentor_fullname); ?></span></p>
        <div class="header-buttons">
            <a href="mentor_dashboard.php" class="header-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="message success">
                <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="message error">
                <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="leaveForm">
            <div class="form-group">
                <label for="leave_type">Leave Type</label>
                <select id="leave_type" name="leave_type" class="form-control" required>
                    <option value="">Select Leave Type</option>
                    <?php foreach ($leave_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['id']); ?>" <?php echo (isset($_POST['leave_type']) && $_POST['leave_type'] == $type['id'] ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" class="form-control" required 
                       value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" class="form-control" required 
                       value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="reason">Reason for Leave</label>
                <textarea id="reason" name="reason" class="form-control" rows="5" required 
                          placeholder="Please provide a detailed reason for your leave application..."><?php echo isset($_POST['reason']) ? htmlspecialchars($_POST['reason']) : ''; ?></textarea>
            </div>

            <div class="form-actions">
                <a href="mentor_dashboard.php" class="btn btn-cancel">
                    <i class="fas fa-times-circle"></i> Cancel
                </a>
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Application
                </button>
            </div>
        </form>
    </div>
</body>
</html>