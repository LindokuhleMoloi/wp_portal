<?php
// check_ticket_status.php
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['employee_code'])) {
    $servername = "localhost";
    $username = "tarryn_Lindokuhle"; 
    $password = "L1nd0kuhle"; 
    $database = "tarryn_workplaceportal"; 

    // Create connection
    $mysqli = new mysqli($servername, $username, $password, $database);

    // Check connection
    if ($mysqli->connect_error) {
        die(json_encode(['status' => 'error', 'message' => 'Database connection failed']));
    }

    $employee_code = $mysqli->real_escape_string($_GET['employee_code'] ?? '');

    if (empty($employee_code)) {
        echo json_encode(['status' => 'error', 'message' => 'Employee code is missing']);
        exit();
    }

    // Modified query to include assigned_to information
    $query = "SELECT t.ticket_id, t.issue_description, t.created_at, t.status, 
                     t.assigned_to, s.name as assigned_to_name
              FROM administered_tickets t
              LEFT JOIN support_team s ON t.assigned_to = s.employee_code
              WHERE t.employee_code = ? 
              ORDER BY t.created_at DESC";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $employee_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $tickets = [];
        while ($row = $result->fetch_assoc()) {
            $tickets[] = [
                'ticket_id' => $row['ticket_id'],
                'issue_description' => $row['issue_description'],
                'created_at' => $row['created_at'],
                'status' => $row['status'],
                'assigned_to' => $row['assigned_to'],
                'assigned_to_name' => $row['assigned_to_name']
            ];
        }
        echo json_encode([
            'status' => 'success',
            'tickets' => $tickets
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No tickets found for this employee code']);
    }

    $stmt->close();
    $mysqli->close();
}
?>