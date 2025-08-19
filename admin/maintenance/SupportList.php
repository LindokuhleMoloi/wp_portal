<?php
// SupportList.php

// Database configuration
$db_host = "localhost";
$db_user = "tarryn_Lindokuhle";
$db_pass = "L1nd0kuhle";
$db_name = "tarryn_workplaceportal";

// Establish database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("<div style='color: #dc3545; padding: 20px; border: 1px solid #f5c6cb; border-radius: 4px;'>An internal error occurred. Please try again later.</div>");
}
?>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">List of Current Support Members</h3>
        <div class="card-tools">
            <a href="javascript:void(0)" id="create_new" class="btn btn-flat btn-primary"><span class="fas fa-plus"></span> Add New</a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <table class="table table-bordered table-striped">
                <colgroup>
                    <col width="5%">
                    <col width="15%">
                    <col width="25%">
                    <col width="25%">
                    <col width="25%">
                </colgroup>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date Added</th>
                        <th>Support Member Name</th>
                        <th>Employee Code</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    $qry = $conn->query("SELECT * FROM `support_team` ORDER BY `name` ASC");
                    while ($row = $qry->fetch_assoc()) :
                    ?>
                        <tr>
                            <td class="text-center"><?= $i++ ?></td>
                            <td><?= date("Y-m-d H:i", strtotime($row['date_added'])) ?></td>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['employee_code']) ?></td>
                            <td align="center">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown">
                                        Actions
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item edit_data" href="javascript:void(0)" data-id="<?= $row['support_member_id'] ?>">Edit</a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item delete_data" href="javascript:void(0)" data-id="<?= $row['support_member_id'] ?>">Delete</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="addEditModal" tabindex="-1" role="dialog" aria-labelledby="addEditModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addEditModalLabel">Manage Support Member</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Form will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize DataTable
    $('.table').DataTable();

    // Handle Create New
    $('#create_new').click(function() {
        $.get('edit_support_member.php')
        .done(function(data) {
            $('#addEditModal .modal-body').html(data);
            $('#addEditModal').modal('show');
        })
        .fail(function() {
            alert('Failed to load form');
        });
    });

    // Handle Edit
    $(document).on('click', '.edit_data', function() {
        var id = $(this).data('id');
        $.get('edit_support_member.php?id=' + id)
        .done(function(data) {
            $('#addEditModal .modal-body').html(data);
            $('#addEditModal').modal('show');
        })
        .fail(function() {
            alert('Failed to load edit form');
        });
    });

    // Handle Delete
    $(document).on('click', '.delete_data', function() {
        var id = $(this).data('id');
        if(confirm('Are you sure you want to delete this member?')) {
            $.post('delete_support_member.php', {id: id})
            .done(function(response) {
                if(response.status === 'success') {
                    location.reload();
                } else {
                    alert(response.message || 'Delete failed');
                }
            })
            .fail(function() {
                alert('Request failed');
            });
        }
    });

    // Handle Form Submit
    $(document).on('submit', '#manage-support-member', function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        var url = $(this).attr('action') || 'save_support_member.php';
        
        $.post(url, formData)
        .done(function(response) {
            if(response.status === 'success') {
                location.reload();
            } else {
                alert(response.message || 'Operation failed');
            }
        })
        .fail(function() {
            alert('Request failed');
        });
    });
});
</script>