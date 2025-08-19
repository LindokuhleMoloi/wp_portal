<?php require_once('../config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Device Enrollment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #343a40;
            color: #fff;
            padding: 0.5rem 1rem; /* Adjusted padding for a smaller header */
            display: flex; /* Use Flexbox for layout */
            justify-content: space-between; /* Space between logo and nav links */
            align-items: center; /* Center vertically */
        }
        header .logo img {
            height: 50px; /* Logo height */
            width: auto;
        }
        .navbar-nav {
            margin: 0; /* Reset margin */
        }
        .navbar-nav .nav-link {
            color: #fff; /* White color for the links */
            margin-left: 1rem; /* Space between links */
        }
        .card {
            margin: 2rem auto;
            max-width: 700px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #343a40;
            color: #fff;
            border-bottom: none;
            padding: 1rem;
        }
        .nav-tabs .nav-link {
            color: #adb5bd;
            font-weight: 600;
        }
        .nav-tabs .nav-link.active {
            background-color: #007bff;
            color: #fff;
            border-radius: 8px;
        }
        .card-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 0.5rem;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
            font-size: 0.875rem; /* Thin text */
        }
        .btn-primary {
            background-color: #007bff;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem; /* Thin text */
        }
        .btn-danger {
            background-color: #dc3545;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem; /* Thin text */
        }
        .form-group {
            margin-bottom: 1rem;
        }
        select.form-control {
            font-size: 0.875rem; /* Thin text */
        }
    </style>
</head>
<body>
    <header>
        <div class="logo">
            <img src="../uploads/Workplace-Portal-topbar-badge.png" alt="Logo">
        </div>
        <nav class="navbar navbar-expand-lg navbar-dark">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="https://workplaceportal.pro-learn.co.za/admin/login.php">Admin</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://workplaceportal.pro-learn.co.za/admin/?page=reports/employee">Logs Report</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="https://workplaceportal.pro-learn.co.za/admin/?page=employee">Company Employee Codes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Write to us</a>
                </li>
            </ul>
        </nav>
    </header>
    <section id="home">
        <div class="container">
            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active" id="device-in-tab" data-toggle="pill" href="#device-in">Device In</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="device-out-tab" data-toggle="pill" href="#device-out">Device Out</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- Device In Tab -->
                        <div id="device-in" class="tab-pane fade show active">
                            <form id="device-in-form">
                                <input type="hidden" name="type" id="device-in-type" value="1">
                                <div class="form-group">
                                    <label for="employee_code_in">Employee Code:</label>
                                    <input type="text" id="employee_code_in" name="employee_code" class="form-control" placeholder="e.g. 2023-0001" required>
                                </div>
                                <div class="form-group">
                                    <label for="device_in">Select Device:</label>
                                    <select id="device_in" name="device_id" class="form-control" required>
                                        <option value="">Select a device</option>
                                        <?php
                                        // Fetch available devices
                                        $query = "SELECT `SN`, `ROTA OR Training` FROM Laptops;"  ;
                                        $result = $conn->query($query);
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<option value='{$row['SN']}'>{$row['ROTA OR Training']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Check In</button>
                            </form>
                        </div>
                        <!-- Device Out Tab -->
                        <div id="device-out" class="tab-pane fade">
                            <form id="device-out-form">
                                <input type="hidden" name="type" id="device-out-type" value="2">
                                <div class="form-group">
                                    <label for="employee_code_out">Employee ID:</label>
                                    <input type="text" id="employee_code_out" name="employee_code" class="form-control" placeholder="e.g. 2023-0001" required>
                                </div>
                                <div class="form-group">
                                    <label for="device_out">Select Device:</label>
                                    <select id="device_out" name="device_id" class="form-control" required>
                                        <option value="">Select a device</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-danger">Check Out</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Handle Device In form submission
            $('#device-in-form').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'log_device.php',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        alert(response);
                        $('#device-in-form')[0].reset();
                        fetchDeviceOutOptions();
                    }
                });
            });

            // Handle Device Out form submission
            $('#device-out-form').submit(function(e) {
                e.preventDefault();
                $.ajax({
                    url: 'log_device.php',
                    method: 'POST',
                    data: $(this).serialize(),
                    success: function(response) {
                        alert(response);
                        $('#device-out-form')[0].reset();
                        fetchDeviceOutOptions();
                    }
                });
            });

            // Fetch devices for Device Out tab when employee ID is entered
            $('#employee_code_out').on('blur', function() {
                fetchDeviceOutOptions();
            });

            function fetchDeviceOutOptions() {
                var employeeCodeOut = $('#employee_code_out').val();
                if (employeeCodeOut) {
                    $.post('fetch_device_out.php', {employee_code: employeeCodeOut}, function(data) {
                        $('#device_out').html(data);
                    });
                }
            }
        });
    </script>
</body>
</html>
