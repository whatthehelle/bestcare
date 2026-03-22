<?php
require_once 'database.php';

$department = trim($_GET['department'] ?? '');

if (empty($department)) {
    echo json_encode([]);
    exit;
}

$stmt = $mysql->prepare("SELECT doctor_id, firstname, middlename, lastname FROM doctors WHERE department = ? AND status = 'available' ORDER BY lastname");
$stmt->bind_param("s", $department);
$stmt->execute();
$result = $stmt->get_result();

$doctors = [];
while ($row = $result->fetch_assoc()) {
    $doctors[] = $row;
}

$stmt->close();
header('Content-Type: application/json');
echo json_encode($doctors);
?>