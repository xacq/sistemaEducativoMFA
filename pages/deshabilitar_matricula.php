<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Identificador de matrícula no válido.']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE matriculas SET estatus = 'Inactiva' WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('i', $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'La matrícula fue deshabilitada correctamente.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el estado de la matrícula.']);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta.']);
}
