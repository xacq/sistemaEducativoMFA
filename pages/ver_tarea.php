<?php
require_once '../config.php';
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$tarea_id = intval($_GET['id'] ?? 0);
if ($tarea_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT 
        t.id, t.titulo, t.descripcion, t.instrucciones, t.recursos, t.puntaje_maximo,
        t.tipo, t.ponderacion, t.fecha_asignacion, t.fecha_entrega, 
        t.tipo_entrega, t.notificar_estudiantes,
        c.nombre AS curso_nombre, g.nombre AS grado_nombre, c.seccion
    FROM tareas t
    JOIN cursos c ON t.curso_id = c.id
    JOIN grados g ON c.grado_id = g.id
    WHERE t.id = ?
");
$stmt->bind_param('i', $tarea_id);
$stmt->execute();
$result = $stmt->get_result();
$tarea = $result->fetch_assoc();

header('Content-Type: application/json');
echo json_encode($tarea);
