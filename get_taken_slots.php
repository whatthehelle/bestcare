<?php
require_once 'database.php';

$doctor_id = intval($_GET['doctor_id'] ?? 0);
$apt_date  = trim($_GET['apt_date'] ?? '');

if (!$doctor_id || !$apt_date) {
    echo json_encode([]);
    exit;
}

$stmt = $mysql->prepare("SELECT apt_time FROM appointments WHERE doctor_id = ? AND apt_date = ? AND status = 'Accepted'");
$stmt->bind_param("is", $doctor_id, $apt_date);
$stmt->execute();
$result = $stmt->get_result();

$taken = [];
while ($row = $result->fetch_assoc()) {
    $taken[] = $row['apt_time'];
}

$stmt->close();
header('Content-Type: application/json');
echo json_encode($taken);
?>