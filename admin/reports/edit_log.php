<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get log ID from URL
$logId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch log details
$log = null;
if ($logId > 0) {
    $query = "SELECT id, employee_id, date_created, type, work_from_home FROM logs WHERE id = $logId";
    $result = $conn->query($query);
    $log = $result->fetch_assoc();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $newTime = $_POST['new_time'];
    $query = "UPDATE logs SET date_created = '$newTime' WHERE id = $logId";
    if ($conn->query($query) === TRUE) {
        echo "<p>Log updated successfully.</p>";
    } else {
        echo "<p>Error updating log: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Log</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Edit Log</h1>
        <?php if ($log): ?>
            <form method="post">
                <div class="form-group">
                    <label for="new_time">New Time</label>
                    <input type="datetime-local" class="form-control" id="new_time" name="new_time" value="<?= date('Y-m-d\TH:i', strtotime($log['date_created'])) ?>" required>
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        <?php else: ?>
            <p>Log not found.</p>
        <?php endif; ?>
    </div>
</body>
</html>