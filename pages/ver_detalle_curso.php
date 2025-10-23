<?php
require_once '../config.php';
header('Content-Type: application/json');

$curso_id = isset($_GET['curso_id']) ? (int)$_GET['curso_id'] : 0;
if ($curso_id <= 0) {
    echo json_encode(['error' => 'ID de curso inválido']);
    exit;
}

$stmt = $mysqli->prepare("
    SELECT 
        c.id, c.codigo, c.estatus,
        g.nombre AS grado, 
        m.nombre AS materia,
        CONCAT(u.nombre, ' ', u.apellido) AS profesor,
        (SELECT COUNT(id) FROM matriculas WHERE curso_id = c.id) AS total_estudiantes,
        (SELECT AVG(cal.calificacion) 
         FROM calificaciones cal 
         JOIN evaluaciones ev ON cal.evaluacion_id = ev.id 
         WHERE ev.curso_id = c.id) AS promedio_notas,
        (SELECT AVG(CASE WHEN a.estado IN ('Presente','Tarde') THEN 1 ELSE 0 END) * 100 
         FROM asistencia a 
         JOIN matriculas mat ON a.matricula_id = mat.id 
         WHERE mat.curso_id = c.id) AS promedio_asistencia
    FROM cursos c
    JOIN materias m ON c.materia_id = m.id
    JOIN grados g ON c.grado_id = g.id
    JOIN profesores p ON c.profesor_id = p.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE c.id = ?
");
$stmt->bind_param('i', $curso_id);
$stmt->execute();
$result = $stmt->get_result();
$curso = $result->fetch_assoc();

if (!$curso) {
    echo json_encode(['error' => 'Curso no encontrado']);
    exit;
}

echo json_encode($curso);
$stmt->close();
