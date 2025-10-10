<?php
session_start();
require_once 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectWithError('Debes iniciar sesión desde el formulario.', '');
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    redirectWithError('Email y contraseña son obligatorios.', $email);
}

$stmt = $mysqli->prepare('SELECT id, email, password, role_id, email_verified_at FROM usuarios WHERE email = ? LIMIT 1');
if (!$stmt) {
    redirectWithError('No se pudo iniciar sesión en este momento. Intente nuevamente.', $email);
}

$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    redirectWithError('Credenciales inválidas.', $email);
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user['password'])) {
    redirectWithError('Credenciales inválidas.', $email);
}

if (empty($user['email_verified_at'])) {
    redirectWithError('Tu cuenta está registrada, pero debes activar tu cuenta desde el enlace enviado a tu correo electrónico.', $email);
}

$mfa_code = random_int(100000, 999999);
$mfa_expiry = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

$stmtUpdate = $mysqli->prepare('UPDATE usuarios SET mfa_code = ?, mfa_expiry = ? WHERE id = ?');
if (!$stmtUpdate) {
    redirectWithError('No se pudo generar el código de verificación. Intente nuevamente.', $email);
}


$stmtUpdate->bind_param('ssi', $mfa_code, $mfa_expiry, $user['id']);
$stmtUpdate->execute();
$stmtUpdate->close();

$mail = new PHPMailer(true);
=======

$stmtUpdate->bind_param('ssi', $mfa_code, $mfa_expiry, $user['id']);
$stmtUpdate->execute();
$stmtUpdate->close();


//Este comentario solo activar en produccion
/*$mail = new PHPMailer(true);
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
    $mail->addAddress($user['email']);

    $mail->isHTML(true);
    $mail->Subject = 'Tu código de verificación de dos pasos';
    $mail->Body    = "Hola,<br><br>Tu código de inicio de sesión es: <h1>$mfa_code</h1>Este código expirará en 10 minutos.<br><br>Si no solicitaste este código, puedes ignorar este mensaje.";
    $mail->AltBody = "Tu código de inicio de sesión es: $mfa_code. Expira en 10 minutos.";

    $mail->send();
} catch (Exception $e) {
    redirectWithError('No se pudo enviar el código de verificación. Intente nuevamente más tarde.', $email);
}

$_SESSION['mfa_user_id'] = $user['id'];
$_SESSION['auth_email'] = $email;

header('Location: verify_mfa.php');
exit;*/




=======
// esta seccion es solamente para desarollo local, eliminar en produccion
// ⚠️ Modo desarrollo: omitir envío de correo MFA
$_SESSION['user_id'] = $user['id'];
$_SESSION['role_id'] = $user['role_id'];

// Redirige directamente al dashboard
switch ($user['role_id']) {
    case 1: header('Location: ./pages/director_dashboard.php'); break;
    case 2: header('Location: ./pages/profesor_dashboard.php'); break;
    case 3: header('Location: ./pages/estudiante_dashboard.php'); break;
    default: header('Location: ./index.php'); break;
}
exit;
//fin seccion dev


function redirectWithError(string $message, string $email): void
{
    $_SESSION['auth_error'] = $message;
    if ($email !== '') {
        $_SESSION['auth_email'] = $email;
    }
    header('Location: index.php');
    exit;
}
?>