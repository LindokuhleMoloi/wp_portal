<?php
// PHP code for fetching employee data and connecting to the database
// Assume $conn is already defined and connected to your database

if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM employee_list where id = '{$_GET['id']}' ");
    if($qry->num_rows > 0){
        foreach($qry->fetch_array() as $k=>$v){
            $$k= $v;
        }

        $qry_meta = $conn->query("SELECT * FROM employee_meta where employee_id = '{$id}'");
        while($row = $qry_meta->fetch_assoc()){
            if(!isset(${$row['meta_field']}))
            ${$row['meta_field']} = $row['meta_value'];
        }
    }
}
?>
<style>
    img#cimg {
        height: 15vh;
        width: 15vh;
        object-fit: cover;
        border-radius: 50%;
    }
    .employee-form-section {
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 5px;
        border-left: 3px solid #007bff;
    }
    .employee-form-section h5 {
        color: #007bff;
        margin-bottom: 15px;
    }
    .leave-info-badge {
        font-size: 0.8rem;
        margin-top: 5px;
        display: none; /* Hidden by default, shown via JS */
    }
    .custom-file-label::after {
        content: "Browse";
    }
</style>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><?php echo isset($id) ? "Update Employee" : 'Register New Employee' ?></h3>
        <div class="card-tools">
            <button class="btn btn-sm btn-flat btn-primary" type="submit" form="employee-form"><i class="fa fa-save"></i> Save</button>
            <a class="btn btn-sm btn-flat btn-default" href="<?php echo base_url."admin?page=employee" ?>"><i class="fa fa-times"></i> Cancel</a>
        </div>
    </div>
    <div class="card-body">
        <form action="" id="employee-form" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
            
            <div class="employee-form-section">
                <h5>Personal Information</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="lastname" class="control-label">Last Name</label>
                            <input type="text" class="form-control form-control-sm" id="lastname" name="lastname" 
                                    value="<?php echo isset($lastname) ? htmlspecialchars($lastname) : '' ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="firstname" class="control-label">First Name</label>
                            <input type="text" class="form-control form-control-sm" id="firstname" name="firstname" 
                                    value="<?php echo isset($firstname) ? htmlspecialchars($firstname) : '' ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="middlename" class="control-label">Middle Name</label>
                            <input type="text" class="form-control form-control-sm" id="middlename" name="middlename" 
                                    value="<?php echo isset($middlename) ? htmlspecialchars($middlename) : '' ?>" placeholder="(optional)">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="gender" class="control-label">Gender</label>
                            <select name="gender" id="gender" class="custom-select custom-select-sm" required>
                                <option value="Male" <?= isset($gender) && $gender == 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= isset($gender) && $gender == 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="dob" class="control-label">Date of Birth</label>
                            <input type="date" class="form-control form-control-sm" id="dob" name="dob" 
                                    value="<?php echo isset($dob) ? $dob : '' ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="contact" class="control-label">Contact #</label>
                            <input type="text" class="form-control form-control-sm" id="contact" name="contact" 
                                    value="<?php echo isset($contact) ? htmlspecialchars($contact) : '' ?>" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address" class="control-label">Address</label>
                    <textarea class="form-control form-control-sm" id="address" name="address" required
                                placeholder="Street, Building, City, State, ZIP Code"><?php echo isset($address) ? htmlspecialchars($address) : '' ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email" class="control-label">Email</label>
                            <input type="email" class="form-control form-control-sm" id="email" name="email" 
                                    value="<?php echo isset($email) ? htmlspecialchars($email) : '' ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email_to" class="control-label">Notification Email</label>
                            <input type="email" class="form-control form-control-sm" id="email_to" name="email_to" 
                                    value="<?php echo isset($email_to) ? htmlspecialchars($email_to) : '' ?>" >
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="employee-form-section">
                <h5>Employment Details</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="department_id" class="control-label">Department</label>
                            <select name="department_id" id="department_id" class="custom-select custom-select-sm select2" required>
                                <option value="" disabled selected>Select Department</option>
                                <?php 
                                $dept_qry = $conn->query("SELECT * FROM department_list WHERE status = 1 ORDER BY name ASC");
                                while($row = $dept_qry->fetch_assoc()):
                                ?>
                                <option value="<?= $row['id'] ?>" <?= isset($department_id) && $department_id == $row['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="designation_id" class="control-label">Designation</label>
                            <select name="designation_id" id="designation_id" class="custom-select custom-select-sm select2" required>
                                <option value="" disabled selected>Select Designation</option>
                                <?php 
                                $desig_qry = $conn->query("SELECT * FROM designation_list WHERE status = 1 ORDER BY name ASC");
                                while($row = $desig_qry->fetch_assoc()):
                                ?>
                                <option value="<?= $row['id'] ?>" <?= isset($designation_id) && $designation_id == $row['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="employer_id" class="control-label">Employer</label>
                            <select name="employer_id" id="employer_id" class="custom-select custom-select-sm select2" >
                                <option value="" disabled selected>Select Employer</option>
                                <?php 
                                $empl_qry = $conn->query("SELECT * FROM employer_list WHERE status = 1 ORDER BY name ASC");
                                while($row = $empl_qry->fetch_assoc()):
                                ?>
                                <option value="<?= $row['id'] ?>" <?= isset($employer_id) && $employer_id == $row['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($row['name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="contract_start_date" class="control-label">Contract Start Date</label>
                            <input type="date" class="form-control form-control-sm" id="contract_start_date" 
                                    name="contract_start_date" value="<?= isset($contract_start_date) ? $contract_start_date : '' ?>" >
                            <small class="text-muted leave-info-badge">1.25 leave days will be allocated after 1 month</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="contract_end_date" class="control-label">Contract End Date</label>
                            <input type="date" class="form-control form-control-sm" id="contract_end_date" 
                                    name="contract_end_date" value="<?= isset($contract_end_date) ? $contract_end_date : '' ?>" placeholder="(ongoing if empty)">
                            </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status" class="control-label">Status</label>
                            <select name="status" id="status" class="custom-select custom-select-sm">
                                <option value="1" <?= isset($status) && $status == 1 ? 'selected' : '' ?>>Active</option>
                                <option value="0" <?= isset($status) && $status == 0 ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                       <div class="col-md-6">
    <div class="form-group">
        <label for="project_manager_id" class="control-label">Project Manager</label>
        <select name="project_manager_id" id="project_manager_id" class="custom-select custom-select-sm select2">
            <option value="" disabled selected>Select Project Manager</option>
            <?php 
            // Query to get employees with Project Manager designation
            $pm_qry = $conn->query("SELECT e.id, e.fullname 
                                  FROM employee_list e
                                  JOIN designation_list d ON e.designation_id = d.id
                                  WHERE e.status = 1 
                                  AND d.name LIKE '%Project Manager%'
                                  ORDER BY e.fullname ASC");
            while($row = $pm_qry->fetch_assoc()):
            ?>
            <option value="<?= $row['id'] ?>" <?= isset($project_manager_id) && $project_manager_id == $row['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['fullname']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>
</div>

<div class="col-md-6">
    <div class="form-group">
        <label for="mentor_id" class="control-label">Mentor</label>
        <select name="mentor_id" id="mentor_id" class="custom-select custom-select-sm select2">
            <option value="" disabled selected>Select Mentor</option>
            <?php 
            // Query to fetch employees with designation_id = 50 (Mentors)
            $mentor_qry = $conn->query("SELECT id, fullname 
                                      FROM employee_list 
                                      WHERE designation_id = 50 
                                     
                                      ORDER BY fullname ASC");
            while($row = $mentor_qry->fetch_assoc()):
            ?>
            <option value="<?= $row['id'] ?>" <?= isset($mentor_id) && $mentor_id == $row['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($row['fullname']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>
</div>
            
            <div class="employee-form-section">
                <h5>Employee Photo</h5>
                <div class="row justify-content-center">
                    <div class="col-md-6 text-center">
                        <div class="form-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="avatar" name="avatar" accept="image/*">
                                <label class="custom-file-label" for="avatar">Choose employee photo</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <img src="<?= validate_image(isset($avatar) ? $avatar : '') ?>" alt="Employee Photo" id="cimg" class="img-thumbnail">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function displayImg(input, _this) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#cimg').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
            _this.siblings('.custom-file-label').html(input.files[0].name);
        }
    }

    $(document).ready(function() {
        // Initialize select2
        $('.select2').select2({
            width: '100%',
            placeholder: $(this).data('placeholder')
        });

        // Show leave info when contract start date is selected
        $('#contract_start_date').on('change', function() {
            if($(this).val()) {
                $('.leave-info-badge').show();
            } else {
                $('.leave-info-badge').hide();
            }
        });

        // Initialize avatar preview
        $('#avatar').on('change', function() {
            displayImg(this, $(this));
        });

        // Form submission
        $('#employee-form').submit(function(e) {
            e.preventDefault();
            var _this = $(this);
            $('.is-invalid').removeClass('is-invalid');
            
            // Validate required fields
            var required = [
                'lastname', 'firstname', 'gender', 'dob', 'contact', 'address', 
                'email', 'department_id', 'designation_id', 
                'employer_id'
                // 'contract_end_date' is removed from here as it's optional
            ];
            
            var isValid = true;
            required.forEach(function(field) {
                if(!$('[name="'+field+'"]').val()) {
                    $('[name="'+field+'"]').addClass('is-invalid');
                    isValid = false;
                }
            });
            
            if(!isValid) {
                alert_toast("Please fill all required fields", "error");
                return false;
            }
            
            start_loader();
            
            // Construct fullname for database
            var fullname = $('#lastname').val() + ', ' + $('#firstname').val();
            if($('#middlename').val()) {
                fullname += ' ' + $('#middlename').val();
            }
            
            var formData = new FormData(this);
            formData.append('fullname', fullname);
            
            $.ajax({
                url: _base_url_ + "classes/Master.php?f=save_employee",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                method: 'POST',
                type: 'POST',
                dataType: 'json',
                error: function(err) {
                    console.log(err);
                    alert_toast("An error occurred", "error");
                    end_loader();
                },
                success: function(resp) {
                    if(resp.status == 'success') {
                        // Schedule leave allocation for new employees
                        if(!<?= isset($id) ? 'true' : 'false' ?>) {
                            $.ajax({
                                url: _base_url_ + "classes/Master.php?f=schedule_leave_allocation",
                                type: "POST",
                                data: {
                                    employee_id: resp.id,
                                    contract_start_date: $('#contract_start_date').val()
                                },
                                dataType: 'json',
                                success: function(leaveResp) {
                                    console.log("Leave allocation scheduled");
                                },
                                error: function(leaveErr) {
                                    console.error("Leave scheduling error", leaveErr);
                                }
                            });
                        }
                        
                        // Send notification email
                        $.ajax({
                            url: _base_url_ + "admin/employee/send_mail.php",
                            type: "POST",
                            data: {
                                id: resp.id,
                                email: $('#email').val(),
                                email_to: $('#email_to').val()
                            },
                            dataType: 'json',
                            success: function(mailResp) {
                                alert_toast("Employee saved successfully!", "success");
                                setTimeout(function() {
                                    location.href = _base_url_ + "admin?page=employee/view_employee&id=" + resp.id;
                                }, 2000);
                            },
                            error: function(mailErr) {
                                alert_toast("Employee saved but email failed", "warning");
                                setTimeout(function() {
                                    location.href = _base_url_ + "admin?page=employee/view_employee&id=" + resp.id;
                                }, 2000);
                            }
                        });
                    } else {
                        alert_toast(resp.msg || "An error occurred", "error");
                        end_loader();
                    }
                }
            });
        });
    });
</script>