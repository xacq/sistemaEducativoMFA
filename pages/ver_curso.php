<?php
require_once __DIR__ . '/../config.php';
session_start();

// Validar sesión
if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$curso_id = intval($_GET['id'] ?? 0);

if ($curso_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de curso inválido']);
    exit;
}

// Consultar información del curso
$sql = "
    SELECT 
        c.id, c.codigo, c.nombre, c.descripcion, c.fecha_inicio, c.fecha_fin, c.capacidad,
        c.creditos, c.estatus, c.seccion,
        g.nombre AS nombre_grado,
        m.nombre AS nombre_materia,
        CONCAT(u.nombre, ' ', u.apellido) AS nombre_profesor
    FROM cursos c
    JOIN grados g ON c.grado_id = g.id
    JOIN materias m ON c.materia_id = m.id
    JOIN profesores p ON c.profesor_id = p.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE c.id = ?
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $curso_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Curso no encontrado']);
    exit;
}

$curso = $result->fetch_assoc();
$stmt->close();

echo json_encode(['success' => true, 'curso' => $curso]);
exit;
?>
