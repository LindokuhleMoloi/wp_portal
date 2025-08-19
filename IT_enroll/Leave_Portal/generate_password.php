<?php
require_once('../../config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    if (empty($_POST['employee_id']) || empty($_POST['employee_code']) || empty($_POST['lastname']) || empty($_POST['firstname'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit;
    }

    $employee_id = (int)$_POST['employee_id'];
    $employee_code = trim($_POST['employee_code']);
    $lastname = trim($_POST['lastname']);
    $firstname = trim($_POST['firstname']);

    // Generate stronger password with multiple components
    $password_components = [
        substr($employee_code, 0, 3),  // First 3 chars of employee code
        substr($lastname, 0, 2),       // First 2 chars of lastname
        substr($firstname, 0, 2),      // First 2 chars of firstname
        bin2hex(random_bytes(3)),      // 6 random hex chars
        rand(100, 999)                // Random 3-digit number
    ];
    
    // Shuffle components and join
    shuffle($password_components);
    $plain_password = implode('', $password_components);
    
    // Ensure password meets complexity requirements
    $plain_password = $this->ensurePasswordComplexity($plain_password);

    // Hash the password
    $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

    // Save to database
    $stmt = $conn->prepare("INSERT INTO employee_access (employee_id, employee_code, password) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password)");
    $stmt->bind_param("iss", $employee_id, $employee_code, $hashed_password);

    if ($stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'plain_password' => $plain_password
        ]);
    } else {
        error_log("Password save failed: " . $stmt->error);
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}

/**
 * Ensures password meets complexity requirements
 */
function ensurePasswordComplexity($password) {
    // Ensure at least one uppercase, one lowercase, one number
    if (!preg_match('/[A-Z]/', $password)) {
        $password .= chr(rand(65, 90)); // Add random uppercase
    }
    if (!preg_match('/[a-z]/', $password)) {
        $password .= chr(rand(97, 122)); // Add random lowercase
    }
    if (!preg_match('/[0-9]/', $password)) {
        $password .= rand(0, 9); // Add random number
    }
    return $password;
}
?>