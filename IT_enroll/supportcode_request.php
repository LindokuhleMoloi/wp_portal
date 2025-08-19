<?php
session_start(); // Start the session

// Database connection
$servername = "localhost";
$username = "tarryn_Lindokuhle";
$password = "L1nd0kuhle";
$dbname = "tarryn_workplaceportal";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeCode = trim($_POST['employee_code']);
    $password = trim($_POST['password']);

    // Prepare the SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM support_team WHERE employee_code = ? AND password = ?");
    $stmt->bind_param("ss", $employeeCode, $password); // "ss" means both parameters are strings

    $stmt->execute();
    $result = $stmt->get_result();

    // Check if a matching record was found
    if ($result->num_rows > 0) {
        // Successful login
        $_SESSION['employee_code'] = $employeeCode; // Store employee code in session
        $_SESSION['success_message'] = "Login successful! Redirecting to your dashboard...";
        header("Location: support_team_dashboard.php"); // Redirect to the dashboard
        exit();
    } else {
        // Login failed
        $error = "Invalid employee code or password.";
    }

    $stmt->close(); // Close the prepared statement
}

$conn->close(); // Close the database connection

// Retrieve success message from session
$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']); // Clear the message after displaying it
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Team Login</title>
    <style>
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #0f4c75, #3282b8);
            color: #f6f7eb;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
.container {
            max-width: 600px;
            width: 100%;
            padding: 40px;
            background: #1b262c;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            animation: fadeIn 1s ease-in-out;
            text-align: center; /* Center-align content within the container */
        }
h1 {
            font-size: 2rem;
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .employee-code {
            font-size: 1.2rem;
            color: #333;
            font-weight: bold;
            background: #FFD700;
            padding: 10px;
            border-radius: 5px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #4CAF50; /* Focus color */
            outline: none;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
        }

        button:hover {
            background-color: #45a049;
        }

        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #007BFF; /* Blue for back button */
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        a:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
        }

        .success-message {
            color: green;
            background-color: #e7f9e7;
            border: 1px solid #4CAF50;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: inline-block;
            width: 100%;
        }
        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            color: #f6f7eb; /* Ensure label text is visible */
        }

        input,
        button {
            width: 100%;
            padding: 14px 20px;
            font-size: 1rem;
            border: 2px solid #3282b8;
            border-radius: 25px;
            background: #1b262c;
            color: #f6f7eb;
            transition: all 0.3s;
            outline: none;
        }

        input:focus {
            border-color: #50c878;
            background: rgba(80, 200, 120, 0.1);
        }

        button {
            background: linear-gradient(135deg, #50c878, #3ac569);
            color: #fff;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.3s ease;
        }

        button:hover {
            background: linear-gradient(135deg, #3ac569, #50c878);
            transform: scale(1.05);
        }
 button.back-arrow {
            background-color: #3282b8;
            color: #f6f7eb;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            position: absolute;
            left: 20px;
            top: 20px;
            width:10%;
        }
    </style>
</head>
<body>
    <div class="container">
       <button class="back-arrow" onclick="window.location.href='helpdesk_front.php';">&larr; Back</button>  
        <h1>Support Team Login</h1>
        <?php if (!empty($successMessage)): ?>
            <p class="success-message"><?= htmlspecialchars($successMessage) ?></p>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <p class="error-message"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form id="login-form" method="POST" action="">
            <label for="employee_code">Employee Code:</label>
            <input type="text" id="employee_code" name="employee_code" placeholder="Enter your employee code" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>
            
            <button type="submit">Login</button>
        </form>
        
        <!-- Back Button -->
      
    </div>
</body>
</html>