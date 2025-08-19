<?php
session_start();

if (!isset($_SESSION['employee_code'])) {
    header("Location: supportcode_request.php");
    exit();
}

$servername = "localhost";
$username = "tarryn_Lindokuhle";
$password = "L1nd0kuhle";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$employeeCode = $_SESSION['employee_code'];

$stmt = $conn->prepare("SELECT name FROM support_team WHERE employee_code = ?");
$stmt->bind_param("s", $employeeCode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $employee = $result->fetch_assoc();
    $name = htmlspecialchars($employee['name']);
    $_SESSION['name'] = $name;
} else {
    $name = "Support Member";
    $_SESSION['name'] = $name;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Team Dashboard</title>
    <style>
      body {
    margin: 0;
    font-family: 'Roboto', sans-serif;
    background: #121212; /* Dark background */
    color: #e0e0e0;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    height: 100vh;
    padding-bottom: 80px;
}

.container {
    max-width: 900px;
    width: 95%;
    padding: 40px;
    background: #1e1e1e; /* Darker container background */
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
    animation: fadeIn 1s ease-in-out;
    z-index: 2;
    text-align: center;
}

h1 {
    font-size: 2.5rem;
    margin-bottom: 25px;
    color: #64b5f6; /* Light blue header */
    border-bottom: 2px solid #333;
    padding-bottom: 10px;
}

.greeting {
    font-size: 1.6rem;
    margin-bottom: 20px;
    color: #a5d6a7; /* Light green greeting */
}

.tabs {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 30px;
}

.tab-button {
    padding: 12px 25px;
    background-color: #37474f; /* Dark gray buttons */
    color: #e0e0e0;
    border: none;
    border-radius: 6px;
    margin: 5px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
}

.tab-button:hover {
    background-color: #455a64;
    transform: translateY(-2px);
}

.tab-content {
    display: none;
    margin-top: 20px;
    width: 100%;
    padding: 20px;
    text-align: left;
}

.tab-content.active {
    display: block;
}

.logout {
    padding: 12px 25px;
    background-color: #d32f2f; /* Red logout button */
    color: #e0e0e0;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s, transform 0.2s;
    margin-top: 30px;
}

.logout:hover {
    background-color: #b71c1c;
    transform: translateY(-2px);
}
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Your Dashboard</h1>
        <p class="greeting">Hey Support Member <?= htmlspecialchars($employeeCode) ?>, <?= $_SESSION['name'] ?>!</p>
        <div class="tabs">
            <button class="tab-button" onclick="location.href='assigned_tickets.php'">All Tickets</button>
            <button class="tab-button" onclick="openTab('team')">Team Performance</button>
            <button class="tab-button" onclick="openTab('reports')">Generate Reports</button>
            <button class="tab-button" onclick="openTab('profile')">User Profile</button>
            <button class="tab-button" onclick="openTab('members')">Team Members</button>
            <button class="tab-button" onclick="openTab('settings')">Account Settings</button>
            <button class="tab-button" onclick="openTab('notifications')">Notifications</button>
            <button class="tab-button" onclick="openTab('knowledge')">Knowledge Base</button>
            <button class="tab-button" onclick="openTab('chat')">Chat</button>
           
            <button class="tab-button" onclick="openTab('statuses')">Manage Statuses</button>
        
            <button class="tab-button" onclick="openTab('logs')">System Logs</button>
        </div>
        <a href="logout.php" class="logout">Logout</a>
    </div>
    <script>
        function openTab(tabName) {
            var i, tabcontent, tabbuttons;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            tabbuttons = document.getElementsByClassName("tab-button");
            for (i = 0; i < tabbuttons.length; i++) {
                tabbuttons[i].classList.remove("active");
            }
            document.getElementById(tabName).classList.add("active");
        }
    </script>
</body>
</html>