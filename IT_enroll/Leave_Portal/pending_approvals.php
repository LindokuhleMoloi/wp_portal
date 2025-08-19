<?php
// pending_approvals.php
session_start();

// Redirect if not logged in as project manager
if (!isset($_SESSION['pm_logged_in'])) {
    header("Location: project_manager_login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "tarryn_Lindokuhle";
$password = "L1nd0kuhle";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current PM details
$pm_id = $_SESSION['pm_employee_id'];
$pm_fullname = $_SESSION['pm_fullname'];

// --- Fetch Pending Leave Requests ---
$pending_leaves = [];
$sql_leaves = "SELECT
                    la.id, la.start_date, la.end_date, la.reason, la.status,
                    el.fullname AS employee_name, el.employee_code, el.avatar, -- Fetch avatar
                    lt.name AS leave_type,
                    DATEDIFF(la.end_date, la.start_date) + 1 AS days_requested
                FROM leave_applications la
                JOIN employee_list el ON la.employee_id = el.id
                JOIN leave_types lt ON la.leave_type_id = lt.id
                WHERE el.project_manager_id = ? AND la.status = 0
                ORDER BY la.start_date ASC";
$stmt_leaves = $conn->prepare($sql_leaves);
if ($stmt_leaves) {
    $stmt_leaves->bind_param("i", $pm_id);
    $stmt_leaves->execute();
    $result_leaves = $stmt_leaves->get_result();
    if ($result_leaves->num_rows > 0) {
        while($row = $result_leaves->fetch_assoc()) {
            $pending_leaves[] = $row;
        }
    }
    $stmt_leaves->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Approvals</title>
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
            right: 30px;
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
            padding: 30px 100px 30px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: -15px -15px 30px -15px;
            width: calc(100% + 30px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            position: relative;
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
            margin-right: 70px;
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

        .pending-count {
            background-color: var(--secondary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Leave Requests Table */
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

        .leave-employee {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .leave-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.1rem;
            overflow: hidden;
        }
        .leave-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .employee-details {
            line-height: 1.4;
        }

        .employee-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .employee-code {
            font-size: 0.8rem;
            color: #666;
        }

        .leave-dates {
            white-space: nowrap;
        }

        .leave-days {
            font-weight: 600;
            color: var(--primary-color);
        }

        .leave-reason {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .leave-reason:hover {
            white-space: normal;
            overflow: visible;
        }

        .leave-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .approve-btn {
            background-color: var(--success-color);
            color: white;
        }

        .approve-btn:hover {
            background-color: #218838;
        }

        .reject-btn {
            background-color: var(--danger-color);
            color: white;
        }

        .reject-btn:hover {
            background-color: #c82333;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .modal-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--dark-color);
        }

        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--dark-color);
        }

        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
            min-height: 100px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* No Pending Leaves */
        .no-pending {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        .no-pending i {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        .no-pending p {
            font-size: 1.2rem;
            margin-bottom: 20px;
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

        /* Notification Styles */
        .notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(20px);
            padding: 15px 30px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            opacity: 0;
            transition: all 0.3s ease-out;
            z-index: 1000;
            max-width: 80%;
            text-align: center;
        }

        .notification.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .notification.success {
            background-color: var(--success-color);
        }

        .notification.error {
            background-color: var(--danger-color);
        }

        .notification i {
            font-size: 1.2rem;
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
                margin-right: 0;
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
            }

            .leave-actions {
                flex-direction: column;
                gap: 5px;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }

            .notification {
                width: 90%;
                padding: 12px 20px;
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
            <i class="fas fa-calendar-check"></i> PENDING APPROVALS
        </h1>
        <div class="welcome-message">
            Welcome, <span><?php echo htmlspecialchars($pm_fullname); ?></span>
        </div>
        <div class="header-buttons">
            <a href="project_manager_profile.php" class="header-btn">
                <i class="fas fa-user-cog"></i> Profile
            </a>
            <a href="pm_logout.php" class="header-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="content-container">
        <div class="content-header">
            <h2 class="content-title">
                <i class="fas fa-clock"></i> Pending Leave Requests
            </h2>
            <span class="pending-count" id="pendingCount"><?php echo count($pending_leaves); ?> pending</span>
        </div>

        <?php if (!empty($pending_leaves)): ?>
            <table class="leave-table" id="leaveTable">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Dates</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_leaves as $leave): ?>
                        <tr id="leaveRow-<?php echo $leave['id']; ?>">
                            <td>
                                <div class="leave-employee">
                                    <div class="leave-avatar">
                                        <?php if (!empty($leave['avatar'])): ?>
                                            <img src="<?php echo htmlspecialchars($leave['avatar']); ?>" alt="Avatar">
                                        <?php else: ?>
                                            <?php echo strtoupper(substr($leave['employee_name'], 0, 1)); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="employee-details">
                                        <div class="employee-name"><?php echo htmlspecialchars($leave['employee_name']); ?></div>
                                        <div class="employee-code"><?php echo htmlspecialchars($leave['employee_code']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                            <td class="leave-dates">
                                <?php echo date('M j', strtotime($leave['start_date'])); ?> -
                                <?php echo date('M j, Y', strtotime($leave['end_date'])); ?>
                            </td>
                            <td class="leave-days"><?php echo $leave['days_requested']; ?></td>
                            <td class="leave-reason" title="<?php echo htmlspecialchars($leave['reason']); ?>">
                                <?php echo htmlspecialchars($leave['reason']); ?>
                            </td>
                            <td>
                                <div class="leave-actions">
                                    <button class="action-btn approve-btn" onclick="approveLeave(<?php echo $leave['id']; ?>)">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button class="action-btn reject-btn" onclick="showRejectModal(<?php echo $leave['id']; ?>)">
                                        <i class="fas fa-times"></i> Reject
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-pending" id="noPendingMessage">
                <i class="fas fa-check-circle"></i>
                <p>No pending leave requests to approve</p>
                <p>All caught up!</p>
            </div>
        <?php endif; ?>

        <a href="project_manager_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <div class="modal" id="rejectModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Reject Leave Request</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form id="rejectForm" onsubmit="submitRejection(event)">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="leave_id" id="modalLeaveId">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejectReason">Reason for Rejection</label>
                        <textarea id="rejectReason" name="reason" required placeholder="Please provide a reason for rejecting this leave request..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="action-btn" onclick="closeModal()" style="background-color: #6c757d; color: white;">
                        Cancel
                    </button>
                    <button type="submit" class="action-btn reject-btn">
                        <i class="fas fa-times"></i> Confirm Rejection
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show reject modal with leave ID
        function showRejectModal(leaveId) {
            document.getElementById('modalLeaveId').value = leaveId;
            document.getElementById('rejectModal').style.display = 'flex';
            document.getElementById('rejectReason').focus();
        }

        // Close modal
        function closeModal() {
            document.getElementById('rejectModal').style.display = 'none';
            document.getElementById('rejectReason').value = '';
        }

        // Approve leave with AJAX
        function approveLeave(leaveId) {
            if (confirm('Are you sure you want to approve this leave request?')) {
                fetch('process_leave_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=approve&leave_id=${leaveId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from table with animation
                        const row = document.getElementById(`leaveRow-${leaveId}`);
                        if (row) {
                            row.style.transition = 'all 0.3s ease-out';
                            row.style.opacity = '0';
                            row.style.height = '0';
                            row.style.padding = '0';
                            row.style.margin = '0';
                            row.style.border = 'none';

                            setTimeout(() => {
                                row.remove();
                                updatePendingCount();
                            }, 300);
                        }

                        // Show success notification
                        showNotification(data.message || 'Leave approved successfully!', 'success');
                    } else {
                        showNotification(data.message || 'Error approving leave', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('An error occurred', 'error');
                });
            }
        }

        // Submit rejection with AJAX
        async function submitRejection(event) {
            event.preventDefault();

            const leaveId = document.getElementById('modalLeaveId').value;
            const reason = document.getElementById('rejectReason').value;

            if (!reason.trim()) {
                showNotification('Please provide a reason for rejection', 'error');
                return;
            }

            try {
                const response = await fetch('process_leave_action.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=reject&leave_id=${leaveId}&reason=${encodeURIComponent(reason)}`
                });
                const data = await response.json();

                if (data.success) {
                    closeModal();

                    const row = document.getElementById(`leaveRow-${leaveId}`);
                    if (row) {
                        row.style.transition = 'all 0.3s ease-out';
                        row.style.opacity = '0';
                        row.style.height = '0';
                        row.style.padding = '0';
                        row.style.margin = '0';
                        row.style.border = 'none';

                        setTimeout(() => {
                            row.remove();
                            updatePendingCount();
                        }, 300);
                    }

                    showNotification(data.message || 'Leave rejected successfully!', 'success');
                } else {
                    showNotification(data.message || 'Error rejecting leave', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification('An error occurred', 'error');
            }
        }

        // Update pending count
        function updatePendingCount() {
            const count = document.querySelectorAll('#leaveTable tbody tr').length;
            document.getElementById('pendingCount').textContent = `${count} pending`;

            if (count === 0) {
                const table = document.getElementById('leaveTable');
                if (table) {
                    table.remove();
                    if (!document.getElementById('noPendingMessage')) {
                        const contentContainer = document.querySelector('.content-container');
                        if (contentContainer) {
                             contentContainer.insertAdjacentHTML('beforeend', `
                                <div class="no-pending" id="noPendingMessage">
                                    <i class="fas fa-check-circle"></i>
                                    <p>No pending leave requests to approve</p>
                                    <p>All caught up!</p>
                                </div>
                            `);
                        }
                    }
                }
            }
        }

        // Show notification
        function showNotification(message, type) {
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(n => n.remove());

            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                ${message}
            `;

            document.body.appendChild(notification);

            void notification.offsetWidth;

            notification.classList.add('show');

            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Simple animation on page load
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.leave-table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    row.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>