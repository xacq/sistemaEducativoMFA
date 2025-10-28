<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config.php';

function jexit($ok, $msg, $extra = []) {
  http_response_code($ok ? 200 : 400);
  echo json_encode(array_merge(['success'=>$ok,'message'=>$msg], $extra));
  exit;
}

if (empty($_SESSION['user_id'])) jexit(false, 'Sesión expirada.');

if (!isset($_POST['tarea_entrega_id'], $_POST['calificacion'])) {
  jexit(false, 'Faltan parámetros.');
}

$tarea_entrega_id = (int) $_POST['tarea_entrega_id'];
$calificacion = is_numeric($_POST['calificacion']) ? (float) $_POST['calificacion'] : null;
$comentario = trim($_POST['comentario'] ?? '');

if ($tarea_entrega_id <= 0 || $calificacion === null) {
  jexit(false, 'Datos inválidos.');
}

// Resuelve profesor_id desde el usuario logueado
$profesor_id = 0;
$stmt = $mysqli->prepare("
  SELECT p.id 
  FROM profesores p
  JOIN usuarios u ON u.id = p.usuario_id
  WHERE u.id = ?
");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($profesor_id);
$stmt->fetch();
$stmt->close();

if ($profesor_id <= 0) jexit(false, 'No se encontró el profesor asociado.');

// (opcional) valida que la entrega exista y pertenezca a un curso del profesor
$valida = $mysqli->prepare("
  SELECT 1
  FROM tarea_entregas te
  JOIN tareas t ON t.id = te.tarea_id
  JOIN cursos c ON c.id = t.curso_id
  WHERE te.id = ? AND c.profesor_id = ?
  LIMIT 1
");
$valida->bind_param('ii', $tarea_entrega_id, $profesor_id);
$valida->execute();
$valida->store_result();
if ($valida->num_rows === 0) {
  $valida->close();
  jexit(false, 'No puedes calificar esta entrega.');
}
$valida->close();

// Inserta/actualiza: REQUIERE UNIQUE en tarea_entrega_id (ver más abajo)
$stmt = $mysqli->prepare("
  INSERT INTO calificaciones_tareas (tarea_entrega_id, profesor_id, calificacion, comentario)
  VALUES (?, ?, ?, ?)
  ON DUPLICATE KEY UPDATE
    calificacion = VALUES(calificacion),
    comentario = VALUES(comentario),
    fecha_calificacion = CURRENT_TIMESTAMP
");
$stmt->bind_param('iids', $tarea_entrega_id, $profesor_id, $calificacion, $comentario);

if (!$stmt->execute()) {
  $err = $mysqli->error ?: 'Error al guardar.';
  $stmt->close();
  jexit(false, $err);
}

$stmt->close();
jexit(true, 'Calificación guardada correctamente.');
