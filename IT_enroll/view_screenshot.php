<?php
// Database connection
$conn = new mysqli("localhost", "tarryn_Lindokuhle", "L1nd0kuhle", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the ticket ID from the URL
$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : null;

if (!$ticket_id) {
    die("Error: Missing ticket ID.");
}

// Fetch the ticket details
$stmt = $conn->prepare("SELECT screenshot FROM helpdesk_support_incoming WHERE ticket_id = ?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Ticket not found.");
}

$ticket = $result->fetch_assoc();
$screenshot = $ticket['screenshot'];
$stmt->close();
$conn->close();

if (!$screenshot) {
    die("No screenshot attached to this ticket.");
}

$imagePath = 'public_html/IT_enroll/uploads/' . $screenshot;

if (!file_exists($imagePath)) {
    die("Screenshot file not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Screenshot | Ticket #<?= $ticket_id ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1b262c;
            --secondary-color: #50c878;
            --light-bg: #f8f9fa;
            --dark-text: #1b262c;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }
        
        .header {
            background-color: var(--primary-color);
            color: white;
            width: 100%;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .header h1 span {
            color: var(--secondary-color);
        }
        
        .image-container {
            max-width: 90%;
            margin: 30px auto;
            text-align: center;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .image-container img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        
        .ticket-info {
            margin-top: 20px;
            color: var(--dark-text);
            font-size: 16px;
        }
        
        .back-button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .back-button:hover {
            background-color: #0f4c75;
        }
        
        footer {
            margin-top: auto;
            background-color: var(--primary-color);
            color: #a0aec0;
            width: 100%;
            padding: 15px;
            text-align: center;
            font-size: 14px;
        }
        
        footer a {
            color: var(--secondary-color);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Ticket #<?= $ticket_id ?> <span>Screenshot</span></h1>
    </div>
    
    <div class="image-container">
        <img src="/IT_enroll/uploads/<?= htmlspecialchars($screenshot) ?>" alt="Ticket Screenshot">
        <div class="ticket-info">
            <p>Ticket ID: <?= $ticket_id ?></p>
        </div>
    </div>
    
    <button class="back-button" onclick="window.history.back()">Back to Ticket</button>
    
    <footer>
        <p>© 2025 Artisans Republik, Division Of Progression</p>
    </footer>
</body>
</html>