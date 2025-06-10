<?php
session_start();

// Si no hay sesión activa, no debería poder guardar.
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Verificar que se está accediendo al script a través de un POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Incluir la conexión a la base de datos
    require_once '../config.php';

    // -------------------------------------------------------------
    // 1. RECOGER DATOS DEL FORMULARIO
    // -------------------------------------------------------------
    
    // Datos para la tabla 'usuarios'
    $nombre = $_POST['studentName'];
    $apellido = $_POST['studentLastName'];
    $email = $_POST['studentEmail'];
    // Contraseña por defecto: el código del estudiante. Se debe cambiar en el primer login.
    // Es CRUCIAL hashear la contraseña.
    $password_default = $_POST['studentID']; 
    $password_hashed = password_hash($password_default, PASSWORD_DEFAULT);
    $rol = 'estudiante'; // Asignar el rol de estudiante

    // Datos para la tabla 'estudiantes'
    $codigo_estudiante = $_POST['studentID'];
    $fecha_nacimiento = $_POST['studentBirthdate'];
    $genero = $_POST['studentGender'];
    $grado = $_POST['studentGrade'];
    $seccion = $_POST['studentSection'];
    $telefono = $_POST['studentPhone'] ?? null; // Usar null si está vacío
    $direccion = $_POST['studentAddress'];
    $tutor_nombre = $_POST['parentName'];
    $tutor_telefono = $_POST['parentPhone'];
    $fecha_inscripcion = $_POST['enrollmentDate'];
    $estado = $_POST['studentStatus'];
    $observaciones = $_POST['studentNotes'] ?? null; // Usar null si está vacío

    // -------------------------------------------------------------
    // 2. MANEJAR LA SUBIDA DE LA FOTO (si existe)
    // -------------------------------------------------------------
    $ruta_foto_perfil = null;
    if (isset($_FILES['studentPhoto']) && $_FILES['studentPhoto']['error'] == UPLOAD_ERR_OK) {
        // Define el directorio de subidas. ¡Asegúrate de que este directorio exista y tenga permisos de escritura!
        $directorio_subidas = '../uploads/profiles/';
        if (!is_dir($directorio_subidas)) {
            mkdir($directorio_subidas, 0777, true);
        }

        $nombre_archivo = uniqid() . '-' . basename($_FILES['studentPhoto']['name']);
        $ruta_completa = $directorio_subidas . $nombre_archivo;

        // Mover el archivo al directorio de destino
        if (move_uploaded_file($_FILES['studentPhoto']['tmp_name'], $ruta_completa)) {
            $ruta_foto_perfil = $ruta_completa;
        } else {
            // Manejar error de subida
            $_SESSION['error_message'] = "Error al subir la fotografía.";
            // El script continuará e insertará al estudiante sin foto. O puedes detenerlo:
            // header('Location: director_estudiantes.php'); exit;
        }
    }

    // -------------------------------------------------------------
    // 3. EJECUTAR LA TRANSACCIÓN EN LA BASE DE DATOS
    // -------------------------------------------------------------
    
    // Iniciar transacción
    $mysqli->begin_transaction();

    try {
        // Paso A: Insertar en la tabla 'usuarios'
        $stmt_user = $mysqli->prepare("
            INSERT INTO usuarios (nombre, apellido, email, password, rol) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt_user->bind_param('sssss', $nombre, $apellido, $email, $password_hashed, $rol);
        $stmt_user->execute();

        // Obtener el ID del usuario recién creado
        $nuevo_usuario_id = $mysqli->insert_id;
        $stmt_user->close();

        // Si no se pudo crear el usuario, lanzar una excepción
        if ($nuevo_usuario_id == 0) {
            throw new Exception("No se pudo crear el registro de usuario.");
        }

        // Paso B: Insertar en la tabla 'estudiantes'
        $stmt_student = $mysqli->prepare("
            INSERT INTO estudiantes (
                usuario_id, codigo_estudiante, fecha_nacimiento, genero, grado, 
                seccion, telefono, direccion, tutor_nombre, tutor_telefono, 
                fecha_inscripcion, estado, foto_perfil, observaciones
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_student->bind_param(
            'isssssssssssss',
            $nuevo_usuario_id,
            $codigo_estudiante,
            $fecha_nacimiento,
            $genero,
            $grado,
            $seccion,
            $telefono,
            $direccion,
            $tutor_nombre,
            $tutor_telefono,
            $fecha_inscripcion,
            $estado,
            $ruta_foto_perfil,
            $observaciones
        );
        $stmt_student->execute();
        $stmt_student->close();
        
        // Si todo fue bien, confirmar los cambios
        $mysqli->commit();

        // Crear un mensaje de éxito para mostrar en la página anterior
        $_SESSION['success_message'] = "Estudiante '$nombre $apellido' agregado exitosamente.";

    } catch (Exception $e) {
        // Si algo falló, revertir todos los cambios
        $mysqli->rollback();
        
        // Crear un mensaje de error
        // Para depuración: $e->getMessage()
        $_SESSION['error_message'] = "Error al agregar el estudiante. Por favor, intente de nuevo. Detalles: " . $e->getMessage();
    }

    // Cerrar la conexión
    $mysqli->close();

    // -------------------------------------------------------------
    // 4. REDIRIGIR DE VUELTA A LA PÁGINA DE GESTIÓN
    // -------------------------------------------------------------
    // Usamos el nombre del archivo original. Asegúrate de que sea correcto.
    header('Location: director_estudiantes.php'); 
    exit;

} else {
    // Si no es un POST, redirigir al inicio.
    header('Location: ./director_dashboard.php');
    exit;
}
?>