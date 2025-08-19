<?php 
// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Current date
$current_date = date("Y-m-d");

// Function to fetch employee logs based on filters
function fetchEmployeeLogs($conn, $filter) {
    $where = " WHERE 1";
    // Filter by date (current date)
    if (!empty($filter['date'])) {
        $where .= " AND DATE(l.`date_created`) = '" . $filter['date'] . "'";
    } else {
        $where .= " AND DATE(l.`date_created`) = '" . date("Y-m-d") . "'";
    }
    // Query to fetch logs
    $qry = $conn->query("SELECT l.*, e.fullname, e.employee_code, d.name as department
                         FROM `logs` l 
                         INNER JOIN `employee_list` e ON l.employee_id = e.id 
                         LEFT JOIN `department_list` d ON e.department_id = d.id
                         {$where}
                         ORDER BY e.department_id, l.employee_id, l.type");
    // Initialize arrays to store data
    $employee_logs = [];
    // Fetch and organize data
    while ($row = $qry->fetch_assoc()) {
        $employee_logs[] = $row;
    }
    return $employee_logs;
}

// Fetch employee logs for current day
$filter = [
    'date' => $current_date
];
$employee_logs = fetchEmployeeLogs($conn, $filter);

// Process the logs to get the count and manage logs display
$count_in_current_day = 0;
$count_out_logged_in = 0;
$employee_log_data = [];
foreach ($employee_logs as $log) {
    $employee_id = $log['employee_id'];
    if (!isset($employee_log_data[$employee_id])) {
        $employee_log_data[$employee_id] = [
            'fullname' => $log['fullname'],
            'employee_code' => $log['employee_code'],
            'department' => $log['department'],
            'logged_in' => null,
            'logged_out' => null
        ];
    }
    if ($log['type'] == 1) {
        $count_in_current_day++;
        $employee_log_data[$employee_id]['logged_in'] = $log['date_created'];
    } elseif ($log['type'] == 2) {
        $count_out_logged_in++;
        $employee_log_data[$employee_id]['logged_out'] = $log['date_created'];
    }
}
$count_remaining = $count_in_current_day - $count_out_logged_in;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Logs</title>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables CSS and JS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.4/js/jquery.dataTables.js"></script>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Custom CSS -->
    <style>
        .employee-counts {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-around;
        }
        .count-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 30%;
        }
        .count-info strong {
            display: block;
            margin-bottom: 10px;
            font-size: 18px;
            color: #333;
        }
        .count-number {
            font-weight: bold;
            font-size: 24px;
            color: #007bff;
        }
        #employee_logs_table {
            margin-top: 20px;
        }
        #employee_logs_table th,
        #employee_logs_table td {
            text-align: center;
        }
        #employee_logs_table thead th {
            background-color: #007bff;
            color: #fff;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        #employee_logs_table tbody td {
            background-color: #f9f9f9;
        }
        /* Updated Button Styling */
        .btn-unlogged {
            background-color: #007bff; /* Primary color */
            color: white; /* Text color */
            border: none; /* No border */
            padding: 10px 15px; /* Padding */
            border-radius: 5px; /* Rounded corners */
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth transition effects */
            font-size: 16px; /* Font larger for better readability */
            font-weight: 600; /* Slightly bolder text */
            cursor: pointer; /* Pointer cursor on hover */
        }
        .btn-unlogged:hover {
            background-color: #0056b3; /* Darker blue for hover effect */
            transform: scale(1.05); /* Slightly scales up the button on hover */
        }
        .btn-unlogged:active {
            background-color: #0056b3; /* Keep the hover color when clicked */
            transform: scale(0.95); /* Shrink slightly when pressed */
        }
        .btn-unlogged::after {
            content: ' \00d7'; /* Icon or special character */
            font-size: 18px; /* Size of the icon */
            margin-left: 5px; /* Space between text and icon */
        }
    </style>
</head>
<body>
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Employee Logs Statistics</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-flat btn-success" id="print"><span class="fas fa-print"></span> Print</button>
            </div>
            <div class="card-tools">
                <button type="button" class="btn btn-flat btn-unlogged" id="errorcorrect">
                    <span class="fas fa-sign-out-alt"></span> Everyone Out
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="container-fluid">
                <h4 class="text-info">Employee Logs Statistics</h4>
                <div class="mb-3">
                    <div class="employee-counts">
                        <div class="count-info">
                            <strong>Count of Ins (Current Day):</strong> <span class="count-number" id="count_in_current_day"><?php echo $count_in_current_day; ?></span>
                        </div>
                        <div class="count-info">
                            <strong>Count of Outs (Logged In Today):</strong> <span class="count-number" id="count_out_logged_in"><?php echo $count_out_logged_in; ?></span>
                        </div>
                        <div class="count-info">
                            <strong>Count Of Remaining:</strong> <span class="count-number" id="c_remaining"><?php echo $count_remaining; ?></span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="employee_logs_table" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Employee Code</th>
                                <th>Department</th>
                                <th>Time (Logged In)</th>
                                <th>Time (Logged Out)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            foreach ($employee_log_data as $employee_id => $log): 
                            ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo $log['fullname']; ?></td>
                                    <td><?php echo $log['employee_code']; ?></td>
                                    <td><?php echo $log['department']; ?></td>
                                    <td>
                                        <?php 
                                        if ($log['logged_in']) {
                                            echo date("Y-m-d H:i", strtotime($log['logged_in']));
                                        } else {
                                            echo '---';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($log['logged_out']) {
                                            echo date("Y-m-d H:i", strtotime($log['logged_out']));
                                        } else {
                                            echo 'Still in building';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Password Confirmation -->
    <div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">Enter Password</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="passwordForm">
                        <div class="form-group">
                            <label for="passwordInput">Password</label>
                            <input type="password" class="form-control" id="passwordInput" placeholder="Enter Password">
                        </div>
                        <div id="passwordError" class="text-danger" style="display:none;">Incorrect password, please try again.</div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmPasswordBtn">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle log everyone out button
            $('#log_everyone_out').on('click', function() {
                $('#passwordModal').modal('show');
            });

            // Handle password confirmation
            $('#confirmPasswordBtn').on('click', function() {
                var password = $('#passwordInput').val();
                if (password == "1234") { // Simple password check for demonstration
                    // Send the logout request for employee_id = 468
                    $.ajax({
                        type: "POST",
                        url: "log_all_out.php",
                        data: { logout_employee_id: 468 },
                        success: function(response) {
                            alert(response);
                            $('#passwordModal').modal('hide');
                        },
                        error: function() {
                            alert("There was an error processing your request.");
                        }
                    });
                } else {
                    $('#passwordError').show();
                }
            });

            // Handle the "Check For UnLogged People" button click
            $('#errorcorrect').on('click', function() {
                window.location.href = "<?php echo base_url ?>admin/?page=reports/statistics";
            });
        });
    </script>
</body>
</html>