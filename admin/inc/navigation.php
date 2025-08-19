<style>
    /* Styling for the main sidebar container */
    .main-sidebar {
        background: linear-gradient(135deg, #1a237e, #00acc1); /* Deep blue to cyan gradient for a cosmic, modern feel */
        color: #e0f7fa; /* Very light cyan text for good contrast */
        border: none;
        box-shadow: 5px 0 15px rgba(0, 188, 212, 0.3); /* Glowing shadow effect */
    }

    /* Styling for the brand link (logo and short name) */
    .brand-link {
        background-color: transparent !important; /* Remove default primary background */
        border-bottom: 1px solid rgba(0, 188, 212, 0.5); /* Light cyan border for a clean look */
        color: #e0f7fa !important; /* Light cyan text for visibility */
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.5); /* Subtle text shadow for depth */
    }

    /* Styling for the brand image (logo) */
    .brand-image {
        border: 2px solid rgba(0, 188, 212, 0.7); /* Light cyan border for emphasis */
        box-shadow: 0 2px 5px rgba(0, 188, 212, 0.5); /* Subtle glow effect */
    }

    /* Styling for navigation links */
    .nav-sidebar .nav-link {
        color: #e0f7fa; /* Light cyan text for readability */
        transition: color 0.3s ease; /* Smooth color transition */
    }

    /* Hover and active states for navigation links */
    .nav-sidebar .nav-link:hover,
    .nav-sidebar .nav-link.activ
        color: #b2ebf2; /* Lighter cyan on hover/active for emphasis */
        background: rgba(0, 188, 212, 0.1); /* Subtle background glow on hover/active */
        border-left: 3px solid #b2ebf2; /* Highlight active/hover with a left border */
    }

    /* Styling for navigation icons */
    .nav-sidebar .nav-icon {
        color: #b2ebf2; /* Lighter cyan icons for visibility */
        margin-right: 0.5rem; /* Spacing between icon and text */
        transition: color 0.3s ease; /* Smooth icon color transition */
    }

    /* Hover and active states for navigation icons */
    .nav-sidebar .nav-icon:hover,
    .nav-sidebar .nav-icon.active {
        color: #e0f7fa; /* Lightest cyan on hover/active for maximum emphasis */
    }

    /* Styling for navigation headers (e.g., "Reports", "Management") */
    .nav-header {
        color: #b2ebf2; /* Lighter cyan headers for clear separation */
        text-transform: uppercase; /* Uppercase text for a modern look */
        font-size: 0.8rem; /* Smaller font size for headers */
        letter-spacing: 1px; /* Letter spacing for better readability */
        margin-top: 1rem; /* Top margin for spacing */
    }

    /* Styling for active navigation pill */
    .nav-pills .nav-link.active {
        background-color: rgba(0, 188, 212, 0.2); /* Active background glow */
    }

    /* Keyframes for the glowing animation */
    @keyframes glow {
        0% { box-shadow: 0 0 5px rgba(0, 188, 212, 0.3); } /* Start with a subtle glow */
        50% { box-shadow: 0 0 15px rgba(0, 188, 212, 0.5); } /* Increase glow intensity at midpoint */
        100% { box-shadow: 0 0 5px rgba(0, 188, 212, 0.3); } /* Return to subtle glow */
    }

    /* Apply the glowing animation to the main sidebar */
    .main-sidebar {
        animation: glow 5s infinite ease-in-out; /* Apply the glow animation */
    }
</style>

<aside class="main-sidebar sidebar-no-expand">
    <a href="<?php echo base_url ?>admin" class="brand-link">
        <img src="<?php echo validate_image($_settings->info('logo')) ?>" alt="Store Logo" class="brand-image img-circle elevation-3 bg-black">
        <span class="brand-text font-weight-light"><?php echo $_settings->info('short_name') ?></span>
    </a>

    <div class="sidebar">
        <nav class="mt-4">
            <ul class="nav nav-pills nav-sidebar flex-column text-sm nav-compact nav-flat nav-child-indent nav-collapse-hide-child" data-widget="treeview" role="menu" data-accordion="false">
                <li class="nav-item dropdown">
                    <a href="./" class="nav-link nav-home">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
               
                <li class="nav-item">
                    <a href="<?php echo base_url ?>admin/?page=Leave_Management/Leave_appearance" class="nav-link nav-Leave_Mangment-Leave_appearance">
                        <i class="nav-icon fas fa-calendar"></i>
                        <p>Leave application</p>
                    </a>
                </li>
                <li class="nav-header">Reports</li>
                <li class="nav-item">
                    <a href="<?php echo base_url ?>admin/?page=reports/employee" class="nav-link nav-reports_employee">
                        <i class="nav-icon fas fa-th-list"></i>
                        <p>Employee Logs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo base_url ?>admin/?page=reports/employee_logs_details" class="nav-link nav-employee_logs_details">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>Real-time Statistics</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo base_url ?>admin/?page=reports/visitor_out" class="nav-link nav-reports_visitor_out">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Visitors Logs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo base_url ?>admin/?page=reports/calendar" class="nav-link nav-reports_calendar">
                        <i class="nav-icon fas fa-user-check"></i>
                        <p>Monthly Attendance</p>
                    </a>
                </li>
                <?php if($_settings->userdata('type') == 1): ?>
                <li class="nav-header">Management</li>
                 <li class="nav-item">
                    <a href="<?php echo base_url ?>admin/?page=employee" class="nav-link nav-employee">
                        <i class="nav-icon fas fa-user-friends"></i>
                        <p>Employee List</p>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="<?php echo base_url ?>admin/?page=maintenance/employer" class="nav-link nav-maintenance_employer">
                        <i class="nav-icon fas fa-handshake"></i>
                        <p>Sponsor List</p>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="<?php echo base_url ?>admin/?page=maintenance/department" class="nav-link nav-maintenance_department">
                        <i class="nav-icon fas fa-building"></i>
                        <p>Department List</p>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="<?php echo base_url ?>admin/?page=maintenance/designation" class="nav-link nav-maintenance_designation">
                        <i class="nav-icon fas fa-list-alt"></i>
                        <p>Designation List</p>
                    </a>
                </li>
                <li class="nav-header">IT Systems</li>
                <li class="nav-item dropdown">
                    <a href="<?php echo base_url ?>admin/?page=maintenance/enrolling" class="nav-link nav-maintenance_enrolling">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        <p>Device Enrollment</p>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="<?php echo base_url ?>admin/?page=maintenance/helpdesk" class="nav-link nav-maintenance_helpdesk">
                        <i class="nav-icon fas fa-wrench"></i>
                        <p>IT Helpdesk </p>
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a href="<?php echo base_url ?>admin/?page=user/list" class="nav-link nav-user_list">
                        <i class="nav-icon fas fa-users"></i>
                        <p>User List</p>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a href="<?php echo base_url ?>admin/?page=system_info" class="nav-link nav-system_info">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>Settings</p>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        </div>
    </aside>