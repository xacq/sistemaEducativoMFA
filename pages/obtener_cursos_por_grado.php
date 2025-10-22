<?php
require_once '../config.php';
header('Content-Type: application/json; charset=UTF-8');

error_reporting(E_ERROR | E_PARSE);

$gradoId = isset($_GET['grado_id']) ? (int)$_GET['grado_id'] : 0;
if ($gradoId <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT id, nombre, codigo
    FROM cursos
    WHERE grado_id = ?
      AND estatus = 'Activo'
    ORDER BY nombre ASC
");
$stmt->bind_param('i', $gradoId);
$stmt->execute();
$res = $stmt->get_result();

$out = [];
while ($row = $res->fetch_assoc()) {
    $out[] = $row;
}
echo json_encode($out);
