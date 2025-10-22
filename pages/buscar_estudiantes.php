<?php
require_once '../config.php';
header('Content-Type: application/json; charset=UTF-8');

// Silencia notices para no romper el JSON
error_reporting(E_ERROR | E_PARSE);

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '') {
    echo json_encode([]);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT e.id AS estudiante_id,
           e.codigo_estudiante,
           e.grado_id,
           CONCAT(u.nombre, ' ', u.apellido) AS nombre_completo,
           g.nombre AS grado_nombre
    FROM estudiantes e
    JOIN usuarios u ON e.usuario_id = u.id
    JOIN grados g ON e.grado_id = g.id
    WHERE u.nombre LIKE CONCAT('%', ?, '%')
       OR u.apellido LIKE CONCAT('%', ?, '%')
    ORDER BY u.apellido ASC, u.nombre ASC
    LIMIT 10
");
$stmt->bind_param('ss', $q, $q);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}
echo json_encode($data);
