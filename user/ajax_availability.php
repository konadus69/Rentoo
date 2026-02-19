<?php
// ajax endpoint to get real-time equipment availability
require_once '../includes/auth.php';
require_once '../config/db.php';

// only logged-in users can access this
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

header('Content-Type: application/json');

// if an id is passed, get just that one item, otherwise get all
if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $conn->prepare("SELECT id, available_quantity FROM equipment WHERE id = ?");
    $stmt->bind_param("i", $id);
} else {
    $stmt = $conn->prepare("SELECT id, available_quantity FROM equipment");
}

$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'data' => $data]);
