<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the ticket ID from the URL
$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : null;

if (!$ticket_id) {
    die("Error: Missing ticket ID.");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
    $reason = isset($_POST['reason']) ? $conn->real_escape_string($_POST['reason']) : null;

    if (!$rating || $rating < 1 || $rating > 10) {
        die("Error: Invalid rating.");
    }

    // Update the ticket with the rating and reason
    $sql = "UPDATE administered_tickets SET rating = ?, rating_reason = ? WHERE ticket_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $rating, $reason, $ticket_id);

    if ($stmt->execute()) {
        $success_message = "Thank you for your feedback!";
    } else {
        $error_message = "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Our Service | Workplace Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1b262c;
            --secondary-color: #50c878;
            --accent-color: #ffc107;
            --light-bg: #f8f9fa;
            --dark-text: #1b262c;
            --light-text: #f8f9fa;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .rating-container {
            width: 100%;
            max-width: 600px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            animation: fadeIn 0.5s ease-in-out;
        }
        
        .rating-header {
            background-color: var(--primary-color);
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .rating-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .rating-header h1 span {
            color: var(--secondary-color);
        }
        
        .rating-content {
            padding: 30px;
            color: var(--dark-text);
        }
        
        .rating-instructions {
            text-align: center;
            margin-bottom: 25px;
            font-size: 16px;
            line-height: 1.6;
        }
        
        .rating-scale {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .rating-number {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e9ecef;
            border-radius: 50%;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .rating-number:hover {
            background-color: #dee2e6;
            transform: scale(1.1);
        }
        
        .rating-number.active {
            background-color: var(--secondary-color);
            color: white;
            transform: scale(1.1);
        }
        
        .rating-feedback {
            margin-bottom: 25px;
        }
        
        .rating-feedback label {
            display: block;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .rating-feedback textarea {
            width: 100%;
            height: 120px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            resize: none;
            transition: border-color 0.3s ease;
        }
        
        .rating-feedback textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
        }
        
        .rating-submit {
            text-align: center;
        }
        
        .rating-submit button {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .rating-submit button:hover {
            background-color: #3fa861;
            transform: translateY(-2px);
        }
        
        .logo-section {
            text-align: center;
            padding: 20px;
            background: var(--light-bg);
        }
        
        .logo-section img {
            max-width: 200px;
        }
        
        .logo-section p {
            color: #718096;
            margin: 10px 0 0;
            font-size: 14px;
        }
        
        .rating-footer {
            background-color: var(--primary-color);
            color: #a0aec0;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }
        
        .rating-footer a {
            color: var(--secondary-color);
            text-decoration: none;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 576px) {
            .rating-container {
                margin: 20px;
                width: calc(100% - 40px);
            }
            
            .rating-number {
                width: 40px;
                height: 40px;
            }
        }
    </style>
</head>
<body>
    <div class="rating-container">
        <div class="rating-header">
            <h1>Rate Our <span>Service</span></h1>
        </div>
        
        <div class="rating-content">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?= $success_message ?>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'https://workplaceportal.pro-learn.co.za/IT_enroll/helpdesk_front.php';
                        }, 2000);
                    </script>
                </div>
            <?php elseif (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?= $error_message ?>
                    <script>
                        setTimeout(function() {
                            window.location.href = 'https://workplaceportal.pro-learn.co.za/IT_enroll/helpdesk_front.php';
                        }, 2000);
                    </script>
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <div class="rating-instructions">
                        <p>Please rate your experience with our support service on a scale from 1 to 10, with 10 being the best.</p>
                    </div>
                    
                    <div class="rating-scale">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <div class="rating-number" onclick="selectRating(<?= $i ?>)"><?= $i ?></div>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="rating" name="rating" required>
                    
                    <div class="rating-feedback">
                        <label for="reason">Tell us more about your experience (required):</label>
                        <textarea id="reason" name="reason" placeholder="What did we do well? How can we improve?" required></textarea>
                    </div>
                    
                    <div class="rating-submit">
                        <button type="submit">Submit Rating</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <div class="logo-section">
            <img src="https://workplaceportal.pro-learn.co.za/uploads/Artisans.png" alt="Artisans Republik Logo">
            <p>Empowering Digital Workforce Solutions</p>
        </div>
        
        <div class="rating-footer">
            <p>Need immediate assistance? <a href="mailto:workplace@pro-learn.co.za">Contact our live support</a></p>
            <p style="margin-top: 10px;">© 2025 Artisans Republik, Division Of Progression</p>
        </div>
    </div>

    <script>
        function selectRating(rating) {
            // Remove active class from all rating numbers
            document.querySelectorAll('.rating-number').forEach(function(el) {
                el.classList.remove('active');
            });
            
            // Add active class to the selected rating
            event.target.classList.add('active');
            
            // Set the hidden input value
            document.getElementById('rating').value = rating;
        }
        
        // Auto-redirect if no form submission needed
        <?php if (isset($success_message) || isset($error_message)): ?>
            setTimeout(function() {
                window.location.href = 'https://workplaceportal.pro-learn.co.za/IT_enroll/helpdesk_front.php';
            }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>