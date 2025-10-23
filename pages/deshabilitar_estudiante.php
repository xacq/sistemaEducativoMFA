<?php
require_once '../config.php';
header('Content-Type: application/json');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE estudiantes SET estado = 'Inactivo' WHERE id = ?");
$stmt->bind_param('i', $id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $ok,
    'message' => $ok 
        ? '✅ Estudiante deshabilitado correctamente.'
        : '⚠️ Ocurrió un error al deshabilitar.'
]);
