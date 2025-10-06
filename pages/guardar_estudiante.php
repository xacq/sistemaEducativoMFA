<?php
session_start();

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./director_dashboard.php');
    exit;
}

require_once '../config.php';
require_once '../vendor/autoload.php';

$nombre = trim($_POST['studentName'] ?? '');
$apellido = trim($_POST['studentLastName'] ?? '');
$email = trim($_POST['studentEmail'] ?? '');
$codigo_estudiante = trim($_POST['studentID'] ?? '');
$password_default = $codigo_estudiante;
$fecha_nacimiento = $_POST['studentBirthdate'] ?? '';
$genero = $_POST['studentGender'] ?? '';
$grado_id = isset($_POST['grado_id']) ? (int) $_POST['grado_id'] : 0;
$seccion = trim($_POST['studentSection'] ?? '');
$telefono = trim($_POST['studentPhone'] ?? '');
$direccion = trim($_POST['studentAddress'] ?? '');
$tutor_nombre = trim($_POST['parentName'] ?? '');
$tutor_telefono = trim($_POST['parentPhone'] ?? '');
$fecha_inscripcion = $_POST['enrollmentDate'] ?? date('Y-m-d');
$estado = $_POST['studentStatus'] ?? 'Activo';
$observaciones = trim($_POST['studentNotes'] ?? '');

if ($nombre === '' || $apellido === '' || $email === '' || $codigo_estudiante === '' || $fecha_nacimiento === '' || $genero === '' || $grado_id === 0 || $seccion === '' || $direccion === '' || $tutor_nombre === '' || $tutor_telefono === '') {
    $_SESSION['error_message'] = 'Todos los campos obligatorios deben completarse para registrar al estudiante.';
    header('Location: director_estudiantes.php');
    exit;
}

$password_hashed = password_hash($password_default, PASSWORD_DEFAULT);
$telefono = $telefono !== '' ? $telefono : null;
$observaciones = $observaciones !== '' ? $observaciones : null;
$ruta_foto_perfil = null;

if (isset($_FILES['studentPhoto']) && $_FILES['studentPhoto']['error'] === UPLOAD_ERR_OK) {
    $directorio_subidas = '../uploads/profiles/';
    if (!is_dir($directorio_subidas) && !mkdir($directorio_subidas, 0777, true) && !is_dir($directorio_subidas)) {
        $_SESSION['error_message'] = 'No se pudo preparar el directorio para guardar la fotografía.';
        header('Location: director_estudiantes.php');
        exit;
    }

    $nombre_archivo = uniqid('', true) . '-' . basename($_FILES['studentPhoto']['name']);
    $ruta_completa = $directorio_subidas . $nombre_archivo;

    if (move_uploaded_file($_FILES['studentPhoto']['tmp_name'], $ruta_completa)) {
        $ruta_foto_perfil = $ruta_completa;
    } else {
        $_SESSION['error_message'] = 'Error al subir la fotografía.';
        header('Location: director_estudiantes.php');
        exit;
    }
}

$mysqli->begin_transaction();
$warnings = [];

try {
    $verificationToken = bin2hex(random_bytes(32));
    $roleId = 3;

    $stmt_user = $mysqli->prepare('INSERT INTO usuarios (nombre, apellido, email, password, role_id, email_verification_token) VALUES (?, ?, ?, ?, ?, ?)');
    if (!$stmt_user) {
        throw new Exception('No se pudo preparar el guardado del usuario.');
    }

    $stmt_user->bind_param('ssssis', $nombre, $apellido, $email, $password_hashed, $roleId, $verificationToken);
    $stmt_user->execute();
    $stmt_user->close();

    $nuevo_usuario_id = $mysqli->insert_id;
    if ($nuevo_usuario_id === 0) {
        throw new Exception('No se pudo obtener el identificador del usuario.');
    }

    $stmt_student = $mysqli->prepare('INSERT INTO estudiantes (usuario_id, codigo_estudiante, fecha_nacimiento, genero, grado_id, seccion, telefono, direccion, tutor_nombre, tutor_telefono, fecha_inscripcion, estado, foto_perfil, observaciones) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    if (!$stmt_student) {
        throw new Exception('No se pudo preparar el guardado del estudiante.');
    }

    $stmt_student->bind_param(
        'isssisssssssss',
        $nuevo_usuario_id,
        $codigo_estudiante,
        $fecha_nacimiento,
        $genero,
        $grado_id,
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

    $estudiante_id = $mysqli->insert_id;

    $curso_id = null;
    $stmtCurso = $mysqli->prepare('SELECT id FROM cursos WHERE grado_id = ? AND seccion = ? AND estatus = "Activo" ORDER BY fecha_inicio IS NULL, fecha_inicio ASC, id ASC LIMIT 1');
    if ($stmtCurso) {
        $stmtCurso->bind_param('is', $grado_id, $seccion);
        $stmtCurso->execute();
        $resultadoCurso = $stmtCurso->get_result();
        if ($filaCurso = $resultadoCurso->fetch_assoc()) {
            $curso_id = (int) $filaCurso['id'];
        }
        $stmtCurso->close();
    }

    if ($curso_id !== null) {
        $stmtMatricula = $mysqli->prepare('INSERT INTO matriculas (estudiante_id, curso_id, fecha_matricula) VALUES (?, ?, ?)');
        if ($stmtMatricula) {
            $stmtMatricula->bind_param('iis', $estudiante_id, $curso_id, $fecha_inscripcion);
            $stmtMatricula->execute();
            $stmtMatricula->close();
        } else {
            throw new Exception('No se pudo preparar la matrícula inicial.');
        }
    } else {
        $warnings[] = 'Estudiante registrado, pero no se encontró un curso activo para el grado y sección especificados.';
    }

    $mysqli->commit();

    $verificationLink = BASE_URL . '/verify_email.php?token=' . $verificationToken;
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tu_correo@gmail.com';
        $mail->Password   = 'tu_contraseña_de_aplicacion';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('no-reply@sistemaregistro.com', 'Sistema Académico');
        $mail->addAddress($email, $nombre . ' ' . $apellido);

        $mail->isHTML(true);
        $mail->Subject = 'Confirma tu registro en Sistema Académico';
        $mail->Body    = "<h2>¡Hola $nombre!</h2><p>Se creó tu cuenta en el Sistema Académico. Para activarla, haz clic en el siguiente enlace:</p><p><a href='$verificationLink'>Verificar mi cuenta</a></p><p>Si no solicitaste este registro, ignora este mensaje.</p>";
        $mail->AltBody = "Hola $nombre, activa tu cuenta ingresando al siguiente enlace: $verificationLink";

        $mail->send();
        $_SESSION['success_message'] = "Estudiante '$nombre $apellido' agregado exitosamente. Se envió un correo de verificación.";
    } catch (Exception $mailException) {
        $_SESSION['success_message'] = "Estudiante '$nombre $apellido' agregado exitosamente, pero no se pudo enviar el correo de verificación.";
        $warnings[] = 'Error al enviar el correo: ' . $mailException->getMessage();
    }
} catch (Exception $e) {
    $mysqli->rollback();
    $_SESSION['error_message'] = 'Error al agregar el estudiante. Detalles: ' . $e->getMessage();
}

$mysqli->close();

if (!empty($warnings)) {
    $_SESSION['warning_message'] = implode(' ', $warnings);
}

header('Location: director_estudiantes.php');
exit;
?>