<?php
// Allow requests from the frontend
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Handle browser preflight requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}
// Connect to the MySQL database
require_once "database.php";

// Only allow GET requests
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    exit;
}

try {
    // Retrieve the doctors and their specialties
    $sql = "SELECT doctor_id, first_name, last_name, specialty
            FROM doctors
            ORDER BY last_name, first_name";

    $stmt = $conn->query($sql);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Return the doctors as JSON
    echo json_encode($doctors);
} catch (PDOException $error) {
    http_response_code(500);
    echo json_encode(["error" => "Unable to retrieve doctors"]);
}