<?php
require __DIR__ . '/../vendor/autoload.php'; // ✅ Correct path from backend/ to root vendor/

$uri = "mongodb+srv://Brian05:Mwenda123@cluster0.g5buxkj.mongodb.net/university_tickets?retryWrites=true&w=majority";


try {
    $client = new MongoDB\Client($uri);
    $db = $client->selectDatabase("university_tickets");
} catch (Exception $e) {
    // Don't echo anything here directly
    error_log("❌ MongoDB connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database connection error."
    ]);
    exit;
}
