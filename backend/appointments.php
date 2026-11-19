<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

require_once "database.php";

// GET: Retrieve appointments with patient and doctor information
if ($_SERVER["REQUEST_METHOD"] === "GET") {

    $sql = "SELECT
                a.appointment_id,
                a.patient_id,
                CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
                a.doctor_id,
                CONCAT(d.first_name, ' ', d.last_name) AS doctor_name,
                d.specialty,
                a.appointment_datetime,
                a.reason,
                a.status
            FROM appointments a
            JOIN patients p
                ON a.patient_id = p.patient_id
            JOIN doctors d
                ON a.doctor_id = d.doctor_id
            ORDER BY a.appointment_datetime ASC";

    $stmt = $conn->query($sql);

    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($appointments);

    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = json_decode(file_get_contents("php://input"), true);

    // Check that the request contains valid JSON
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid JSON request body"
        ]);
        exit;
    }

    $patientId = filter_var(
        $data["patient_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $doctorId = filter_var(
        $data["doctor_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $appointmentDatetime = $data["appointment_datetime"] ?? null;
    $reason = trim($data["reason"] ?? "");

    // Check required fields
    if (
        $patientId === false || $patientId === null || $patientId < 1 ||
        $doctorId === false || $doctorId === null || $doctorId < 1 ||
        !is_string($appointmentDatetime) ||
        $appointmentDatetime === ""
    ) {
        http_response_code(400);
        echo json_encode([
            "error" => "Valid patient ID, doctor ID, and appointment date/time are required"
        ]);
        exit;
    }

    // Validate the date and time format
    $date = DateTime::createFromFormat(
        "!Y-m-d H:i:s",
        $appointmentDatetime
    );

    $dateErrors = DateTime::getLastErrors();

    if (
        !$date ||
        ($dateErrors !== false &&
            ($dateErrors["warning_count"] > 0 ||
             $dateErrors["error_count"] > 0)) ||
        $date->format("Y-m-d H:i:s") !== $appointmentDatetime
    ) {
        http_response_code(400);
        echo json_encode([
            "error" => "Use the format YYYY-MM-DD HH:MM:SS for the appointment date and time"
        ]);
        exit;
    }

    // Check that the patient exists
    $sql = "SELECT patient_id
            FROM patients
            WHERE patient_id = :patient_id";

    $stmt = $conn->prepare($sql);
    $stmt->execute(["patient_id" => $patientId]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            "error" => "Patient not found"
        ]);
        exit;
    }

    // Check that the doctor exists
    $sql = "SELECT doctor_id
            FROM doctors
            WHERE doctor_id = :doctor_id";

    $stmt = $conn->prepare($sql);
    $stmt->execute(["doctor_id" => $doctorId]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            "error" => "Doctor not found"
        ]);
        exit;
    }

    // Check whether this doctor is already booked
    $sql = "SELECT appointment_id
            FROM appointments
            WHERE doctor_id = :doctor_id
              AND appointment_datetime = :appointment_datetime
              AND status = 'scheduled'";

    $stmt = $conn->prepare($sql);

    $stmt->execute([
        "doctor_id" => $doctorId,
        "appointment_datetime" => $appointmentDatetime
    ]);

    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            "error" => "This doctor already has an appointment at that time"
        ]);
        exit;
    }

    // Insert the appointment into the database
    $sql = "INSERT INTO appointments (
                patient_id,
                doctor_id,
                appointment_datetime,
                reason
            )
            VALUES (
                :patient_id,
                :doctor_id,
                :appointment_datetime,
                :reason
            )";

    try {
        $stmt = $conn->prepare($sql);

        $stmt->execute([
            "patient_id" => $patientId,
            "doctor_id" => $doctorId,
            "appointment_datetime" => $appointmentDatetime,
            "reason" => $reason !== "" ? $reason : null
        ]);
    } catch (PDOException $e) {
        // 23000 = duplicate key: another booking took this slot
        // (the database also blocks cancelled appointments at the same time)
        if ($e->getCode() === "23000" &&
            isset($e->errorInfo[1]) &&
            $e->errorInfo[1] === 1062
        ){
            http_response_code(409);
            echo json_encode([
                "error" => "This doctor already has an appointment at that time"
            ]);
            exit;
        }

        error_log("appointments.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(["error" => "Unable to schedule appointment"]);
        exit;
    }

    http_response_code(201);

    echo json_encode([
        "message" => "Appointment scheduled successfully",
        "appointment_id" => $conn->lastInsertId()
    ]);

    exit;
}
// PUT: Cancel an appointment
if ($_SERVER["REQUEST_METHOD"] === "PUT") {

    $data = json_decode(file_get_contents("php://input"), true);

    // Check that the request contains valid JSON
    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode([
            "error" => "Invalid JSON request body"
        ]);
        exit;
    }

    // Get and validate the appointment ID
    $appointmentId = filter_var(
        $data["appointment_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (
        $appointmentId === false ||
        $appointmentId === null ||
        $appointmentId < 1
    ) {
        http_response_code(400);
        echo json_encode([
            "error" => "A valid appointment ID is required"
        ]);
        exit;
    }

    // Check whether the appointment exists
    $sql = "SELECT appointment_id, status
            FROM appointments
            WHERE appointment_id = :appointment_id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        "appointment_id" => $appointmentId
    ]);

    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment) {
        http_response_code(404);
        echo json_encode([
            "error" => "Appointment not found"
        ]);
        exit;
    }

    // Only scheduled appointments can be cancelled
    if ($appointment["status"] !== "scheduled") {
        http_response_code(409);
        echo json_encode([
            "error" => "Only scheduled appointments can be cancelled"
        ]);
        exit;
    }

    // Update the appointment status
    $sql = "UPDATE appointments
            SET status = 'cancelled'
            WHERE appointment_id = :appointment_id";

    $stmt = $conn->prepare($sql);
    $stmt->execute([
        "appointment_id" => $appointmentId
    ]);

    echo json_encode([
        "message" => "Appointment cancelled successfully"
    ]);

    exit;
}

// Reject unsupported methods
http_response_code(405);

echo json_encode([
    "error" => "Method not allowed"
]);