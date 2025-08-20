<?php

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Create uploads directory if it doesn't exist
$uploadDir = 'public_html/IT_enroll/uploads';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Function to send email
function sendEmail($toEmail, $subject, $body)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'mail.pro-learn.co.za';
        $mail->SMTPAuth = true;
        $mail->Username = 'workplace@pro-learn.co.za';
        $mail->Password = 'WokPro@123';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('workplace@pro-learn.co.za', 'Workplace Portal');
        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mysqli = new mysqli("localhost", "root", "", "tarryn_workplaceportal");
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    $screenshot = null;
    $uploadError = null;

    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
        $fileType = mime_content_type($_FILES['screenshot']['tmp_name']);
        $maxSize = 2 * 1024 * 1024;

        if ($_FILES['screenshot']['size'] > $maxSize) {
            $uploadError = "File too large. Maximum 2MB allowed.";
        } elseif (!array_key_exists($fileType, $allowedTypes)) {
            $uploadError = "Invalid file type. Only JPG, PNG, GIF allowed.";
        } else {
            $extension = $allowedTypes[$fileType];
            $filename = uniqid() . '.' . $extension;
            $uploadPath = $uploadDir . '/' . $filename;

            if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $uploadPath)) {
                $screenshot = $filename;
            } else {
                $uploadError = "Error uploading file.";
            }
        }
    }

    if (!$uploadError) {
        $stmt = $mysqli->prepare("INSERT INTO helpdesk_support_incoming (employee_code, name, role, email, issue_description, screenshot, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $_POST['employee_code'], $_POST['name'], $_POST['role'], $_POST['email'], $_POST['issue_description'], $screenshot);

        if ($stmt->execute()) {
            $subject = "Your Ticket Has Been Received";
            $body = '
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Ticket Received</title>
                    <style>
                        @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap");
                        body {font-family:"Poppins",sans-serif;background:#f8f9fa;margin:0;padding:0;}
                        .email-container {max-width:700px;margin:0 auto;background:#ffffff;}
                        .header {background:#1b262c;padding:40px 0;text-align:center;}
                        .header h1 {color:#fff;font-size:28px;margin:0;line-height:1.2;}
                        .content {padding:0;}
                        .logo-section {text-align:center;padding:0;background:#f8fafc;}
                        .logo {max-width:220px;height:auto;display:block;margin:0 auto;}
                        .message-box {background:#f8fafc;padding:25px;position:relative;}
                        .message-box:before {content:"✓";position:absolute;left:-15px;top:-15px;background:#50c878;color:#fff;width:40px;height:40px;border-radius:50%;display:grid;place-items:center;font-size:20px;box-shadow:0 4px 6px rgba(80,200,120,0.2);}
                        .greeting {font-size:20px;color:#2d3748;margin:0 0 15px 0;}
                        .details {font-size:16px;line-height:1.5;}
                        .details p {margin:0 0 10px 0;}
                        .footer {background:#1b262c;color:#a0aec0;padding:20px 0;text-align:center;}
                    </style>
                </head>
                <body>
                    <div class="email-container">
                        <div class="header"><h1>Ticket <span style="color:#50c878;">Received</span> Successfully</h1></div>
                        <div class="content">
                            <div class="message-box">
                                <p class="greeting">Dear ' . htmlspecialchars($_POST['name']) . ',</p>
                                <div class="details">
                                    <p>🎉 Thank you for submitting your ticket! Our team is already reviewing it.</p>
                                    <p>🔍 Next steps:<br>1. Technical review<br>2. Priority assessment<br>3. Technician assignment</p>
                                    <p>⏳ Average response: 5min</p>
                                </div>
                            </div>
                            <div class="logo-section">
                                <img src="https://workplaceportal.pro-learn.co.za/uploads/Artisans.png" alt="Logo" class="logo">
                                <p style="color:#718096;margin:5px 0 0;font-size:14px;">Empowering Digital Workforce</p>
                            </div>
                        </div>
                        <div class="footer">
                            <p style="margin:0 0 5px 0;"><a href="mailto:workplace@pro-learn.co.za" style="color:#50c878;text-decoration:none;">Contact Live Support</a></p>
                            <p style="margin:0;font-size:13px;">© 2025 Artisans Republik</p>
                        </div>
                    </div>
                </body>
                </html>';

            sendEmail($_POST['email'], $subject, $body);

            // Show success modal and exit
            echo '
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Ticket Submitted</title>
                    <style>
                        .success-modal {
                            position: fixed;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            background: rgba(0,0,0,0.8);
                            display: flex;
                            justify-content: center;
                            align-items: center;
                            z-index: 10000;
                            animation: fadeIn 0.3s ease;
                        }
                        .modal-content {
                            background: linear-gradient(135deg, #1b262c, #0f4c75);
                            padding: 40px;
                            border-radius: 16px;
                            text-align: center;
                            max-width: 500px;
                            width: 90%;
                            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
                            border-top: 5px solid #50c878;
                            animation: slideUp 0.5s ease;
                        }
                        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                        @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
                        .continue-btn {
                            background: linear-gradient(135deg, #50c878, #3ac569);
                            color: white;
                            border: none;
                            padding: 12px 30px;
                            border-radius: 25px;
                            font-weight: bold;
                            cursor: pointer;
                            font-size: 16px;
                            transition: all 0.3s ease;
                            margin-top: 20px;
                        }
                        .continue-btn:hover {
                            transform: scale(1.05);
                            box-shadow: 0 5px 15px rgba(80, 200, 120, 0.4);
                        }
                    </style>
                </head>
                <body>
                    <div class="success-modal">
                        <div class="modal-content">
                            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#50c878" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 20px;">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                            <h2 style="color: #ffffff; margin-bottom: 15px; font-size: 24px;">Ticket Submitted Successfully!</h2>
                            <p style="color: #bbbbbb; margin-bottom: 25px; line-height: 1.6;">
                                Your support ticket has been received. Our team will review it shortly.<br>
                                You\'ll receive a confirmation email with your ticket details.
                            </p>
                            <button class="continue-btn" onclick="window.location.href=\'helpdesk_front.php\'">Continue</button>
                        </div>
                    </div>
                    <script>
                        // Auto-redirect after 5 seconds
                        setTimeout(function() {
                            window.location.href = "helpdesk_front.php";
                        }, 5000);
                    </script>
                </body>
                </html>';
            exit();
        } else {
            echo "<script>alert('Error: " . addslashes($stmt->error) . "'); window.history.back();</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('" . addslashes($uploadError) . "'); window.history.back();</script>";
    }
    $mysqli->close();
}

// Set background image (using absolute URL)
$backgroundImageUrl = 'https://workplaceportal.pro-learn.co.za/TICKETFORMIMAGES/future.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <title>Helpdesk Support</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Roboto', sans-serif;
            background: url('<?php echo $backgroundImageUrl; ?>') no-repeat center center fixed;
            background-size: cover;
            color: #f6f7eb;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .container {
            width: 100%;
            max-width: 800px;
            background: rgba(27, 38, 44, 0.8); /* Semi-transparent background */
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            padding: 20px;
            animation: fadeIn 1s ease-in-out;
            margin-top: 20px;
        }
        /* Rest of the styles remain the same */
        h1 { font-size: 2rem; text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 3px solid #50c878; text-transform: uppercase; letter-spacing: 1px; }
        input, select, textarea { width: 100%; padding: 10px 14px; font-size: 0.9rem; border: 2px solid #3282b8; border-radius: 20px; background: #1b262c; color: #f6f7eb; transition: all 0.3s; margin-bottom: 15px; outline: none; }
        input:focus, textarea:focus { border-color: #50c878; background: rgba(80, 200, 120, 0.1); }
        textarea { resize: none; height: 100px; }
        button { padding: 12px; font-size: 0.9rem; font-weight: bold; color: #fff; background: linear-gradient(135deg, #50c878, #3ac569); border: none; border-radius: 20px; cursor: pointer; transition: background 0.3s ease, transform 0.3s ease; margin-top: 15px; width: 100%; }
        button:hover { background: linear-gradient(135deg, #3ac569, #50c878); transform: scale(1.05); }
        .file-preview-container { margin-top: 10px; text-align: center; }
        .file-preview { display: inline-block; position: relative; margin: 8px; }
        .file-preview img { max-width: 120px; max-height: 120px; border-radius: 8px; border: 2px solid #3282b8; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); }
        .remove-file { position: absolute; top: -8px; right: -8px; background: #ff6b6b; color: white; border-radius: 50%; width: 20px; height: 20px; text-align: center; line-height: 20px; cursor: pointer; font-size: 12px; }
        .file-upload { margin-bottom: 15px; }
        .file-upload-text { display: block; margin-bottom: 6px; color: #bbbbbb; font-size: 0.85rem;}
        .file-upload-text small { font-size: 0.7rem; color: #888; display: block; }
        .button-container { display: flex; flex-direction: column; gap: 10px; margin-top: 15px; }
        .button-container button { flex: 1; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        footer { width: 100%; background: #1b262c; color: #f6f7eb; text-align: center; padding: 10px 0; box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.5); font-size: 0.8rem; margin-top: 30px;}
        footer p { margin: 0; }
        .download-button {
            position: relative;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: uppercase; letter-spacing: 1px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-top: 20px;
        }
        .download-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }
        .download-button::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(to right, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.5));
            transform: rotate(-45deg);
            transition: transform 0.3s ease;
        }
        .download-button:hover::before {
            transform: rotate(0deg);
        }
        .download-button::after {
            content: '\f019';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
            opacity: 0.8;
        }
        @media (max-width: 600px) {
            .container { padding: 15px; }
            h1 { font-size: 1.8rem; }
            input, select, textarea, button, .download-button { font-size: 0.85rem; padding: 10px 12px; }
            textarea { height: 80px; }
            .file-preview img { max-width: 100px; max-height: 100px; }
            .button-container { gap: 8px; }
            footer { font-size: 0.8rem; }
        }
        .email-update-link {
            margin: -10px 0 15px;
            text-align: right;
        }
        .email-update-link a {
            color: #50c878;
            font-size: 0.85rem;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .email-update-link a:hover {
            color: #3ac569;
            text-decoration: underline;
        }
        .email-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
        }
        .email-modal-content {
            position: relative;
            background: #1b262c;
            margin: 15% auto;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            border-top: 4px solid #50c878;
        }
        .email-modal-content h3 {
            color: #f6f7eb;
            margin-bottom: 20px;
            text-align: center;
        }
        .email-modal-content input[type="email"] {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            background: #0f4c75;
            border: 1px solid #3282b8;
            border-radius: 5px;
            color: #fff;
        }
        .email-modal-content button {
            background: linear-gradient(135deg, #50c878, #3ac569);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            width: 100%;
            cursor: pointer;
            font-weight: bold;
        }
        .close-modal {
            color: #aaa;
            position: absolute;
            right: 20px;
            top: 10px;
            font-size: 28px;
            cursor: pointer;
        }
        .close-modal:hover {
            color: #fff;
        }
    </style>
    <script>
        // JavaScript code remains the same
        document.addEventListener('DOMContentLoaded', () => {
            const employeeCodeInput = document.getElementById('employee_code');
            const roleInput = document.getElementById('role');
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const adminCodes = ["2023-0001", "2023-0002", "2023-0048"];

            employeeCodeInput.addEventListener('input', async () => {
                const employeeCode = employeeCodeInput.value;
                if (employeeCode) {
                    try {
                        const response = await fetch(`get_names.php?employee_code=${employeeCode}`);
                        if (!response.ok) throw new Error("Network error");
                        const data = await response.json();
                        if (data.length > 0) {
                            nameInput.value = data[0].fullname;
                            emailInput.value = data[0].email;
                        } else {
                            nameInput.value = '';
                            emailInput.value = '';
                        }

                        const supportResponse = await fetch(`get_support_team.php?employee_code=${employeeCode}`);
                        if (supportResponse.ok) {
                            const supportData = await supportResponse.json();
                            if (supportData.length > 0) {
                                roleInput.value = 'IT Member';
                            } else {
                                roleInput.value = adminCodes.includes(employeeCode) ? 'Admin' : 'Staff';
                            }
                        } else {
                            roleInput.value = adminCodes.includes(employeeCode) ? 'Admin' : 'Staff';
                        }
                        roleInput.readOnly = true;
                    } catch (error) {
                        console.error("Failed to fetch data:", error);
                    }
                } else {
                    roleInput.value = '';
                    roleInput.readOnly = false;
                }
            });

            const fileInput = document.getElementById('screenshot');
            const filePreviewContainer = document.createElement('div');
            filePreviewContainer.className = 'file-preview-container';
            fileInput.parentNode.appendChild(filePreviewContainer);

            fileInput.addEventListener('change', function(e) {
                filePreviewContainer.innerHTML = '';
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    const previewDiv = document.createElement('div');
                    previewDiv.className = 'file-preview';
                    reader.onload = function(e) {
                        previewDiv.innerHTML = `<img src="${e.target.result}" alt="Preview"><div class="remove-file" onclick="removeFile()">×</div>`;
                        filePreviewContainer.appendChild(previewDiv);
                    };
                    reader.readAsDataURL(this.files[0]);
                }
            });
        });

        function removeFile() {
            const fileInput = document.getElementById('screenshot');
            const previewContainer = document.querySelector('.file-preview-container');
            fileInput.value = '';
            previewContainer.innerHTML = '';
        }

        function showEmailUpdateModal() {
            document.getElementById('emailUpdateModal').style.display = 'block';
        }

        function closeEmailModal() {
            document.getElementById('emailUpdateModal').style.display = 'none';
        }

        async function updateEmail(event) {
            event.preventDefault();
            const employeeCode = document.getElementById('employee_code').value;
            const newEmail = document.getElementById('new_email').value;

            try {
                const response = await fetch('update_email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `employee_code=${employeeCode}&new_email=${newEmail}`
                });

                const data = await response.json();

                if (data.status === 'success') {
                    alert('Email updated successfully!');
                    document.getElementById('email').value = newEmail; // Update the email field on the form
                    closeEmailModal();
                } else {
                    alert(`Error updating email: ${data.message}`);
                }
            } catch (error) {
                console.error('Error updating email:', error);
                alert('An error occurred while updating the email.');
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Employee Self Service</h1>
        <form id="issue-form" method="POST" enctype="multipart/form-data">
            <input type="text" id="employee_code" name="employee_code" placeholder="Employee Code" required>
            <input type="text" id="name" name="name" placeholder="Name" readonly required>
            <input type="text" id="role" name="role" placeholder="Role" readonly required>
            <input type="email" id="email" name="email" placeholder="Email Address" readonly required>
            <div class="email-update-link">
                <a href="#" onclick="showEmailUpdateModal()">✏️ Update Email Address</a>
            </div>
            <textarea id="issue_description" name="issue_description" placeholder="Describe your issue in detail..." required></textarea>
            <div class="file-upload">
                <label for="screenshot" class="file-upload-text">
                    Upload Screenshot (Optional)
                    <small>Max 2MB - JPG, PNG, GIF only</small>
                </label>
                <input type="file" id="screenshot" name="screenshot" accept="image/jpeg, image/png, image/gif">
            </div>
            <button type="submit">Submit Ticket</button>
            <div class="button-container">
                <button type="button" onclick="window.location.href='follow_up.php'">Follow Up</button>
                <button type="button" onclick="window.location.href='supportcode_request.php'">IT Support Login</button>
            </div>
        </form>
        <a href="https://workplaceportal.pro-learn.co.za/Workplace Portal IT Helpdesk User Guide.pdf" download="Workplace Portal IT Helpdesk User Guide.pdf">
            <button class="download-button">Download User Guide</button>
        </a>
    </div>

    <!-- Add this modal at the bottom of the body before footer -->
    <div id="emailUpdateModal" class="email-modal">
        <div class="email-modal-content">
            <span class="close-modal" onclick="closeEmailModal()">&times;</span>
            <h3>Update Email Address</h3>
            <form id="emailUpdateForm" onsubmit="return updateEmail(event)">
                <input type="email" id="new_email" required placeholder="New Email Address">
                <button type="submit">Update Email</button>
            </form>
        </div>
    </div>

    <footer>
        <p>&copy; Developed by Artisans Republik, division of Progression</p>
    </footer>
</body>
</html>
