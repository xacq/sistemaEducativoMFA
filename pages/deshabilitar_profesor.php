<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = (int)$_POST['id'];
$stmt = $mysqli->prepare("UPDATE profesores SET estatus = 'Inactivo' WHERE id = ?");
$stmt->bind_param('i', $id);
$stmt->execute();

echo json_encode(['success' => true, 'message' => 'Profesor deshabilitado correctamente.']);
$stmt->close();
