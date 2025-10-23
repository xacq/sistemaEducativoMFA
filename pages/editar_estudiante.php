<?php
require_once '../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$id = (int)$_POST['id'];
$telefono = trim($_POST['telefono']);
$direccion = trim($_POST['direccion']);
$estado = ($_POST['estado'] === 'Inactivo') ? 'Inactivo' : 'Activo';

$stmt = $mysqli->prepare("
    UPDATE estudiantes 
    SET telefono = ?, direccion = ?, estado = ?
    WHERE id = ?
");
$stmt->bind_param('sssi', $telefono, $direccion, $estado, $id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $ok,
    'message' => $ok 
        ? '✅ Cambios guardados correctamente.' 
        : '⚠️ No se pudo actualizar el registro.'
]);
