<?php
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['employee_code'])) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "tarryn_workplaceportal";

    $mysqli = new mysqli($servername, $username, $password, $database);

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    $employee_code = $_GET['employee_code'];
    $tickets_per_page = 5;
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($current_page - 1) * $tickets_per_page;

    // Get total number of tickets
    $total_sql = "SELECT COUNT(*) AS total_tickets FROM administered_tickets WHERE employee_code = ?";
    $stmt = $mysqli->prepare($total_sql);
    $stmt->bind_param("s", $employee_code);
    $stmt->execute();
    $total_result = $stmt->get_result();
    $total_row = $total_result->fetch_assoc();
    $total_tickets = $total_row['total_tickets'];

    // Fetch tickets with assigned support member's name and screenshot
    $sql = "SELECT at.ticket_id, at.issue_description, at.created_at, at.status, at.screenshot,
                        st.name AS assigned_to_name
                FROM administered_tickets at
                LEFT JOIN support_team st ON at.assigned_to = st.support_member_id
                WHERE at.employee_code = ?
                LIMIT ? OFFSET ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sii", $employee_code, $tickets_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    echo "Invalid request.";
    exit;
}

$total_pages = ceil($total_tickets / $tickets_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            margin: 0;
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #0f4c75, #3282b8);
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            min-height: 100vh;
            color: #f6f7eb;
            text-align: center;
            padding: 10px;
            overflow-x: hidden;
        }

        .container {
            width: 90%;
            max-width: 1000px;
            padding: 40px;
            background: #1b262c;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            animation: fadeIn 1s ease-in-out;
            margin-left: 5%;
            overflow: hidden;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
            border-bottom: 3px solid #50c878;
            padding-bottom: 10px;
        }

        #all-tickets-logged {
            margin-top: 20px;
            background: #2e3b47;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .section-title {
            font-size: 1.5rem;
            color: #50c878;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #50c878;
            padding-bottom: 10px;
            text-transform: uppercase;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            min-width: 800px;
        }

        th, td {
            padding: 15px;
            text-align: center;
            border: 1px solid #4e5d6a;
        }

        td:nth-child(2) {
            width: 30%;
            font-family: 'Courier New', monospace;
        }

        th {
            background-color: #50c878;
            color: #f6f7eb;
        }

        tr:nth-child(even) {
            background-color: #2e3b47;
        }

        tr:hover {
            background-color: #4e5d6a;
        }

        .status-badge {
            padding: 7px 12px;
            border-radius: 10px;
            font-weight: bold;
            text-align: center;
            display: inline-block;
        }

        .status-open {
            background-color: #ff4d4d;
            color: #fff;
        }

        .status-closed {
            background-color: #50c878;
            color: #fff;
        }

        .status-pending {
            background-color: #f4c542;
            color: #fff;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .pagination a {
            color: #f6f7eb;
            padding: 10px 16px;
            margin: 0 8px 8px 0;
            text-decoration: none;
            border: 2px solid #50c878;
            border-radius: 8px;
        }

        .pagination a:hover {
            background-color: #50c878;
            color: #fff;
        }

        .pagination .active {
            background-color: #50c878;
            color: #fff;
        }

        button.back-arrow {
            background-color: #3282b8;
            color: #f6f7eb;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-size: 1rem;
            cursor: pointer;
            margin-bottom: 20px;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }

        button.back-arrow:hover {
            background-color: #0f4c75;
        }

        .notification-container {
            position: absolute;
            top: 20px;
            right: 30px;
        }

        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: #000;
            display: inline-block;
            position: relative;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .notification-icon::before {
            content: '\1F514';
            font-size: 30px;
            color: #fff;
            position: absolute;
            top: 7px;
            left: 7px;
        }

        .notification-icon.red {
            background-color: #ff4d4d;
        }

        .notification-icon.black {
            background-color: #000;
        }

        /* Notification Popup */
        .notification-popup {
            position: absolute;
            top: 80px;
            right: 30px;
            background-color: #fff;
            color: #333;
            border-radius: 8px;
            padding: 15px;
            width: 250px;
            display: none;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .notification-popup h4 {
            margin-top: 0;
            font-size: 1.2rem;
        }

        .notification-popup p {
            margin: 0;
            font-size: 1rem;
        }

        .screenshot-preview {
            max-width: 100px;
            max-height: 100px;
            border-radius: 5px;
            margin-top: 5px;
            cursor: pointer;
        }

        .footer {
            margin-top: 50px;
            padding: 20px 0;
            background-color: #1b262c;
            color: #f6f7eb;
            text-align: center;
            width: 100%;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.9);
        }

        .modal-content {
            margin: auto;
            display: block;
            max-width: 90%;
            max-height: 90%;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #f1f1f1;
            font-size: 40px;
            font-weight: bold;
            transition: 0.3s;
        }

        .modal-close:hover,
        .modal-close:focus {
            color: #bbb;
            text-decoration: none;
            cursor: pointer;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            h1 {
                font-size: 1.8rem;
            }

            .container {
                padding: 20px;
                margin-left: 0;
            }

            .pagination a {
                font-size: 0.9rem;
                padding: 8px 12px;
                margin: 0 5px 5px 0;
            }

            button.back-arrow {
                font-size: 0.9rem;
                padding: 10px 15px;
            }

            td:nth-child(2) {
                width: 50%;
            }

            .screenshot-preview {
                max-width: 70px;
                max-height: 70px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="back-arrow" onclick="window.location.href='follow_up.php';">&larr; Back</button>
        <header>
            <h1>Your Dashboard</h1>
            <div class="notification-container">
                <span id="message-notification" class="notification-icon red" onclick="toggleNotification()"></span>
                <div id="notification-popup" class="notification-popup">
                    <h4>Ticket Solved</h4>
                    <p>Ticket ID: 12345</p>
                    <p>Issue: System Crash</p>
                    <p>Status: Closed</p>
                    <p>Resolved at: 2025-02-18 10:30 AM</p>
                </div>
            </div>
        </header>

        <div id="all-tickets-logged">
            <div class="section-title">All Tickets Logged</div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Ticket ID</th>
                            <th>Issue</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>IT Support Assigned</th>
                            <th>Screenshot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['ticket_id'] ?></td>
                                <td><?= $row['issue_description'] ?></td>
                                <td><?= $row['created_at'] ?></td>
                                <td class="status-badge status-<?= strtolower($row['status']) ?>"><?= ucfirst($row['status']) ?></td>
                                <td><?= $row['assigned_to_name'] ? $row['assigned_to_name'] : 'Unassigned'; ?></td>
                                <td>
                                    <?php if ($row['screenshot']): ?>
                                        <img src="https://workplaceportal.pro-learn.co.za/IT_enroll/public_html/IT_enroll/uploads/<?= htmlspecialchars($row['screenshot']) ?>" alt="Screenshot" class="screenshot-preview" onclick="openModal('https://workplaceportal.pro-learn.co.za/IT_enroll/public_html/IT_enroll/uploads/<?= htmlspecialchars($row['screenshot']) ?>')">
                                    <?php else: ?>
                                        No Screenshot
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pagination">
            <?php for ($page = 1; $page <= $total_pages; $page++): ?>
                <a href="?employee_code=<?= $employee_code; ?>&page=<?= $page; ?>"
                    class="<?= $page == $current_page ? 'active' : ''; ?>">
                    <?= $page; ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; Developed by Artisans Republik, division of Progression</p>
    </footer>

    <div id="screenshotModal" class="modal">
        <span class="modal-close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="fullScreenshot">
    </div>

    <script>
        // Toggle the visibility of the notification popup
        function toggleNotification() {
            var popup = document.getElementById('notification-popup');
            if (popup.style.display === 'none' || popup.style.display === '') {
                popup.style.display = 'block';
            } else {
                popup.style.display = 'none';
            }
        }
        
        
         // Open Modal
         
              function openModal(imgSrc) {
            var modal = document.getElementById("screenshotModal");
            var modalImg = document.getElementById("fullScreenshot");
            modal.style.display = "block";
            modalImg.src = imgSrc;
        }

        // Close Modal
        function closeModal() {
            var modal = document.getElementById("screenshotModal");
            modal.style.display = "none";
        }

        // Close modal if clicked outside the image
        window.onclick = function(event) {
            var modal = document.getElementById("screenshotModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>