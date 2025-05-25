<?php
session_start();
require 'config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION["user_id"])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Handle delete operation
        if (isset($_POST['delete_id'])) {
            $stmt = $conn->prepare("DELETE FROM history WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $_POST['delete_id'], $user_id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            exit();
        }
        
        // Handle update operation
        if (isset($_POST['update_id'])) {
            $expression = trim($_POST['expression'] ?? '');
            $result = trim($_POST['result'] ?? '');
            
            if (empty($expression) || empty($result)) {
                throw new Exception('Missing expression or result');
            }
            
            $stmt = $conn->prepare("UPDATE history SET expression = ?, result = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssii", $expression, $result, $_POST['update_id'], $user_id);
            $stmt->execute();
            
            echo json_encode(['success' => true]);
            exit();
        }
        
        // Handle new calculation
        $expression = trim($_POST['expression'] ?? '');
        $result = trim($_POST['result'] ?? '');
        
        if (empty($expression) || empty($result)) {
            throw new Exception('Missing expression or result');
        }
        
        $stmt = $conn->prepare("INSERT INTO history (user_id, expression, result) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $expression, $result);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'id' => $stmt->insert_id
        ]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>