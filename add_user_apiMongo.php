<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php'; // This should connect to MongoDB Atlas

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Simple input sanitizer
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Read input and sanitize
    $userId     = sanitizeInput($_POST['userId'] ?? '');
    $name       = sanitizeInput($_POST['name'] ?? '');
    $email      = sanitizeInput($_POST['email'] ?? '');
    $role       = sanitizeInput($_POST['role'] ?? '');
    $phone      = sanitizeInput($_POST['phone'] ?? '');
    $department = sanitizeInput($_POST['department'] ?? '');
    $status     = sanitizeInput($_POST['status'] ?? 'active');
    $createdAt  = sanitizeInput($_POST['createdAt'] ?? '');
    $loginCount = (int)($_POST['loginCount'] ?? 0);

    // Validation
    $errors = [];

    if (empty($userId)) $errors[] = 'User ID is required';
    if (empty($name) || strlen($name) < 2) $errors[] = 'Full name must be at least 2 characters';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email';
    if (empty($role)) $errors[] = 'Role is required';

    $allowedRoles = ['admin', 'user', 'moderator', 'guest'];
    if (!in_array($role, $allowedRoles)) $errors[] = 'Invalid role selected';

    $allowedStatuses = ['active', 'inactive', 'suspended'];
    if (!in_array($status, $allowedStatuses)) $errors[] = 'Invalid status selected';

    if (!empty($phone) && !preg_match('/^(?:\+254|254|0)?7\d{8}$/', $phone)) {
        $errors[] = 'Invalid Kenyan phone number. Must start with 07, 2547, or +2547 and have 10 digits after.';
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        exit;
    }

    // Use the MongoDB collection
    $usersCollection = $db->users;

    // Check for existing user ID
    $existingUserId = $usersCollection->findOne(['user_id' => $userId]);
    if ($existingUserId) {
        echo json_encode(['success' => false, 'message' => 'User ID already exists']);
        exit;
    }

    // Check for existing email
    $existingEmail = $usersCollection->findOne(['email' => $email]);
    if ($existingEmail) {
        echo json_encode(['success' => false, 'message' => 'Email address already exists']);
        exit;
    }

    // Prepare user data
    $newUser = [
        'user_id' => $userId,
        'name' => $name,
        'email' => $email,
        'role' => $role,
        'phone' => $phone,
        'department' => $department,
        'status' => $status,
        'login_count' => $loginCount,
        'created_at' => new MongoDB\BSON\UTCDateTime(strtotime($createdAt ?: 'now') * 1000),
        'updated_at' => new MongoDB\BSON\UTCDateTime()
    ];

    // Insert user
    $insertResult = $usersCollection->insertOne($newUser);

    if ($insertResult->getInsertedCount() === 1) {
        $userData = $usersCollection->findOne(['_id' => $insertResult->getInsertedId()], [
            'projection' => ['_id' => 0, 'user_id' => 1, 'name' => 1, 'email' => 1, 'role' => 1, 'status' => 1]
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'user' => $userData
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create user']);
    }

} catch (MongoDB\Driver\Exception\Exception $e) {
    http_response_code(500);
    error_log("MongoDB error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error.', 'error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    error_log("Unexpected error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unexpected server error.', 'error' => $e->getMessage()]);
}

?>
