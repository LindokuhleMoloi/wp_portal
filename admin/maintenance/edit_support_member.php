<?php
// edit_support_member.php

$db_host = "localhost";
$db_user = "tarryn_Lindokuhle";
$db_pass = "L1nd0kuhle";
$db_name = "tarryn_workplaceportal";

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die(json_encode(['status' => 'error', 'message' => 'Database connection error.']));
}

if (isset($_GET['id'])) {
    $edit_id = $_GET['id'];
    $edit_qry = $conn->query("SELECT * FROM support_team WHERE support_member_id = " . $edit_id);
    if ($edit_qry->num_rows > 0) {
        $edit_row = $edit_qry->fetch_assoc();
        extract($edit_row);
    }
}

?>

<form action="" method="post" id="manage-support-member">
    <input type="hidden" name="id" value="<?php echo isset($support_member_id) ? $support_member_id : '' ?>">
    <div class="form-group">
        <label for="name">Name</label>
        <input type="text" name="name" id="name" class="form-control" value="<?php echo isset($name) ? $name : '' ?>" required>
    </div>
    <div class="form-group">
        <label for="employee_code">Employee Code</label>
        <input type="text" name="employee_code" id="employee_code" class="form-control" value="<?php echo isset($employee_code) ? $employee_code : '' ?>" required>
    </div>
    <div class="form-group">
        <label for="password">Password</label>
        <input type="password" name="password" id="password" class="form-control" <?php if (!isset($support_member_id)) { echo "required"; } ?>>
    </div>
    <?php if(isset($support_member_id)): ?>
    <div class="form-group">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="change_password" name="change_password">
            <label class="form-check-label" for="change_password">Change Password</label>
        </div>
    </div>
    <?php endif; ?>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <button type="submit" name="save" class="btn btn-primary">Save changes</button>
    </div>
</form>

<?php $conn->close(); ?>