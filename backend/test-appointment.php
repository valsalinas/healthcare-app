
<?php

$url = "http://localhost:8000/appointments.php";

$data = [
    "patient_id" => 12,
    "doctor_id" => 1,
    "appointment_datetime" => "2026-11-10 11:00:00",
    "reason" => "Routine checkup"
];

$options = [
    "http" => [
        "method" => "POST",
        "header" => "Content-Type: application/json\r\n",
        "content" => json_encode($data),
        "ignore_errors" => true
    ]
];

$context = stream_context_create($options);

$response = @file_get_contents($url, false, $context);

// Show the API response, including HTTP error responses
if ($response !== false) {
    echo $response;
} else {
    echo "The request could not retrieve a response.\n";

    if (isset($http_response_header)) {
        print_r($http_response_header);
    }
}
