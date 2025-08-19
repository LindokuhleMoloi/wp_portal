<?php
include('db_connect.php');

if(isset($_GET['id'])){
    $qry = $conn->query("SELECT * FROM `support_team` where id = '{$_GET['id']}'");
    if($qry->num_rows > 0){
        $res = $qry->fetch_array();
        foreach($res as $k => $v){
            if(!is_numeric($k))
            $$k = $v;
        }
    }
}
?>

<div class="container-fluid">
    <form action="" id="manage-support-member">
        <input type="hidden" name="id" value="<?php echo isset($id) ? $id : '' ?>">
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
            <input type="password" name="password" id="password" class="form-control" <?php echo !isset($id) ? 'required' : '' ?>>
        </div>
    </form>
</div>

<script>
    $(document).ready(function(){
        $('#manage-support-member').submit(function(e){
            e.preventDefault();
            start_loader()
            $.ajax({
                url:"save_support_member.php", // changed url
                method:'POST',
                data:$(this).serialize(),
                dataType:'json',
                error:err=>{
                    console.log(err)
                    alert_toast("An error occured",'error')
                    end_loader()
                },
                success:function(resp){
                    if(resp.status == 'success'){
                        location.reload()
                    }else{
                        alert_toast("An error occured",'error')
                        end_loader();
                    }
                }
            })
        })
    })
</script>