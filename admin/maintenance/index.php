<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Your CSS styles here */
        .main-sidebar {
            background: linear-gradient(135deg, #1a237e, #00acc1);
            color: #e0f7fa;
            border: none;
            box-shadow: 5px 0 15px rgba(0, 188, 212, 0.3);
        }
        .nav-sidebar .nav-link {
            color: #e0f7fa;
            transition: color 0.3s ease;
        }
        .nav-sidebar .nav-link:hover,
        .nav-sidebar .nav-link.active {
            color: #b2ebf2;
            background: rgba(0, 188, 212, 0.1);
            border-left: 3px solid #b2ebf2;
        }
        .nav-sidebar .nav-icon {
            color: #b2ebf2;
            margin-right: 0.5rem;
            transition: color 0.3s ease;
        }
        .nav-header {
            color: #b2ebf2;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
            margin-top: 1rem;
        }
        .nav-pills .nav-link.active {
            background-color: rgba(0, 188, 212, 0.2);
        }
        @keyframes glow {
            0% { box-shadow: 0 0 5px rgba(0, 188, 212, 0.3); }
            50% { box-shadow: 0 0 15px rgba(0, 188, 212, 0.5); }
            100% { box-shadow: 0 0 5px rgba(0, 188, 212, 0.3); }
        }
        .main-sidebar {
            animation: glow 5s infinite ease-in-out;
        }
    </style>
</head>
<body>

<aside class="main-sidebar sidebar-no-expand">
    <div class="sidebar">
        <nav class="mt-4">
            <ul class="nav nav-pills nav-sidebar flex-column text-sm nav-compact nav-flat nav-child-indent nav-collapse-hide-child" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item dropdown">
                    <a href="<?php echo base_url ?>admin/?page=maintenance/helpdesk" class="nav-link nav-maintenance_helpdesk">
                        <i class="nav-icon fas fa-wrench"></i>
                        <p>IT Helpdesk</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>

<div class="modal fade" id="helpdeskLoginModal" tabindex="-1" role="dialog" aria-labelledby="helpdeskLoginModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpdeskLoginModalLabel">IT Helpdesk Login</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="helpdeskLoginForm">
                    <div class="form-group">
                        <label for="helpdeskUsername">Username</label>
                        <input type="text" class="form-control" id="helpdeskUsername" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="helpdeskPassword">Password</label>
                        <input type="password" class="form-control" id="helpdeskPassword" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
                <div id="helpdeskLoginError" style="color: red; display: none;"></div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.nav-maintenance_helpdesk').click(function(e) {
            e.preventDefault();
            $('#helpdeskLoginModal').modal('show');
        });

        $('#helpdeskLoginForm').submit(function(e) {
            e.preventDefault();

            var username = $('#helpdeskUsername').val();
            var password = $('#helpdeskPassword').val();

            $.ajax({
                url: '<?php echo base_url ?>admin/maintenance/helpdesk_login.php',
                type: 'POST',
                data: { username: username, password: password },
                success: function(response) {
                    if (response === 'success') {
                        window.location.href = '<?php echo base_url ?>admin/maintenance/helpdesk.php';
                    } else {
                        $('#helpdeskLoginError').text(response).show();
                    }
                },
                error: function() {
                    $('#helpdeskLoginError').text('An error occurred. Please try again.').show();
                }
            });
        });
    });
</script>

</body>
</html>