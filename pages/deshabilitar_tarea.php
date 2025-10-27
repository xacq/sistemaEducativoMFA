<?php
require_once '../config.php';
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$tarea_id = intval($_POST['id'] ?? 0);
if ($tarea_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

// En lugar de eliminar, marcamos como deshabilitada
$stmt = $mysqli->prepare("UPDATE tareas SET tipo = CONCAT(tipo, ' (DESHABILITADA)') WHERE id = ?");
$stmt->bind_param('i', $tarea_id);
$ok = $stmt->execute();

header('Content-Type: application/json');
echo json_encode([
    'success' => $ok,
    'message' => $ok ? '✅ La tarea fue deshabilitada correctamente.' : '❌ Error al deshabilitar la tarea.'
]);
