<?php

$url = "http://localhost:8000/appointments.php";

$data = [
    "appointment_id" => 3
];

$options = [
    "http" => [
        "method" => "PUT",
        "header" => "Content-Type: application/json\r\n",
        "content" => json_encode($data),
        "ignore_errors" => true
    ]
];

$context = stream_context_create($options);

$response = file_get_contents($url, false, $context);

echo $response;