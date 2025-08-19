<?php
// Include necessary files and initialize database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve POST data
    $viscode = $_POST['viscode'];
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $purpose = $_POST['purpose'];

    // Perform validation and database insertion
    // Example validation and insertion
    $query = "INSERT INTO visitor_logs (viscode, name, contact, purpose, logged_in_time) 
              VALUES ('$viscode', '$name', '$contact', '$purpose', NOW())";
    
    if (mysqli_query($conn, $query)) {
        $response['success'] = true;
        $response['id'] = mysqli_insert_id($conn);
    } else {
        $response['success'] = false;
        $response['error'] = mysqli_error($conn); // Optional: for debugging
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Handle invalid request method
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>
