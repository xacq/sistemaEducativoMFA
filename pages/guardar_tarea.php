<?php
session_start();

// Proteger la página
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    require_once '../config.php';

    // 1. RECOGER DATOS DEL FORMULARIO
    $curso_id = (int)$_POST['assignmentCourse'];
    $titulo = $_POST['assignmentTitle'];
    $tipo = $_POST['assignmentType'];
    $ponderacion = (int)$_POST['assignmentWeight'];
    $fecha_asignacion = $_POST['assignmentStartDate'];
    $fecha_entrega = $_POST['assignmentDueDate'];
    $descripcion = !empty($_POST['assignmentDescription']) ? $_POST['assignmentDescription'] : null;
    $instrucciones = !empty($_POST['assignmentInstructions']) ? $_POST['assignmentInstructions'] : null;
    $recursos = !empty($_POST['assignmentResources']) ? $_POST['assignmentResources'] : null;
    $puntaje_maximo = (int)$_POST['assignmentMaxScore'];
    $tipo_entrega = $_POST['assignmentSubmissionType'];
    $notificar_estudiantes = isset($_POST['notifyStudentsAssignment']) ? 1 : 0;

    // 2. MANEJAR LA SUBIDA DE MÚLTIPLES ARCHIVOS
    $uploaded_files = [];
    $directorio_subidas = '../uploads/assignments/'; // ¡Crea esta carpeta y dale permisos!
    
    if (!is_dir($directorio_subidas)) {
        mkdir($directorio_subidas, 0777, true);
    }
    
    // Comprobar si se subieron archivos
    if (isset($_FILES['assignmentAttachments']) && !empty($_FILES['assignmentAttachments']['name'][0])) {
        $total_files = count($_FILES['assignmentAttachments']['name']);

        for ($i = 0; $i < $total_files; $i++) {
            // Verificar que no haya errores de subida
            if ($_FILES['assignmentAttachments']['error'][$i] === UPLOAD_ERR_OK) {
                $original_filename = basename($_FILES['assignmentAttachments']['name'][$i]);
                $new_filename = uniqid() . '-' . $original_filename;
                $filepath = $directorio_subidas . $new_filename;

                if (move_uploaded_file($_FILES['assignmentAttachments']['tmp_name'][$i], $filepath)) {
                    $uploaded_files[] = [
                        'filename' => $original_filename,
                        'filepath' => $filepath // Guardamos la ruta relativa
                    ];
                } else {
                    $_SESSION['error_message'] = "Error al mover el archivo: " . htmlspecialchars($original_filename);
                    header('Location: profesor_tareas.php'); // Redirigir si falla la subida
                    exit;
                }
            }
        }
    }

    // 3. INSERTAR EN LA BASE DE DATOS USANDO UNA TRANSACCIÓN
    $mysqli->begin_transaction();

    try {
        // Paso A: Insertar la tarea en la tabla `tareas`
        $stmt_tarea = $mysqli->prepare("
            INSERT INTO tareas (
                curso_id, titulo, descripcion, instrucciones, recursos, 
                puntaje_maximo, tipo_entrega, notificar_estudiantes, tipo, 
                ponderacion, fecha_asignacion, fecha_entrega
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_tarea->bind_param(
            'issssisissis', // i: integer, s: string
            $curso_id, $titulo, $descripcion, $instrucciones, $recursos,
            $puntaje_maximo, $tipo_entrega, $notificar_estudiantes, $tipo,
            $ponderacion, $fecha_asignacion, $fecha_entrega
        );
        $stmt_tarea->execute();

        // Obtener el ID de la tarea recién creada
        $nueva_tarea_id = $mysqli->insert_id;
        if (!$nueva_tarea_id) {
            throw new Exception("No se pudo crear el registro de la tarea.");
        }
        $stmt_tarea->close();

        // Paso B: Si hay archivos subidos, insertarlos en `tarea_adjuntos`
        if (!empty($uploaded_files)) {
            $stmt_adjuntos = $mysqli->prepare("
                INSERT INTO tarea_adjuntos (tarea_id, filename, filepath) VALUES (?, ?, ?)
            ");
            foreach ($uploaded_files as $file) {
                $stmt_adjuntos->bind_param('iss', $nueva_tarea_id, $file['filename'], $file['filepath']);
                $stmt_adjuntos->execute();
            }
            $stmt_adjuntos->close();
        }

        // Si todo ha ido bien, confirmar los cambios
        $mysqli->commit();
        $_SESSION['success_message'] = "Tarea '$titulo' creada exitosamente.";

    } catch (Exception $e) {
        // Si algo falla, revertir todos los cambios
        $mysqli->rollback();
        // Para depuración: $e->getMessage()
        $_SESSION['error_message'] = "Error al crear la tarea: " . $e->getMessage();
    }

    // Cerrar la conexión
    $mysqli->close();

    // 4. REDIRIGIR DE VUELTA
    header('Location: profesor_tareas.php'); // Cambia este nombre si tu archivo se llama diferente
    exit;

} else {
    // Redirigir si no es POST
    header('Location: ../profesor_dashboard.php');
    exit;
}
?>