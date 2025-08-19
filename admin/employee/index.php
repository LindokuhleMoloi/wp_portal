<?php if($_settings->chk_flashdata('success')): ?>
<script>
    alert_toast("<?php echo $_settings->flashdata('success') ?>",'success')
</script>
<?php endif;?>

<style>
    .img-avatar {
        width: 45px;
        height: 45px;
        object-fit: cover;
        object-position: center center;
        border-radius: 100%;
        cursor: pointer;
        border: 2px solid;
        border-image: linear-gradient(to right, #1b7278, #435b48) 1;
    }

    .img-avatar:hover {
        transform: scale(1.1);
        transition: transform 0.3s ease;
    }

    .employee-info-item {
        margin-bottom: 5px;
        font-size: 0.85rem;
    }

    .info-label {
        color: #6c757d;
        display: inline-block;
        width: 70px;
    }

    .info-value {
        color: #343a40;
    }

    .status-badge {
        font-size: 0.75rem;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .table-header th {
        background-color: #f8f9fa;
        color: #343a40;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        padding: 12px 15px;
    }

    .table-body td {
        vertical-align: middle;
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
    }

    .text-ellipsis {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
        display: inline-block;
    }

    /* Enhanced Contract Expiration Styles */
    .contract-expired-container {
        background-color: #ffebee;
        border-radius: 5px;
        padding: 5px 10px;
        border-left: 4px solid #dc3545;
        animation: pulse 2s infinite;
    }
    
    .contract-expired-date {
        color: #dc3545;
        font-weight: bold;
        font-size: 0.9rem;
    }
    
    .expired-badge {
        background-color: #dc3545;
        color: white;
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 15px;
        margin-left: 5px;
        text-transform: uppercase;
        font-weight: bold;
        letter-spacing: 1px;
        box-shadow: 0 2px 4px rgba(220, 53, 69, 0.3);
    }
    
    @keyframes pulse {
        0% { background-color: #ffebee; }
        50% { background-color: #ffcdd2; }
        100% { background-color: #ffebee; }
    }
    
    /* Highlight entire row if contract expired */
    tr.expired-contract {
        background-color: #fff5f5;
    }
    
    tr.expired-contract:hover {
        background-color: #ffebee !important;
    }

    /* Original color scheme elements */
    .card-primary {
        border-color: #1b7278;
    }
    
    .card-primary > .card-header {
        background-color: #1b7278;
        color: white;
    }
    
    .btn-primary {
        background-color: #1b7278;
        border-color: #1b7278;
    }
    
    .badge-success {
        background-color: #28a745;
    }
    
    .badge-danger {
        background-color: #dc3545;
    }
    
    /* Project Manager badge */
    .pm-badge {
        background-color: #6f42c1;
        color: white;
        font-size: 0.7rem;
        padding: 3px 8px;
        border-radius: 15px;
        display: inline-block;
        margin-top: 3px;
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">Employee List</h3>
        <div class="card-tools">
            <a href="<?php echo base_url."admin?page=employee/manage_employee" ?>" id="create_new" class="btn btn-flat btn-sm btn-primary">
                <span class="fas fa-plus"></span> Add New
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="container-fluid">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-header">
                        <tr>
                            <th width="5%">#</th>
                            <th width="15%">Employee</th>
                            <th width="20%">Position</th>
                            <th width="15%">Contact</th>
                            <th width="15%">Project Manager</th>
                            <th width="15%">Contract</th>
                            <th width="10%">Status</th>
                            <th width="10%">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $i = 1;
                            // Get all lookup data
                            $department_qry = $conn->query("SELECT * FROM department_list");
                            $department_arr = array_column($department_qry->fetch_all(MYSQLI_ASSOC),'name','id');
                            
                            $designation_qry = $conn->query("SELECT * FROM designation_list");
                            $designation_arr = array_column($designation_qry->fetch_all(MYSQLI_ASSOC),'name','id');
                            
                            $employer_qry = $conn->query("SELECT * FROM employer_list");
                            $employer_arr = array_column($employer_qry->fetch_all(MYSQLI_ASSOC),'name','id');
                            
                            // Get project managers
                            $pm_qry = $conn->query("SELECT id, pm_name FROM project_manager");
                            $pm_arr = array_column($pm_qry->fetch_all(MYSQLI_ASSOC), 'pm_name', 'id');
                            
                            // Get all employees with project manager info
                            $qry = $conn->query("SELECT e.*, p.pm_name 
                                               FROM `employee_list` e 
                                               LEFT JOIN project_manager p ON e.project_manager_id = p.id 
                                               ORDER BY e.fullname ASC");
                            while($row = $qry->fetch_assoc()):
                                // Format data
                                $department = isset($department_arr[$row['department_id']]) ? $department_arr[$row['department_id']] : 'N/A';
                                $designation = isset($designation_arr[$row['designation_id']]) ? $designation_arr[$row['designation_id']] : 'N/A';
                                $employer = isset($employer_arr[$row['employer_id']]) ? $employer_arr[$row['employer_id']] : 'N/A';
                                $project_manager = !empty($row['pm_name']) ? $row['pm_name'] : 'Not Assigned';
                                
                                $contract_start = !empty($row['contract_start_date']) ? date("M d, Y", strtotime($row['contract_start_date'])) : 'N/A';
                                $contract_end = !empty($row['contract_end_date']) ? date("M d, Y", strtotime($row['contract_end_date'])) : 'Ongoing';
                                
                                // Check if contract has expired
                                $is_expired = false;
                                if (!empty($row['contract_end_date']) && strtotime($row['contract_end_date']) < time()) {
                                    $is_expired = true;
                                }
                        ?>
                        <tr class="<?php echo $is_expired ? 'expired-contract' : '' ?>">
                            <td class="text-center"><?php echo $i++; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo validate_image("uploads/employee-".$row['id'].".png")."?v=".(isset($row['date_updated']) ? strtotime($row['date_updated']) : "") ?>" 
                                         class="img-avatar mr-3" alt="Employee Photo">
                                    <div>
                                        <div class="font-weight-bold"><?php echo $row['fullname'] ?></div>
                                        <small class="text-muted"><?php echo $row['employee_code'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="employee-info-item">
                                    <span class="info-label">Dept:</span>
                                    <span class="info-value"><?php echo $department ?></span>
                                </div>
                                <div class="employee-info-item">
                                    <span class="info-label">Role:</span>
                                    <span class="info-value"><?php echo $designation ?></span>
                                </div>
                                <div class="employee-info-item">
                                    <span class="info-label">Employer:</span>
                                    <span class="info-value"><?php echo $employer ?></span>
                                </div>
                            </td>
                            <td>
                                <div class="employee-info-item">
                                    <span class="info-label">Email:</span>
                                    <span class="info-value text-ellipsis" title="<?php echo $row['email'] ?>"><?php echo $row['email'] ?></span>
                                </div>
                                <div class="employee-info-item">
                                    <span class="info-label">Phone:</span>
                                    <span class="info-value"><?php echo $row['contact'] ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if(!empty($row['project_manager_id'])): ?>
                                <div class="pm-badge">
                                    <i class="fas fa-user-tie mr-1"></i>
                                    <?php echo $project_manager ?>
                                </div>
                                <?php else: ?>
                                <span class="text-muted">Not Assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="employee-info-item">
                                    <span class="info-label">Start:</span>
                                    <span class="info-value"><?php echo $contract_start ?></span>
                                </div>
                                <div class="employee-info-item">
                                    <span class="info-label">End:</span>
                                    <?php if($is_expired): ?>
                                    <div class="contract-expired-container">
                                        <span class="contract-expired-date">
                                            <?php echo $contract_end ?>
                                            <span class="expired-badge">Contract Expired</span>
                                        </span>
                                    </div>
                                    <?php else: ?>
                                        <span class="info-value"><?php echo $contract_end ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="status-badge badge <?php echo $row['status'] == 1 ? 'badge-success' : 'badge-danger' ?>">
                                    <?php echo $row['status'] == 1 ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="<?php echo base_url."admin?page=employee/view_employee&id=".$row['id'] ?>" class="btn btn-flat btn-sm btn-default" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?php echo base_url."admin?page=employee/manage_employee&id=".$row['id'] ?>" class="btn btn-flat btn-sm btn-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-flat btn-sm btn-danger delete_data" data-id="<?php echo $row['id'] ?>" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Image Modal -->
<div id="imageModal" class="modal fade" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Employee Photo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" alt="Employee Photo" class="img-fluid rounded">
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function(){
        // Show modal on image click
        $('.img-avatar').on('click', function(){
            var imgSrc = $(this).attr('src');
            $('#modalImage').attr('src', imgSrc);
            $('#imageModal').modal('show');
        });

        // Delete function
        $('.delete_data').click(function(){
            _conf("Are you sure to delete this employee permanently?","delete_employee",[$(this).attr('data-id')])
        });
        
        // Initialize DataTable
        $('.table').DataTable({
            responsive: true,
            dom: '<"top"f>rt<"bottom"lip><"clear">',
            language: {
                search: "_INPUT_",
                searchPlaceholder: "Search employees...",
            },
            columnDefs: [
                { responsivePriority: 1, targets: 1 }, // Employee name column
                { responsivePriority: 2, targets: -1 } // Action column
            ]
        });

        // Delete employee function
        window.delete_employee = function($id){
            start_loader();
            $.ajax({
                url:_base_url_+"classes/Master.php?f=delete_employee",
                method:"POST",
                data:{id: $id},
                dataType:"json",
                error:err=>{
                    console.log(err)
                    alert_toast("An error occurred.",'error');
                    end_loader();
                },
                success:function(resp){
                    if(typeof resp == 'object' && resp.status == 'success'){
                        location.reload();
                    }else{
                        alert_toast("An error occurred.",'error');
                        end_loader();
                    }
                }
            })
        }
        
        // Initialize tooltips
        $('[title]').tooltip();
    });
</script>