<?php
ob_start(); // Start output buffering

require_once 'PHPMailer/src/Exception.php';
require_once 'PHPMailer/src/PHPMailer.php';
require_once 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Validate parameters
if (!isset($_GET['ticket_id'], $_GET['response'])) {
    die("Invalid request parameters");
}

$ticket_id = (int)$_GET['ticket_id'];
$response = $_GET['response'];

// Fetch ticket information
$stmt = $conn->prepare("SELECT email FROM administered_tickets WHERE ticket_id = ?");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Ticket not found");
}

$row = $result->fetch_assoc();
$ticket_opener_email = $row['email'];
$stmt->close();

if ($response === 'yes') {
    $stmt = $conn->prepare("UPDATE administered_tickets SET status = 'Closed', closed_at = NOW() WHERE ticket_id = ?");
    $stmt->bind_param("i", $ticket_id);
    
    if ($stmt->execute()) {
        // Full email template with updated styling
        $subject = "Your Ticket Has Been Resolved and Closed";
        $body = '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket Resolved and Closed</title>
            <style>
                @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap");
                body { font-family: "Poppins", sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
                .email-container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
                .header { background: #1b262c; padding: 40px 30px; text-align: center; }
                .header h1 { color: #ffffff; font-size: 28px; margin: 0; font-weight: 600; }
                .header h1 span { color: #50c878; }
                .content { padding: 30px; color: #4a5568; line-height: 1.7; }
                .footer { background: #1b262c; color: #a0aec0; padding: 20px; text-align: center; font-size: 14px; }
                .footer a { color: #50c878; text-decoration: none; }
                .btn { display: inline-block; padding: 12px 24px; margin-top: 15px; background: #50c878; color: white; text-decoration: none; border-radius: 25px; font-weight: 600; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h1>Ticket <span>Resolved</span> Successfully</h1>
                </div>
                <div class="content">
                    <p>Dear User,</p>
                    <p>We are pleased to inform you that your ticket with ID <strong>'.$ticket_id.'</strong> has been successfully resolved and closed.</p>
                    <p>Thank you for trusting us. If you have any further concerns, feel free to reach out again.</p>
                    <a href="https://workplaceportal.pro-learn.co.za/IT_enroll/rate_service.php?ticket_id='.$ticket_id.'" class="btn">Rate Our Service</a>
                </div>
                <div class="logo-section" style="text-align: center; padding: 20px; background: #f8fafc;">
                    <img src="https://workplaceportal.pro-learn.co.za/uploads/Artisans.png" alt="Artisans Republik Logo" style="max-width: 200px;">
                    <p style="color: #718096; margin: 10px 0 0; font-size: 14px;">Empowering Digital Workforce Solutions</p>
                </div>
                <div class="footer">
                    <p>Need immediate assistance? <a href="mailto:workplace@pro-learn.co.za">Contact our live support</a></p>
                    <p style="margin-top: 10px;">© 2025 Artisans Republik, Division Of Progression</p>
                </div>
            </div>
        </body>
        </html>';

        // Send email
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
            $mail->addAddress($ticket_opener_email);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();
        } catch (Exception $e) {}

        // Clear all output buffers
        while (ob_get_level() > 0) ob_end_clean();
        
        // Send proper HTML with SweetAlert
        echo '<!DOCTYPE html>
        <html>
        <head>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        </head>
        <body>
            <script>
                Swal.fire({
                    icon: "success",
                    title: "Ticket Closed Successfully",
                    text: "Your ticket has been successfully resolved and closed.",
                    showConfirmButton: true,
                    timer: 3000,
                    timerProgressBar: true,
                    willClose: () => {
                        window.location.href = "https://workplaceportal.pro-learn.co.za/IT_enroll/helpdesk_front.php";
                    }
                });
            </script>
        </body>
        </html>';
        exit();
    }
    $stmt->close();
} else {
    // "No" response handling with updated styling
    $subject = "Your Ticket Remains Open";
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Ticket Remains Open</title>
        <style>
            @import url("https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap");
            body { font-family: "Poppins", sans-serif; background: #f8f9fa; margin: 0; padding: 0; }
            .email-container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 8px 30px rgba(0,0,0,0.1); }
            .header { background: #1b262c; padding: 40px 30px; text-align: center; }
            .header h1 { color: #ffffff; font-size: 28px; margin: 0; font-weight: 600; }
            .header h1 span { color: #ffc107; }
            .content { padding: 30px; color: #4a5568; line-height: 1.7; }
            .footer { background: #1b262c; color: #a0aec0; padding: 20px; text-align: center; font-size: 14px; }
            .footer a { color: #50c878; text-decoration: none; }
        </style>
    </head>
    <body>
        <div class="email-container">
            <div class="header">
                <h1>Ticket <span>Remains</span> Open</h1>
            </div>
            <div class="content">
                <p>Dear User,</p>
                <p>Your ticket with ID <strong>'.$ticket_id.'</strong> remains open. We will get back to you soon.</p>
                <p>If you have any further concerns, feel free to reach out again.</p>
            </div>
            <div class="logo-section" style="text-align: center; padding: 20px; background: #f8fafc;">
                <img src="https://workplaceportal.pro-learn.co.za/uploads/Artisans.png" alt="Artisans Republik Logo" style="max-width: 200px;">
                <p style="color: #718096; margin: 10px 0 0; font-size: 14px;">Empowering Digital Workforce Solutions</p>
            </div>
            <div class="footer">
                <p>Need immediate assistance? <a href="mailto:workplace@pro-learn.co.za">Contact our live support</a></p>
                <p style="margin-top: 10px;">© 2025 Artisans Republik, Division Of Progression</p>
            </div>
        </div>
    </body>
    </html>';

    // Send email
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
        $mail->addAddress($ticket_opener_email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
    } catch (Exception $e) {}

    // Clear output
    while (ob_get_level() > 0) ob_end_clean();
    
    echo '<!DOCTYPE html>
    <html>
    <head>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>
    <body>
        <script>
            Swal.fire({
                icon: "info",
                title: "Ticket Remains Open",
                text: "The ticket will stay open for further investigation.",
                willClose: () => {
                    window.location.href = "https://workplaceportal.pro-learn.co.za/IT_enroll/helpdesk_front.php";
                }
            });
        </script>
    </body>
    </html>';
    exit();
}

$conn->close();
?>