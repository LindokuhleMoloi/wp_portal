<?php
// Database configuration
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "tarryn_workplaceportal";

// Establish database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("<div style='color: #dc3545; padding: 20px; border: 1px solid #f5c6cb; border-radius: 4px;'>Connection failed: " . $conn->connect_error . "</div>");
}

// PHPMailer configuration
require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send email with unified styling template
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param array $ticket Ticket data array
 * @param string $assigned_member_name Assigned support member name
 * @return bool True if sent successfully
 */
function sendAssignmentEmail($to, $subject, $ticket, $assigned_member_name)
{
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'mail.pro-learn.co.za';
        $mail->SMTPAuth = true;
        $mail->Username = 'workplace@pro-learn.co.za';
        $mail->Password = 'WokPro@123';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('workplace@pro-learn.co.za', 'Workplace Portal IT');
        $mail->addAddress($to);
        $mail->isHTML(true);

        // Content
        $mail->Subject = $subject;
        $mail->Body = generateEmailTemplate($ticket, $assigned_member_name);

        return $mail->send();
    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate email HTML template
 * @param array $ticket Ticket data
 * @param string $assigned_member Assigned member name
 * @return string HTML content
 */
function generateEmailTemplate($ticket, $assigned_member)
{
    $screenshot_url = $ticket['screenshot'] ? "https://workplaceportal.pro-learn.co.za/IT_enroll/public_html/IT_enroll/uploads/" . htmlspecialchars($ticket['screenshot']) : "No Screenshot Available";
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap');
        body { font-family: 'Poppins', sans-serif; background: #f8f9fa; margin: 0; }
        .email-container { max-width: 700px; margin: 30px auto; background: #fff; border-radius: 16px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #0f4c75 0%, #3282b8 100%); padding: 40px; text-align: center; }
        h1 { color: #fff; font-size: 28px; margin: 0; }
        .content { padding: 40px; color: #4a5568; }
        .message-box { background: #f8f9fa; border-radius: 12px; padding: 25px; position: relative; }
        .message-box:before { content: '✓'; position: absolute; left: -15px; top: -15px;
            background: #50c878; color: #fff; width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .details { font-size: 16px; line-height: 1.7; }
        .logo-section { text-align: center; padding: 30px 0; background: #f8fafc; }
        .logo { max-width: 220px; }
        .footer { background: #1b262c; color: #a0aec0; padding: 25px; text-align: center; }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='header'>
            <h1>Ticket <span style="color: #50c878">Assigned</span></h1>
        </div>
        <div class='content'>
            <div class='message-box'>
                <p>Dear {$ticket['name']},</p>
                <div class='details'>
                    <p>🎯 Ticket ID: <strong>{$ticket['ticket_id']}</strong></p>
                    <p>🔧 Assigned to: <strong>{$assigned_member}</strong></p>
                    <p>⏱️ Expected response within 5 minutes</p>
                </div>
            </div>
        </div>
        <div class='logo-section'>
            <img src='https://workplaceportal.pro-learn.co.za/uploads/Artisans.png'
                alt='Logo' class='logo'>
            <p>Empowering Digital Workforce Solutions</p>
        </div>
        <div class='footer'>
            <p>Need help? <a href='mailto:workplace@pro-learn.co.za' style='color: #50c878;'>Contact support</a></p>
            <p>© 2025 Artisans Republik</p>
        </div>
    </div>
</body>
</html>
HTML;
}

// Ticket Assignment Handler
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_ticket'])) {
    // Validate and sanitize input
    $ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_SANITIZE_NUMBER_INT);
    $assigned_member_id = filter_input(INPUT_POST, 'assigned_member', FILTER_SANITIZE_STRING);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Retrieve ticket details
        $stmt = $conn->prepare(
            "SELECT ticket_id, employee_code, name, email, issue_description, screenshot, role
            FROM helpdesk_support_incoming
            WHERE ticket_id = ?"
        );
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$ticket) {
            throw new Exception("Ticket not found in incoming queue");
        }

        // Get support member details
        $stmt = $conn->prepare(
            "SELECT support_member_id, name
            FROM support_team
            WHERE support_member_id = ?"
        );
        $stmt->bind_param("s", $assigned_member_id);
        $stmt->execute();
        $support_member = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$support_member) {
            throw new Exception("Invalid support team member selected");
        }

        // Move ticket to administered
        $stmt = $conn->prepare(
            "INSERT INTO administered_tickets
            (ticket_id, employee_code, name, email, issue_description, assigned_to, status, created_at, role, screenshot)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?)"
        );
        $stmt->bind_param(
            "isssssss",
            $ticket['ticket_id'],
            $ticket['employee_code'],
            $ticket['name'],
            $ticket['email'],
            $ticket['issue_description'],
            $assigned_member_id,
            $ticket['role'],
            $ticket['screenshot']
        );
        $stmt->execute();
        $stmt->close();

        // Remove from incoming
        $stmt = $conn->prepare(
            "DELETE FROM helpdesk_support_incoming
            WHERE ticket_id = ?"
        );
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $stmt->close();

        // Send notification
        if (!sendAssignmentEmail(
            $ticket['email'],
            "Ticket #{$ticket['ticket_id']} Assignment",
            $ticket,
            $support_member['name']
        )) {
            throw new Exception("Failed to send assignment email");
        }

        // Commit transaction
        $conn->commit();
        $success_message = "Ticket #{$ticket['ticket_id']} successfully assigned to {$support_member['name']}";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Assignment failed: " . $e->getMessage();
    }
}

// Ticket Deletion Handler for Active Tickets and Returned Tickets
if (isset($_POST['delete_ticket'])) {
    $ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Start transaction
    $conn->begin_transaction();
    try {
        // Check if the ticket exists in administered_tickets
        $stmt_administered = $conn->prepare("SELECT ticket_id FROM administered_tickets WHERE ticket_id = ?");
        $stmt_administered->bind_param("i", $ticket_id);
        $stmt_administered->execute();
        $result_administered = $stmt_administered->get_result();

        // Check if the ticket exists in helpdesk_support_incoming with a return reason (returned tickets)
        $stmt_returned = $conn->prepare("SELECT ticket_id FROM helpdesk_support_incoming WHERE ticket_id = ? AND returned_reason IS NOT NULL");
        $stmt_returned->bind_param("i", $ticket_id);
        $stmt->execute();
        $result_returned = $stmt_returned->get_result();
        
        if ($result_administered->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM administered_tickets WHERE ticket_id = ?");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            $stmt->close();
            $message = "Ticket #{$ticket_id} successfully deleted from Active Tickets.";
        } elseif ($result_returned->num_rows > 0) {
            $stmt = $conn->prepare("DELETE FROM helpdesk_support_incoming WHERE ticket_id = ? AND returned_reason IS NOT NULL");
            $stmt->bind_param("i", $ticket_id);
            $stmt->execute();
            $stmt->close();
            $message = "Ticket #{$ticket_id} successfully deleted from Returned Tickets.";
        } else {
            throw new Exception("Ticket #{$ticket_id} not found.");
        }

        $conn->commit();
        $success_message = $message;
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Deletion failed: " . $e->getMessage();
    }
}

// Pending Ticket Deletion Handler
if (isset($_POST['delete_incoming_ticket'])) {
    $ticket_id = filter_input(INPUT_POST, 'ticket_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Start transaction
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM helpdesk_support_incoming WHERE ticket_id = ?");
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        $success_message = "Ticket #{$ticket_id} successfully deleted from Incoming tickets.";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Deletion failed: " . $e->getMessage();
    }
}

// Data Fetching - Updated queries to include screenshots for all ticket types
$tables = [
    'incoming' => $conn->query(
        "SELECT ticket_id, employee_code, name, role, issue_description, created_at, screenshot
        FROM helpdesk_support_incoming
        WHERE returned_reason IS NULL" // Only get non-returned tickets
    ),
    'administered' => $conn->query(
        "SELECT at.ticket_id, at.issue_description, at.status, at.created_at, at.closed_at, 
            st.name as assigned_to_name, at.employee_code, at.role, at.email, 
            at.rating, at.rating_reason, at.screenshot
        FROM administered_tickets at
        LEFT JOIN support_team st ON at.assigned_to = st.support_member_id"
    ),
    'returned' => $conn->query(
        "SELECT hsi.ticket_id, hsi.employee_code, hsi.name, hsi.issue_description, 
            hsi.returned_reason, st.name as returned_by, hsi.screenshot
        FROM helpdesk_support_incoming hsi
        LEFT JOIN support_team st ON hsi.returned_by = st.support_member_id
        WHERE hsi.returned_reason IS NOT NULL"
    )
];

// Support team members
$support_members = $conn->query(
    "SELECT support_member_id, name
    FROM support_team"
)->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Helpdesk Admin Dashboard</title>
    <style>
        :root {
            --primary: #0f4c75;
            --secondary: #3282b8;
            --accent: #50c878;
            --text: #2d3748;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
            margin: 0;
            padding: 20px 10px;
            min-width: 1300px;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            padding: 0 20px;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            margin: 25px 0;
        }

        .card-header {
            padding: 20px;
            border-bottom: 2px solid var(--primary);
        }

        .card-title {
            margin: 0;
            color: var(--primary);
            font-size: 1.5rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .form-inline {
            display: inline-flex;
            gap: 10px;
        }

        select {
            padding: 8px 12px;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            min-width: 200px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-in-progress { background: #cce5ff; color: #004085; }
        .status-resolved { background: #d4edda; color: #155724; }

        /* Circular screenshot thumbnails */
        .screenshot-thumb {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .screenshot-thumb:hover {
            transform: scale(1.1);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }

        .modal-content {
            display: block;
            margin: 5% auto;
            max-width: 80%;
            max-height: 80%;
            border: 3px solid white;
            border-radius: 8px;
        }

        .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
        }
        .returned-ticket-row {
            background-color: #ffebee; /* Mild red background */
        }
        .login-modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 30%; /* Adjust width as needed */
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .login-modal-content h5 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        .login-modal-content label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }

        .login-modal-content input[type="text"],
        .login-modal-content input[type="password"] {
            width: calc(100% - 12px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .login-modal-content button {
            background-color: #50c878;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .login-modal-content button:hover {
            background-color: #45a049;
        }

        #helpdeskLoginError {
            color: red;
            text-align: center;
            margin-top: 10px;
            display: none;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
</head>
<body>
    <div class="container">
        <h1>IT Helpdesk Admin Dashboard</h1>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">⏳ Pending Assignment</h2>
            </div>
            <div class="card-body">
                <?php if ($tables['incoming']->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Employee Code</th>
                                <th>Employee</th>
                                <th>Issue Description</th>
                                <th>Screenshot</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $tables['incoming']->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($row['ticket_id']) ?></td>
                                    <td><?= htmlspecialchars($row['employee_code']) ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['issue_description']) ?></td>
                                    <td>
                                        <?php if ($row['screenshot']): ?>
                                            <img src="https://workplaceportal.pro-learn.co.za/IT_enroll/public_html/IT_enroll/uploads/<?= htmlspecialchars($row['screenshot']) ?>"
                                                alt="Screenshot"
                                                class="screenshot-thumb"
                                                onclick="openModal(this.src)">
                                        <?php else: ?>
                                            No Screenshot
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M j, Y H:i', strtotime($row['created_at'])) ?></td>
                                    <td>
                                        <form method="POST" class="form-inline">
                                            <input type="hidden" name="ticket_id"
                                                value="<?= htmlspecialchars($row['ticket_id']) ?>">
                                            <select name="assigned_member" required>
                                                <option value="">Select Assignee</option>
                                                <?php foreach ($support_members as $member): ?>
                                                    <option value="<?= htmlspecialchars($member['support_member_id']) ?>">
                                                        <?= htmlspecialchars($member['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_ticket"
                                                class="btn btn-primary">
                                                Assign Ticket
                                            </button>
                                            <button type="submit" name="delete_incoming_ticket" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this ticket?')">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-success">No pending tickets requiring assignment</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">🛠️ Active Tickets</h2>
            </div>
            <div class="card-body">
                <?php if ($tables['administered']->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Employee Code</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Issue Description</th>
                                <th>Assigned To</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Screenshot</th>
                                <th>Rating</th>
                                <th>Rating Reason</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $tables['administered']->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($row['ticket_id']) ?></td>
                                    <td><?= htmlspecialchars($row['employee_code']) ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['role']) ?></td>
                                    <td><?= htmlspecialchars($row['issue_description']) ?></td>
                                    <td><?= htmlspecialchars($row['assigned_to_name']) ?></td>
                                    <td>
                                        <span class="status-badge status-<?= str_replace(' ', '-', strtolower($row['status'])) ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $row['closed_at']
                                            ? date('M j, Y H:i', strtotime($row['closed_at']))
                                            : 'Active' ?>
                                    </td>
                                    <td>
                                        <?php if ($row['screenshot']): ?>
                                            <img src="https://workplaceportal.pro-learn.co.za/IT_enroll/public_html/IT_enroll/uploads/<?= htmlspecialchars($row['screenshot']) ?>"
                                                alt="Screenshot"
                                                class="screenshot-thumb"
                                                onclick="openModal(this.src)">
                                        <?php else: ?>
                                            No Screenshot
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['rating']) ?></td>
                                    <td><?= htmlspecialchars($row['rating_reason']) ?></td>
                                    <td>
                                        <form method="POST" class="form-inline">
                                            <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($row['ticket_id']) ?>">
                                 
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">No active tickets being processed</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title">↩️ Returned Tickets</h2>
            </div>
            <div class="card-body">
                <?php if ($tables['returned']->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket ID</th>
                                <th>Employee</th>
                                <th>Issue</th>
                                <th>Reason</th>
                                <th>Screenshot</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $tables['returned']->fetch_assoc()): ?>
                                <tr class="returned-ticket-row">
                                    <td>#<?= htmlspecialchars($row['ticket_id']) ?></td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td><?= htmlspecialchars($row['issue_description']) ?></td>
                                    <td><?= htmlspecialchars($row['returned_reason']) ?></td>
                                    <td>
                                        <?php if ($row['screenshot']): ?>
                                            <img src="https://workplaceportal.pro-learn.co.za/IT_enroll/public_html/IT_enroll/uploads/<?= htmlspecialchars($row['screenshot']) ?>"
                                                alt="Screenshot"
                                                class="screenshot-thumb"
                                                onclick="openModal(this.src)">
                                        <?php else: ?>
                                            No Screenshot
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="form-inline">
                                            <input type="hidden" name="ticket_id"
                                                value="<?= htmlspecialchars($row['ticket_id']) ?>">
                                            <select name="assigned_member" required>
                                                <option value="">Select Assignee</option>
                                                <?php foreach ($support_members as $member): ?>
                                                    <option value="<?= htmlspecialchars($member['support_member_id']) ?>">
                                                        <?= htmlspecialchars($member['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_ticket"
                                                class="btn btn-primary">
                                                Reassign
                                            </button>
                                          
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">No recently returned tickets</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

  

    <script>
      
        // Modal functionality for screenshots
        function openModal(imgSrc) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById("modalImage");

            modal.style.display = "block";
            modalImg.src = imgSrc;
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = "none";
        }

        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == document.getElementById('imageModal').style.display = "none";
        }

        window.onclick = function(event) {
            const modal = document.getElementById('imageModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

    <?php
    $conn->close();
    ?>
</body>
</html>