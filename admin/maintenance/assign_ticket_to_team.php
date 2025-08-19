<?php
include('config.php');  // Include your database connection

// Check if the required POST data is available
if (isset($_POST['ticket_id']) && isset($_POST['member_id'])) {
    $ticket_id = $_POST['ticket_id'];
    $member_id = $_POST['member_id'];

    // Fetch the support member details from the support_team table
    $query = $conn->query("SELECT * FROM `support_team` WHERE `id` = '$member_id'");
    
    if ($query->num_rows > 0) {
        $support_member = $query->fetch_assoc();
        $member_name = $support_member['name'];

        // Get ticket details from the helpdesk_support_incoming table
        $ticket_query = $conn->query("SELECT * FROM `helpdesk_support_incoming` WHERE `id` = '$ticket_id'");
        
        if ($ticket_query->num_rows > 0) {
            $ticket = $ticket_query->fetch_assoc();
            $employee_code = $ticket['employee_code'];
            $ticket_number = $ticket['ticket_number'];
            $issue_description = $ticket['issue_description'];
            $role = $ticket['role'];
            $name = $ticket['name'];
            $created_at = $ticket['created_at'];

            // Insert into the support_team_ticketbox table
            $insert_query = $conn->query("INSERT INTO `support_team_ticketbox` 
                (`ticket_id`, `support_member_id`, `support_member_name`, `employee_code`, `name`, `role`, `issue_description`, `created_at`, `status`) 
                VALUES 
                ('$ticket_id', '$member_id', '$member_name', '$employee_code', '$name', '$role', '$issue_description', '$created_at', 'Pending')");

            if ($insert_query) {
                echo json_encode(['success' => true, 'message' => 'Ticket successfully assigned to support member']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error assigning ticket to support member.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Ticket not found.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Support member not found.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Ticket ID and Member ID are required.']);
}
?>
