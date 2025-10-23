<?php
require_once '../config.php';
header('Content-Type: application/json; charset=UTF-8');

$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
if ($curso_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de curso inválido.']);
    exit;
}

$sql = "
    SELECT
        e.id AS estudiante_id,
        CONCAT(u.nombre, ' ', u.apellido) AS estudiante,
        CONCAT(ev.titulo, ' (', ev.tipo_evaluacion, ' - ', ev.periodo, ')') AS evaluacion,
        cal.calificacion,
        cal.fecha,
        cal.comentario,
        c.id AS curso_id,
        m.nombre AS materia
    FROM calificaciones cal
    JOIN matriculas mat ON cal.matricula_id = mat.id
    JOIN estudiantes e ON mat.estudiante_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    JOIN evaluaciones ev ON cal.evaluacion_id = ev.id
    JOIN cursos c ON ev.curso_id = c.id
    JOIN materias m ON c.materia_id = m.id
    WHERE c.id = ?
    ORDER BY u.apellido, u.nombre, ev.fecha
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $curso_id);
$stmt->execute();
$res = $stmt->get_result();

$calificaciones = [];
while ($row = $res->fetch_assoc()) {
    $calificaciones[] = $row;
}
$stmt->close();

if (empty($calificaciones)) {
    echo json_encode(['success' => false, 'message' => 'No hay calificaciones registradas para este curso.']);
    exit;
}

echo json_encode(['success' => true, 'calificaciones' => $calificaciones]);
