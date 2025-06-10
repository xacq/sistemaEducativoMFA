<?php
session_start();

// Proteger la página: solo usuarios logueados pueden acceder
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Asegurarse de que el script fue llamado por un método POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Incluir la configuración de la base de datos
    require_once '../config.php';

    // 1. Recoger y sanear los datos del formulario
    $codigo = $_POST['courseCode'];
    $nombre = $_POST['courseName'];
    $grado = $_POST['courseGrade'];
    $seccion = $_POST['courseSection'];
    $profesor_id = (int)$_POST['courseTeacher']; // Convertir a entero
    $materia_id = (int)$_POST['courseSubject']; // Convertir a entero
    $capacidad = (int)$_POST['courseCapacity']; // Convertir a entero
    $creditos = (int)$_POST['courseCredits'];   // Convertir a entero
    $fecha_inicio = $_POST['courseStartDate'];
    $fecha_fin = $_POST['courseEndDate'];
    $estatus = $_POST['courseStatus'];
    // Para campos opcionales, usar el operador de fusión de null
    $descripcion = !empty($_POST['courseDescription']) ? $_POST['courseDescription'] : null;

    // 2. Validaciones adicionales (opcional pero recomendado)
    if (empty($codigo) || empty($nombre) || empty($grado) || empty($seccion) || $profesor_id == 0 || $materia_id == 0) {
        $_SESSION['error_message'] = "Todos los campos obligatorios deben ser completados.";
        // Cambia 'director_cursos.php' por el nombre real de tu archivo
        header('Location: director_cursos.php');
        exit;
    }

    // 3. Preparar y ejecutar la consulta de inserción
    try {
        $stmt = $mysqli->prepare("
            INSERT INTO cursos (
                codigo, nombre, grado, seccion, profesor_id, materia_id,
                capacidad, creditos, descripcion, fecha_inicio, fecha_fin, estatus
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        // "sssiisssssss" es la cadena de tipos:
        // s: string, i: integer
        $stmt->bind_param(
            'sssiisssssss',
            $codigo,
            $nombre,
            $grado,
            $seccion,
            $profesor_id,
            $materia_id,
            $capacidad,
            $creditos,
            $descripcion,
            $fecha_inicio,
            $fecha_fin,
            $estatus
        );

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Curso '$nombre' agregado exitosamente.";
        } else {
            // Error en la ejecución
            throw new Exception($stmt->error);
        }

        $stmt->close();
        
    } catch (Exception $e) {
        // Capturar cualquier error (ej. código de curso duplicado)
        $_SESSION['error_message'] = "Error al guardar el curso. Detalles: " . $e->getMessage();
    }
    
    // Cerrar la conexión
    $mysqli->close();

    // 4. Redirigir de vuelta a la página principal
    header('Location: director_cursos.php'); // Asegúrate de que este nombre de archivo sea correcto
    exit;

} else {
    // Si alguien intenta acceder directamente a este archivo, lo redirigimos
    header('Location: ../director_dashboard.php');
    exit;
}
?>