<?php
require_once 'db.php'; // Uses your existing MongoDB connection ($db)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        // Input sanitization
        function sanitizeInput($input) {
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }

        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all fields']);
            exit;
        }

        if (strlen($username) < 3) {
            echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit;
        }

        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
            exit;
        }

        if ($password !== $confirmPassword) {
            echo json_encode(['success' => false, 'message' => 'Passwords do not match']);
            exit;
        }

        $collection = $db->login; // Use 'login' collection

        // Check for existing username or email
        $existing = $collection->findOne([
            '$or' => [
                ['username' => $username],
                ['email' => $email]
            ]
        ]);

        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $result = $collection->insertOne([
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);

        if ($result->getInsertedCount() === 1) {
            echo json_encode(['success' => true, 'message' => 'Registration successful! Redirecting...']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to register user.']);
        }
    } catch (Exception $e) {
        error_log("❌ Registration error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Something went wrong. Try again.']);
    }

    exit;
}
?>
