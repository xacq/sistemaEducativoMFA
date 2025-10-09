<?php
session_start();

require_once 'config.php';

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['auth_error'] = 'Completa el formulario de registro para crear una cuenta.';
    header('Location: index.php');
    exit;
}

$nombre   = trim($_POST['nombre'] ?? '');
$apellido = trim($_POST['apellido'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role_id  = isset($_POST['role_id']) ? (int) $_POST['role_id'] : 0;

$errors = [];
if ($nombre === '' || $apellido === '' || $email === '' || $password === '' || $role_id === 0) {
    $errors[] = 'Todos los campos son obligatorios.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email no válido.';
}

if (!in_array($role_id, [1, 2, 3], true)) {
    $errors[] = 'Rol seleccionado no es válido.';
}

$stmt_check = $mysqli->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
if ($stmt_check) {
    $stmt_check->bind_param('s', $email);
    $stmt_check->execute();
    $stmt_check->store_result();
    if ($stmt_check->num_rows > 0) {
        $errors[] = 'Este correo electrónico ya está registrado.';
    }
    $stmt_check->close();
} else {
    $errors[] = 'No fue posible validar el correo electrónico en este momento.';
}

if ($errors) {
    $_SESSION['auth_error'] = implode(' ', $errors);
    $_SESSION['auth_email'] = $email;
    header('Location: index.php');
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));

try {
    $stmt = $mysqli->prepare('INSERT INTO usuarios (nombre, apellido, email, password, role_id, email_verification_token) VALUES (?, ?, ?, ?, ?, ?)');
    if (!$stmt) {
        throw new Exception('No se pudo completar el registro en este momento.');
    }

    $stmt->bind_param('ssssis', $nombre, $apellido, $email, $hash, $role_id, $token);
    $stmt->execute();
    $stmt->close();

    $verification_link = BASE_URL . '/verify_email.php?token=' . $token;

    $mail = new PHPMailer(true);
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
    $mail->Body    = "<h2>¡Hola $nombre! Gracias por registrarte.</h2><p>Para activar tu cuenta haz clic en el siguiente enlace:</p><p><a href='$verification_link'>Verificar mi cuenta</a></p><p>Si no solicitaste el registro, ignora este mensaje.</p>";
    $mail->AltBody = "Hola $nombre, para activar tu cuenta, copia y pega este enlace en tu navegador: $verification_link";

    $mail->send();

    $_SESSION['auth_success'] = 'Registro exitoso. Revisa tu correo electrónico para activar tu cuenta.';
    header('Location: index.php');
    exit;
} catch (Exception $e) {
    $_SESSION['auth_error'] = 'No se pudo completar el registro: ' . $e->getMessage();
    $_SESSION['auth_email'] = $email;
    header('Location: index.php');
    exit;
}

?>