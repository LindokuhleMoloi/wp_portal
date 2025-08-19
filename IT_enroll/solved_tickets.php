

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solved Tickets</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #87CEEB, #F0E68C);
            margin: 0;
            padding: 0;
        }

        .container {
            background-color: #ffffff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 80%;
            margin: 20px auto;
            text-align: center;
            overflow-y: auto;
        }

        h1 {
            font-size: 2.3rem;
            color: #333;
            margin-bottom: 20px;
        }

        .ticket-button {
            background-color: #4CAF50;
            padding: 10px;
            margin: 10px;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .ticket-button:hover {
            background-color: #45a049;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        table th {
            background-color: #f4f4f4;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        .status-pending {
            background-color: #FFA07A;
            padding: 5px;
            border-radius: 5px;
            color: #fff;
        }

        .status-solved {
            background-color: #4CAF50;
            padding: 5px;
            border-radius: 5px;
            color: #fff;
        }

        .status-escalated {
            background-color: #FF5722;
            padding: 5px;
            border-radius: 5px;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Solved Tickets</h1>

        <!-- Button to toggle back to assigned tickets -->
      

        <!-- Solved Tickets Table -->
        <h2>Solved Tickets</h2>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Ticket Number</th>
                    <th>Employee Code</th>
                    <th>Employee Name</th>
                    <th>Assigned Support Member</th>
                    <th>Issue Description</th>
                    <th>Date Assigned</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                $qry = $conn->query("
                    SELECT 
                        stt.id, 
                        stt.ticket_id, 
                        stt.employee_code, 
                        stt.name AS employee_name, 
                        stt.support_member_name, 
                        stt.issue_description, 
                        stt.date_ticket_assigned, 
                        stt.status,
                        hsi.ticket_number 
                    FROM support_team_ticketbox stt
                    JOIN helpdesk_support_incoming hsi ON stt.ticket_id = hsi.id
                    WHERE stt.status = 'Solved'  -- Only showing solved tickets
                    ORDER BY stt.date_ticket_assigned DESC
                ");
                
                while ($row = $qry->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td><?php echo htmlspecialchars($row['ticket_number']); ?></td>
                        <td><?php echo htmlspecialchars($row['employee_code']); ?></td>
                        <td><?php echo htmlspecialchars($row['employee_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['support_member_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['issue_description']); ?></td>
                        <td><?php echo date("Y-m-d H:i", strtotime($row['date_ticket_assigned'])); ?></td>
                        <td>
                            <span class="status-solved">Solved</span>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
