<?php
require_once('./config.php');
require_once('classes/DBConnection.php');

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$visitorId = $input['id'];

if (!$visitorId) {
  echo json_encode(['status' => 'error', 'message' => 'Invalid visitor ID.']);
  exit;
}

$conn = new mysqli("localhost", "tarryn_Lindokuhle", "L1nd0kuhle", "tarryn_workplaceportal");

if ($conn->connect_error) {
  echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
  exit;
}

$conn->autocommit(false);

try {
  // Fetch visitor details
  $query = "SELECT * FROM visitor_logs WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param('i', $visitorId);
  $stmt->execute();
  $result = $stmt->get_result();
  $visitor = $result->fetch_assoc();
  $stmt->close();

  if (!$visitor) {
    throw new Exception('Visitor not found.');
  }
  
  // Insert visitor into visitor_out
  $query = "INSERT INTO visitor_out (name, contact, viscode, purpose, type, date_created,date_Out)
            VALUES (?, ?, ?, ?, 2, ?, CURRENT_TIMESTAMP)";
  $stmt = $conn->prepare($query);
  $stmt->bind_param('sssss', $visitor['name'], $visitor['contact'], $visitor['viscode'], $visitor['purpose'], $visitor['date_created']);
  $stmt->execute();
  $stmt->close();

  // Remove visitor from visitor_logs
  $query = "DELETE FROM visitor_logs WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param('i', $visitorId);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  echo json_encode(['status' => 'success', 'message' => 'Visitor moved out successfully.']);
} catch (Exception $e) {
  $conn->rollback();
  echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>
