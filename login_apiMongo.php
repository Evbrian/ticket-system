<?php
require_once "db.php"; // Connects to MongoDB via $db

header("Content-Type: application/json");

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? sanitizeInput($_POST['user_id']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($user_id) || empty($password)) {
        echo json_encode(["success" => false, "message" => "User ID and Password are required."]);
        exit;
    }

    try {
        $collection = $db->accounts; // 👈 Change 'account' if your collection has a different name

        // Find the user by user_id
        $user = $collection->findOne(["user_id" => $user_id]);

        if ($user && password_verify($password, $user['password'])) {
            session_start();
            $_SESSION['user_id'] = $user_id;

            echo json_encode(["success" => true, "message" => "Login successful."]);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid credentials."]);
        }

    } catch (Exception $e) {
        error_log("Login Error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}
