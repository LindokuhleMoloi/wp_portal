<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Report - Under Construction</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100%" height="100%" fill="#f4f4f4" /><g fill="#ddd"><circle cx="25" cy="25" r="10" /><circle cx="75" cy="75" r="10" /><circle cx="25" cy="75" r="10" /><circle cx="75" cy="25" r="10" /></g></svg>') repeat; /* Light pattern background */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.9); /* Slightly transparent white */
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        h1 {
            font-size: 3em;
            color: #ff9800; /* Construction orange */
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .construction-icon {
            font-size: 5em;
            color: #ff5722; /* Brighter construction red */
            margin-bottom: 30px;
            animation: rotateConstruction 2s linear infinite;
        }

        @keyframes rotateConstruction {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        p {
            font-size: 1.2em;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #555;
        }

        .update-message {
            background-color: #e8f5e9; /* Light green for positive updates */
            color: #43a047; /* Darker green for text */
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            font-weight: bold;
        }

        .table-container {
            margin-top: 40px;
            width: 100%;
            overflow-x: auto; /* Enable horizontal scrolling on smaller screens */
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #ddd;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"> </head>
<body>
    <div class="container">
        <i class="fas fa-tools construction-icon"></i>
        <h1>Leave Application System - Under Construction</h1>
        <p>This leave report feature is currently being developed by IT and Design Team. We're working hard to bring you a seamless and efficient way to manage your leave information.</p>
        <p>Stay tuned for updates and enhancements!</p>
        <div class="update-message">
            <i class="fas fa-sync-alt fa-spin"></i> Updates are coming soon!
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Employee Code</th>
                        <th>Employee Name</th>
                        <th>Leave Type</th>
                        <th>Total Entitlement (Days)</th>
                        <th>Used Leave (Days)</th>
                        <th>Remaining Leave (Days)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaveBalances as $balance): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($balance['employee_code']); ?></td>
                            <td><?php echo htmlspecialchars($balance['name']); ?></td>
                            <td><?php echo htmlspecialchars($balance['leave_type']); ?></td>
                            <td><?php echo htmlspecialchars($balance['total_entitlement']); ?></td>
                            <td><?php echo htmlspecialchars($balance['used_leave']); ?></td>
                            <td><?php echo htmlspecialchars($balance['remaining_leave']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>