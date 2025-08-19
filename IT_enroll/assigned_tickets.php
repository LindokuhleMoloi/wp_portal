<?php
session_start();

if (!isset($_SESSION['employee_code'])) {
    header("Location: supportcode_request.php");
    exit();
}

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email configuration
define('SMTP_HOST', 'mail.pro-learn.co.za');
define('SMTP_USER', 'workplace@pro-learn.co.za');
define('SMTP_PASS', 'WokPro@123');
define('SMTP_PORT', 587);

/**
* Note: This file may contain artifacts of previous malicious infection.
* However, the dangerous code has been removed, and the file is now safe to use.
*/


// Handle ticket closure confirmation from email link
if (isset($_GET['ticket_id']) && isset($_GET['response'])) {
    if (confirmTicketClosure($conn, (int)$_GET['ticket_id'], $_GET['response'])) {
        if($_GET['response'] === 'yes'){
            echo "<script>alert('Ticket closure confirmed.'); window.location.href = 'helpdesk_front.php';</script>";
        }
        else{
            echo "<script>alert('Ticket closure confirmation cancelled.'); window.location.href = 'helpdesk_front.php';</script>";
        }
    } else {
        echo "<script>alert('Failed to confirm ticket closure.'); window.location.href = 'helpdesk_front.php';</script>";
    }
}

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
            background: linear-gradient(135deg, #1c1c1c, #2a2a2a);
            color: #e0e0e0;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            box-sizing: border-box;
        }

        .container {
            max-width: 900px;
            width: 100%;
            padding: 30px;
            background: linear-gradient(145deg, #222, #333);
            border-radius: 16px;
            box-shadow: 8px 8px 16px #1a1a1a, -8px -8px 16px #3a3a3a;
            animation: fadeIn 1s ease-in-out;
            z-index: 2;
            text-align: center;
            box-sizing: border-box;
            margin-top: 20px;
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: #64b5f6;
            border-bottom: 2px solid #444;
            padding-bottom: 10px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .greeting {
            font-size: 1.4rem;
            margin-bottom: 15px;
            color: #a5d6a7;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .logout {
            display: inline-block;
            padding: 12px 25px;
            background: linear-gradient(135deg, #e53935, #b71c1c);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 3px 3px 6px #1a1a1a, -3px -3px 6px #3a3a3a;
            transition: transform 0.3s, box-shadow 0.3s;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .logout:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            opacity: 0;
            transition: opacity 0.3s;
            z-index: -1;
        }

        .logout:hover {
            transform: translateY(-3px);
            box-shadow: 5px 5px 10px #1a1a1a, -5px -5px 10px #3a3a3a;
        }

        .logout:hover:before {
            opacity: 1;
        }

        .logout:after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            transition: width 0.4s ease-out, height 0.4s ease-out, opacity 0.4s ease-out;
            z-index: -1;
        }

        .logout:active:after {
            width: 200%;
            height: 200%;
            opacity: 1;
        }

        .ticket-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background-color: #2c2c2c;
            color: #e0e0e0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 3px 3px 6px #1a1a1a, -3px -3px 6px #3a3a3a;
            display: block;
            overflow-x: auto;
            white-space: nowrap;
        }

        .ticket-table th, .ticket-table td {
            border: 1px solid #444;
            padding: 10px;
            text-align: left;
            font-size: 0.9rem;
        }

        .ticket-table th {
            background-color: #333;
            font-weight: 600;
            text-transform: uppercase;
        }

        img {
            max-width: 60px;
            max-height: 45px;
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.3s;
        }

        img:hover {
            transform: scale(1.1);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
        }

        .modal-content {
            margin: 10% auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
            border-radius: 12px;
            box-shadow: 5px 5px 10px #1a1a1a, -5px -5px 10px #3a3a3a;
        }

        .modal-content, #caption {
            animation-name: zoom;
            animation-duration: 0.6s;
        }

        @keyframes zoom {
            from {transform: scale(0)}
            to {transform: scale(1)}
        }

        .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
        }

        .close:hover,
        .close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        .back-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #424242;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            text-decoration: none;
            margin-top: 20px;
            transition: background-color 0.3s, transform 0.2s;
            cursor: pointer;
        }

        .back-button:hover {
            background-color: #616161;
            transform: translateY(-2px);
        }

        .modal-dialog {
            margin: 10% auto;
            max-width: 90%;
        }

        .modal-header {
            background-color: #333;
            color: #fff;
            border-bottom: 1px solid #444;
        }

        .modal-body {
            background-color: #2c2c2c;
        }

        .modal-footer {
            background-color: #333;
            border-top: 1px solid #444;
        }

        .form-control {
            background-color: #444;
            color: #fff;
            border: 1px solid #666;
        }

        .form-control:focus {
            background-color: #555;
            border-color: #64b5f6;
            box-shadow: none;
        }

        .btn-warning {
            background-color: #ff9800;
            border-color: #f57c00;
        }

        .btn-warning:hover {
            background-color: #ffb74d;
            border-color: #f9a825;
        }

        @media (max-width: 600px) {
            .container {
                padding: 15px;
            }

            h1 {
                font-size: 1.8rem;
            }

            .greeting {
                font-size: 1.2rem;
            }

            .logout, .back-button {
                padding: 10px 18px;
                font-size: 0.8rem;
            }

            .ticket-table th, .ticket-table td {
                padding: 8px;
                font-size: 0.8rem;
            }

            img {
                max-width: 50px;
                max-height: 38px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Your Dashboard</h1>
        <p class="greeting">Hey Support Member <?= htmlspecialchars($employeeCode) ?>, <?= $_SESSION['name'] ?>!</p>

        <a href="logout.php" class="logout">Logout</a>

        <table class="ticket-table">
            <thead>
                <tr>
                    <th>Ticket ID</th>
                    <th>Issue Description</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Closed At</th>
                    <th>Assigned To</th>
                    <th>Screenshot</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): ?>
                    <tr>
                        <td><?= htmlspecialchars($ticket['ticket_id']) ?></td>
                        <td><?= htmlspecialchars($ticket['issue_description']) ?></td>
                        <td><?= htmlspecialchars($ticket['email']) ?></td>
                        <td><?= htmlspecialchars($ticket['status']) ?></td>
                        <td>
                            <?php 
                            if ($ticket['status'] === 'Closed' && $ticket['closed_at']) {
                                echo htmlspecialchars($ticket['closed_at']);
                            } else {
                                echo 'Pending Confirmation';
                            }
                            ?>
                        </td>
                        <td><?= htmlspecialchars($ticket['assigned_name'] ?? 'Unassigned') ?></td>
                        <td>
                            <?php if ($ticket['screenshot']): ?>
                                <img src="https://workplaceportal.pro-learn.co.za/IT_enroll/public_html/IT_enroll/uploads/<?= htmlspecialchars($ticket['screenshot']) ?>" alt="Screenshot" onclick="openModal('https://workplaceportal.pro-learn.co.za/IT_enroll/public_html/IT_enroll/uploads/<?= htmlspecialchars($ticket['screenshot']) ?>')">
                            <?php else: ?>
                                No Screenshot
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ticket['status'] !== 'Closed'): ?>
                                <button type="button" class="btn btn-warning" data-toggle="modal" data-target="#returnModal" data-ticket-id="<?= $ticket['ticket_id'] ?>">Return</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="ticket_id" value="<?= $ticket['ticket_id'] ?>">
                                    <button type="submit" name="close_ticket" class="btn btn-primary">Close</button>
                                </form>
                            <?php else: ?>
                                No action available
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <a href="support_team_dashboard.php" class="back-button">Back</a>
    </div>

    <div class="modal fade" id="returnModal" tabindex="-1" role="dialog" aria-labelledby="returnModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="returnModalLabel">Return Ticket</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="returnForm" method="POST">
                        <input type="hidden" name="ticket_id" id="modalTicketId">
                        <div class="form-group">
                            <label for="returnReason">Reason for returning this ticket:</label>
                            <textarea class="form-control" id="returnReason" name="returned_reason" rows="4" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" form="returnForm" name="return_ticket" class="btn btn-warning">Return Ticket</button>
                </div>
            </div>
        </div>
    </div>

    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function openModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').style.display = "block";
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = "none";
        }

        $('#returnModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var ticketId = button.data('ticket-id');
            var modal = $(this);
            modal.find('#modalTicketId').val(ticketId);
        });

        $('#returnModal').on('hidden.bs.modal', function () {
            $('#returnReason').val('');
        });
    </script>
</body>
</html>