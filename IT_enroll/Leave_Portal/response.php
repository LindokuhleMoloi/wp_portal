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

// Get the ticket ID from the URL or form submission
$ticket_id = isset($_GET['ticket_id']) ? $_GET['ticket_id'] : (isset($_POST['ticket_id']) ? $_POST['ticket_id'] : '');

// Fetch ticket details
$ticket_details = [];
if ($ticket_id) {
    // Fetch ticket details from the database
    $sql_ticket = "SELECT * FROM administered_tickets WHERE ticket_id = ?";
    $stmt_ticket = $conn->prepare($sql_ticket);
    $stmt_ticket->bind_param("s", $ticket_id);
    $stmt_ticket->execute();
    $result_ticket = $stmt_ticket->get_result();

    if ($result_ticket->num_rows > 0) {
        $ticket_details = $result_ticket->fetch_assoc();
    }
}

// Handle message submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];
    $ticket_id = $_POST['ticket_id'];  // Ensure ticket_id is included for context

    // Insert the message into the database
    $sql_insert_message = "UPDATE administered_tickets SET message = ? WHERE ticket_id = ?";
    $stmt_insert_message = $conn->prepare($sql_insert_message);
    $stmt_insert_message->bind_param("ss", $message, $ticket_id);
    $stmt_insert_message->execute();

    // Optionally, you can redirect to the same page after the message submission
    header("Location: response.php?ticket_id=$ticket_id");
    exit();
}

// Close the connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Respond to Ticket</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #0f4c75, #3282b8);
            color: #f6f7eb;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            padding-bottom: 80px;
        }

        .container {
            max-width: 70%;
            width: 100%;
            padding: 40px;
            background: #1b262c;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            animation: fadeIn 1s ease-in-out;
            z-index: 2;
        }

        h1 {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #50c878;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ticket-details {
            background: #FFD700;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        tr:hover {
            background-color: #3282b8;
        }

        textarea {
            width: 100%;
            max-width: 500px;
            padding: 10px;
            font-size: 1rem;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-bottom: 10px;
        }

        button.submit-btn {
            padding: 10px 20px;
            background-color: #50c878;
            border: none;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }

        button.submit-btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="back-arrow" onclick="window.location.href='support_team_dashboard.php';">&larr; Back</button>
        <h1>Respond to Ticket</h1>

        <?php if (!empty($ticket_details)): ?>
            <div class="ticket-details">
                <p><strong>Ticket ID:</strong> <?= htmlspecialchars($ticket_details['ticket_id']); ?></p>
                <p><strong>Issue Description:</strong> <?= htmlspecialchars($ticket_details['issue_description']); ?></p>
                <p><strong>Created At:</strong> <?= htmlspecialchars($ticket_details['created_at']); ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($ticket_details['status']); ?></p>
            </div>

            <form method="POST" action="response.php">
                <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket_details['ticket_id']); ?>">
                <textarea name="message" rows="4" placeholder="Type your response here..."></textarea>
                <button type="submit" class="submit-btn">Submit Response</button>
            </form>

        <?php else: ?>
            <p>No ticket found or invalid ticket ID.</p>
        <?php endif; ?>
    </div>
</body>
</html>
