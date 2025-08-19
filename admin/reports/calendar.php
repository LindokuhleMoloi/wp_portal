<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get selected month, year, employee filter, and department filter
$selectedMonth = isset($_POST['month']) ? intval($_POST['month']) : date('n');
$selectedYear = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
$selectedDepartment = isset($_POST['department']) ? intval($_POST['department']) : 0;
$selectedEmployeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$selectedEmployeeCode = isset($_POST['employee_code']) ? $_POST['employee_code'] : '';

// Validate month and year
$selectedMonth = max(1, min(12, $selectedMonth));
$selectedYear = max(2000, min(2099, $selectedYear));

// Calculate start and end dates of the month
$monthStart = new DateTime("$selectedYear-$selectedMonth-01");
$monthEnd = clone $monthStart;
$monthEnd->modify('last day of this month');

// Public holidays from January 2024 to December 2100 (Example)
$publicHolidays = [
    // Fixed date holidays
    '2024-01-01' => 'New Year\'s Day',
    '2024-03-21' => 'Human Rights Day',
    '2024-04-27' => 'Freedom Day',
    '2024-05-01' => 'Worker\'s Day',
    '2024-06-16' => 'Youth Day',
    '2024-08-09' => 'Women\'s Day',
    '2024-09-24' => 'Heritage Day',
    '2024-12-16' => 'Day of Reconciliation',
    '2024-12-25' => 'Christmas Day',
    '2024-12-26' => 'Day of Goodwill',
    
    // Easter holidays 2024
    '2024-03-29' => 'Good Friday',
    '2024-04-01' => 'Easter Monday',
    
    // 2025 holidays
    '2025-01-01' => 'New Year\'s Day',
    '2025-03-21' => 'Human Rights Day',
    '2025-04-27' => 'Freedom Day',
    '2025-05-01' => 'Worker\'s Day',
    '2025-06-16' => 'Youth Day',
    '2025-08-09' => 'Women\'s Day',
    '2025-09-24' => 'Heritage Day',
    '2025-12-16' => 'Day of Reconciliation',
    '2025-12-25' => 'Christmas Day',
    '2025-12-26' => 'Day of Goodwill',
    
    // Easter holidays 2025
    '2025-04-18' => 'Good Friday',
    '2025-04-21' => 'Easter Monday',
];

// Fetch unique departments for dropdown
$departmentsQuery = "SELECT id, name FROM department_list WHERE status = 1";
$departmentsResult = $conn->query($departmentsQuery);
$departments = [];
while ($department = $departmentsResult->fetch_assoc()) {
    $departments[] = $department;
}

// Fetch employees with their departments
$employeesQuery = "
    SELECT e.id, e.fullname, e.employee_code, e.department_id, d.name AS department_name
    FROM employee_list e
    LEFT JOIN department_list d ON e.department_id = d.id
    WHERE e.status = 1
    ORDER BY e.fullname ASC
";

$employeesResult = $conn->query($employeesQuery);
$employees = [];
while ($employee = $employeesResult->fetch_assoc()) {
    $employees[] = $employee;
}

// Prepare data for each employee's calendar
$employeeCalendars = [];

foreach ($employees as $employee) {
    // Filter by selected department
    if ($selectedDepartment > 0 && $employee['department_id'] != $selectedDepartment) {
        continue;
    }

    // Filter by selected employee ID
    if ($selectedEmployeeId > 0 && $employee['id'] != $selectedEmployeeId) {
        continue;
    }

    // Filter by employee code (if provided)
    if (!empty($selectedEmployeeCode) && strpos($employee['employee_code'], $selectedEmployeeCode) === false) {
        continue;
    }

    $employeeId = $employee['id'];
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

    $logs = $conn->query($logsQuery);

    // Prepare logged-in dates and times
    $logData = [];
    while ($log = $logs->fetch_assoc()) {
        $date = date('Y-m-d', strtotime($log['log_date']));
        $logData[$date] = [
            'time_in' => date('H:i', strtotime($log['time_in'])),
            'time_out' => date('H:i', strtotime($log['time_out'])),
            'work_from_home' => $log['work_from_home']
        ];
    }

    // Create calendar for this employee
    $calendar = [];
    $currentDay = clone $monthStart;
    $today = new DateTime(); // Today's date

    // Add empty days before the start of the month
    $firstDayOfMonth = $monthStart->format('N'); // 1 (Monday) to 7 (Sunday)
    for ($i = 1; $i < $firstDayOfMonth; $i++) {
        $calendar[] = ['date' => '', 'class' => '', 'time_info' => ''];
    }

    // Populate the calendar with the days of the month
    while ($currentDay <= $monthEnd) {
        $formattedDate = $currentDay->format('Y-m-d');
        $logInfo = isset($logData[$formattedDate]) ? $logData[$formattedDate] : null;
        $class = '';
        $timeInfo = '';
        $holidayInfo = '';

        // Check for public holidays
        if (array_key_exists($formattedDate, $publicHolidays)) {
            $class = 'public-holiday'; // Special class for public holidays
            $holidayInfo = $publicHolidays[$formattedDate];
        } else {
            $dayOfWeek = $currentDay->format('N'); // 1 (Monday) to 7 (Sunday)
            if ($dayOfWeek == 6 || $dayOfWeek == 7) {
                // Weekend days
                $class = ''; // Plain white
            } else {
                if ($currentDay > $today) {
                    // Future dates
                    $class = ''; // Plain white
                } else {
                    if ($logInfo) {
                        if ($logInfo['work_from_home']) {
                            $class = 'work-from-home'; // Yellow for work-from-home days
                            $timeInfo = 'W-F-H';
                        } elseif ($logInfo['time_in'] && $logInfo['time_out']) {
                            $class = 'present-day'; // Green for present days
                            $timeInfo = 'In: ' . $logInfo['time_in'] . ' Out: ' . $logInfo['time_out'];
                        } else {
                            $class = 'NotIn'; // Red for absent days
                            $timeInfo = 'Not In';
                        }
                    } else {
                        $class = 'NotIn'; // Red for absent days
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

    // Add empty days after the end of the month to complete the grid
    $totalDays = count($calendar) % 7;
    if ($totalDays > 0) {
        for ($i = $totalDays; $i < 7; $i++) {
            $calendar[] = ['date' => '', 'class' => '', 'time_info' => ''];
        }
    }

    $employeeCalendars[$employee['fullname']] = [
        'employee_code' => $employee['employee_code'],
        'department_name' => $employee['department_name'],
        'employee_id' => $employee['id'],
        'calendar' => $calendar
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Attendance Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background: #f5f7fa;
            height: 100vh;
            overflow: hidden;
        }
        .container {
            max-width: 1200px; 
            margin: 20px auto; 
            padding: 20px;
            background: #fff; 
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 100px);
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
        
        /* Styled Filters */
        .search-box {
            background: #ffffff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .search-box div {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
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
        
        /* Calendar Styles */
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
        
        /* Bright Status Colors */
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
            background: rgba(0, 0, 0, 0.7);
            border-radius: 3px;
            padding: 2px 4px;
        }
        
        /* Print Button */
        .print-button {
            margin: 15px 0;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .print-button:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        /* Compact Footer Styles */
        .calendar-footer {
            grid-column: 1 / -1;
            padding: 12px 15px;
            background: #ecf0f1;
            border: 1px solid #bdc3c7;
            border-radius: 0 0 8px 8px;
            font-size: 0.85em;
        }
        .certification-text {
            margin-bottom: 10px;
            color: #2c3e50;
            font-style: italic;
            text-align: center;
            font-weight: 500;
        }
        .signature-container {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        .signature-box {
            flex: 1;
        }
        .signature-label {
            display: block;
            font-size: 0.8em;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .signature-line {
            height: 25px;
            border-bottom: 2px solid #95a5a6;
        }
         /* Enhanced Footer Styles */
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
    }
    </style>
</head>
<body>
    <div class="container">
        <img src="reports/Progression-Butterfly.png" alt="Company Logo" class="logo"> 
        <h1>Monthly Attendance Overview</h1>
        
        <form method="post" class="search-box">
            <div>
                <select name="month">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $selectedMonth ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                    <?php endfor; ?>
                </select>
                <select name="year">
                    <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <select name="department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= $department['id'] ?>" <?= $department['id'] == $selectedDepartment ? 'selected' : '' ?>><?= $department['name'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="employee_id">
                    <option value="">Select Employee</option>
                    <?php foreach ($employees as $employee): ?>
                        <option value="<?= $employee['id'] ?>" <?= $employee['id'] == $selectedEmployeeId ? 'selected' : '' ?>><?= $employee['fullname'] ?> (<?= $employee['employee_code'] ?>)</option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="employee_code" placeholder="Filter by Employee Code" value="<?= htmlspecialchars($selectedEmployeeCode) ?>" />
                <button type="submit">Apply Filters</button>
          
            </div>
        </form>

        <?php foreach ($employeeCalendars as $fullname => $data): ?>
            <div class="employee-calendar">
                <h3 style="font-size: 1.3em; margin-bottom: 10px; color: #7f8c8d;">
                    <?= $fullname ?> <span style="color: #7f8c8d; font-size: 0.9em;">(<?= $data['employee_code'] ?>)</span>
                    <div style="font-size: 0.8em; color: #7f8c8d;"><?= $data['department_name'] ?></div>
                </h3>
                
                <div class="print-summary" style="margin-bottom: 15px;">
                    <div style="display: flex; gap: 15px; font-size: 0.9em;">
                        <div><strong style="color: #7f8c8d;">Month:</strong> <?= date('F Y', mktime(0, 0, 0, $selectedMonth, 1)) ?></div>
                    </div>
                </div>
                
             
                
                <div class="calendar">
                    <div class="header">Mon</div>
                    <div class="header">Tue</div>
                    <div class="header">Wed</div>
                    <div class="header">Thu</div>
                    <div class="header">Fri</div>
                    <div class="header">Sat</div>
                    <div class="header">Sun</div>
                    <?php foreach ($data['calendar'] as $day): ?>
                        <div class="<?= $day['class'] ?>">
                            <span class="date"><?= $day['date'] ?></span>
                            <span class="time-info"><?= $day['time_info'] ?></span>
                            <?php if (!empty($day['holiday_info'])): ?>
                                <div class="holiday-label"><?= $day['holiday_info'] ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Footer Section -->
                    <div class="calendar-footer">
    <div class="certification-text">
        I HEREBY CERTIFY THAT THE ABOVE ATTENDANCE RECORD IS ACCURATE AND COMPLETE
    </div>
    <div class="signature-container">
        <div class="signature-box">
            <span class="signature-label">EMPLOYEE SIGNATURE</span>
            <div class="signature-line"></div>
            <div class="signature-date">Date: ___________________</div>
        </div>
        <div class="signature-box">
            <span class="signature-label">MANAGER/SUPERVISOR APPROVAL</span>
            <div class="signature-line"></div>
            <div class="signature-date">Date: ___________________</div>
        </div>
    </div>
</div>
                </div>
                
                <div style="height: 20px;"></div> <!-- Spacer -->
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Make print button work
        document.querySelectorAll('.print-button').forEach(button => {
            button.addEventListener('click', function() {
                const employeeId = this.closest('.employee-calendar').querySelector('[name="employee_id"]')?.value || '';
                const month = <?= $selectedMonth ?>;
                const year = <?= $selectedYear ?>;
                window.open(`view.php?employee_id=${employeeId}&month=${month}&year=${year}`, '_blank');
            });
        });
 
    </script>

</body>
</html>