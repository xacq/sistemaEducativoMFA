<?php
session_start();
require_once 'config.php';

// CAMBIO MFA: Incluir la librería PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Si usas Composer
//require './vendor/phpmailer/phpmailer/src/Exception.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = $_POST['password'] ?? '';


if (!$email || !$password) {
    bad('Email y contraseña son obligatorios.');
}

// 1) Buscar usuario por email
$stmt = $mysqli->prepare("SELECT id, email, password, role_id FROM usuarios WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    bad('Credenciales inválidas.');
}

$user = $result->fetch_assoc();

// 2) Verificar contraseña
if (!password_verify($password, $user['password'])) {
    bad('Credenciales inválidas.');
}

// --- AÑADE ESTA CORRECCIÓN AQUÍ ---
// 2.1) ¡NUEVO! Verificar si el email ha sido activado
$stmt_verified = $mysqli->prepare("SELECT email_verified_at FROM usuarios WHERE id = ?");
$stmt_verified->bind_param('i', $user['id']);
$stmt_verified->execute();
$result_verified = $stmt_verified->get_result()->fetch_assoc();

if ($result_verified['email_verified_at'] === null) {
    bad('Tu cuenta está registrada, pero debes activar tu cuenta haciendo clic en el enlace que enviamos a tu correo electrónico antes de poder iniciar sesión.');
}
// --- FIN DE LA CORRECCIÓN ---

// 3) CAMBIO MFA: Las credenciales son correctas. Ahora iniciamos el flujo MFA.
// =========================================================================

// 3.1) Generar un código y su fecha de expiración (ej. 10 minutos)
$mfa_code = random_int(100000, 999999);
$mfa_expiry = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

// 3.2) Guardar el código y la fecha en la base de datos para este usuario
$stmt = $mysqli->prepare("UPDATE usuarios SET mfa_code = ?, mfa_expiry = ? WHERE id = ?");
$stmt->bind_param('ssi', $mfa_code, $mfa_expiry, $user['id']);
$stmt->execute();

// 3.3) Enviar el código por correo electrónico usando PHPMailer
$mail = new PHPMailer(true);

try {
    // Configuración del servidor SMTP (usa los datos de tu proveedor de correo)
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com'; // Ejemplo: Gmail
    $mail->SMTPAuth   = true;
    $mail->Username   = 'tu_correo@gmail.com'; // Tu dirección de correo
    $mail->Password   = 'tu_contraseña_de_aplicacion'; // ¡NO es tu contraseña normal! Es una "App Password" de Google
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    // Remitente y Destinatario
    $mail->setFrom('no-reply@sistemaregistro.com', 'Sistema Académico');
    $mail->addAddress($user['email']); 

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Tu código de verificación de dos pasos';
    $mail->Body    = "Hola,<br><br>Tu código de inicio de sesión es: <h1>$mfa_code</h1>Este código expirará en 10 minutos.<br><br>Si no solicitaste este código, puedes ignorar este mensaje.";
    $mail->AltBody = "Tu código de inicio de sesión es: $mfa_code. Expira en 10 minutos.";

    $mail->send();

} catch (Exception $e) {
    bad("No se pudo enviar el código de verificación. Error: {$mail->ErrorInfo}");
}

// 3.4) Guardar en la sesión que el usuario está pendiente de verificación
$_SESSION['mfa_user_id'] = $user['id'];

// 3.5) Redirigir a la nueva página de verificación
header('Location: verify_mfa.php');
exit;

// =========================================================================

function bad($message) {
    // Pequeña mejora a tu función para pasar mensajes personalizados
    echo '<p style="color:red;">' . htmlspecialchars($message) . '</p>';
    echo '<p><a href="index.php">Intentar nuevamente</a></p>';
    exit;
}
?>