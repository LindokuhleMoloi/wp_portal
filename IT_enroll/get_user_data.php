<?php
// Database connection
$servername = "localhost"; // Your server name
$username = "tarryn_Lindokuhle"; // Your username
$password = "L1nd0kuhle"; // Your password
$dbname = "tarryn_workplaceportal"; // Your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$employee_code = $_GET['employee_code'];

// Prepare and execute the SQL query to fetch user data
$sql = "SELECT e.fullname, e.employee_code, i.issue_description, i.created_at 
        FROM employee_list e
        LEFT JOIN helpdesk_support_incoming i ON e.employee_code = i.employee_code
        WHERE e.employee_code = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $employee_code);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the results
$userInfo = [];
if ($row = $result->fetch_assoc()) {
    $userInfo['fullname'] = $row['fullname'];
    $userInfo['employee_code'] = $row['employee_code'];
    $userInfo['pendingIssues'] = [];

    // Fetch all issues for the employee
    do {
        if ($row['issue_description']) { // Check if issue_description is not null
            $userInfo['pendingIssues'][] = [
                'description' => $row['issue_description'],
                'created_at' => $row['created_at'],
            ];
        }
    } while ($row = $result->fetch_assoc());
}

// Return the user data as JSON
header('Content-Type: application/json');
echo json_encode($userInfo);

// Close the connection
$conn->close();
?>
