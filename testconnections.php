<?php
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$result = $conn->query("SHOW VARIABLES LIKE 'max_connections'");
$row = $result->fetch_assoc();
echo "Max connections: " . $row['Value'] . "<br>";

// How many connections currently used
$result2 = $conn->query("SHOW STATUS WHERE `variable_name` = 'Threads_connected'");
$row2 = $result2->fetch_assoc();
echo "Current connections: " . $row2['Value'];

$conn->close();