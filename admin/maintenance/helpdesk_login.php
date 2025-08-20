<?php
// helpdesk_login.php
session_start();

$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "tarryn_workplaceportal";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT support_member_id, password FROM support_team WHERE employee_code = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['helpdesk_user_id'] = $row['support_member_id'];
            echo "success";
        } else {
            echo "Incorrect username or password.";
        }
    } else {
        echo "Incorrect username or password.";
    }

    $stmt->close();
}

$conn->close();
?>