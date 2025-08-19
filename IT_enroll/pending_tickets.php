

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Tickets</title>
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

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
        }

        .employee-code {
            font-size: 1.2rem;
            color: #333;
            font-weight: bold;
            background: #FFD700;
            padding: 5px 10px;
            border-radius: 5px;
            text-transform: uppercase;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.2);
        }

        .notifications {
            font-size: 1.5rem;
            color: #FF5722;
            cursor: pointer;
        }

        h1 {
            font-size: 2.3rem;
            color: #333;
            margin-bottom: 20px;
        }

        .wrapper {
            margin: 20px 0;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .wrapper:hover {
            transform: translateY(-5px);
        }

        .section-title {
            font-size: 1.6rem;
            color: #4CAF50;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #4CAF50;
            color: white;
        }

        td {
            background-color: #f9f9f9;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #333;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            font-size: 1.2rem;
            margin-top: 10px;
            cursor: pointer;
        }

        button:hover {
            background-color: #444;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Pending Tickets</h1>
    <div class="wrapper">
      
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
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php $counter = 1; ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><?php echo "prog-" . str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT); ?></td>
                            <td><?php echo $row['employee_code']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['assigned_support_member']; ?></td>
                            <td><?php echo $row['issue_description']; ?></td>
                            <td><?php echo $row['created_at']; ?></td>
                            <td><?php echo $row['status']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">No pending tickets found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>

<?php
// Close the database connection
mysqli_close($conn);
?>
