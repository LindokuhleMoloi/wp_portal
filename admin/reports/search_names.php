<?php
// Include your database connection file here
include 'db_connection.php'; // Update this to your actual DB connection script

if (isset($_GET['term'])) {
    $term = $_GET['term'];
    $sql = "SELECT name, contact, purpose FROM visitor_logs WHERE name LIKE '%$term%' LIMIT 10";
    $result = $conn->query($sql);
    $suggestions = [];
    
    while ($row = $result->fetch_assoc()) {
        $suggestions[] = $row;
    }
    
    echo json_encode($suggestions);
}
?>
