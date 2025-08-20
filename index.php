<?php 
require_once('./config.php');

// Start session and check authentication

?>
<!DOCTYPE html>
<html lang="en" class="" style="height: auto;">
<?php require_once('inc/header.php'); ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet" />
    <style>
      body {
        background-image: url("<?php echo validate_image($_settings->info('cover')) ?>");
        background-size: cover;
        background-repeat: no-repeat;
      }
      
      /* Authentication Overlay */
      .auth-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.9);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        color: white;
        text-align: center;
        flex-direction: column;
      }
      
      .auth-overlay-content {
        max-width: 600px;
        padding: 2rem;
      }
      
      .auth-overlay h2 {
        font-size: 2.5rem;
        margin-bottom: 1.5rem;
        color: #fff;
      }
      
      .auth-overlay p {
        font-size: 1.2rem;
        margin-bottom: 2rem;
        line-height: 1.6;
      }
      
      .auth-btn {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 12px 30px;
        font-size: 1.1rem;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-block;
      }
      
      .auth-btn:hover {
        background: #45a049;
        transform: scale(1.05);
      }
      
      /* Person Icon Styles */
      .person-icon {
        display: flex;
        flex-direction: column;
        align-items: center;
        margin: 0 15px;
        position: relative;
      }
      
      .icon-container {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
      }
      
      .icon-in .icon-container {
        background: linear-gradient(135deg, #4CAF50, #45a049);
        border: 3px solid #3d8b40;
      }
      
      .icon-out .icon-container {
        background: linear-gradient(135deg, #dc3545, #c82333);
        border: 3px solid #bd2130;
      }
      
      .icon-container i {
        font-size: 2.5rem;
        color: white;
        transition: all 0.3s ease;
      }
      
      .person-icon:hover .icon-container {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
      }
      
      .person-icon:hover .icon-container i {
        transform: scale(1.1);
      }
      
      .button-text {
        font-size: 1.4rem;
        font-weight: 600;
        color: #333;
        text-align: center;
        margin-top: 5px;
        text-shadow: 1px 1px 2px rgba(255,255,255,0.8);
      }
      
      .buttons-container {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 2rem;
        gap: 30px;
      }
      
      /* Existing Styles */
      .login-title {
        text-shadow: 2px 2px black;
        backdrop-filter: invert(.3);
      }
      * {
        padding: 0;
        margin: 0;
        box-sizing: border-box;
      }
      html {
        font-size: 62.5%;
      }
      *:not(i) {
        font-family: "Poppins", sans-serif;
      }
      header {
        position: fixed;
        width: 100%;
        background-color: black;
        padding: 3rem 5rem;
        height: 14%;
      }
      nav {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      nav ul {
        list-style: none;
        display: flex;
        gap: 2rem;
      }
      nav a {
        font-size: 1.8rem;
        text-decoration: none;
      }
      nav a#logo {
        color: #000000;
        font-weight: 700;
      }
      nav ul a {
        color: #ffffff;
        font-weight: 600;
      }
      nav ul a:hover {
        border-bottom: 2px solid #ffffff;
      }
      section#home {
        height: 100vh;
        display: grid;
        place-items: center;
      }
      h1 {
        font-size: 4rem;
      }
      #ham-menu {
        display: none;
      }
      nav ul.active {
        left: 0;
      }
      @media only screen and (max-width: 991px) {
        html {
          font-size: 56.25%;
        }
        header {
          padding: 2.2rem 5rem;
        }
      }
      @media only screen and (max-width: 767px) {
        html {
          font-size: 50%;
        }
        #ham-menu {
          display: block;
          color: #ffffff;
        }
        nav a#logo,
        #ham-menu {
          font-size: 3.2rem;
        }
        nav ul {
          background-color: black;
          position: fixed;
          left: -100vw;
          top: 73.6px;
          width: 100vw;
          height: calc(100vh - 73.6px);
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: space-around;
          transition: 1s;
          gap: 0;
        }
        .buttons-container {
          flex-direction: column;
          gap: 20px;
        }
        .person-icon {
          margin: 10px 0;
        }
      }
      .pill-btn {
        background-color: #007bff;
        color: white;
        font-size: 1.6rem;
        padding: 0.1rem 1.0rem;
        margin: 0 5px;
        border-radius: 50rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.3s ease;
      }
      .pill-btn:hover {
        background-color: #0056b3;
      }
      .input-group {
        display: flex;
        align-items: center;
        justify-content: center;
      }
      select#viscode {
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-color: #f0f0f0;
        border: 1px solid #ccc;
        padding: 0.5rem 1rem;
        font-size: 1.6rem;
        border-radius: 0.5rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        width: 100%;
        transition: all 0.3s ease;
      }
      select#viscode:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
        outline: none;
      }
      select#viscode option {
        padding: 0.5rem;
        font-size: 1.4rem;
      }
    </style>
</head>
<body>
    <!-- Authentication Overlay -->
    

    <header>
        <nav>
            <a href="#home" id="logo"><img src="uploads/Workplace-Portal-topbar-badge.png"></a>
            <i class="fas fa-bars" id="ham-menu"></i>
            <ul id="nav-bar">
                <li><a href="http://localhost/employee_gatepass/admin/login.php">Admin</a></li>
                <li><a href="http://localhost/employee_gatepass/admin/?page=reports/employee">Logs Report</a></li>
                <li><a href="http://localhost/employee_gatepass/admin/?page=employee" target="blank">Company Employee Codes</a></li>
                <li><a href="mailto:workplaceportal@artisansrepublik.com?subject=Feedback&body=Message" target="blank">Report a bug</a></li>
                <li><a href="http://localhost/employee_gatepass/IT_enroll/enrolling.php">Enroll</a></li>
                <li><a href="http://localhost/employee_gatepass/IT_enroll/Leave_Portal/follow_up.php">Employee Access</a></li>
            </ul>
        </nav>
    </header>
    
    <section id="home">
        <div class="w-100">
            <div class="row justify-content-center">
                <div class="col-12 col-sm-6">
                    <div class="card card-primary card-tabs">
                        <div class="card-header p-0 pt-1">
                            <ul class="nav nav-tabs" id="panel-tab" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="custom-tabs-one-employees-tab" data-toggle="pill" href="#custom-tabs-one-employees" role="tab" aria-controls="custom-tabs-one-employees" aria-selected="true">Employees</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-one-visitors-tab" data-toggle="pill" href="#custom-tabs-one-visitors" role="tab" aria-controls="custom-tabs-one-visitors" aria-selected="false">Visitors In</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-one-exit-tab" data-toggle="pill" href="#custom-tabs-one-exit" role="tab" aria-controls="custom-tabs-one-exit" aria-selected="false">Visitors Out and Exit</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="panel-tab-Content">
                                <!-- Employee Tab Content -->
                                <div class="tab-pane fade active show" id="custom-tabs-one-employees" role="tabpanel" aria-labelledby="custom-tabs-one-employees-tab">
                                    <div class="container-fluid py-5">
                                        <form action="" id="employee-log-form">
                                            <input type="hidden" name="type">
                                            <div class="col-12">
                                                <div class="row justify-content-center">
                                                    <div class="col-md-8">
                                                        <div class="form-group text-center">
                                                            <label for="employee_code">PLEASE ENTER THE EMPLOYEE CODE TO LOG THEM IN.</label>
                                                            <div class="input-group">
                                                                <button type="button" class="pill-btn" data-clipboard-text="2023-">2023-</button>
                                                                <input type="text" id="employee_code" name="employee_code" class="form-control form-control-lg rounded-0" placeholder="Hint 2023-0001" autofocus autocomplete="off">
                                                                <button type="button" class="pill-btn" data-clipboard-text="2024-">2024-</button>
                                                                <button type="button" class="pill-btn" data-clipboard-text="2025-">2025-</button>
                                                            </div>
                                                        </div>
                                                        <div class="buttons-container">
                                                            <div class="person-icon icon-in">
                                                                <div class="icon-container">
                                                                    <i class="fas fa-sign-in-alt"></i>
                                                                </div>
                                                                <button class="btn btn-lg rounded-pill btn-primary px-4 elog" type="button" data-type='1'>
                                                                    <span class="button-text">Check In</span>
                                                                </button>
                                                            </div>
                                                            <div class="person-icon icon-out">
                                                                <div class="icon-container">
                                                                    <i class="fas fa-sign-out-alt"></i>
                                                                </div>
                                                                <button class="btn btn-lg rounded-pill btn-danger px-4 elog" type="button" data-type='2'>
                                                                    <span class="button-text">Check Out</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Visitor Tab Content -->
                                <div class="tab-pane fade" id="custom-tabs-one-visitors" role="tabpanel" aria-labelledby="custom-tabs-one-visitors-tab">
                                    <div class="container-fluid py-5">
                                        <form action="" id="visitor-log-form">
                                            <input type="hidden" name="type">
                                            <div class="col-12">
                                                <div class="row justify-content-center">
                                                    <div class="col-md-8">
                                                        <div class="form-group text-center">
                                                            <label for="viscode">Visitor Code</label>
                                                            <select id="viscode" name="viscode" class="form-control form-control-lg rounded-0" autocomplete="on">
                                                                <?php for ($i = 1; $i <= 20; $i++) {
                                                                    $viscode = sprintf("VIS-%04d", $i);
                                                                    echo "<option value=\"$viscode\">$viscode</option>";
                                                                } ?>
                                                            </select>
                                                        </div>
                                                        <div class="form-group text-center">
                                                            <label for="name">Name</label>
                                                            <input type="text" id="name" name="name" class="form-control form-control-lg rounded-0" autocomplete="on">
                                                        </div>
                                                        <div class="form-group text-center">
                                                            <label for="contact">Contact #</label>
                                                            <input type="text" id="contact" name="contact" class="form-control form-control-lg rounded-0" autocomplete="on">
                                                        </div>
                                                        <div class="form-group text-center">
                                                            <label for="purpose">Purpose</label>
                                                            <textarea rows="2" id="purpose" name="purpose" class="form-control form-control-lg rounded-0" autocomplete="on"></textarea>
                                                        </div>
                                                        <div class="buttons-container">
                                                            <div class="person-icon icon-in">
                                                                <div class="icon-container">
                                                                    <i class="fas fa-sign-in-alt"></i>
                                                                </div>
                                                                <button class="btn btn-lg rounded-pill btn-primary px-4 elog" type="button" data-type='1'>
                                                                    <span class="button-text">Visitor In</span>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                
                                <!-- Exit Tab Content -->
                                <div class="tab-pane fade" id="custom-tabs-one-exit" role="tabpanel" aria-labelledby="custom-tabs-one-exit-tab">
                                    <div class="container-fluid py-5">
                                        <div class="row justify-content-center">
                                            <div class="col-md-8">
                                                <div class="form-group text-center">
                                                    <label for="current_visitors">Current Visitors Today</label>
                                                    <select id="current_visitors" class="form-control form-control-lg rounded-0">
                                                        <?php
                                                            $today = date('Y-m-d');
                                                            $query = "SELECT * FROM visitor_logs WHERE DATE(date_created) = '{$today}' AND type = 1";
                                                            $result = mysqli_query($conn, $query);
                                                            while ($row = mysqli_fetch_assoc($result)) {
                                                                echo "<option value='{$row['id']}'>{$row['name']} ({$row['viscode']})</option>";
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="buttons-container">
                                                    <div class="person-icon icon-out">
                                                        <div class="icon-container">
                                                            <i class="fas fa-door-open"></i>
                                                        </div>
                                                        <button id="visitor-out-btn" class="btn btn-lg rounded-pill btn-danger px-4" type="button">
                                                            <span class="button-text">Visitor Out</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Authentication Check
        function checkAuth() {
            fetch('check_auth.php')
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    if (data.authenticated) {
                        document.getElementById('authOverlay').style.display = 'none';
                    } else {
                        document.getElementById('authOverlay').style.display = 'flex';
                        setTimeout(() => {
                            window.location.href = 'admin/login.php';
                        }, 3000); // Redirect after 3 seconds if not authenticated
                    }
                })
                /*.catch(error => {
                    console.error('Error checking auth:', error);
                    window.location.href = 'admin/login.php';
                });*/
        }

        // Initial check
        checkAuth();
        
        // Periodically check auth status (every 5 minutes)
        setInterval(checkAuth, 300000);

        // Existing functionality
        var pillButtons = document.querySelectorAll('.pill-btn');
        pillButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var textToInsert = this.getAttribute('data-clipboard-text');
                var input = document.getElementById('employee_code');
                input.value = textToInsert;
                input.focus();
            });
        });

        document.getElementById('ham-menu').addEventListener('click', function () {
            var navUl = document.querySelector('nav ul');
            navUl.classList.toggle('active');
        });

        // Visitor out button functionality
        document.getElementById('visitor-out-btn').addEventListener('click', function () {
            var selectedVisitorId = document.getElementById('current_visitors').value;
            if (selectedVisitorId) {
                fetch('move_visitor.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: selectedVisitorId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert_swal('Success', 'Visitor moved out successfully.', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert_swal('Error', 'Error: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert_swal('Error', 'An error occurred while processing your request.', 'error');
                });
            } else {
                alert_swal('Error', 'Please select a visitor.', 'error');
            }
        });

        // Tab panel focus
        $('#panel-tab .nav-link').click(function () {
            setTimeout(() => {
                if ($('#employee_code').is(':visible') == true)
                    $('#employee_code').focus()
                if ($('#name').is(':visible') == true)
                    $('#name').focus()
            }, 650);
        });

        // Form submissions
        $('form#employee-log-form #employee_code').on('keypress', function (e) {
            if (e.which == 13)
                e.preventDefault()
        });

        $('.elog').click(function () {
            if ($('#employee_code').is(':visible') == true) {
                $('form#employee-log-form [name="type"]').val($(this).attr('data-type'))
                $('form#employee-log-form').submit()
            }
            if ($('#name').is(':visible') == true) {
                $('form#visitor-log-form [name="type"]').val($(this).attr('data-type'))
                $('form#visitor-log-form').submit()
            }
        });

        $('form#employee-log-form').submit(function (e) {
            e.preventDefault();
            start_loader();
            $.ajax({
                url: _base_url_ + 'classes/Master.php?f=log_employee',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                error: err => {
                    console.log(err)
                    alert_toast("An error occurred", "error")
                    end_loader();
                },
                success: function (resp) {
                    if (resp.status == 'success') {
                        alert_swal(resp.title, resp.msg, 'success')
                        $('form#employee-log-form').get(0).reset()
                    } else {
                        alert_swal(resp.title, resp.msg, 'error', 0)
                    }
                    end_loader();
                }
            })
        });

        $('form#visitor-log-form').submit(function (e) {
            e.preventDefault();
            start_loader();
            $.ajax({
                url: _base_url_ + 'classes/Master.php?f=log_visitor',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                error: err => {
                    console.log(err)
                    alert_toast("An error occurred", "error")
                    end_loader();
                },
                success: function (resp) {
                    if (resp.status == 'success') {
                        alert_swal(resp.title, resp.msg, 'success')
                        $('form#visitor-log-form').get(0).reset()
                    } else {
                        alert_swal(resp.title, resp.msg, 'error', 0)
                    }
                    end_loader();
                }
            })
        });

        // Viscode selection restriction
        var selectedViscode = null;
        var lastSelectionTime = null;

        document.getElementById('viscode').addEventListener('change', function () {
            var now = new Date().getTime();
            var selectedValue = this.value;

            if (selectedViscode === null) {
                selectedViscode = selectedValue;
                lastSelectionTime = now;
                return;
            }

            if (selectedViscode === selectedValue) {
                var hoursPassed = (now - lastSelectionTime) / (1000 * 60 * 60);
                if (hoursPassed < 5) {
                    alert('This viscode cannot be reselected until after 5 hours.');
                    this.value = selectedViscode;
                    return;
                }
            }

            selectedViscode = selectedValue;
            lastSelectionTime = now;
        });
    });

    function alert_swal(title, message, type) {
        Swal.fire({
            icon: type,
            title: title,
            text: message,
            timer: 3500,
            showClass: {
                popup: 'swal2-show',
                backdrop: 'swal2-backdrop-show'
            },
            hideClass: {
                popup: 'swal2-hide',
                backdrop: 'swal2-backdrop-hide'
            },
            customClass: {
                popup: 'modern-swal',
                icon: 'modern-swal-icon'
            },
            background: '#ffffff',
            buttonsStyling: false,
            confirmButtonText: 'OK',
            confirmButtonAriaLabel: 'OK'
        }).then((result) => {
            setTimeout(() => {
                if (result.isConfirmed || result.isDismissed) {
                    location.reload();
                }
            }, 650);
        });
    }
    </script>
    <?php require_once('inc/footer.php') ?>
</body>
</html>