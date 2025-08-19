<?php
// Database connection
$servername = "localhost"; // Your server name
$username = "tarryn_Lindokuhle"; // Your username
$password = "L1nd0kuhle"; // Your password
$dbname = "tarryn_workplaceportal"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare the SQL query to fetch support team members
$sql = "SELECT id, name FROM support_team"; // Adjust the table name and fields as necessary
$result = $conn->query($sql);

// Initialize an array to hold the support team members
$supportTeam = [];

// Check if there are results and fetch them
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $supportTeam[] = $row; // Add each row to the support team array
    }
}

// Set the content type to JSON
header('Content-Type: application/json');

// Return the JSON-encoded array
echo json_encode($supportTeam);

// Close the database connection
$conn->close();
?>
