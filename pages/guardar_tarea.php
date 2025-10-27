<?php
session_start();
require_once '../config.php';

// Verificar sesión
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$profesor_user_id = $_SESSION['user_id'];

// Validar que el formulario fue enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar y recoger los valores del formulario
    $titulo = trim($_POST['assignmentTitle']);
    $curso_id = intval($_POST['assignmentCourse']);
    $tipo = trim($_POST['assignmentType']);
    $ponderacion = intval($_POST['assignmentWeight']);
    $fecha_asignacion = $_POST['assignmentStartDate'];
    $fecha_entrega = $_POST['assignmentDueDate'];
    $descripcion = trim($_POST['assignmentDescription']);
    $instrucciones = trim($_POST['assignmentInstructions']);
    $recursos = trim($_POST['assignmentResources']);
    $puntaje_maximo = intval($_POST['assignmentMaxScore']);
    $tipo_entrega = trim($_POST['assignmentSubmissionType']);
    $notificar_estudiantes = isset($_POST['notifyStudentsAssignment']) ? 1 : 0;

    // Validaciones básicas
    if (empty($titulo) || empty($curso_id) || empty($tipo) || empty($fecha_asignacion) || empty($fecha_entrega)) {
        $_SESSION['error_message'] = 'Por favor, completa todos los campos obligatorios.';
        header('Location: profesor_tareas.php');
        exit;
    }

    // Insertar la tarea
    $stmt = $mysqli->prepare("
        INSERT INTO tareas (
            curso_id, titulo, descripcion, instrucciones, recursos, puntaje_maximo, 
            tipo_entrega, notificar_estudiantes, tipo, ponderacion, fecha_asignacion, fecha_entrega
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        'issssisiisss',
        $curso_id,
        $titulo,
        $descripcion,
        $instrucciones,
        $recursos,
        $puntaje_maximo,
        $tipo_entrega,
        $notificar_estudiantes,
        $tipo,
        $ponderacion,
        $fecha_asignacion,
        $fecha_entrega
    );

    if ($stmt->execute()) {
        $tarea_id = $stmt->insert_id;
        $stmt->close();

        // 2️⃣ Si hay archivos adjuntos, los guardamos
        if (!empty($_FILES['assignmentAttachments']['name'][0])) {
            $upload_dir = "../uploads/tareas/$tarea_id/";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['assignmentAttachments']['tmp_name'] as $key => $tmp_name) {
                $file_name = basename($_FILES['assignmentAttachments']['name'][$key]);
                $target_file = $upload_dir . $file_name;
                move_uploaded_file($tmp_name, $target_file);
            }
        }

        $_SESSION['success_message'] = '✅ La tarea fue creada correctamente.';
    } else {
        $_SESSION['error_message'] = '❌ Error al guardar la tarea: ' . $stmt->error;
        $stmt->close();
    }
} else {
    $_SESSION['error_message'] = 'Método no permitido.';
}

// Redirigir de nuevo al listado
header('Location: profesor_tareas.php');
exit;
