<?php
// Database connection
$servername = "localhost";
$username = "tarryn_Lindokuhle";
$password = "L1nd0kuhle";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $sn = $_POST['sn'];
    $item_name = $_POST['item_name'];
    $type = $_POST['type'];
    $status = $_POST['status'];
    $rota_or_training = $_POST['rota_or_training'];
    $notes = $_POST['notes'];

    // Prepare and execute SQL query
    $sql = "INSERT INTO Laptops (SN, `Item name`, Type, Status, `ROTA OR Training`, Notes) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssss", $sn, $item_name, $type, $status, $rota_or_training, $notes);

    if ($stmt->execute()) {
        $message = "Device added successfully.";
    } else {
        $message = "Error: " . $stmt->error;
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add New Device</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .container {
            max-width: 600px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add New Device</h2>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="form-group">
                <label for="sn">Serial Number (SN)</label>
                <input type="text" class="form-control" id="sn" name="sn" required>
            </div>
            <div class="form-group">
                <label for="item_name">Item Name</label>
                <input type="text" class="form-control" id="item_name" name="item_name" required>
            </div>
            <div class="form-group">
                <label for="type">Type</label>
                <input type="text" class="form-control" id="type" name="type" required>
            </div>
            <div class="form-group">
                <label for="status">Status</label>
                <input type="text" class="form-control" id="status" name="status" required>
            </div>
            <div class="form-group">
                <label for="rota_or_training">ROTA OR Training</label>
                <input type="text" class="form-control" id="rota_or_training" name="rota_or_training" required>
            </div>
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea class="form-control" id="notes" name="notes"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Add Device</button>
            <a href="<?php echo base_url ?>admin/?page=maintenance/enrolling" class="btn btn-secondary">Back to Logs</a>
        </form>
    </div>
</body>
</html>
