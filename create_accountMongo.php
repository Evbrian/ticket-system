<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once "db.php"; // MongoDB Atlas connection

function sanitizeInput($data) {
    return trim(htmlspecialchars(strip_tags($data)));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

// Get and sanitize input
$role     = isset($_POST['role']) ? strtolower(sanitizeInput($_POST['role'])) : '';
$user_id  = isset($_POST['user_id']) ? sanitizeInput($_POST['user_id']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate required fields
if (empty($role) || empty($user_id) || empty($password)) {
    echo json_encode([
        "success" => false,
        "message" => "All fields are required."
    ]);
    exit;
}

try {
    // Debug input
    // var_dump("|$user_id|", "|$role|");

    // Step 1: Check if user exists in the users collection (case-sensitive match)
    $user = $db->users->findOne([
        'user_id' => $user_id,
        'role' => $role
    ]);

    if (!$user) {
        // Try again with case-insensitive role match using regex
        $user = $db->users->findOne([
            'user_id' => $user_id,
            'role' => new MongoDB\BSON\Regex('^' . preg_quote($role) . '$', 'i')
        ]);
    }

    if (!$user) {
        echo json_encode([
            "success" => false,
            "message" => "User ID and Role do not match any registered user."
        ]);
        exit;
    }

    // Step 2: Check if account already exists
    $existingAccount = $db->accounts->findOne([
        'user_id' => $user_id
    ]);

    if ($existingAccount) {
        echo json_encode([
            "success" => false,
            "message" => "Account already exists for this User ID."
        ]);
        exit;
    }

    // Step 3: Create account
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $db->accounts->insertOne([
        'user_id'    => $user_id,
        'role'       => $role,
        'password'   => $hashedPassword,
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Account created successfully."
    ]);
    exit;

} catch (Exception $e) {
    error_log("MongoDB Exception: " . $e->getMessage());

    echo json_encode([
        "success" => false,
        "message" => "An unexpected error occurred while creating the account."
    ]);
    exit;
}
