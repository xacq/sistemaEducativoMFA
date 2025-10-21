<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';
session_start();

$response = ['success' => false, 'message' => 'Error desconocido'];

try {
    // Validar sesión
    if (empty($_SESSION['user_id'])) {
        throw new Exception('Sesión expirada. Inicie sesión nuevamente.');
    }

    // Validar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido.');
    }

    // Recibir datos
    $id           = (int)($_POST['id'] ?? 0);
    $nombre       = trim($_POST['nombre'] ?? '');
    $creditos     = (int)($_POST['creditos'] ?? 0);
    $capacidad    = (int)($_POST['capacidad'] ?? 0);
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $estatus      = $_POST['estatus'] ?? 'Activo';

    if ($id <= 0 || $nombre === '') {
        throw new Exception('Datos incompletos o inválidos.');
    }

    $stmt = $mysqli->prepare("
        UPDATE cursos 
        SET nombre=?, creditos=?, capacidad=?, descripcion=?, estatus=? 
        WHERE id=?
    ");
    $stmt->bind_param('siissi', $nombre, $creditos, $capacidad, $descripcion, $estatus, $id);

    if (!$stmt->execute()) {
        throw new Exception('Error al actualizar: ' . $stmt->error);
    }

    $response = [
        'success' => true,
        'message' => 'Curso actualizado correctamente.'
    ];
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>
