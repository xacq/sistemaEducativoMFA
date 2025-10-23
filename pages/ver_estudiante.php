<?php
require_once '../config.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['error' => 'ID no válido']);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT e.id, e.codigo_estudiante, e.estado, e.fecha_nacimiento, e.telefono, e.direccion,
           u.nombre, u.apellido, u.email, g.nombre AS grado_nombre
    FROM estudiantes e
    JOIN usuarios u ON e.usuario_id = u.id
    JOIN grados g ON e.grado_id = g.id
    WHERE e.id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Estudiante no encontrado']);
}
$stmt->close();
