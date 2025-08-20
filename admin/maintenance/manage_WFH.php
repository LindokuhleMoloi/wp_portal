<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = "";
$errorMessage = "";

// Handle Work-from-Home day update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['employee_code']) && isset($_POST['day_of_week'])) {
        $employeeCode = $conn->real_escape_string($_POST['employee_code']);
        $dayOfWeek = intval($_POST['day_of_week']);
        
        // Get the employee_id based on employee_code
        $result = $conn->query("SELECT id FROM employee_list WHERE employee_code = '$employeeCode'");
        $employee = $result->fetch_assoc();
        $employeeId = $employee['id'];
        
        // Clear existing WFH days for the employee
        $conn->query("DELETE FROM logs WHERE employee_id = $employeeId AND work_from_home = 3");
        
        // Define the start and end dates
        $startDate = '2024-01-01';
        $endDate = '2024-12-31';
        
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $end->modify('+1 day');
        
        $selectedDay = $dayOfWeek;
        
        while ($start < $end) {
            if ($start->format('N') == $selectedDay) {
                $date = $start->format('Y-m-d');
                $sql = "INSERT INTO logs (employee_id, date_created, type, work_from_home)
                        VALUES ($employeeId, '$date 08:00:00', 3, 3)";
                $conn->query($sql);
            }
            $start->modify('+1 day');
        }
        
        // Map dayOfWeek to day name for the success message
        $dayNames = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday'
        ];
        $dayName = $dayNames[$selectedDay];
        $successMessage = "Your new work-from-home day is $dayName.";
    }
}

// Fetch employee codes for the dropdown
$employeeCodes = [];
$result = $conn->query("SELECT employee_code FROM employee_list");
while ($row = $result->fetch_assoc()) {
    $employeeCodes[] = $row['employee_code'];
}

// Fetch current WFH days for the selected employee
$employeeWFH = [];
if (isset($_GET['employee_code'])) {
    $selectedCode = $conn->real_escape_string($_GET['employee_code']);
    $result = $conn->query("SELECT l.date_created, DAYOFWEEK(l.date_created) AS day_of_week
                            FROM logs l
                            JOIN employee_list el ON l.employee_id = el.id
                            WHERE el.employee_code = '$selectedCode' AND l.work_from_home = 3");

    while ($row = $result->fetch_assoc()) {
        $date_created = $row['date_created'];
        $day_of_week = $row['day_of_week'];
        
        $dayNames = [
            1 => 'Sunday',
            2 => 'Monday',
            3 => 'Tuesday',
            4 => 'Wednesday',
            5 => 'Thursday',
            6 => 'Friday',
            7 => 'Saturday'
        ];
        $dayName = $dayNames[$day_of_week];
        
        if (!isset($employeeWFH[$date_created])) {
            $employeeWFH[$date_created] = [
                'day' => $dayName
            ];
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Work-From-Home Days</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        h1 {
            font-size: 2em;
            color: #ffffff;
            background-color: #007bff;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 0;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        label {
            font-weight: bold;
        }
        select, input[type="submit"] {
            padding: 10px;
            font-size: 1em;
            border-radius: 8px;
            border: 1px solid #ccc;
            background: #ffffff;
            color: #333;
            transition: background-color 0.3s, border-color 0.3s;
        }
        select:focus, input[type="submit"]:hover {
            background-color: #007bff;
            color: white;
            border-color: #0056b3;
        }
        input[type="submit"] {
            cursor: pointer;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
        }
        .message {
            display: block;
            padding: 10px;
            margin-top: 10px;
            border-radius: 8px;
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .btn-manage-accommodations {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 1em;
            text-align: center;
        }
        .btn-manage-accommodations:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Work-From-Home Days</h1>
        <form method="post">
            <label for="employee_code">Employee Code:</label>
            <select id="employee_code" name="employee_code" required>
                <?php foreach ($employeeCodes as $code): ?>
                    <option value="<?php echo htmlspecialchars($code); ?>"><?php echo htmlspecialchars($code); ?></option>
                <?php endforeach; ?>
            </select>
            
            <label for="day_of_week">Select Work-From-Home Day:</label>
            <select id="day_of_week" name="day_of_week" required>
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thursday</option>
                <option value="5">Friday</option>
                <option value="6">Saturday</option>
                <option value="7">Sunday</option>
            </select>
            
            <input type="submit" value="Set Work-From-Home Day">
        </form>
        
        <!-- Display success or error message -->
        <?php if ($successMessage): ?>
            <div class="message"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>
        
        <!-- Current Work-From-Home Days Table -->
        <?php if (isset($_GET['employee_code'])): ?>
            <h2>Current Work-From-Home Days for Employee: <?php echo htmlspecialchars($_GET['employee_code']); ?></h2>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Day</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employeeWFH as $date => $info): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($date); ?></td>
                            <td><?php echo htmlspecialchars($info['day']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>