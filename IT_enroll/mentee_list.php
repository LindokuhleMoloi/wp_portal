<?php
session_start();

// Redirect if not logged in as mentor
if (!isset($_SESSION['mentor_logged_in'])) {
    header("Location: mentor_login.php");
    exit();
}

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

// --- Fetch Mentees ---
// The original query is good for getting the mentees and their pending leave counts
$mentees = [];
$sql_mentees = "SELECT
                el.id, el.employee_code, el.fullname,
                el.email, el.contact, dl.name AS designation,
                el.contract_start_date, el.contract_end_date,
                COUNT(CASE WHEN la.mentor_status = 0 THEN la.id ELSE NULL END) AS pending_leaves_count,
                COUNT(la.id) AS total_leaves_count
            FROM employee_list el
            LEFT JOIN designation_list dl ON el.designation_id = dl.id
            LEFT JOIN leave_applications la ON el.id = la.employee_id
            WHERE el.mentor_id = ?
            GROUP BY el.id
            ORDER BY el.fullname ASC";
$stmt_mentees = $conn->prepare($sql_mentees);
if ($stmt_mentees) {
    $stmt_mentees->bind_param("i", $mentor_id);
    $stmt_mentees->execute();
    $result_mentees = $stmt_mentees->get_result();
    if ($result_mentees->num_rows > 0) {
        while($row = $result_mentees->fetch_assoc()) {
            $mentees[] = $row;
        }
    }
    $stmt_mentees->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Mentees</title>
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

        .mentee-count {
            background-color: var(--secondary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 1rem;
            font-weight: 600;
        }

        /* Mentee Table */
        .mentee-table {
            width: 100%;
            border-collapse: collapse;
        }

        .mentee-table th {
            background-color: var(--light-color);
            color: var(--dark-color);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .mentee-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }

        .mentee-table tr:last-child td {
            border-bottom: none;
        }

        .mentee-table tr:hover td {
            background-color: rgba(15, 101, 116, 0.05);
        }

        .mentee-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .mentee-avatar {
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
        }

        .mentee-details {
            line-height: 1.4;
        }

        .mentee-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .mentee-code {
            font-size: 0.8rem;
            color: #666;
        }

        .mentee-contact {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .mentee-email {
            color: var(--primary-color);
            text-decoration: none;
        }

        .mentee-email:hover {
            text-decoration: underline;
        }

        .contract-dates {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .contract-date {
            font-size: 0.85rem;
        }

        .pending-leaves-badge { /* Renamed for clarity */
            background-color: var(--warning-color);
            color: #333;
            padding: 3px 10px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: 700;
            display: inline-block;
        }

        .view-leaves-btn { /* New button style */
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            margin-top: 5px; /* Spacing between view_mentee and view_leaves */
        }

        .view-leaves-btn:hover {
            background-color: #0a4f5b;
            transform: translateY(-2px);
        }

        .view-profile-btn { /* Renamed original view-btn for clarity */
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }

        .view-profile-btn:hover {
            background-color: #1e96c8;
            transform: translateY(-2px);
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

        /* No Mentees */
        .no-mentees {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        .no-mentees i {
            font-size: 3rem;
            color: var(--accent-color);
            margin-bottom: 20px;
        }

        .no-mentees p {
            font-size: 1.2rem;
            margin-bottom: 20px;
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
            
            .mentee-table {
                display: block;
                overflow-x: auto;
            }
            .mentee-table thead, .mentee-table tbody, .mentee-table th, .mentee-table td, .mentee-table tr {
                display: block;
            }
            .mentee-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            .mentee-table tr {
                border: 1px solid #ccc;
                margin-bottom: 10px;
                border-radius: 8px;
                overflow: hidden;
            }
            .mentee-table td {
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: right;
            }
            .mentee-table td:before {
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
            .mentee-table td:last-child {
                border-bottom: none;
            }
            .mentee-info {
                justify-content: flex-end;
            }
            .mentee-details {
                text-align: right;
            }
            .action-buttons-group { /* New class for grouping view buttons */
                display: flex;
                flex-direction: column;
                gap: 5px;
                align-items: flex-end; /* Align buttons to the right */
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
            <i class="fas fa-user-graduate"></i> MY MENTEES
        </h1>
        <div class="welcome-message">
            Welcome, <span><?php echo htmlspecialchars($mentor_fullname); ?></span>
        </div>
        <div class="header-buttons">
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
        <div class="content-header">
            <h2 class="content-title">
                <i class="fas fa-user-friends"></i> Mentee List
            </h2>
            <span class="mentee-count"><?php echo count($mentees); ?> mentees</span>
        </div>

        <?php if (!empty($mentees)): ?>
            <table class="mentee-table">
                <thead>
                    <tr>
                        <th>Mentee</th>
                        <th>Designation</th>
                        <th>Contact</th>
                        <th>Contract Dates</th>
                        <th>Status</th>
                        <th>Actions</th> </tr>
                </thead>
                <tbody>
                    <?php foreach ($mentees as $mentee): ?>
                        <tr>
                            <td data-label="Mentee">
                                <div class="mentee-info">
                                    <div class="mentee-avatar">
                                        <?php echo strtoupper(substr($mentee['fullname'], 0, 1)); ?>
                                    </div>
                                    <div class="mentee-details">
                                        <div class="mentee-name"><?php echo htmlspecialchars($mentee['fullname']); ?></div>
                                        <div class="mentee-code"><?php echo htmlspecialchars($mentee['employee_code']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Designation"><?php echo htmlspecialchars($mentee['designation']); ?></td>
                            <td data-label="Contact" class="mentee-contact">
                                <a href="mailto:<?php echo htmlspecialchars($mentee['email']); ?>" class="mentee-email">
                                    <?php echo htmlspecialchars($mentee['email']); ?>
                                </a>
                                <span><?php echo htmlspecialchars($mentee['contact']); ?></span>
                            </td>
                            <td data-label="Contract Dates" class="contract-dates">
                                <span class="contract-date"><strong>Start:</strong> <?php echo date("M d, Y", strtotime($mentee['contract_start_date'])); ?></span>
                                <?php if ($mentee['contract_end_date']): ?>
                                    <span class="contract-date"><strong>End:</strong> <?php echo date("M d, Y", strtotime($mentee['contract_end_date'])); ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <?php if ($mentee['pending_leaves_count'] > 0): ?>
                                    <a href="pending_leaves.php" class="pending-leaves-badge">
                                        <?php echo $mentee['pending_leaves_count']; ?> pending leave(s)
                                    </a>
                                <?php else: ?>
                                    <span style="color: var(--success-color);">No pending leaves</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Actions">
                                <div class="action-buttons-group">
                                    <a href="view_mentee.php?id=<?php echo $mentee['id']; ?>" class="view-profile-btn">
                                        <i class="fas fa-eye"></i> View Profile
                                    </a>
                                    <a href="view_mentee_leaves.php?employee_id=<?php echo $mentee['id']; ?>" class="view-leaves-btn">
                                        <i class="fas fa-calendar-alt"></i> View All Leaves (<?php echo $mentee['total_leaves_count']; ?>)
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-mentees">
                <i class="fas fa-user-slash"></i>
                <p>No mentees currently assigned to you</p>
                <a href="mentor_dashboard.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        <?php endif; ?>

        <a href="mentor_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <script>
        // Simple animation on page load
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('.mentee-table tbody tr');
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                row.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>