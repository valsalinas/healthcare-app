<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once "database.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") { //What type of HTTP request did I receive? If it's a POST request, then I want to create a new patient.

    $data = json_decode(file_get_contents("php://input"), true); //saves the data sent by the client in a variable called $data. The data is sent in JSON format, so we need to decode it into a PHP associative array.
    $firstName = $data["first_name"];
    $lastName = $data["last_name"];
    $email = $data["email"];
    $phone = $data["phone"];

    $sql = "INSERT INTO patients (first_name, last_name, email, phone)
            VALUES (:first_name, :last_name, :email, :phone)";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        "first_name" => $firstName,
        "last_name" => $lastName,
        "email" => $email,
        "phone" => $phone
    ]);

    echo json_encode([
        "message" => "Patient created successfully"
    ]);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "PUT") {

    $data = json_decode(file_get_contents("php://input"), true);

    $patientId = $data["patient_id"];
    $firstName = $data["first_name"];
    $lastName = $data["last_name"];
    $email = $data["email"];
    $phone = $data["phone"];

    $sql = "UPDATE patients
            SET first_name = :first_name,
                last_name = :last_name,
                email = :email,
                phone = :phone
            WHERE patient_id = :patient_id";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        "first_name" => $firstName,
        "last_name" => $lastName,
        "email" => $email,
        "phone" => $phone,
        "patient_id" => $patientId
    ]);

    echo json_encode([
        "message" => "Patient updated successfully"
    ]);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "DELETE") {

    $data = json_decode(file_get_contents("php://input"), true);

    $patientId = $data["patient_id"];

    $sql = "DELETE FROM patients
            WHERE patient_id = :patient_id";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        "patient_id" => $patientId
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            "message" => "Patient deleted successfully"
        ]);
    } else {
        echo json_encode([
            "error" => "Patient not found"
        ]);
    }

    exit;
}

if (isset($_GET["id"])) { //"Did the user provide an id in the URL?"

    $id = $_GET["id"];

    $sql = "SELECT * FROM patients WHERE patient_id = :id";

    $stmt = $conn->prepare($sql);

    $stmt->execute(["id" => $id]);

    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($patient) {
        echo json_encode($patient);
    } else {
        echo json_encode(["error" => "Patient not found"]);
    }

} elseif (isset($_GET["name"])) {
    $name = $_GET["name"];
    $sql = "SELECT * FROM patients
            WHERE first_name LIKE :first_name
            OR last_name LIKE :last_name";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        "first_name" => "%$name%",
        "last_name" => "%$name%"
    ]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($patients);
} else {
    $sql = "SELECT * FROM patients";
    $stmt = $conn->query($sql);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($patients);
}