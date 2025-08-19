<?php
require_once(__DIR__ . '/../classes/DBConnection.php');
$db = new DBConnection();
$conn = $db->conn; 

// Set the default timezone
date_default_timezone_set('Africa/Johannesburg');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filters (month, year, department, employee id, employee code)
$selectedMonth = isset($_POST['month']) ? intval($_POST['month']) : (isset($_GET['month']) ? intval($_GET['month']) : date('n'));
$selectedYear = isset($_POST['year']) ? intval($_POST['year']) : (isset($_GET['year']) ? intval($_GET['year']) : date('Y'));
$selectedDepartment = isset($_POST['department']) ? intval($_POST['department']) : (isset($_GET['department']) ? intval($_GET['department']) : 0);
$selectedEmployeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : (isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0);
$selectedEmployeeCode = isset($_POST['employee_code']) ? $_POST['employee_code'] : (isset($_GET['employee_code']) ? $_GET['employee_code'] : '');

// Validate month/year
$selectedMonth = max(1, min(12, $selectedMonth));
$selectedYear = max(2000, min(2099, $selectedYear));

// Calculate start/end dates of the month
$monthStart = new DateTime("$selectedYear-$selectedMonth-01");
$monthEnd = clone $monthStart;
$monthEnd->modify('last day of this month');

// Current date with timezone
$today = new DateTime('now', new DateTimeZone('Africa/Johannesburg'));

// Public holidays array
$publicHolidays = [
    '2024-01-01' => "New Year's Day",
    '2024-03-21' => 'Human Rights Day',
    '2024-04-27' => 'Freedom Day',
    '2024-05-01' => "Worker's Day",
    '2024-06-16' => 'Youth Day',
    '2024-08-09' => "Women's Day",
    '2024-09-24' => 'Heritage Day',
    '2024-12-16' => 'Day of Reconciliation',
    '2024-12-25' => 'Christmas Day',
    '2024-12-26' => 'Day of Goodwill',
    '2024-03-29' => 'Good Friday',
    '2024-04-01' => 'Easter Monday',
    '2025-01-01' => "New Year's Day",
    '2025-03-21' => 'Human Rights Day',
    '2025-04-27' => 'Freedom Day',
    '2025-05-01' => "Worker's Day",
    '2025-06-16' => 'Youth Day',
    '2025-08-09' => "Women's Day",
    '2025-09-24' => 'Heritage Day',
    '2025-12-16' => 'Day of Reconciliation',
    '2025-12-25' => 'Christmas Day',
    '2025-12-26' => 'Day of Goodwill',
    '2025-04-18' => 'Good Friday',
    '2025-04-21' => 'Easter Monday',
];

// Fetch departments
$departmentsQuery = "SELECT id, name FROM department_list WHERE status = 1";
$departmentsResult = $conn->query($departmentsQuery);
$departments = [];
while ($row = $departmentsResult->fetch_assoc()) {
    $departments[] = $row;
}

// Fetch employees
$employeesQuery = "
    SELECT e.id, e.fullname, e.employee_code, e.department_id, d.name AS department_name
    FROM employee_list e
    LEFT JOIN department_list d ON e.department_id = d.id
    WHERE e.status = 1
    ORDER BY e.fullname ASC
";
$employeesResult = $conn->query($employeesQuery);
$employees = [];
$employeeCodeToIdMap = [];
while ($emp = $employeesResult->fetch_assoc()) {
    $employees[] = $emp;
    $employeeCodeToIdMap[$emp['employee_code']] = $emp['id'];
}

// Map employee code to id if provided and no employee_id set
if (!empty($selectedEmployeeCode) && $selectedEmployeeId == 0) {
    if (isset($employeeCodeToIdMap[$selectedEmployeeCode])) {
        $selectedEmployeeId = $employeeCodeToIdMap[$selectedEmployeeCode];
    }
}

// Prepare calendars for each employee filtered
$employeeCalendars = [];

foreach ($employees as $employee) {
    if ($selectedDepartment > 0 && $employee['department_id'] != $selectedDepartment) {
        continue;
    }
    if ($selectedEmployeeId > 0 && $employee['id'] != $selectedEmployeeId) {
        continue;
    }
    if ($selectedEmployeeId == 0 && !empty($selectedEmployeeCode) && $employee['employee_code'] !== $selectedEmployeeCode) {
        continue;
    }

    $employeeId = $employee['id'];

    // Get logs grouped by date with min time_in and max time_out and work_from_home status
    $logsQuery = "
        SELECT DATE(date_created) AS log_date, 
               MIN(CASE WHEN type = 1 THEN date_created END) AS time_in, 
               MAX(CASE WHEN type = 2 THEN date_created END) AS time_out,
               MAX(work_from_home) AS work_from_home
        FROM logs
        WHERE employee_id = $employeeId
          AND DATE(date_created) BETWEEN '{$monthStart->format('Y-m-d')}' AND '{$monthEnd->format('Y-m-d')}'
        GROUP BY DATE(date_created)
    ";
    $logsResult = $conn->query($logsQuery);
    $logData = [];
    while ($log = $logsResult->fetch_assoc()) {
        $dateKey = $log['log_date'];
        $logData[$dateKey] = [
            'time_in' => $log['time_in'] ? date('H:i', strtotime($log['time_in'])) : '',
            'time_out' => $log['time_out'] ? date('H:i', strtotime($log['time_out'])) : '',
            'work_from_home' => $log['work_from_home']
        ];
    }

    // Build calendar array for this employee
    $calendar = [];
    $currentDay = clone $monthStart;

    // Empty days before first day of month (Monday=1)
    $firstDayOfMonth = $monthStart->format('N');
    for ($i = 1; $i < $firstDayOfMonth; $i++) {
        $calendar[] = ['date' => '', 'class' => '', 'time_info' => '', 'holiday_info' => ''];
    }

    // Loop days in month
    while ($currentDay <= $monthEnd) {
        $dateStr = $currentDay->format('Y-m-d');
        $dayLog = $logData[$dateStr] ?? null;
        $class = '';
        $timeInfo = '';
        $holidayInfo = '';

        if (array_key_exists($dateStr, $publicHolidays)) {
            $class = 'public-holiday';
            $holidayInfo = $publicHolidays[$dateStr];
        } else {
            $dayOfWeek = $currentDay->format('N');
            if ($dayOfWeek >= 6) { // Sat or Sun (weekend)
                $class = ''; // no special class for weekends
            } else {
                $currentDateStr = $currentDay->format('Y-m-d');
                $todayDateStr = $today->format('Y-m-d');
                
                if ($currentDateStr > $todayDateStr) {
                    // Future date
                    $class = '';
                } elseif ($currentDateStr == $todayDateStr) {
                    // Today's date - special handling
                    if ($dayLog) {
                        if ($dayLog['work_from_home']) {
                            $class = 'work-from-home';
                            $timeInfo = 'W-F-H';
                        } elseif ($dayLog['time_in'] && $dayLog['time_out']) {
                            $class = 'present-day';
                            $timeInfo = 'In: ' . $dayLog['time_in'] . ' Out: ' . $dayLog['time_out'];
                        } elseif ($dayLog['time_in']) {
                            $class = 'present-day'; // Only clocked in so far
                            $timeInfo = 'In: ' . $dayLog['time_in'];
                        } else {
                            // Check if it's still working hours
                            $currentHour = $today->format('H');
                            if ($currentHour < 9) { // Before work starts
                                $class = ''; // Not marked yet
                            } else {
                                $class = 'NotIn';
                                $timeInfo = 'Not In';
                            }
                        }
                    } else {
                        // Check if it's still working hours
                        $currentHour = $today->format('H');
                        if ($currentHour < 9) { // Before work starts
                            $class = ''; // Not marked yet
                        } else {
                            $class = 'NotIn';
                            $timeInfo = 'Not In';
                        }
                    }
                } else {
                    // Past date
                    if ($dayLog) {
                        if ($dayLog['work_from_home']) {
                            $class = 'work-from-home';
                            $timeInfo = 'W-F-H';
                        } elseif ($dayLog['time_in'] && $dayLog['time_out']) {
                            $class = 'present-day';
                            $timeInfo = 'In: ' . $dayLog['time_in'] . ' Out: ' . $dayLog['time_out'];
                        } else {
                            $class = 'NotIn';
                            $timeInfo = 'Not In';
                        }
                    } else {
                        $class = 'NotIn';
                        $timeInfo = 'Not In';
                    }
                }
            }
        }

        $calendar[] = [
            'date' => $currentDay->format('j'),
            'class' => $class,
            'time_info' => $timeInfo,
            'holiday_info' => $holidayInfo
        ];
        $currentDay->modify('+1 day');
    }

    // Fill trailing days to complete week row (7 days)
    $totalDays = count($calendar) % 7;
    if ($totalDays > 0) {
        for ($i = $totalDays; $i < 7; $i++) {
            $calendar[] = ['date' => '', 'class' => '', 'time_info' => '', 'holiday_info' => ''];
        }
    }

    $employeeCalendars[$employee['fullname']] = [
        'employee_code' => $employee['employee_code'],
        'department_name' => $employee['department_name'],
        'employee_id' => $employeeId,
        'calendar' => $calendar
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Monthly Attendance Overview</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        color: #333;
        margin: 0; padding: 0;
        background: url('access.png') no-repeat center center;
        background-size: contain;
        background-color: #000;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 20px;
        min-height: 100vh;
    }
    .container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: rgba(255,255,255,0.95);
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        height: auto;
        max-height: calc(100vh - 100px);
        overflow-y: auto;
    }
    h1 {
        font-size: 1.8em;
        margin-bottom: 20px;
        color: #2c3e50;
        text-align: center;
        font-weight: 700;
    }
    .logo {
        display: block;
        margin: 0 auto 15px;
        max-width: 120px;
        height: auto;
    }
    .print-btn-container {
        text-align: center;
        margin: 20px 0;
    }
    .print-btn {
        background: #3d3d3d;
        padding: 10px 20px;
        display: inline-block;
        color: white;
        font-weight: bold;
        border-radius: 5px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
    }
    .print-btn:hover {
        background: #2c2c2c;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    @media print {
        .print-btn { display: none; }
    }
    .search-box {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .search-box div {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        justify-content: center;
    }
    .search-box select,
    .search-box input {
        padding: 10px 15px;
        border: 2px solid #3498db;
        border-radius: 6px;
        font-size: 0.95em;
        background: #fff;
        transition: all 0.3s;
        min-width: 150px;
    }
    .search-box select:focus,
    .search-box input:focus {
        border-color: #e74c3c;
        outline: none;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
    }
    .search-box button {
        padding: 10px 20px;
        background: #e74c3c;
        color: white;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    .search-box button:hover {
        background: #c0392b;
        transform: translateY(-2px);
    }
    .calendar {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 2px;
        background: #e0e0e0;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .calendar .header {
        background: #3498db;
        color: white;
        font-weight: 600;
        padding: 10px;
        text-align: center;
        font-size: 0.9em;
    }
    .calendar div {
        padding: 12px;
        text-align: center;
        border: 1px solid #e0e0e0;
        box-sizing: border-box;
        position: relative;
        font-size: 0.9em;
        transition: all 0.2s;
    }
    .calendar div:hover {
        transform: scale(1.05);
        z-index: 1;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .work-from-home {
        background: #f39c12;
        color: white;
        font-weight: 600;
    }
    .present-day {
        background: #2ecc71;
        color: white;
        font-weight: 600;
    }
    .NotIn {
        background: #e74c3c;
        color: white;
        font-weight: 600;
    }
    .public-holiday {
        background: #9b59b6;
        color: white;
        font-weight: 600;
    }
    .holiday-label {
        position: absolute;
        bottom: 3px;
        left: 0;
        right: 0;
        text-align: center;
        font-size: 0.75em;
        font-weight: 600;
        color: white;
        background: rgba(0,0,0,0.7);
        border-radius: 3px;
        padding: 2px 4px;
    }
    /* Smaller font for time info */
    .calendar div small {
        display: block;
        margin-top: 4px;
        font-size: 0.75em;
        font-weight: 600;
        color: inherit; /* inherit text color */
    }
    .calendar-footer {
        grid-column: 1 / -1;
        padding: 25px 30px;
        background: #f8f9fa;
        border: 2px solid #dee2e6;
        border-radius: 0 0 10px 10px;
        margin-top: -1px;
    }
    .certification-text {
        margin-bottom: 25px;
        color: #2c3e50;
        text-align: center;
        font-size: 1.2rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .signature-container {
        display: flex;
        justify-content: space-between;
        gap: 30px;
        margin-top: 15px;
    }
    .signature-box {
        flex: 1;
        text-align: center;
    }
    .signature-label {
        display: block;
        font-size: 1.1rem;
        color: #495057;
        margin-bottom: 15px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .signature-line {
        height: 40px;
        border-bottom: 3px solid #7f8c8d;
        margin: 0 auto;
        width: 80%;
    }
    .signature-date {
        margin-top: 15px;
        font-size: 1rem;
        color: #6c757d;
    }
    @media print {
        .calendar-footer {
            padding: 20px 25px;
            border-width: 1px;
        }
        .certification-text {
            font-size: 1.1rem;
            margin-bottom: 20px;
        }
        .signature-label {
            font-size: 1rem;
        }
        .signature-line {
            height: 35px;
        }
        .print-btn {
            display: none;
        }
    }
</style>
</head>
<body>
<div class="container">
    <div class="print-btn-container">
    <button class="print-btn" onclick="window.print()">Print Calendar</button>
</div>
    <img src="Progression-Butterfly.png" class="logo" alt="Company Logo" />
    <h1>Monthly Attendance Overview</h1>

    <form method="post" class="search-box">
        <div>
            <select name="month">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m == $selectedMonth ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                    </option>
                <?php endfor; ?>
            </select>

            <select name="year">
                <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                    <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>>
                        <?= $y ?>
                    </option>
                <?php endfor; ?>
            </select>

            <button type="submit">Apply Filters</button>
        </div>
    </form>

    <?php if (empty($employeeCalendars)): ?>
        <div class="alert alert-info text-center" role="alert">
            No attendance records found for the selected criteria.
        </div>
    <?php else: ?>
        <?php foreach ($employeeCalendars as $fullname => $data): ?>
            <div class="employee-calendar">
                <h3 style="font-size: 1.3em; color: #34495e; margin-top: 35px; margin-bottom: 15px;">
                    <?= htmlspecialchars($fullname) ?> (<?= htmlspecialchars($data['employee_code']) ?>) — <?= htmlspecialchars($data['department_name']) ?>
                </h3>
                <div class="calendar">
                    <div class="header">Mon</div>
                    <div class="header">Tue</div>
                    <div class="header">Wed</div>
                    <div class="header">Thu</div>
                    <div class="header">Fri</div>
                    <div class="header">Sat</div>
                    <div class="header">Sun</div>

                    <?php foreach ($data['calendar'] as $day): ?>
                        <div class="<?= $day['class'] ?>" title="<?= htmlspecialchars($day['time_info'] . ($day['holiday_info'] ? ' - ' . $day['holiday_info'] : '')) ?>">
                            <?= $day['date'] ?>
                            <?php if (!empty($day['time_info'])): ?>
                                <br><small><?= htmlspecialchars($day['time_info']) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($day['holiday_info'])): ?>
                                <div class="holiday-label"><?= htmlspecialchars($day['holiday_info']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
    // Session inactivity timeout - 4 minutes = 240000 ms
    (function() {
        let timer;
        function resetTimer() {
            clearTimeout(timer);
            timer = setTimeout(logout, 240000);
        }
        function logout() {
            alert('Session expired due to inactivity.');
            window.location.href = '/logout.php'; // Change to your logout URL
        }
        window.onload = resetTimer;
        document.onmousemove = resetTimer;
        document.onkeydown = resetTimer;
        document.onclick = resetTimer;
        document.onscroll = resetTimer;
    })();

    document.querySelector('form.search-box').addEventListener('submit', function(e) {
        // Optional: add form validation or effects here
    });
</script>
</body>
</html>

<?php
unset($db); // Close DB connection
?>