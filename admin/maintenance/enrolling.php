<?php
// Database connection
$servername = "localhost";
$username = "tarryn_Lindokuhle";
$password = "L1nd0kuhle";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize date variables
$from = isset($_GET['from']) ? $_GET['from'] : date("Y-m-d", strtotime(date('Y-m-d') . " -1 week"));
$to = isset($_GET['to']) ? $_GET['to'] : date("Y-m-d");

// Prepare and execute SQL query for device enrollment logs with date filtering
$sql = "
    SELECT l.employee_id, e.fullname, e.employee_code, l.type, l.date_created, l.date_out, l.SN_ID, lp.`Item name`, l.Return_time
    FROM Laptop_Logs l
    JOIN employee_list e ON l.employee_id = e.id
    JOIN Laptops lp ON l.SN_ID = lp.SN
    WHERE l.date_created BETWEEN ? AND ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ss', $from, $to);
$stmt->execute();
$result = $stmt->get_result();

// Fetch device statistics for the dashboard
$current_date = date("Y-m-d");

// Fetch total number of devices
$total_devices_query = "SELECT COUNT(*) as total_devices FROM Laptops";
$total_devices_result = $conn->query($total_devices_query);
$total_devices = ($total_devices_result && $total_devices_result->num_rows > 0) ? $total_devices_result->fetch_assoc()['total_devices'] : 0;

// Fetch checked-in devices
$checked_in_devices_query = "SELECT COUNT(*) as checked_in_devices FROM Laptop_Logs WHERE type = 1";
$checked_in_devices_result = $conn->query($checked_in_devices_query);
$checked_in_devices = ($checked_in_devices_result && $checked_in_devices_result->num_rows > 0) ? $checked_in_devices_result->fetch_assoc()['checked_in_devices'] : 0;

// Fetch checked-out devices
$checked_out_devices_query = "SELECT COUNT(*) as checked_out_devices FROM Laptop_Logs WHERE type = 2";
$checked_out_devices_result = $conn->query($checked_out_devices_query);
$checked_out_devices = ($checked_out_devices_result && $checked_out_devices_result->num_rows > 0) ? $checked_out_devices_result->fetch_assoc()['checked_out_devices'] : 0;

// Fetch devices checked-in but not checked out
$checked_in_not_out_query = "
    SELECT COUNT(*) as checked_in_not_out
    FROM Laptop_Logs l1
    WHERE l1.type = 1
    AND NOT EXISTS (
        SELECT 1
        FROM Laptop_Logs l2
        WHERE l2.SN_ID = l1.SN_ID
        AND l2.type = 2
    )
";
$checked_in_not_out_result = $conn->query($checked_in_not_out_query);
$checked_in_not_out = ($checked_in_not_out_result && $checked_in_not_out_result->num_rows > 0) ? $checked_in_not_out_result->fetch_assoc()['checked_in_not_out'] : 0;

// Calculate remaining devices
$remaining_devices = $total_devices - $checked_out_devices;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Enrollment Logs & Dashboard</title>

    <style>
        /* Dashboard and Table Styling */
        .dashboard-counts {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }
        .count-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            width: 30%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .count-number {
            font-weight: bold;
            font-size: 24px;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h3>Devices Enrollment Logs & Dashboard</h3>
        <div class="dashboard-counts">
            <div class="count-info">
                <strong>Devices Checked In</strong>
                <span class="count-number"><?php echo $checked_in_devices; ?></span>
            </div>
            <div class="count-info">
                <strong>Devices Checked In but Not Yet Out</strong>
                <span class="count-number"><?php echo $checked_in_not_out; ?></span>
            </div>
            <div class="count-info">
                <strong>Total Devices</strong>
                <span class="count-number"><?php echo $total_devices; ?></span>
            </div>
            <div class="count-info">
                <strong>Devices Checked Out</strong>
                <span class="count-number"><?php echo $checked_out_devices; ?></span>
            </div>
            <div class="count-info">
                <strong>Devices Remaining</strong>
                <span class="count-number"><?php echo $remaining_devices; ?></span>
            </div>
        </div>

        <!-- Device Logs Table -->
        <div class="card card-outline card-primary">
            <div class="card-header">
                <h3 class="card-title">Device Enrollment Logs</h3>
                <div class="card-tools">
                    <a href="<?php echo base_url ?>admin/?page=maintenance/allDevices" class="btn btn-flat btn-info">
                        <span class="fas fa-list"></span> All Devices
                    </a>
                    <button type="button" class="btn btn-flat btn-success" id="print">
                        <span class="fas fa-print"></span> Print
                    </button>
                </div>
            </div>
            <div class="card-body">
                <fieldset>
                    <legend class="text-info">Filter Date Range</legend>
                    <form action="" id="filter-data">
                        <div class="row align-items-end">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="from" class="control-label text-info">From</label>
                                    <input type="date" id="from" name="from" class="form-control form-control-sm rounded-0" value="<?php echo $from; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="to" class="control-label text-info">To</label>
                                    <input type="date" id="to" name="to" class="form-control form-control-sm rounded-0" value="<?php echo $to; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-flat btn-sm btn-primary"><i class="fa fa-filter"></i> Filter</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </fieldset>

                <!-- DataTable for Logs -->
                <div id="print_out">
                    <div class="container-fluid">
                        <table id="logsTable" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Employee ID</th>
                                    <th>Employee Code</th>
                                    <th>Employee Name</th>
                                    <th>Device Name</th>
                                    <th>Date Checked In</th>
                                    <th>Date Checked Out</th>
                                    <th>Return Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i = 1;
                                while ($row = $result->fetch_assoc()) {
                                    echo "
                                    <tr>
                                        <td>{$i}</td>
                                        <td>{$row['employee_id']}</td>
                                        <td>{$row['employee_code']}</td>
                                        <td>{$row['fullname']}</td>
                                        <td>{$row['Item name']}</td>
                                        <td>{$row['date_created']}</td>
                                        <td>{$row['date_out']}</td>
                                        <td>{$row['Return_time']}</td>
                                    </tr>";
                                    $i++;
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DataTable and AJAX Script -->
   <script>
    var dtTable;
    var heldContent = '';

    $(document).ready(function(){
        $('.table td,.table th').addClass('py-1 px-2 align-middle');
        dtTable = $('.table').DataTable();

        $('#filter-data').submit(function(e){
            e.preventDefault();
            location.href = location.href + "&" + $(this).serialize();
        });

        $('#stick').click(function(){
            heldContent = $('#print_out').html();
            alert('Current content is now held.');
        });

        $('#print').click(function(){
            start_loader();
            dtTable.destroy();
            var _el = $('<div>');
            var _head = $('head').clone();
            _head.find('title').text("Employee Logs List - Print View");
            var p = $('<div>');

            if (heldContent) {
                p.html(heldContent);
            } else {
                p.html($('#print_out').html());
            }

            p.find('tr.text-light').removeClass("text-light bg-navy");
            _el.append(_head);
            _el.append('<div class="d-flex justify-content-center">' +
                      '<div class="col-1 text-right">' +
                      '<img src="<?php echo validate_image($_settings->info('logo')) ?>" width="65px" height="65px" />' +
                      '</div>' +
                      '<div class="col-10">' +
                      '<h4 class="text-center"><?php echo $_settings->info('name') ?></h4>' +
                      '<h4 class="text-center">Employee Logs List</h4>' +
                      '</div>' +
                      '<div class="col-1 text-right">' +
                      '</div>' +
                      '</div><hr/>');
            _el.append(p.html());
            var nw = window.open("", "", "width=1200,height=900,left=250,location=no,titlebar=yes");
            nw.document.write(_el.html());
            nw.document.close();
            setTimeout(() => {
                nw.print();
                setTimeout(() => {
                    nw.close();
                    end_loader();
                    dtTable = $('.table').DataTable();
                }, 200);
            }, 500);
        });
    });
</script>
