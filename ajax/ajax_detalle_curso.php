<?php
require_once __DIR__ . '/../config.php';
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["error" => "ID del curso no proporcionado"]);
    exit;
}

$id = (int)$_GET['id'];

$sql = "
    SELECT c.*, 
           m.nombre AS materia,
           u.nombre AS profesor_nombre, u.apellido AS profesor_apellido
    FROM cursos c
    JOIN profesores p ON c.profesor_id = p.id
    JOIN usuarios u ON p.usuario_id = u.id
    JOIN materias m ON c.materia_id = m.id
    WHERE c.id = ?
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
echo json_encode($result->fetch_assoc());
