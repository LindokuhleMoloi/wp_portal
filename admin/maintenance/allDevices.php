<?php
// all_devices.php

// Database connection details
$host = 'localhost';
$username = 'tarryn_Lindokuhle';
$password = 'L1nd0kuhle';
$database = 'tarryn_workplaceportal';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle filter form submission
$searchItemName = isset($_GET['item_name']) ? $_GET['item_name'] : '';
$searchType = isset($_GET['type']) ? $_GET['type'] : '';
$searchStatus = isset($_GET['status']) ? $_GET['status'] : '';

// Build SQL query with filters
$sql = "SELECT * FROM Laptops WHERE 1=1";
if ($searchItemName) {
    $sql .= " AND `Item name` LIKE '%" . $conn->real_escape_string($searchItemName) . "%'";
}
if ($searchType) {
    $sql .= " AND `Type` LIKE '%" . $conn->real_escape_string($searchType) . "%'";
}
if ($searchStatus) {
    $sql .= " AND `Status` LIKE '%" . $conn->real_escape_string($searchStatus) . "%'";
}

$result = $conn->query($sql);

// Handle delete request
if (isset($_GET['delete'])) {
    $idToDelete = $_GET['delete'];

    // Check if the device exists
    $checkSql = "SELECT * FROM Laptops WHERE id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $idToDelete);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        // Prepare delete statement
        $deleteSql = "DELETE FROM Laptops WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $idToDelete);
        
        if ($deleteStmt->execute()) {
            // Successfully deleted
            echo "<script>alert('Device deleted successfully.'); window.location.href='all_devices.php?item_name=" . urlencode($searchItemName) . "&type=" . urlencode($searchType) . "&status=" . urlencode($searchStatus) . "';</script>";
        } else {
            // Error deleting device
            echo "<script>alert('Error deleting device: " . $deleteStmt->error . "');</script>";
        }
        $deleteStmt->close();
    } else {
        echo "<script>alert('Device not found.');</script>";
    }
    $checkStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Devices</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            color: #333;
        }
        .container {
            margin-top: 20px;
        }
        .header {
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 2rem;
            color: #007bff;
            text-align: center;
        }
        .btn-flat {
            border-radius: 0;
        }
        .filter-form {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #e9ecef;
        }
        .no-data {
            text-align: center;
            padding: 20px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>All Devices</h1>
            <a href="<?php echo base_url ?>admin/?page=maintenance/adding" class="btn btn-flat btn-success"><span class="fas fa-plus"></span> Add New Device</a>
        </div>

        <!-- Filter Form -->
        <div class="filter-form">
            <form method="GET" action="">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="item_name">Item Name</label>
                        <input type="text" class="form-control" id="item_name" name="item_name" value="<?php echo htmlspecialchars($searchItemName); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="type">Type</label>
                        <input type="text" class="form-control" id="type" name="type" value="<?php echo htmlspecialchars($searchType); ?>">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="status">Status</label>
                        <input type="text" class="form-control" id="status" name="status" value="<?php echo htmlspecialchars($searchStatus); ?>">
                    </div>
                    <div class="form-group col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Devices Table -->
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Item Name</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>ROTA/Training</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    // Output data of each row
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row['SN']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Item name']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Status']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['ROTA OR Training']) . "</td>";
                        echo "<td>" . htmlspecialchars($row['Notes']) . "</td>";
                        echo "<td>
                            <a href='?delete=" . urlencode($row['id']) . "&item_name=" . urlencode($searchItemName) . "&type=" . urlencode($searchType) . "&status=" . urlencode($searchStatus) . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this device?\");'>Delete</a>
                        </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='7' class='no-data'>No devices found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
