<?php
require_once __DIR__ . '/../config.php';
session_start();

if (empty($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE cursos SET estatus = 'Inactivo' WHERE id = ?");
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Curso deshabilitado correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al deshabilitar: ' . $stmt->error]);
}
$stmt->close();
exit;
?>
