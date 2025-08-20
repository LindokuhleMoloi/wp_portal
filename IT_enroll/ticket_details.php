<?php
// ticket_details.php
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ticket_id']) && isset($_GET['employee_code'])) {
    $servername = "localhost";
    $username = "root"; 
    $password = ""; 
    $database = "tarryn_workplaceportal"; 

    // Create connection
    $mysqli = new mysqli($servername, $username, $password, $database);

    // Check connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    $ticket_id = $mysqli->real_escape_string($_GET['ticket_id']);
    $employee_code = $mysqli->real_escape_string($_GET['employee_code']);

    // Enhanced query to get ticket details with assignee information
    $query = "SELECT t.*, s.name as assigned_to_name
              FROM administered_tickets t
              LEFT JOIN support_team s ON t.assigned_to = s.employee_code
              WHERE t.ticket_id = ? AND t.employee_code = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("ss", $ticket_id, $employee_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $ticket = $result->fetch_assoc();
    } else {
        die("Ticket not found or doesn't belong to this employee");
    }

    $stmt->close();
    $mysqli->close();
} else {
    die("Invalid request");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Details</title>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f5f5f5;
            color: #333;
            padding: 20px;
        }
        .ticket-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .ticket-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .ticket-id {
            font-size: 1.5rem;
            color: #0f4c75;
            font-weight: bold;
        }
        .ticket-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 500;
            margin-left: 10px;
        }
        .status-pending {
            background-color: rgba(255, 165, 0, 0.2);
            color: #FFA500;
        }
        .status-resolved {
            background-color: rgba(80, 200, 120, 0.2);
            color: #50c878;
        }
        .status-in-progress {
            background-color: rgba(50, 130, 184, 0.2);
            color: #3282b8;
        }
        .ticket-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 10px;
            color: #666;
            font-size: 0.9rem;
        }
        .ticket-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .ticket-meta-item i {
            color: #0f4c75;
        }
        .ticket-body {
            margin-top: 20px;
        }
        .ticket-section {
            margin-bottom: 20px;
        }
        .ticket-section h3 {
            color: #0f4c75;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 8px 15px;
            background: #0f4c75;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: #3282b8;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <div>
                <span class="ticket-id">Ticket #<?php echo htmlspecialchars($ticket['ticket_id']); ?></span>
                <span class="ticket-status status-<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>">
                    <?php echo htmlspecialchars($ticket['status']); ?>
                </span>
            </div>
            <div class="ticket-meta">
                <div class="ticket-meta-item">
                    <i class="fas fa-user"></i>
                    <span>Employee: <?php echo htmlspecialchars($ticket['employee_code']); ?></span>
                </div>
                <div class="ticket-meta-item">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Created: <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></span>
                </div>
                <?php if (!empty($ticket['assigned_to_name'])): ?>
                <div class="ticket-meta-item">
                    <i class="fas fa-user-shield"></i>
                    <span>Assigned To: <?php echo htmlspecialchars($ticket['assigned_to_name']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="ticket-body">
            <div class="ticket-section">
                <h3><i class="fas fa-question-circle"></i> Issue Description</h3>
                <p><?php echo nl2br(htmlspecialchars($ticket['issue_description'])); ?></p>
            </div>
            
            <?php if (!empty($ticket['resolution_notes'])): ?>
            <div class="ticket-section">
                <h3><i class="fas fa-check-circle"></i> Resolution Notes</h3>
                <p><?php echo nl2br(htmlspecialchars($ticket['resolution_notes'])); ?></p>
            </div>
            <?php endif; ?>
        </div>
        
        <a href="follow_up.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Ticket List
        </a>
    </div>
</body>
</html>