<?php
include 'config.php'; 
session_start();

// Define admin credentials
$admin_username = 'admin';
$admin_password = 'admin';

// Handle login logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the login credentials from the form
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if the entered credentials match the admin credentials
    if ($username === $admin_username && $password === $admin_password) {
        // Successful login for admin, set session variables
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_role'] = 'admin';  // You can set any role here

        // Redirect to helpdesk page or admin dashboard
        header('Location: helpdesk.php');
        exit();
    } else {
        $error = "Invalid credentials!";  // Display error for incorrect login
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <div>
        <h2>Login</h2>
        <?php if (isset($error)) { echo "<p>$error</p>"; } ?>
        <form action="login.php" method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            <br>
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            <br>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
