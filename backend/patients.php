<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once "database.php";

// ---------- Helpers ----------

// Send a JSON response with a status code and stop.
function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

// Read the request body and make sure it is valid JSON.
function readJsonBody(): array
{
    $data = json_decode(file_get_contents("php://input"), true);

    if (!is_array($data)) {
        respond(400, ["error" => "Invalid JSON request body"]);
    }

    return $data;
}

// Return a trimmed string, or "" if the value is missing or not a string.
function textField(array $data, string $key): string
{
    $value = $data[$key] ?? "";
    return is_string($value) ? trim($value) : "";
}

// Make sure a patient ID is a positive whole number.
function validPatientId(mixed $value): int
{
    $id = filter_var($value, FILTER_VALIDATE_INT);

    if ($id === false || $id < 1) {
        respond(400, ["error" => "A valid patient ID is required"]);
    }

    return $id;
}

// Validate the patient fields used by both POST and PUT.
function validatePatientFields(array $data): array
{
    $firstName = textField($data, "first_name");
    $lastName  = textField($data, "last_name");
    $email     = textField($data, "email");
    $phone     = textField($data, "phone");

    if ($firstName === "" || $lastName === "" || $email === "") {
        respond(400, ["error" => "First name, last name, and email are required"]);
    }

    if (mb_strlen($firstName) > 50 || mb_strlen($lastName) > 50) {
        respond(400, ["error" => "First and last names must be 50 characters or fewer"]);
    }

    if (mb_strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(400, ["error" => "Please enter a valid email address"]);
    }

    // Phone is optional in the database, but if provided it must look like a phone number.
    if ($phone !== "" && (mb_strlen($phone) > 20 || !preg_match('/^[0-9+().\- ]+$/', $phone))) {
        respond(400, ["error" => "Phone can use digits, spaces, + - ( ) and be at most 20 characters"]);
    }

    return [
        "first_name" => $firstName,
        "last_name"  => $lastName,
        "email"      => $email,
        "phone"      => $phone !== "" ? $phone : null,
    ];
}

function patientExists(PDO $conn, int $patientId): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM patients WHERE patient_id = :patient_id");
    $stmt->execute(["patient_id" => $patientId]);
    return (bool) $stmt->fetchColumn();
}

// ---------- Routing ----------

$method = $_SERVER["REQUEST_METHOD"];

try {

    // CREATE
    if ($method === "POST") {
        $fields = validatePatientFields(readJsonBody());

        $sql = "INSERT INTO patients (first_name, last_name, email, phone)
                VALUES (:first_name, :last_name, :email, :phone)";

        $stmt = $conn->prepare($sql);
        $stmt->execute($fields);

        respond(201, [
            "message" => "Patient created successfully",
            "patient_id" => (int) $conn->lastInsertId()
        ]);
    }

    // UPDATE
    if ($method === "PUT") {
        $data = readJsonBody();
        $patientId = validPatientId($data["patient_id"] ?? null);
        $fields = validatePatientFields($data);

        // rowCount() is 0 when nothing changed, so check existence separately.
        if (!patientExists($conn, $patientId)) {
            respond(404, ["error" => "Patient not found"]);
        }

        $sql = "UPDATE patients
                SET first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone
                WHERE patient_id = :patient_id";

        $fields["patient_id"] = $patientId;

        $stmt = $conn->prepare($sql);
        $stmt->execute($fields);

        respond(200, ["message" => "Patient updated successfully"]);
    }

    // DELETE
    if ($method === "DELETE") {
        $data = readJsonBody();
        $patientId = validPatientId($data["patient_id"] ?? null);

        $stmt = $conn->prepare("DELETE FROM patients WHERE patient_id = :patient_id");
        $stmt->execute(["patient_id" => $patientId]);

        if ($stmt->rowCount() === 0) {
            respond(404, ["error" => "Patient not found"]);
        }

        respond(200, ["message" => "Patient deleted successfully"]);
    }

    // READ
    if ($method === "GET") {

        // One patient by ID
        if (isset($_GET["id"])) {
            $id = validPatientId($_GET["id"]);

            $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = :id");
            $stmt->execute(["id" => $id]);
            $patient = $stmt->fetch();

            if (!$patient) {
                respond(404, ["error" => "Patient not found"]);
            }

            respond(200, $patient);
        }

        // Search by first or last name
        if (isset($_GET["name"])) {
            $name = is_string($_GET["name"]) ? trim($_GET["name"]) : "";

            if ($name === "") {
                respond(400, ["error" => "Search text is required"]);
            }

            $sql = "SELECT * FROM patients
                    WHERE first_name LIKE :first_name
                       OR last_name LIKE :last_name
                    ORDER BY last_name, first_name";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                "first_name" => "%$name%",
                "last_name"  => "%$name%"
            ]);

            respond(200, $stmt->fetchAll());
        }

        // All patients
        $stmt = $conn->query("SELECT * FROM patients ORDER BY last_name, first_name");
        respond(200, $stmt->fetchAll());
    }

    respond(405, ["error" => "Method not allowed"]);

} catch (PDOException $e) {
    // A patient with appointments can't be deleted (foreign key).
    if ($method === "DELETE" && $e->getCode() === "23000") {
        respond(409, ["error" => "This patient has appointments and cannot be deleted"]);
    }

    error_log("patients.php: " . $e->getMessage());
    respond(500, ["error" => "Database error"]);
}