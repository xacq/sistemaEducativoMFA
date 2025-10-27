<?php
require_once '../config.php';
header('Content-Type: application/json; charset=UTF-8');

// Seguridad básica
if (!isset($_GET['estudiante_id']) || !is_numeric($_GET['estudiante_id'])) {
    echo json_encode(['error' => 'ID de estudiante inválido.']);
    exit;
}

$estudiante_id = (int)$_GET['estudiante_id'];

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // ===== PERFIL DEL ESTUDIANTE =====
    $stmt = $mysqli->prepare("
        SELECT 
            e.id,
            e.codigo_estudiante,
            u.nombre,
            u.apellido,
            u.email,
            e.fecha_nacimiento,
            e.genero,
            e.direccion,
            e.tutor_nombre,
            e.tutor_telefono,
            e.estado,
            e.foto_perfil,
            e.observaciones,
            g.nombre AS grado_nombre
        FROM estudiantes e
        JOIN usuarios u ON e.usuario_id = u.id
        JOIN grados g ON e.grado_id = g.id
        WHERE e.id = ?
    ");
    $stmt->bind_param('i', $estudiante_id);
    $stmt->execute();
    $perfil = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$perfil) {
        echo json_encode(['error' => 'Estudiante no encontrado.']);
        exit;
    }

    // ===== CALIFICACIONES =====
    $stmt = $mysqli->prepare("
        SELECT 
            c.nombre AS curso_nombre,
            ev.titulo AS evaluacion_titulo,
            cal.calificacion,
            ev.puntaje_maximo
        FROM calificaciones cal
        JOIN evaluaciones ev ON cal.evaluacion_id = ev.id
        JOIN matriculas m ON cal.matricula_id = m.id
        JOIN cursos c ON m.curso_id = c.id
        WHERE m.estudiante_id = ?
        ORDER BY ev.fecha DESC
        LIMIT 20
    ");
    $stmt->bind_param('i', $estudiante_id);
    $stmt->execute();
    $result_cal = $stmt->get_result();
    $calificaciones = $result_cal->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ===== ASISTENCIA (último mes) =====
    $stmt = $mysqli->prepare("
        SELECT 
            a.fecha,
            a.estado,
            c.nombre AS curso_nombre
        FROM asistencia a
        JOIN matriculas m ON a.matricula_id = m.id
        JOIN cursos c ON m.curso_id = c.id
        WHERE m.estudiante_id = ?
        AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY a.fecha DESC
    ");
    $stmt->bind_param('i', $estudiante_id);
    $stmt->execute();
    $result_asis = $stmt->get_result();
    $asistencia = $result_asis->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'perfil' => $perfil,
        'calificaciones' => $calificaciones,
        'asistencia' => $asistencia
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode(['error' => 'Error al obtener los datos: ' . $e->getMessage()]);
}
