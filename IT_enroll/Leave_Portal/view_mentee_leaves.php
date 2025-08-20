<?php
session_start();

// Redirect if not logged in as mentor
if (!isset($_SESSION['mentor_logged_in'])) {
    header("Location: mentor_login.php");
    exit();
}

// Ensure an employee_id is provided
if (!isset($_GET['employee_id']) || !is_numeric($_GET['employee_id'])) {
    header("Location: mentee_list.php"); // Redirect back if no valid ID
    exit();
}

$mentee_employee_id = filter_var($_GET['employee_id'], FILTER_SANITIZE_NUMBER_INT);

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current mentor details
$mentor_id = $_SESSION['mentor_employee_id'];
$mentor_fullname = $_SESSION['mentor_fullname'];

// --- Verify Mentee Belongs to Mentor and Fetch Mentee Details ---
$mentee_details = null;
$sql_verify_mentee = "SELECT fullname, employee_code FROM employee_list WHERE id = ? AND mentor_id = ?";
$stmt_verify_mentee = $conn->prepare($sql_verify_mentee);
if ($stmt_verify_mentee) {
    $stmt_verify_mentee->bind_param("ii", $mentee_employee_id, $mentor_id);
    $stmt_verify_mentee->execute();
    $result_verify_mentee = $stmt_verify_mentee->get_result();
    if ($result_verify_mentee->num_rows > 0) {
        $mentee_details = $result_verify_mentee->fetch_assoc();
    } else {
        // Mentee not found or does not belong to this mentor
        header("Location: mentee_list.php");
        exit();
    }
    $stmt_verify_mentee->close();
} else {
    die("Error preparing mentee verification statement: " . $conn->error);
}

// --- Fetch Only Pending Leave Applications for the specific Mentee ---
$mentee_leaves = [];
// Modified query: Only fetch applications where mentor_status is 0 (pending)
$sql_mentee_leaves = "SELECT
                            la.id AS application_id,
                            lt.name AS leave_type_name,
                            la.start_date,
                            la.end_date,
                            la.reason,
                            la.date_applied,
                            la.mentor_status,
                            la.pm_status,
                            la.rejection_reason
                        FROM leave_applications la
                        LEFT JOIN leave_types lt ON la.leave_type_id = lt.id
                        WHERE la.employee_id = ? AND la.mentor_status = 0
                        ORDER BY la.date_applied DESC"; // Order by most recent first

$stmt_mentee_leaves = $conn->prepare($sql_mentee_leaves);
if ($stmt_mentee_leaves) {
    $stmt_mentee_leaves->bind_param("i", $mentee_employee_id);
    $stmt_mentee_leaves->execute();
    $result_mentee_leaves = $stmt_mentee_leaves->get_result();
    if ($result_mentee_leaves->num_rows > 0) {
        while($row = $result_mentee_leaves->fetch_assoc()) {
            $mentee_leaves[] = $row;
        }
    }
    $stmt_mentee_leaves->close();
} else {
    die("Error preparing leave applications statement: " . $conn->error);
}

$conn->close();

// Function to get status text (unchanged)
function getStatusText($mentor_status, $pm_status) {
    if ($mentor_status == 0 && $pm_status == 0) {
        return '<span class="status-badge status-pending">Mentor & PM Pending</span>';
    } elseif ($mentor_status == 0) {
        return '<span class="status-badge status-pending">Mentor Pending</span>';
    } elseif ($pm_status == 0) {
        return '<span class="status-badge status-pending">PM Pending</span>';
    } elseif ($mentor_status == 1 && $pm_status == 1) {
        return '<span class="status-badge status-approved">Approved</span>';
    } elseif ($mentor_status == 2 || $pm_status == 2) {
        return '<span class="status-badge status-rejected">Rejected</span>';
    }
    return '<span class="status-badge status-unknown">Unknown</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Leaves for <?php echo htmlspecialchars($mentee_details['fullname']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0e6574;
            --secondary-color: #e84e17;
            --accent-color: #2fa8e0;
            --dark-color: #0b3e4d;
            --light-color: #f8f9fa;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Montserrat', sans-serif;
        }

        body {
            background: url('system-image.png') no-repeat center center fixed;
            background-size: cover;
            background-color: #000;
            display: flex;
            flex-direction: column;
            padding: 15px;
            position: relative;
        }

        .logo {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 80px;
            height: 60px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
        }

        .logo-letter {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 2.8rem;
            color: white;
            transition: transform 0.3s ease;
        }

        .logo-letter.w {
            transform: translateY(-2px);
        }

        .logo-letter.p {
            transform: translateY(2px);
        }

        .logo:hover .logo-letter.w {
            transform: translateY(-4px);
        }

        .logo:hover .logo-letter.p {
            transform: translateY(4px);
        }

        .header-strip {
            background: linear-gradient(to right, var(--dark-color), var(--primary-color));
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -15px -15px 30px -15px;
            width: calc(100% + 30px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .header-title {
            color: white;
            font-size: 2.2rem;
            font-weight: 700;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title i {
            font-size: 1.8rem;
        }

        .welcome-message {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            text-align: right;
        }

        .welcome-message span {
            color: var(--accent-color);
        }

        .header-buttons {
            display: flex;
            gap: 15px;
        }

        .header-btn {
            background-color: white;
            color: var(--dark-color);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .header-btn i {
            font-size: 1rem;
        }

        /* Main Content */
        .content-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 30px;
            flex-grow: 1;
        }

        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-color);
        }

        .content-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .content-title i {
            color: var(--primary-color);
        }

        .leave-count {
            background-color: var(--secondary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Leave Table */
        .leave-table {
            width: 100%;
            border-collapse: collapse;
        }

        .leave-table th {
            background-color: var(--light-color);
            color: var(--dark-color);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .leave-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }

        .leave-table tr:last-child td {
            border-bottom: none;
        }

        .leave-table tr:hover td {
            background-color: rgba(15, 101, 116, 0.05);
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 700;
            color: white;
            text-align: center;
            white-space: nowrap;
        }

        .status-pending { background-color: var(--warning-color); color: #333; }
        .status-approved { background-color: var(--success-color); }
        .status-rejected { background-color: var(--danger-color); }
        .status-unknown { background-color: #6c757d; }

        .rejection-reason {
            font-style: italic;
            color: var(--danger-color);
            font-size: 0.8em;
            margin-top: 5px;
        }

        .action-buttons button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.85rem;
            margin-right: 5px;
            transition: background-color 0.3s ease;
        }

        .action-buttons button.reject {
            background-color: var(--danger-color);
        }

        .action-buttons button:hover {
            opacity: 0.9;
        }

        /* Back Button */
        .back-btn {
            display: inline-block;
            margin-top: 20px;
            background-color: var(--light-color);
            color: var(--dark-color);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
        }

        /* No Leaves */
        .no-leaves {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        .no-leaves i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .no-leaves p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        /* Flash Messages */
        .flash-message {
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
        }

        .flash-message.success {
            background-color: var(--success-color);
            color: white;
        }

        .flash-message.error {
            background-color: var(--danger-color);
            color: white;
        }

        /* Modal for Rejection Reason */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 1; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #fefefe;
            margin: auto;
            padding: 25px;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-content h3 {
            color: var(--dark-color);
            margin-bottom: 15px;
            font-size: 1.5rem;
        }

        .modal-content textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            min-height: 100px;
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9rem;
        }

        .modal-content button {
            background-color: var(--danger-color);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .modal-content button:hover {
            background-color: #c82333;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .header-strip {
                flex-direction: column;
                gap: 15px;
                padding: 20px;
                text-align: center;
            }
            
            .welcome-message {
                text-align: center;
            }
            
            .header-buttons {
                justify-content: center;
            }
            
            .content-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .logo {
                top: 15px;
                right: 15px;
                width: 60px;
                height: 50px;
            }
            
            .logo-letter {
                font-size: 2.2rem;
            }
            
            .leave-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap; /* Prevent table content from wrapping */
            }
            .leave-table thead, .leave-table tbody, .leave-table th, .leave-table td, .leave-table tr {
                display: block;
            }
            .leave-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            .leave-table tr {
                border: 1px solid #ccc;
                margin-bottom: 10px;
                border-radius: 8px;
                overflow: hidden;
            }
            .leave-table td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            .leave-table td:before {
                content: attr(data-label);
                position: absolute;
                left: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                text-align: left;
                font-weight: 600;
                color: var(--dark-color);
            }
            .leave-table td:last-child {
                border-bottom: none;
            }
            .action-buttons {
                display: flex;
                flex-direction: column;
                gap: 5px;
                align-items: flex-end; /* Align buttons to the right */
            }
            .action-buttons button {
                width: 100%; /* Full width buttons on small screens */
            }
        }
    </style>
</head>
<body>
    <div class="logo">
        <div class="logo-letter w">W</div>
        <div class="logo-letter p">P</div>
    </div>

    <div class="header-strip">
        <h1 class="header-title">
            <i class="fas fa-calendar-alt"></i> PENDING MENTEE LEAVES
        </h1>
        <div class="welcome-message">
            Welcome, <span><?php echo htmlspecialchars($mentor_fullname); ?></span>
        </div>
        <div class="header-buttons">
            <a href="mentee_list.php" class="header-btn">
                <i class="fas fa-users"></i> My Mentees
            </a>
            <a href="pending_leaves.php" class="header-btn">
                <i class="fas fa-calendar-check"></i> Pending Leaves
            </a>
            <a href="mentor_profile.php" class="header-btn">
                <i class="fas fa-user-cog"></i> Profile
            </a>
            <a href="mentor_logout.php" class="header-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="content-container">
        <?php
        // Display flash messages from session
        if (isset($_SESSION['success_message'])) {
            echo '<div class="flash-message success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<div class="flash-message error">' . $_SESSION['error_message'] . '</div>';
            unset($_SESSION['error_message']);
        }
        ?>

        <div class="content-header">
            <h2 class="content-title">
                <i class="fas fa-list-alt"></i> Pending Leave Applications for <?php echo htmlspecialchars($mentee_details['fullname']); ?>
            </h2>
            <span class="leave-count"><?php echo count($mentee_leaves); ?> pending leave(s)</span>
        </div>

        <?php if (!empty($mentee_leaves)): ?>
            <table class="leave-table">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Dates</th>
                        <th>Reason</th>
                        <th>Date Applied</th>
                        <th>Overall Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mentee_leaves as $leave): ?>
                        <tr>
                            <td data-label="Leave Type"><?php echo htmlspecialchars($leave['leave_type_name']); ?></td>
                            <td data-label="Dates">
                                <?php echo date("M d, Y", strtotime($leave['start_date'])); ?> to<br>
                                <?php echo date("M d, Y", strtotime($leave['end_date'])); ?>
                            </td>
                            <td data-label="Reason">
                                <?php echo htmlspecialchars($leave['reason']); ?>
                                <?php if ($leave['rejection_reason']): ?>
                                    <div class="rejection-reason">Reason: <?php echo htmlspecialchars($leave['rejection_reason']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td data-label="Date Applied"><?php echo date("M d, Y H:i", strtotime($leave['date_applied'])); ?></td>
                            <td data-label="Overall Status">
                                <?php echo getStatusText($leave['mentor_status'], $leave['pm_status']); ?>
                            </td>
                            <td data-label="Actions">
                                <div class="action-buttons">
                                    <form action="process_leave_mentoraction.php" method="POST" style="display:inline-block;">
                                        <input type="hidden" name="application_id" value="<?php echo $leave['application_id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="mentee_employee_id" value="<?php echo $mentee_employee_id; ?>">
                                        <button type="submit"><i class="fas fa-check"></i> Approve</button>
                                    </form>
                                    <button type="button" class="reject-btn reject" data-id="<?php echo $leave['application_id']; ?>"><i class="fas fa-times"></i> Reject</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-leaves">
                <i class="fas fa-calendar-times"></i>
                <p><?php echo htmlspecialchars($mentee_details['fullname']); ?> has no pending leave applications.</p>
            </div>
        <?php endif; ?>

        <a href="mentee_list.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Mentee List
        </a>
    </div>

    <div id="rejectionModal" class="modal">
        <div class="modal-content">
            <span class="close-button">&times;</span>
            <h3>Reject Leave Application</h3>
            <form id="rejectionForm" action="process_leave_mentoraction.php" method="POST">
                <input type="hidden" name="application_id" id="modalApplicationId">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="mentee_employee_id" value="<?php echo $mentee_employee_id; ?>">
                <label for="rejection_reason">Reason for rejection:</label>
                <textarea id="rejection_reason" name="rejection_reason" rows="5" required></textarea>
                <button type="submit">Submit Rejection</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Table row animation
            const rows = document.querySelectorAll('.leave-table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                row.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100 * index);
            });

            // Modal functionality
            const modal = document.getElementById('rejectionModal');
            const closeButton = document.querySelector('.close-button');
            const rejectButtons = document.querySelectorAll('.reject-btn');
            const modalApplicationId = document.getElementById('modalApplicationId');
            const rejectionReasonTextarea = document.getElementById('rejection_reason');

            rejectButtons.forEach(button => {
                button.addEventListener('click', function() {
                    modalApplicationId.value = this.dataset.id;
                    rejectionReasonTextarea.value = ''; // Clear previous reason
                    modal.style.display = 'flex'; // Use flex to center the modal
                });
            });

            closeButton.addEventListener('click', function() {
                modal.style.display = 'none';
            });

            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>