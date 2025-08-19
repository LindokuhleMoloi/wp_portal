<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

require_once('../config.php');

class Master extends DBConnection {
    private $settings;
    
    private function send_email($to, $subject, $body) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'mail.pro-learn.co.za';
            $mail->SMTPAuth = true;
            $mail->Username = 'workplace@pro-learn.co.za';
            $mail->Password = 'WokPro@123';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->setFrom('workplace@pro-learn.co.za', 'Workplace Portal');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function __construct() {
        global $_settings;
        $this->settings = $_settings;
        parent::__construct();
    }
    
    public function __destruct() {
        parent::__destruct();
    }
    
    function capture_err() {
        if(!$this->conn->error)
            return false;
        else {
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
            return json_encode($resp);
            exit;
        }
    }
    
    // Department functions
    function save_department() {
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v) {
            if(!in_array($k,array('id'))) {
                if(!empty($data)) $data .=",";
                $data .= " {$k}='{$v}' ";
            }
        }
        $check = $this->conn->query("SELECT * FROM department_list where name = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
        if($this->capture_err())
            return $this->capture_err();
        if($check > 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Department Name already exist.";
            return json_encode($resp);
            exit;
        }
        if(empty($id)) {
            $sql = "INSERT INTO department_list set {$data} ";
            $save = $this->conn->query($sql);
        } else {
            $sql = "UPDATE department_list set {$data} where id = '{$id}' ";
            $save = $this->conn->query($sql);
        }
        if($save) {
            $resp['status'] = 'success';
            if(empty($id)) {
                $res['msg'] = "New Department successfully saved.";
                $id = $this->conn->insert_id;
            } else {
                $res['msg'] = "Department successfully updated.";
            }
            $this->settings->set_flashdata('success',$res['msg']);
        } else {
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        return json_encode($resp);
    }
    
    function delete_department() {
        extract($_POST);
        $del = $this->conn->query("DELETE FROM department_list where id = '{$id}'");
        if($del) {
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Department successfully deleted.");
        } else {
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }
    
    // Designation functions
    function save_designation() {
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v) {
            if(!in_array($k,array('id'))) {
                $v = $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=",";
                $data .= " {$k}='{$v}' ";
            }
        }
        $check = $this->conn->query("SELECT * FROM designation_list where name = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
        if($this->capture_err())
            return $this->capture_err();
        if($check > 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Designation already exists.";
            return json_encode($resp);
            exit;
        }
        if(empty($id)) {
            $sql = "INSERT INTO designation_list set {$data} ";
            $save = $this->conn->query($sql);
        } else {
            $sql = "UPDATE designation_list set {$data} where id = '{$id}' ";
            $save = $this->conn->query($sql);
        }
        if($save) {
            $resp['status'] = 'success';
            if(empty($id))
                $this->settings->set_flashdata('success',"New Designation successfully saved.");
            else
                $this->settings->set_flashdata('success',"Designation successfully updated.");
        } else {
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        return json_encode($resp);
    }
    
    function delete_designation() {
        extract($_POST);
        $del = $this->conn->query("DELETE FROM designation_list where id = '{$id}'");
        if($del) {
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Designation successfully deleted.");
        } else {
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }
    public function save_mentor() {
        $conn = $this->conn;
        $response = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
        
        try {
            $conn->autocommit(FALSE);
            
            $id = $_POST['id'] ?? '';
            $mentor_id_code = $_POST['mentor_id'] ?? '';
            $mentor_name = $conn->real_escape_string($_POST['mentor_name'] ?? '');
            $email = $conn->real_escape_string($_POST['email'] ?? '');
            $phone = $conn->real_escape_string($_POST['phone'] ?? '');
            $date_assigned = $conn->real_escape_string($_POST['date_assigned'] ?? date('Y-m-d'));
            $status = intval($_POST['status'] ?? 1);
            $description = $conn->real_escape_string($_POST['description'] ?? '');

            if(empty($mentor_name)) throw new Exception("Mentor's Full Name is required.");
            if(empty($date_assigned)) throw new Exception("Date Assigned is required.");

            $avatar_path = '';
            if(!empty($_FILES['img']['name'])) {
                $file_ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
                $temp_file_name = "mentor-temp_".uniqid().".".$file_ext;
                $upload_dir = "uploads/mentors/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $temp_upload_path = $upload_dir . $temp_file_name;
                
                if(move_uploaded_file($_FILES['img']['tmp_name'], $temp_upload_path)) {
                    $avatar_path = $temp_upload_path;
                } else {
                    throw new Exception("Failed to upload image.");
                }
            }

            $mentor_data = [
                'mentor_name' => $mentor_name,
                'email' => $email,
                'phone' => $phone,
                'date_assigned' => $date_assigned,
                'status' => $status,
                'description' => $description
            ];

            if(empty($id)) {
                if(empty($mentor_id_code)) {
                     $last_id_result = $conn->query("SELECT MAX(id) as last_id FROM `mentor`")->fetch_assoc();
                     $last_mentor_db_id = $last_id_result ? $last_id_result['last_id'] : 0;
                     $mentor_id_code = 'MT-' . str_pad($last_mentor_db_id + 1, 5, '0', STR_PAD_LEFT);
                }
                $mentor_data['mentor_id'] = $mentor_id_code;

                $columns = implode(", ", array_keys($mentor_data));
                $values = array_map(function($val) use ($conn) { return "'".$conn->real_escape_string($val)."'"; }, array_values($mentor_data));
                $values = implode(", ", $values);
                
                $sql = "INSERT INTO mentor ({$columns}) VALUES ({$values})";
                $save = $conn->query($sql);
                
                if(!$save) {
                    throw new Exception("Failed to save Mentor: ".$conn->error);
                }
                $id = $conn->insert_id;

                if (!empty($avatar_path)) {
                    $new_avatar_name = "mentor-{$id}.png";
                    $new_avatar_path = $upload_dir . $new_avatar_name;
                    if (rename($avatar_path, $new_avatar_path)) {
                        $conn->query("UPDATE mentor SET avatar = '{$new_avatar_path}' WHERE id = '{$id}'");
                    } else {
                        error_log("Failed to rename uploaded Mentor image from {$avatar_path} to {$new_avatar_path}");
                    }
                }
                $response['msg'] = "New Mentor successfully saved.";
            } else {
                $updates = [];
                foreach($mentor_data as $col => $val) {
                    $updates[] = "`{$col}` = '".$conn->real_escape_string($val)."'";
                }

                if (!empty($avatar_path)) {
                    $old_avatar_qry = $conn->query("SELECT avatar FROM mentor WHERE id = '{$id}'")->fetch_assoc();
                    if ($old_avatar_qry && !empty($old_avatar_qry['avatar']) && $old_avatar_qry['avatar'] != "uploads/mentors/default.png" && file_exists($old_avatar_qry['avatar'])) {
                        unlink($old_avatar_qry['avatar']);
                    }

                    $new_avatar_name = "mentor-{$id}.png";
                    $new_avatar_path = $upload_dir . $new_avatar_name;
                    if (rename($avatar_path, $new_avatar_path)) {
                        $updates[] = "`avatar` = '".$conn->real_escape_string($new_avatar_path)."'";
                    } else {
                        error_log("Failed to rename uploaded Mentor image from {$avatar_path} to {$new_avatar_path}");
                    }
                }
                
                $sql = "UPDATE mentor SET ".implode(", ", $updates)." WHERE id = '{$id}'";
                $save = $conn->query($sql);
                
                if(!$save) {
                    throw new Exception("Failed to update Mentor: ".$conn->error);
                }
                $response['msg'] = "Mentor successfully updated.";
            }
            
            $conn->commit();
            $response['status'] = 'success';
            $this->settings->set_flashdata('success', $response['msg']);
            
        } catch(Exception $e) {
            $conn->rollback();
            if (!empty($avatar_path) && file_exists($avatar_path)) {
                unlink($avatar_path);
            }
            $response = ['status' => 'failed', 'msg' => $e->getMessage()];
        } finally {
            $conn->autocommit(TRUE);
        }
        
        return json_encode($response);
    }

    function delete_mentor() {
        extract($_POST);
        
        if(empty($id) || !is_numeric($id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Invalid Mentor ID";
            return json_encode($resp);
        }
        
        $this->conn->autocommit(FALSE);
        
        try {
            $mentor_qry = $this->conn->query("SELECT * FROM mentor WHERE id = '{$id}'");
            if($mentor_qry->num_rows == 0) {
                throw new Exception("Mentor not found");
            }
            $mentor_data = $mentor_qry->fetch_assoc();
            
            // First, unassign all employees from this mentor
            $this->conn->query("UPDATE employee_list SET mentor_id = NULL WHERE mentor_id = '{$id}'");
            
            // Then delete the mentor
            $del = $this->conn->query("DELETE FROM mentor WHERE id = '{$id}'");
            
            if(!$del) {
                throw new Exception("Failed to delete Mentor: ".$this->conn->error);
            }
            
            // Delete avatar if exists
            if(!empty($mentor_data['avatar']) && $mentor_data['avatar'] != "uploads/mentors/default.png" && file_exists($mentor_data['avatar'])) {
                unlink($mentor_data['avatar']);
            }
            
            $this->conn->commit();
            
            $resp['status'] = 'success';
            $resp['msg'] = "Mentor {$mentor_data['mentor_name']} (ID: {$mentor_data['mentor_id']}) successfully deleted.";
            $this->settings->set_flashdata('success', $resp['msg']);
            
        } catch(Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        } finally {
            $this->conn->autocommit(TRUE);
        }
        
        return json_encode($resp);
    }
    // Employer functions
    function save_employer() {
        extract($_POST);
        $data = "";
        foreach($_POST as $k =>$v) {
            if(!in_array($k,array('id'))) {
                $v = $this->conn->real_escape_string($v);
                if(!empty($data)) $data .=",";
                $data .= " {$k}='{$v}' ";
            }
        }
        $check = $this->conn->query("SELECT * FROM employer_list where name = '{$name}' ".(!empty($id) ? " and id != {$id} " : "")." ")->num_rows;
        if($this->capture_err())
            return $this->capture_err();
        if($check > 0) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Employer already exists.";
            return json_encode($resp);
            exit;
        }
        if(empty($id)) {
            $sql = "INSERT INTO employer_list set {$data} ";
            $save = $this->conn->query($sql);
        } else {
            $sql = "UPDATE employer_list set {$data} where id = '{$id}' ";
            $save = $this->conn->query($sql);
        }
        if($save) {
            $resp['status'] = 'success';
            if(empty($id))
                $this->settings->set_flashdata('success',"New Employer successfully saved.");
            else
                $this->settings->set_flashdata('success',"Employer successfully updated.");
        } else {
            $resp['status'] = 'failed';
            $resp['err'] = $this->conn->error."[{$sql}]";
        }
        return json_encode($resp);
    }
    
    function delete_employer() {
        extract($_POST);
        $del = $this->conn->query("DELETE FROM employer_list where id = '{$id}'");
        if($del) {
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Employer successfully deleted.");
        } else {
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }
    
    // Project Manager functions
    public function save_pm() {
        $conn = $this->conn;
        $response = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
        
        try {
            $conn->autocommit(FALSE);
            
            $id = $_POST['id'] ?? '';
            $pm_id_code = $_POST['pm_id'] ?? '';
            $pm_name = $conn->real_escape_string($_POST['pm_name'] ?? '');
            $email = $conn->real_escape_string($_POST['email'] ?? '');
            $phone = $conn->real_escape_string($_POST['phone'] ?? '');
            $date_assigned = $conn->real_escape_string($_POST['date_assigned'] ?? date('Y-m-d'));
            $status = intval($_POST['status'] ?? 1);
            $description = $conn->real_escape_string($_POST['description'] ?? '');

            if(empty($pm_name)) throw new Exception("Project Manager's Full Name is required.");
            if(empty($date_assigned)) throw new Exception("Date Assigned is required.");

            $avatar_path = '';
            if(!empty($_FILES['img']['name'])) {
                $file_ext = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
                $temp_file_name = "pm-temp_".uniqid().".".$file_ext;
                $upload_dir = "uploads/pms/";
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $temp_upload_path = $upload_dir . $temp_file_name;
                
                if(move_uploaded_file($_FILES['img']['tmp_name'], $temp_upload_path)) {
                    $avatar_path = $temp_upload_path;
                } else {
                    throw new Exception("Failed to upload image.");
                }
            }

            $pm_data = [
                'pm_name' => $pm_name,
                'email' => $email,
                'phone' => $phone,
                'date_assigned' => $date_assigned,
                'status' => $status,
                'description' => $description
            ];

            if(empty($id)) {
                if(empty($pm_id_code)) {
                     $last_id_result = $conn->query("SELECT MAX(id) as last_id FROM `project_manager`")->fetch_assoc();
                     $last_pm_db_id = $last_id_result ? $last_id_result['last_id'] : 0;
                     $pm_id_code = 'PM-' . str_pad($last_pm_db_id + 1, 5, '0', STR_PAD_LEFT);
                }
                $pm_data['pm_id'] = $pm_id_code;

                $columns = implode(", ", array_keys($pm_data));
                $values = array_map(function($val) use ($conn) { return "'".$conn->real_escape_string($val)."'"; }, array_values($pm_data));
                $values = implode(", ", $values);
                
                $sql = "INSERT INTO project_manager ({$columns}) VALUES ({$values})";
                $save = $conn->query($sql);
                
                if(!$save) {
                    throw new Exception("Failed to save Project Manager: ".$conn->error);
                }
                $id = $conn->insert_id;

                if (!empty($avatar_path)) {
                    $new_avatar_name = "pm-{$id}.png";
                    $new_avatar_path = $upload_dir . $new_avatar_name;
                    if (rename($avatar_path, $new_avatar_path)) {
                        $conn->query("UPDATE project_manager SET avatar = '{$new_avatar_path}' WHERE id = '{$id}'");
                    } else {
                        error_log("Failed to rename uploaded PM image from {$avatar_path} to {$new_avatar_path}");
                    }
                }
                $response['msg'] = "New Project Manager successfully saved.";
            } else {
                $updates = [];
                foreach($pm_data as $col => $val) {
                    $updates[] = "`{$col}` = '".$conn->real_escape_string($val)."'";
                }

                if (!empty($avatar_path)) {
                    $old_avatar_qry = $conn->query("SELECT avatar FROM project_manager WHERE id = '{$id}'")->fetch_assoc();
                    if ($old_avatar_qry && !empty($old_avatar_qry['avatar']) && $old_avatar_qry['avatar'] != "uploads/pms/default.png" && file_exists($old_avatar_qry['avatar'])) {
                        unlink($old_avatar_qry['avatar']);
                    }

                    $new_avatar_name = "pm-{$id}.png";
                    $new_avatar_path = $upload_dir . $new_avatar_name;
                    if (rename($avatar_path, $new_avatar_path)) {
                        $updates[] = "`avatar` = '".$conn->real_escape_string($new_avatar_path)."'";
                    } else {
                        error_log("Failed to rename uploaded PM image from {$avatar_path} to {$new_avatar_path}");
                    }
                }
                
                $sql = "UPDATE project_manager SET ".implode(", ", $updates)." WHERE id = '{$id}'";
                $save = $conn->query($sql);
                
                if(!$save) {
                    throw new Exception("Failed to update Project Manager: ".$conn->error);
                }
                $response['msg'] = "Project Manager successfully updated.";
            }
            
            $conn->commit();
            $response['status'] = 'success';
            $this->settings->set_flashdata('success', $response['msg']);
            
        } catch(Exception $e) {
            $conn->rollback();
            if (!empty($avatar_path) && file_exists($avatar_path)) {
                unlink($avatar_path);
            }
            $response = ['status' => 'failed', 'msg' => $e->getMessage()];
        } finally {
            $conn->autocommit(TRUE);
        }
        
        return json_encode($response);
    }
    
    function delete_pm() {
        extract($_POST);
        
        if(empty($id) || !is_numeric($id)) {
            $resp['status'] = 'failed';
            $resp['msg'] = "Invalid Project Manager ID";
            return json_encode($resp);
        }
        
        $this->conn->autocommit(FALSE);
        
        try {
            $pm_qry = $this->conn->query("SELECT * FROM project_manager WHERE id = '{$id}'");
            if($pm_qry->num_rows == 0) {
                throw new Exception("Project Manager not found");
            }
            $pm_data = $pm_qry->fetch_assoc();
            
            // First, unassign all employees from this PM
            $this->conn->query("UPDATE employee_list SET project_manager_id = NULL WHERE project_manager_id = '{$id}'");
            
            // Then delete the PM
            $del = $this->conn->query("DELETE FROM project_manager WHERE id = '{$id}'");
            
            if(!$del) {
                throw new Exception("Failed to delete Project Manager: ".$this->conn->error);
            }
            
            // Delete avatar if exists
            if(!empty($pm_data['avatar']) && $pm_data['avatar'] != "uploads/pms/default.png" && file_exists($pm_data['avatar'])) {
                unlink($pm_data['avatar']);
            }
            
            $this->conn->commit();
            
            $resp['status'] = 'success';
            $resp['msg'] = "Project Manager {$pm_data['pm_name']} (ID: {$pm_data['pm_id']}) successfully deleted.";
            $this->settings->set_flashdata('success', $resp['msg']);
            
        } catch(Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        } finally {
            $this->conn->autocommit(TRUE);
        }
        
        return json_encode($resp);
    }
    
    // Employee functions
    public function save_employee() {
        $conn = $this->conn;
        $response = ['status' => 'failed', 'msg' => 'Unknown error occurred'];
        
        try {
            $conn->autocommit(FALSE);
            
            $id = $_POST['id'] ?? '';
            $employee_code = $_POST['employee_code'] ?? '';
            $fullname = $conn->real_escape_string($_POST['fullname'] ?? '');
            $department_id = intval($_POST['department_id'] ?? 0);
            $designation_id = intval($_POST['designation_id'] ?? 0);
            $employer_id = intval($_POST['employer_id'] ?? 0);
            $project_manager_id = intval($_POST['project_manager_id'] ?? 0);
            $mentor_id = intval($_POST['mentor_id'] ?? 0);
            $status = intval($_POST['status'] ?? 1);
            $email = $_POST['email'] ?? '';
            $firstname = $_POST['firstname'] ?? '';
            $lastname = $_POST['lastname'] ?? '';
            
            if(empty($fullname)) throw new Exception("Full name is required");
            if($department_id <= 0) throw new Exception("Department is required");
            if($designation_id <= 0) throw new Exception("Designation is required");
            if($employer_id <= 0) throw new Exception("Employer is required");
            
            $avatar = '';
            if(!empty($_FILES['avatar']['name'])) {
                $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $file_name = "employee-".($id ? $id : 'temp').".png";
                $upload_path = "uploads/".$file_name;
                
                if(move_uploaded_file($_FILES['avatar']['tmp_name'], $upload_path)) {
                    $avatar = $upload_path;
                }
            }
            
            $employee_data = [
                'fullname' => $fullname,
                'department_id' => $department_id,
                'designation_id' => $designation_id,
                'employer_id' => $employer_id,
                'project_manager_id' => $project_manager_id,
                'mentor_id' => $mentor_id,
                'status' => $status,
                'email' => $email,
                'contact' => $_POST['contact'] ?? null,
                'contract_start_date' => $_POST['contract_start_date'] ?? null,
                'contract_end_date' => $_POST['contract_end_date'] ?? null,
                'date_of_birth' => $_POST['dob'] ?? null,
                'address' => $_POST['address'] ?? null,
                'gender' => $_POST['gender'] ?? null
            ];
            
            if(!empty($avatar)) {
                $employee_data['avatar'] = $avatar;
            }
            
            if(empty($id)) {
                if(empty($employee_code)) {
                    $year = date('Y');
                    $last_code = $conn->query("SELECT MAX(employee_code) as last_code FROM employee_list 
                                             WHERE employee_code LIKE '{$year}-%'")->fetch_assoc();
                    $next_num = 1;
                    if($last_code && !empty($last_code['last_code'])) {
                        $parts = explode('-', $last_code['last_code']);
                        $next_num = intval($parts[1]) + 1;
                    }
                    $employee_code = "{$year}-".str_pad($next_num, 4, '0', STR_PAD_LEFT);
                }
                
                $employee_data['employee_code'] = $employee_code;
                
                $columns = implode(", ", array_keys($employee_data));
                $values = "'".implode("', '", array_values($employee_data))."'";
                
                $sql = "INSERT INTO employee_list ($columns) VALUES ($values)";
                $save = $conn->query($sql);
                
                if(!$save) {
                    throw new Exception("Failed to save employee: ".$conn->error);
                }
                
                $id = $conn->insert_id;
            } else {
                $updates = [];
                foreach($employee_data as $col => $val) {
                    $updates[] = "`{$col}` = '{$val}'";
                }
                
                $sql = "UPDATE employee_list SET ".implode(", ", $updates)." WHERE id = '{$id}'";
                $save = $conn->query($sql);
                
                if(!$save) {
                    throw new Exception("Failed to update employee: ".$conn->error);
                }
                
                $conn->query("DELETE FROM employee_meta WHERE employee_id = '{$id}'");
            }
            
            $meta_fields = ['lastname', 'firstname', 'middlename', 'gender', 'dob', 'contact', 
                           'address', 'email', 'email_to'];
            
            foreach($meta_fields as $field) {
                if(isset($_POST[$field])) {
                    $value = $conn->real_escape_string($_POST[$field]);
                    $sql = "INSERT INTO employee_meta (employee_id, meta_field, meta_value) 
                            VALUES ('{$id}', '{$field}', '{$value}')";
                    $save_meta = $conn->query($sql);
                    
                    if(!$save_meta) {
                        throw new Exception("Failed to save meta data for {$field}: ".$conn->error);
                    }
                }
            }
            
            $conn->commit();
            
            if(empty($_POST['id']) && !empty($email)) {
                $subject = "Welcome to Progression's Workplace Portal";
                $body = "
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 0; color: #333; }
        .email-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .email-header { background: linear-gradient(135deg, #1b262c, #0f4c75); color: white; padding: 30px 20px; text-align: center; }
        .email-header h1 { margin: 0; font-size: 24px; font-weight: 600; }
        .email-body { padding: 30px; line-height: 1.6; }
        .employee-info { background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0; border-left: 4px solid #50c878; }
        .login-note { font-style: italic; color: #666; margin-top: 25px; }
        .email-footer { text-align: center; padding: 20px; background: #f8f9fa; border-top: 1px solid #eee; }
        .logo { display: block; margin: 15px auto 0; max-width: 200px; height: auto; }
        strong { color: #1b262c; }
    </style>
</head>
<body>
    <div class='email-container'>
        <div class='email-header'>
            <h1>Welcome to Progression's Workplace Portal</h1>
        </div>
        <div class='email-body'>
            <p>Dear <strong>$firstname $lastname</strong>,</p>
            <p>Welcome aboard! We are excited to have you join our team and utilize Progression's Workplace portal.</p>
            <div class='employee-info'>
                <p>Your unique employee code is: <strong>$employee_code</strong></p>
                <p>Please use this code to clock in and out for your work hours.</p>
            </div>
            <p class='login-note'>Please keep this information confidential for security purposes.</p>
            <p>Best regards,</p>
            <p>The Progression Team</p>
        </div>
        <div class='email-footer'>
            <p>Artisans Republik, Division Of Progression</p>
            <img src='https://workplaceportal.pro-learn.co.za/uploads/Emailcover.png' alt='Company Logo' class='logo'>
        </div>
    </div>
</body>
</html>";

                $email_sent = $this->send_email($email, $subject, $body);
                if($email_sent) {
                    $response = ['status' => 'success', 'id' => $id, 'msg' => 'Employee saved and welcome email sent!'];
                } else {
                    $response = ['status' => 'success', 'id' => $id, 'msg' => 'Employee saved but email failed to send'];
                }
            } else {
                $response = ['status' => 'success', 'id' => $id, 'msg' => 'Employee saved successfully'];
            }
            
        } catch(Exception $e) {
            $conn->rollback();
            $response = ['status' => 'failed', 'msg' => $e->getMessage()];
        } finally {
            $conn->autocommit(TRUE);
        }
        
        return json_encode($response);
    }
    
    public function save_meta() {
        extract($_POST);
        if(!empty($employee_id)) {
            foreach($meta as $field => $value) {
                $this->conn->query("DELETE FROM employee_meta WHERE employee_id = '{$employee_id}' AND meta_field = '{$field}'");
                $insert = $this->conn->query("INSERT INTO employee_meta (employee_id, meta_field, meta_value) VALUES ('{$employee_id}', '{$field}', '{$value}')");
                if(!$insert) {
                    return json_encode(array('status'=>'failed', 'msg'=>'Failed to save meta data'));
                }
            }
            return json_encode(array('status'=>'success'));
        }
        return json_encode(array('status'=>'failed', 'msg'=>'Invalid employee ID'));
    }
    
    function delete_employee() {
        extract($_POST);
        $this->conn->autocommit(FALSE);
        
        try {
            // Get employee details before deletion
            $employee = $this->conn->query("SELECT * FROM employee_list WHERE id = '{$id}'")->fetch_assoc();
            if(!$employee) {
                throw new Exception("Employee not found");
            }
            
            // Delete meta data first
            $this->conn->query("DELETE FROM employee_meta WHERE employee_id = '{$id}'");
            
            // Delete employee
            $del = $this->conn->query("DELETE FROM employee_list where id = '{$id}'");
            if(!$del) {
                throw new Exception($this->conn->error);
            }
            
            // Delete avatar if exists
            if(!empty($employee['avatar']) && file_exists($employee['avatar'])) {
                unlink($employee['avatar']);
            }
            
            $this->conn->commit();
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Employee successfully deleted.");
            
        } catch(Exception $e) {
            $this->conn->rollback();
            $resp['status'] = 'failed';
            $resp['msg'] = $e->getMessage();
        } finally {
            $this->conn->autocommit(TRUE);
        }
        
        return json_encode($resp);
    }
    
    // Support Team functions
    function fetch_support_team_members() {
        $qry = $this->conn->query("SELECT name FROM support_team");
        $resp = array();
        if ($qry->num_rows > 0) {
            while ($row = $qry->fetch_assoc()) {
                $resp[] = $row;
            }
            return json_encode(['status' => 'success', 'data' => $resp]);
        } else {
            return json_encode(['status' => 'failed', 'msg' => 'No support team members found.']);
        }
    }
    
    function add_support_member() {
        extract($_POST);
        
        if (empty($employee_code) || empty($name) || empty($password)) {
            return json_encode(['status' => 'failed', 'msg' => 'All fields are required.']);
        }

        $check = $this->conn->query("SELECT * FROM support_team WHERE employee_code = '{$employee_code}'")->num_rows;
        if ($check > 0) {
            return json_encode(['status' => 'failed', 'msg' => 'Employee Code already exists.']);
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO support_team (employee_code, name, password) VALUES ('{$employee_code}', '{$name}', '{$hashed_password}')";
        $save = $this->conn->query($sql);

        if ($save) {
            return json_encode(['status' => 'success', 'msg' => 'Support Member successfully added.']);
        } else {
            return json_encode(['status' => 'failed', 'error' => $this->conn->error, 'sql' => $sql]);
        }
    }
    
    // Logging functions
    function log_employee() {
        extract($_POST);
        $qry = $this->conn->query("SELECT * FROM employee_list WHERE employee_code = '{$employee_code}'");
        if($qry->num_rows>0) {
            $res = $qry->fetch_array();
            $employee_id = $res['id'];
            $last_log = $this->conn->query("SELECT * FROM logs WHERE employee_id = '{$employee_id}' ORDER BY date_created DESC LIMIT 1")-> fetch_array();
            if($type == 1 && $last_log['type'] ==1) {
                $resp['status'] = 'Failed';
                $resp['title'] = 'Already Logged In';
                $resp['msg'] = 'You are already logged In';
            } else if($type == 2 && $last_log['type'] == 2) {
                $resp['status'] = 'Failed';
                $resp['title'] = 'Already Logged Out';
                $resp['msg'] = 'You are aready logged out';
            } else {
                $sql ="INSERT INTO logs SET employee_id = '{$employee_id}', type = '{$type}'";
                $save = $this->conn->query($sql);
                if ($save) {
                    $resp['status'] = 'success';
                    if ($type == 1) {
                        $resp['title'] = 'Successfully Logged In';
                        $resp['msg'] = 'Welcome '.$res['fullname'];
                    } else {
                        $resp['title'] = 'Successfully Logged Out';
                        $resp['msg'] = 'Goodbye '.$res['fullname'];
                    }
                } else {
                    $resp['status'] = 'failed';
                    $resp['title'] = 'Logging Error';
                    $resp['msg'] = '';
                }
            }
        } else {
            $resp['status'] = 'failed';
            $resp['title'] = 'Unknown Employee Code';
            $resp['msg'] = ''; 
        }
        return json_encode($resp);
    }
    
    function log_visitor() {
        extract($_POST);
        $data = "";
        foreach($_POST as $k => $v) {
            if(!is_numeric($v))
            $v = $this->conn->real_escape_string($v);
            if(!empty($data)) $data .= ", ";
            $data .= " {$k} = '{$v}' ";
        }
        $sql = "INSERT INTO visitor_logs set {$data}";
        $save = $this->conn->query($sql);
        if($save) {
            $resp['status'] = 'success';
            if($type == 1) {
                $resp['title'] = 'Sucessfully Logged In';
                $resp['msg'] = 'Welcome '. $name;
                $resp['registered_viscode'] = $viscode; 
            } else {
                $resp['title'] = 'Sucessfully Logged Out';
                $resp['msg'] = 'Goodbye '. $name;
            }
        } else {
            $resp['status'] = 'failed';
            $resp['title'] = 'Logging Error';
        }
        return json_encode($resp);
    }
    
    function log_laptop_usage() {
        extract($_POST);
        $qry = $this->conn->query("SELECT * FROM employee_list WHERE employee_code = '{$employee_code}'");
        if($qry->num_rows>0) {
            $res = $qry->fetch_array();
            $employee_id = $res['id'];
            $last_log = $this->conn->query("SELECT * FROM Laptop_Logs WHERE employee_id = '{$employee_id}' ORDER BY date_created DESC LIMIT 1")-> fetch_array();
            if($type == 1 && $last_log['type'] ==1) {
                $resp['status'] = 'Failed';
                $resp['title'] = 'Already Logged In';
                $resp['msg'] = 'You are already logged In';
            } else if($type == 2 && $last_log['type'] == 2) {
                $resp['status'] = 'Failed';
                $resp['title'] = 'Already Logged Out';
                $resp['msg'] = 'You are aready logged out';
            } else {
                $sql ="INSERT INTO Laptops_Logs SET employee_id = '{$employee_id}', type = '{$type}'";
                $save = $this->conn->query($sql);
                if ($save) {
                    $resp['status'] = 'success';
                    if ($type == 1) {
                        $resp['title'] = 'Successfully Logged In';
                        $resp['msg'] = 'Welcome '.$res['fullname'];
                    } else {
                        $resp['title'] = 'Successfully Logged Out';
                        $resp['msg'] = 'Goodbye '.$res['fullname'];
                    }
                } else {
                    $resp['status'] = 'failed';
                    $resp['title'] = 'Logging Error';
                    $resp['msg'] = '';
                }
            }
        }
        return json_encode($resp);
    }
    
    function delete_helpdesk_log() {
        extract($_POST);
        $del = $this->conn->query("DELETE FROM helpdesk_support_incoming where id = '{$id}'");
        if($del) {
            $resp['status'] = 'success';
            $this->settings->set_flashdata('success',"Log successfully deleted.");
        } else {
            $resp['status'] = 'failed';
            $resp['error'] = $this->conn->error;
        }
        return json_encode($resp);
    }
    
    // Getter functions for dropdowns
    function get_designation() {
        $qry = $this->conn->query("SELECT * FROM designation_list where status = 1 order by name asc");
        return json_encode($qry->fetch_all(MYSQLI_ASSOC));
    }
    
    function get_employer() {
        $qry = $this->conn->query("SELECT * FROM employer_list where status = 1 order by name asc");
        return json_encode($qry->fetch_all(MYSQLI_ASSOC));
    }
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();

switch ($action) {
    case 'save_department':
        echo $Master->save_department();
        break;
    case 'save_employer':
        echo $Master->save_employer();
        break;
    case 'delete_employer':
        echo $Master->delete_employer();
        break;
    case 'delete_department':
        echo $Master->delete_department();
        break;
    case 'save_designation':
        echo $Master->save_designation();
        break;
    case 'delete_designation':
        echo $Master->delete_designation();
        break;
    case 'get_designation':
        echo $Master->get_designation();
        break;
    case 'get_employer':
        echo $Master->get_employer();
        break;
    case 'save_employee':
        echo $Master->save_employee();
        break;
	case 'delete_employee':
		echo $Master->delete_employee();
	break;
	case 'log_employee':
		echo $Master->log_employee();
	break;
	case 'log_visitor':
		echo $Master->log_visitor();
	break;
    case 'log_laptop_usage':
        echo $Master->log_laptop_usage();
	break;
	case 'delete_helpdesk_log':
        echo $Master->delete_helpdesk_log();
	break;
	case 'save_pm': // Add this new case for saving Project Managers
        echo $Master->save_pm();
    break;
    case 'delete_pm':
        echo $Master->delete_pm();
    break;
case 'save_mentor':
        echo $Master->save_mentor();
        break;
        
    case 'delete_mentor':
        echo $Master->delete_mentor();
        break;
	case 'add_support_member':
    echo $Master->add_support_member();
    break;
    case 'save_meta':  // Add new case for meta data
        echo $Master->save_meta();
        break;

	default:
		// echo $sysset->index();
		break;
}