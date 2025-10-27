<?php
session_start();
require_once '../config.php';

if (empty($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Sesión expirada.';
    header('Location: profesor_asistencia.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $asistencias = $_POST['asistencia'] ?? [];

    foreach ($asistencias as $matricula_id => $datos) {
        $estado = $datos['estado'] ?? 'Presente';
        $obs = $datos['observaciones'] ?? null;

        // REEMPLAZAR o INSERTAR si ya existe (gracias al UNIQUE KEY matricula_id + fecha)
        $stmt = $mysqli->prepare("
            INSERT INTO asistencia (matricula_id, fecha, estado, observaciones)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE estado = VALUES(estado), observaciones = VALUES(observaciones)
        ");
        $stmt->bind_param('isss', $matricula_id, $fecha, $estado, $obs);
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION['success_message'] = 'Asistencia guardada correctamente.';
    header("Location: profesor_asistencia.php?curso_id=$curso_id&fecha=$fecha");
    exit;
}
