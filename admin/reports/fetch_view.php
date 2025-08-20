<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch employee details
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$sql = "SELECT fullname, employee_code, other_details FROM employee_list WHERE id = $id";
$result = $conn->query($sql);

header('Content-Type: text/html; charset=utf-8');

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo '<div class="employee-details">';
    echo '<p><strong>Full Name:</strong> ' . htmlspecialchars($row['fullname']) . '</p>';
    echo '<p><strong>Employee Code:</strong> ' . htmlspecialchars($row['employee_code']) . '</p>';
    echo '<p><strong>Other Details:</strong> ' . htmlspecialchars($row['other_details']) . '</p>';
    echo '</div>';
} else {
    echo '<p>No details found for this employee.</p>';
}

$conn->close();
?>
