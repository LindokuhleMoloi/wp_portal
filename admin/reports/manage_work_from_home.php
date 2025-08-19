<?php
// Database connection
$conn = new mysqli("localhost", "tarryn_Lindokuhle", "L1nd0kuhle", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $employeeId = $_POST['employee_id'];
        $requestDate = $_POST['request_date'];
        $status = $_POST['status'];
        $reason = $_POST['reason'];

        $stmt = $conn->prepare("INSERT INTO work_from_home (employee_id, request_date, status, reason) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $employeeId, $requestDate, $status, $reason);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['update'])) {
        $id = $_POST['id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("UPDATE work_from_home SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM work_from_home WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

// Fetch work-from-home requests
$requestsQuery = "
    SELECT w.id, e.fullname, w.request_date, w.status, w.reason 
    FROM work_from_home w
    JOIN employee_list e ON w.employee_id = e.id
    ORDER BY w.request_date DESC
";
$requests = $conn->query($requestsQuery);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Work from Home Requests</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 2em;
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background: #007bff;
            color: white;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-group button {
            padding: 10px 20px;
            border-radius: 4px;
            border: none;
            background-color: #007bff;
            color: white;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #0056b3;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manage Work from Home Requests</h1>

        <!-- Add New Request Form -->
        <form method="POST">
            <h2>Add New Request</h2>
            <div class="form-group">
                <label for="employee_id">Employee</label>
                <select name="employee_id" id="employee_id" required>
                    <?php
                    // Fetch employees for dropdown
                    $employeesQuery = "SELECT id, fullname FROM employee_list ORDER BY fullname ASC";
                    $employees = $conn->query($employeesQuery);
                    while ($employee = $employees->fetch_assoc()) {
                        echo "<option value='{$employee['id']}'>{$employee['fullname']}</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label for="request_date">Request Date</label>
                <input type="date" name="request_date" id="request_date" required>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" required>
                    <option value="Pending">Pending</option>
                    <option value="Approved">Approved</option>
                    <option value="Rejected">Rejected</option>
                </select>
            </div>
            <div class="form-group">
                <label for="reason">Reason</label>
                <textarea name="reason" id="reason" rows="4"></textarea>
            </div>
            <div class="form-group">
                <button type="submit" name="add">Add Request</button>
            </div>
        </form>

        <!-- Existing Requests Table -->
        <h2>Existing Requests</h2>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Request Date</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($request = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($request['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($request['request_date']); ?></td>
                        <td><?php echo htmlspecialchars($request['status']); ?></td>
                        <td><?php echo htmlspecialchars($request['reason']); ?></td>
                        <td>
                            <!-- Update Status Form -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                <select name="status" required>
                                    <option value="Pending" <?php if ($request['status'] === 'Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Approved" <?php if ($request['status'] === 'Approved') echo 'selected'; ?>>Approved</option>
                                    <option value="Rejected" <?php if ($request['status'] === 'Rejected') echo 'selected'; ?>>Rejected</option>
                                </select>
                                <button type="submit" name="update">Update</button>
                            </form>
                            <!-- Delete Button -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($request['id']); ?>">
                                <button type="submit" name="delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Add any necessary JavaScript here
    </script>
</body>
</html>

<?php
$conn->close();
?>
