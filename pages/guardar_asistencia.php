<?php
// guardar_asistencia.php
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo "Acceso no autorizado.";
    exit;
}

require_once '../config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Recuperar datos del formulario
    $curso_id = $_POST['curso_id'];
    $fecha = $_POST['fecha'];
    $asistencias = $_POST['asistencia'];

    if (empty($curso_id) || empty($fecha) || empty($asistencias)) {
        $_SESSION['error_message'] = "Faltan datos para guardar la asistencia.";
        header("Location: profesor_asistencia.php?curso_id=$curso_id&fecha=$fecha");
        exit;
    }

    $mysqli->begin_transaction();

    try {
        // Preparar una sola consulta para insertar o actualizar
        // Usamos ON DUPLICATE KEY UPDATE porque (matricula_id, fecha) es una clave única.
        $stmt = $mysqli->prepare("
            INSERT INTO asistencia (matricula_id, fecha, estado, observaciones) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE estado = VALUES(estado), observaciones = VALUES(observaciones)
        ");

        foreach ($asistencias as $matricula_id => $data) {
            $estado = $data['estado'];
            // $observaciones = !empty($data['observaciones']) ? $data['observaciones'] : null; // <--- ELIMINA ESTA LÍNEA

            $stmt->bind_param('isss', $matricula_id, $fecha, $estado, $observaciones); // <-- TAMBIÉN MODIFICA ESTO
            $stmt->execute();
        }
        
        // Si todo va bien, confirmamos la transacción
        $mysqli->commit();
        $_SESSION['success_message'] = "Asistencia guardada correctamente para la fecha $fecha.";

    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['error_message'] = "Error al guardar la asistencia: " . $e->getMessage();
    }
    
    $stmt->close();
    $mysqli->close();

    // Redirigir de vuelta a la página con los filtros seleccionados
    header("Location: profesor_asistencia.php?curso_id=$curso_id&fecha=$fecha");
    exit;
}
?>