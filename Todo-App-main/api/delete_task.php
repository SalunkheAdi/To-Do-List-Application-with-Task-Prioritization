<?php
require_once 'config.php';
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");

try {
    // Get and validate task_id
    $taskId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($taskId <= 0) {
        throw new Exception("Invalid task ID");
    }

    // Delete the task
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    if (!$stmt->execute([$taskId])) {
        throw new Exception("Error deleting task");
    }

    if ($stmt->rowCount() === 0) {
        throw new Exception("Task not found");
    }

    // Return success response
    echo json_encode([
        "status" => "success",
        "message" => "Task deleted successfully"
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?> 