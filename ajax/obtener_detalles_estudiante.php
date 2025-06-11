<?php
// profesor/ajax/obtener_detalles_estudiante.php

session_start();
header('Content-Type: application/json');

// Validaciones de seguridad
if (empty($_SESSION['user_id']) || empty($_GET['estudiante_id'])) {
    echo json_encode(['error' => 'Acceso no autorizado o ID de estudiante no proporcionado.']);
    exit;
}

require_once '../../config.php';

$estudiante_id = (int)$_GET['estudiante_id'];
$response = [];

// 1. Obtener datos del perfil del estudiante
$stmt_profile = $mysqli->prepare("
    SELECT 
        u.nombre, u.apellido, u.email,
        e.codigo_estudiante, e.fecha_nacimiento, e.genero, e.direccion, e.telefono,
        e.tutor_nombre, e.tutor_telefono, e.fecha_inscripcion, e.foto_perfil, e.observaciones,
        g.nombre as grado_nombre
    FROM estudiantes e
    JOIN usuarios u ON e.usuario_id = u.id
    JOIN grados g ON e.grado_id = g.id
    WHERE e.id = ?
");
$stmt_profile->bind_param('i', $estudiante_id);
$stmt_profile->execute();
$result_profile = $stmt_profile->get_result();
$response['perfil'] = $result_profile->fetch_assoc();
$stmt_profile->close();

// 2. Obtener calificaciones
$response['calificaciones'] = [];
$stmt_grades = $mysqli->prepare("
    SELECT 
        c.nombre as curso_nombre,
        ev.titulo as evaluacion_titulo,
        cal.calificacion,
        ev.puntaje_maximo
    FROM calificaciones cal
    JOIN matriculas m ON cal.matricula_id = m.id
    JOIN evaluaciones ev ON cal.evaluacion_id = ev.id
    JOIN cursos c ON m.curso_id = c.id
    WHERE m.estudiante_id = ?
    ORDER BY c.nombre, ev.fecha
");
$stmt_grades->bind_param('i', $estudiante_id);
$stmt_grades->execute();
$result_grades = $stmt_grades->get_result();
while ($row = $result_grades->fetch_assoc()) {
    $response['calificaciones'][] = $row;
}
$stmt_grades->close();

// 3. Obtener asistencia
$response['asistencia'] = [];
$stmt_attendance = $mysqli->prepare("
    SELECT a.fecha, a.estado, c.nombre as curso_nombre
    FROM asistencia a
    JOIN matriculas m ON a.matricula_id = m.id
    JOIN cursos c ON m.curso_id = c.id
    WHERE m.estudiante_id = ? AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    ORDER BY a.fecha DESC
");
$stmt_attendance->bind_param('i', $estudiante_id);
$stmt_attendance->execute();
$result_attendance = $stmt_attendance->get_result();
while ($row = $result_attendance->fetch_assoc()) {
    $response['asistencia'][] = $row;
}
$stmt_attendance->close();

// Devolver la respuesta como JSON
echo json_encode($response);
$mysqli->close();
?>