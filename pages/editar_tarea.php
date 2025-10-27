<?php
require_once '../config.php';
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $instrucciones = trim($_POST['instrucciones']);
    $recursos = trim($_POST['recursos']);
    $fecha_entrega = $_POST['fecha_entrega'];
    $ponderacion = intval($_POST['ponderacion']);
    $puntaje_maximo = intval($_POST['puntaje_maximo']);

    $stmt = $mysqli->prepare("
        UPDATE tareas 
        SET titulo=?, descripcion=?, instrucciones=?, recursos=?, 
            fecha_entrega=?, ponderacion=?, puntaje_maximo=? 
        WHERE id=?
    ");
    $stmt->bind_param('ssssiiii', $titulo, $descripcion, $instrucciones, $recursos, $fecha_entrega, $ponderacion, $puntaje_maximo, $id);
    $ok = $stmt->execute();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => $ok,
        'message' => $ok ? '✅ La tarea fue actualizada correctamente.' : '❌ Error al actualizar la tarea.'
    ]);
}
