<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get and validate parameters
$employeeId = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

$selectedMonth = max(1, min(12, $selectedMonth));
$selectedYear = max(2000, min(2099, $selectedYear));

// Fetch employee details
$employeeQuery = "SELECT e.fullname, e.employee_code, d.name AS department_name
                 FROM employee_list e
                 LEFT JOIN department_list d ON e.department_id = d.id
                 WHERE e.id = $employeeId";
$employeeResult = $conn->query($employeeQuery);
$employee = $employeeResult->fetch_assoc();

if (!$employee) die("Employee not found");

// Date calculations
$monthStart = new DateTime("$selectedYear-$selectedMonth-01");
$monthEnd = clone $monthStart;
$monthEnd->modify('last day of this month');

// Fetch attendance data
$logsQuery = "SELECT DATE(date_created) AS log_date, 
              MIN(CASE WHEN type = 1 THEN date_created END) AS time_in, 
              MAX(CASE WHEN type = 2 THEN date_created END) AS time_out,
              MAX(work_from_home) AS work_from_home
              FROM logs 
              WHERE employee_id = $employeeId 
              AND DATE(date_created) BETWEEN '{$monthStart->format('Y-m-d')}' AND '{$monthEnd->format('Y-m-d')}'
              GROUP BY DATE(date_created)";
$logsResult = $conn->query($logsQuery);
$logData = [];
while ($log = $logsResult->fetch_assoc()) {
    $logData[$log['log_date']] = $log;
}

// Public holidays (example for 2024)
$publicHolidays = [
    '2024-01-01' => 'New Year\'s Day',
    // Add other holidays as needed
];

// Generate days array
$currentDay = clone $monthStart;
$days = [];
while ($currentDay <= $monthEnd) {
    $formattedDate = $currentDay->format('Y-m-d');
    $dayOfWeek = $currentDay->format('D');
    
    $days[] = [
        'date' => $currentDay->format('j'),
        'day' => $dayOfWeek,
        'full_date' => $formattedDate,
        'is_holiday' => isset($publicHolidays[$formattedDate]),
        'holiday_name' => $publicHolidays[$formattedDate] ?? '',
        'is_weekend' => in_array($dayOfWeek, ['Sat', 'Sun']),
        'time_in' => '',
        'time_out' => '',
        'absent' => true
    ];
    $currentDay->modify('+1 day');
}

// Populate log data
foreach ($days as &$day) {
    if (isset($logData[$day['full_date']])) {
        $log = $logData[$day['full_date']];
        $day['time_in'] = $log['time_in'] ? date('H:i', strtotime($log['time_in'])) : '';
        $day['time_out'] = $log['time_out'] ? date('H:i', strtotime($log['time_out'])) : '';
        $day['absent'] = false;
    }
}
unset($day);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Register - <?= htmlspecialchars($employee['fullname']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: center;
        }

        .register-container {
            width: 100%;
            max-width: 1200px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
            vertical-align: middle;
        }

        th {
            background: #007bff;
            color: white;
            font-weight: bold;
        }

        .employee-details {
            text-align: left;
            padding: 20px !important;
            background: #f8f9fa;
        }

        .weekend {
            background: #e6e6fa;
            color: #4b0082;
        }

        .holiday {
            background: #9370db;
            color: white;
        }

        .signature-box {
            height: 40px;
            border-bottom: 1px solid #000;
        }

        .checkbox-container {
            display: flex;
            justify-content: center;
            gap: 8px;
        }

        .checkbox-option {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .checkbox-box {
            width: 16px;
            height: 16px;
            border: 1px solid #000;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .register-container {
                box-shadow: none;
                padding: 10px;
            }
            .weekend, .holiday {
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <table>
            <thead>
                <tr>
                    <th colspan="9" style="font-size: 1.5em; padding: 25px;">
                        PROGRESSION - ATTENDANCE REGISTER
                    </th>
                </tr>
                <tr>
                    <td colspan="9" class="employee-details">
                        <div style="line-height: 1.8;">
                            <strong>LEARNER NAME:</strong> <?= htmlspecialchars($employee['fullname']) ?><br>
                            <strong>LEAD EMPLOYER:</strong> Progression (Pty) Ltd<br>
                            <strong>HOST EMPLOYER:</strong> [Host Employer Name]<br>
                            <strong>QUALIFICATION:</strong> [Qualification Name]
                        </div>
                        <div style="margin-top: 15px;">
                            <strong>PERIOD:</strong> <?= htmlspecialchars($monthStart->format('F Y')) ?><br>
                            <strong>EMPLOYEE CODE:</strong> <?= htmlspecialchars($employee['employee_code']) ?><br>
                            <strong>DEPARTMENT:</strong> <?= htmlspecialchars($employee['department_name']) ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Signature</th>
                    <th colspan="2">Time</th>
                    <th>Reason if Absent</th>
                    <th colspan="2">Type of Leave</th>
                    <th>Note Supplied</th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>In</th>
                    <th>Out</th>
                    <th></th>
                    <th>Paid</th>
                    <th>Unpaid</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $day): ?>
                <tr class="<?= $day['is_weekend'] ? 'weekend' : '' ?> <?= $day['is_holiday'] ? 'holiday' : '' ?>">
                    <td><?= $day['date'] ?></td>
                    <td><?= strtoupper($day['day']) ?></td>
                    <td class="signature-box"></td>
                    <td><?= htmlspecialchars($day['time_in']) ?></td>
                    <td><?= htmlspecialchars($day['time_out']) ?></td>
                    <td>
                        <?php if ($day['is_holiday']): ?>
                            <?= htmlspecialchars($day['holiday_name']) ?>
                        <?php elseif ($day['is_weekend']): ?>
                            Weekend
                        <?php elseif ($day['absent']): ?>
                            Absent
                        <?php endif; ?>
                    </td>
                    <td></td>
                    <td></td>
                    <td>
                        <div class="checkbox-container">
                            <div class="checkbox-option">
                                <div class="checkbox-box">□</div>
                                <small>Yes</small>
                            </div>
                            <div class="checkbox-option">
                                <div class="checkbox-box">□</div>
                                <small>No</small>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="9" style="padding: 20px; background: #f8f9fa;">
                        <div style="margin-bottom: 15px;">
                            <strong>KEY:</strong> 
                            A = Annual, S = Sick, RA = Reasonable Accommodation, 
                            FR = Family Responsibility, M = Maternity
                        </div>
                        <div style="margin-bottom: 15px;">
                            I hereby certify that the above is a correct analysis of my attendance and absence for the month.
                        </div>
                        <div style="display: flex; justify-content: space-between; gap: 20px;">
                            <div style="flex: 1;">
                                Compiled by (Name & Signature):<br>
                                <div class="signature-box" style="margin-top: 8px;"></div>
                            </div>
                            <div style="flex: 1;">
                                Authorised by Manager:<br>
                                <div class="signature-box" style="margin-top: 8px;"></div>
                            </div>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</body>
</html>