<?php

require_once "database.php";

$sql = "SELECT * FROM patients";

$stmt = $conn->query($sql);

$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: application/json");

echo json_encode($patients);