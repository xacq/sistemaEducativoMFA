<?php
require_once '../config.php';

if (empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de profesor requerido']);
    exit;
}

$id = (int)$_GET['id'];
$stmt = $mysqli->prepare("
    SELECT p.*, u.nombre, u.apellido, u.email
    FROM profesores p
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE p.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$result) {
    http_response_code(404);
    echo json_encode(['error' => 'Profesor no encontrado']);
    exit;
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
