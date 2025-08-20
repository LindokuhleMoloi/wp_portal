<?php

// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get selected month and year or use defaults
$selectedMonth = isset($_POST['month']) ? intval($_POST['month']) : date('n');
$selectedYear = isset($_POST['year']) ? intval($_POST['year']) : date('Y');

// Validate inputs
$selectedMonth = max(1, min(12, $selectedMonth));
$selectedYear = max(2000, min(2099, $selectedYear));

// Current date for inclusion
$currentDate = date('Y-m-d');

// Query to find login records without corresponding logout records
$query = "
    SELECT e.fullname, e.employee_code, l.date_created AS login_time, l.id AS login_id
    FROM logs l
    JOIN employee_list e ON l.employee_id = e.id
    WHERE MONTH(l.date_created) = $selectedMonth
    AND YEAR(l.date_created) = $selectedYear
    AND l.type = 1
    AND NOT EXISTS (
        SELECT 1
        FROM logs l2
        WHERE l2.employee_id = l.employee_id
        AND DATE(l2.date_created) = DATE(l.date_created)
        AND l2.type = 2
    )
    ORDER BY e.fullname, l.date_created
";

// Execute the query
$result = $conn->query($query);

// Prepare data for the table
$attendanceErrors = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $attendanceErrors[] = [
            'employee_name' => $row['fullname'],
            'employee_code' => $row['employee_code'],
            'login_time' => $row['login_time'],
            'login_id' => $row['login_id'],
        ];
    }
}

// Handle corrections
$successMessage = false;

if (isset($_POST['correct_selected']) && !empty($_POST['selected_logs'])) {
    $selectedLogs = $_POST['selected_logs'];

    foreach ($selectedLogs as $loginId) {
        $loginDataQuery = "SELECT employee_id, DATE(date_created) AS login_date FROM logs WHERE id = $loginId";
        $loginDataResult = $conn->query($loginDataQuery);
        
        if ($loginDataResult && $loginDataResult->num_rows > 0) {
            $loginData = $loginDataResult->fetch_assoc();
            $employeeId = $loginData['employee_id'];
            $loginDate = $loginData['login_date'];
            $dayOfWeek = date('N', strtotime($loginDate)); 

            if ($dayOfWeek >= 1 && $dayOfWeek <= 4) {
                $randomMinute = rand(0, 7); 
                $logoutTime = "$loginDate 16:" . str_pad($randomMinute, 2, '0', STR_PAD_LEFT) . ":00";
            } elseif ($dayOfWeek == 5) {
                $randomMinute = rand(0, 10); 
                $logoutTime = "$loginDate 14:" . str_pad($randomMinute, 2, '0', STR_PAD_LEFT) . ":00";
            } else {
                continue;  
            }

            $insertQuery = "
                INSERT INTO logs (employee_id, type, date_created)
                SELECT $employeeId, 2, '$logoutTime'
                WHERE NOT EXISTS (
                    SELECT 1 FROM logs
                    WHERE employee_id = $employeeId
                    AND DATE(date_created) = '$loginDate'
                    AND type = 2
                )
            ";

            $conn->query($insertQuery);
        }
    }

    $currentDayQuery = "
        INSERT INTO logs (employee_id, type, date_created)
        SELECT l.employee_id, 2, CONCAT(CURDATE(), ' 14:', LPAD(FLOOR(RAND() * 11), 2, '0'), ':00')
        FROM logs l
        WHERE l.type = 1
        AND DATE(l.date_created) = CURDATE()
        AND NOT EXISTS (
            SELECT 1 FROM logs l2
            WHERE l2.employee_id = l.employee_id
            AND DATE(l2.date_created) = CURDATE()
            AND l2.type = 2
        )
        AND DAYOFWEEK(CURDATE()) = 6  
    ";

    $conn->query($currentDayQuery);
    $successMessage = true;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Diligence</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <style>
        body { background-color: #f8f9fa; font-family: Arial, sans-serif; }
        .container { margin-top: 30px; }
        input[type="checkbox"] {
            width: 20px; /* Increased Size */
            height: 20px; /* Increased Size */
            margin: 0; /* Align margins */
            cursor: pointer; /* Pointer on hover */
        }
    </style>
</head>
<body>
<div class="container">
    <h1 class="text-center">You can log out everyone</h1>

    <!-- Filter Form -->
    <form method="post" class="form-inline justify-content-center mb-4">
        <div class="form-group mx-2">
            <label for="month" class="mr-2">Month:</label>
            <select id="month" name="month" class="form-control">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $m == $selectedMonth ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="form-group mx-2">
            <label for="year" class="mr-2">Year:</label>
            <select id="year" name="year" class="form-control">
                <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                    <option value="<?= $y ?>" <?= $y == $selectedYear ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary mx-2">Filter</button>
    </form>

    <!-- Results Table -->
    <?php if (!empty($attendanceErrors)): ?>
        <form method="post">
            <table id="errorsTable" class="table table-bordered table-hover">
                <thead class="thead-dark">
                <tr>
                    <th><input type="checkbox" onclick="toggleSelectAll(this)"></th>
                    <th>Employee Name</th>
                    <th>Employee Code</th>
                    <th>Login Time</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($attendanceErrors as $error): ?>
                    <tr>
                        <td><input type="checkbox" name="selected_logs[]" value="<?= $error['login_id'] ?>" class="select-log"></td>
                        <td><?= htmlspecialchars($error['employee_name']) ?></td>
                        <td><?= htmlspecialchars($error['employee_code']) ?></td>
                        <td><?= htmlspecialchars($error['login_time']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <button type="submit" name="correct_selected" class="btn btn-success mt-3">Logout All Selected</button>
        </form>
    <?php else: ?>
        <p class="text-center mt-4">No errors found for the selected month and year.</p>
    <?php endif; ?>
</div>

<script>
$(document).ready(function () {
    $('#errorsTable').DataTable();

    <?php if ($successMessage): ?>
        alert("Selected logouts have been corrected successfully!");
    <?php endif; ?>
});

// Function to select or deselect all checkboxes
function toggleSelectAll(source) {
    const table = $('#errorsTable').DataTable();
    const checkboxes = table.$('input.select-log'); // Get all checkboxes in DataTable
    checkboxes.prop('checked', source.checked); // Set each checkbox to checked or unchecked
}
</script>
</body>
</html>