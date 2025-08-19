<?php
// Database connection
$conn = new mysqli("localhost", "tarryn_Lindokuhle", "L1nd0kuhle", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filters from POST request
$employeeCode = isset($_POST['employee_code']) ? $_POST['employee_code'] : '';
$startDate = isset($_POST['start_date']) ? $_POST['start_date'] : '';
$endDate = isset($_POST['end_date']) ? $_POST['end_date'] : '';

// Fetch logs based on filters
$logs = [];
if (!empty($employeeCode) && !empty($startDate) && !empty($endDate)) {
    $query = "
        SELECT id, employee_id, date_created, type, work_from_home 
        FROM logs 
        WHERE employee_id = (SELECT id FROM employee_list WHERE employee_code = '$employeeCode')
          AND DATE(date_created) BETWEEN '$startDate' AND '$endDate'
        ORDER BY date_created ASC
    ";
    $result = $conn->query($query);
    while ($log = $result->fetch_assoc()) {
        $logs[] = $log;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Logs</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .filter-box {
            margin-bottom: 20px;
        }
        .filter-box input, .filter-box button {
            margin-right: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Edit Logs</h1>
        <form method="post" class="filter-box">
            <input type="text" name="employee_code" placeholder="Employee Code" value="<?= htmlspecialchars($employeeCode) ?>" required>
            <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" required>
            <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" required>
            <button type="submit">Filter</button>
        </form>

        <?php if (!empty($logs)): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee ID</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Work From Home</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><?= $log['employee_id'] ?></td>
                            <td><?= date('Y-m-d H:i:s', strtotime($log['date_created'])) ?></td>
                            <td><?= $log['type'] == 1 ? 'Time In' : 'Time Out' ?></td>
                            <td><?= $log['work_from_home'] ? 'Yes' : 'No' ?></td>
                            <td>
                                <a href="edit_log.php?id=<?= $log['id'] ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No logs found for the given filters.</p>
        <?php endif; ?>
    </div>
</body>
</html>