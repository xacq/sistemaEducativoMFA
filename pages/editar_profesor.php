<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = (int)$_POST['id'];
$departamento = trim($_POST['departamento']);
$cargo = trim($_POST['cargo']);
$telefono = trim($_POST['telefono']);
$direccion = trim($_POST['direccion']);
$formacion = trim($_POST['formacion_academica']);
$estatus = ($_POST['estatus'] === 'Inactivo') ? 'Inactivo' : 'Activo';

$stmt = $mysqli->prepare("
    UPDATE profesores 
    SET departamento = ?, cargo = ?, telefono = ?, direccion = ?, formacion_academica = ?, estatus = ?
    WHERE id = ?
");
$stmt->bind_param('ssssssi', $departamento, $cargo, $telefono, $direccion, $formacion, $estatus, $id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => '✅ Cambios guardados correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => '⚠️ No se pudieron guardar los cambios.']);
}
$stmt->close();
