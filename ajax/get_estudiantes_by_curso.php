<?php
session_start();
header('Content-Type: application/json');

// Solo director (1) y profesor (2)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role_id'], [1, 2])) {
    echo json_encode(['status' => 'error', 'message' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/../config.php';

$curso_id = intval($_POST['curso_id'] ?? 0);

if ($curso_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Curso inválido.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT e.id, u.nombre
    FROM matriculas m
    INNER JOIN estudiantes e ON m.estudiante_id = e.id
    INNER JOIN usuarios u ON e.usuario_id = u.id
    WHERE m.curso_id = :curso_id
");
$stmt->execute([':curso_id' => $curso_id]);

echo json_encode([
    'status' => 'ok',
    'estudiantes' => $stmt->fetchAll(PDO::FETCH_ASSOC)
]);
