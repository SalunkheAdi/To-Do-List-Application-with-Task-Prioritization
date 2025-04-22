<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set session cookie parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
session_start();

require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Get all tasks
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Database error: " . implode(" ", $pdo->errorInfo()));
        }
        
        $stmt->execute([$user_id]);
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert completed status to boolean
        foreach ($tasks as &$task) {
            $task['completed'] = (bool)$task['completed'];
        }
        
        // Return tasks directly without wrapping in status/data
        echo json_encode($tasks);
        exit();
    }

    // Create new task
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!$data || !isset($data->task) || !isset($data->priority)) {
            throw new Exception("Task and priority are required");
        }
        
        $task = htmlspecialchars(trim($data->task));
        $priority = htmlspecialchars(trim($data->priority));
        $dueDate = !empty($data->due_date) ? htmlspecialchars(trim($data->due_date)) : null;
        
        $sql = "INSERT INTO tasks (user_id, task, priority, due_date) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Database error: " . implode(" ", $pdo->errorInfo()));
        }
        
        $stmt->execute([$user_id, $task, $priority, $dueDate]);
        $newId = $pdo->lastInsertId();
        
        // Fetch the newly created task
        $sql = "SELECT * FROM tasks WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$newId]);
        $newTask = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$newTask) {
            throw new Exception("Failed to retrieve created task");
        }
        
        $newTask['completed'] = (bool)$newTask['completed'];
        
        echo json_encode([
            "status" => "success",
            "message" => "Task created successfully",
            "task" => $newTask
        ]);
        exit();
    }

    // Update task completion status
    if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $data = json_decode(file_get_contents("php://input"));
        
        if (!$data || !isset($data->id)) {
            throw new Exception("Task ID is required");
        }
        
        $id = intval($data->id);
        
        // First check if task exists and belongs to user
        $check_sql = "SELECT completed FROM tasks WHERE id = ? AND user_id = ?";
        $check_stmt = $pdo->prepare($check_sql);
        
        if (!$check_stmt) {
            throw new Exception("Database error: " . implode(" ", $pdo->errorInfo()));
        }
        
        $check_stmt->execute([$id, $user_id]);
        $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$row) {
            throw new Exception("Task not found or unauthorized");
        }
        
        // Toggle completion status
        $new_status = $row['completed'] ? 0 : 1;
        
        // Update the task
        $sql = "UPDATE tasks SET completed = ? WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Database error: " . implode(" ", $pdo->errorInfo()));
        }
        
        $stmt->execute([$new_status, $id, $user_id]);
        
        echo json_encode([
            "status" => "success",
            "message" => "Task updated successfully",
            "task" => [
                "id" => $id,
                "completed" => (bool)$new_status
            ]
        ]);
        exit();
    }

    // Delete task
    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if (!isset($_GET['id'])) {
            throw new Exception("Task ID is required");
        }
        
        $id = intval($_GET['id']);
        
        $sql = "DELETE FROM tasks WHERE id = ? AND user_id = ?";
        $stmt = $pdo->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Database error: " . implode(" ", $pdo->errorInfo()));
        }
        
        $stmt->execute([$id, $user_id]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception("Task not found or unauthorized");
        }
        
        echo json_encode([
            "status" => "success",
            "message" => "Task deleted successfully",
            "id" => $id
        ]);
        exit();
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
} finally {
    if (isset($pdo)) {
        $pdo = null;
    }
}
?> 