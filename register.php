<?php
// register.php
require_once 'config.php';

// CAMBIO: Incluir PHPMailer al principio del archivo
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // O la ruta manual a PHPMailer

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// 1) Recoger y sanear datos (He limpiado un poco la sanitización innecesaria)
$nombre   = trim($_POST['nombre']);
$apellido = trim($_POST['apellido']);
$email    = trim($_POST['email']);
$password = $_POST['password'];
$role_id  = (int) $_POST['role_id'];

// 2) Validaciones... (tu código está bien)
$errors = [];
if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || !$role_id) {
    $errors[] = 'Todos los campos son obligatorios.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email no válido.';
}

// ... comprobación de si ya existe el email (recomendado)
$stmt_check = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt_check->bind_param('s', $email);
$stmt_check->execute();
$stmt_check->store_result();
if ($stmt_check->num_rows > 0) {
    $errors[] = 'Este correo electrónico ya está registrado.';
}
$stmt_check->close();

if ($errors) {
    foreach ($errors as $e) echo "<p style='color:red;'>$e</p>";
    echo "<p><a href='index.html'>Volver</a></p>";
    exit;
}

// 4) Hashear contraseña...
$hash = password_hash($password, PASSWORD_DEFAULT);

// 5) Insertar en la BD...
$stmt = $mysqli->prepare("INSERT INTO usuarios (nombre, apellido, email, password, role_id) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param('ssssi', $nombre, $apellido, $email, $hash, $role_id);

if ($stmt->execute()) {
    $user_id = $mysqli->insert_id;
    $token = bin2hex(random_bytes(32));

    $stmt_token = $mysqli->prepare("UPDATE usuarios SET email_verification_token = ? WHERE id = ?");
    $stmt_token->bind_param('si', $token, $user_id);
    $stmt_token->execute();
    
    // --- LÓGICA PARA ENVIAR EL EMAIL (aquí la tienes) ---
    $verification_link = "http://localhost/sistema_academico_final/verify_email.php?token=" . $token;

    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP (¡la misma que usaste para el MFA!)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Servidor SMTP
        $mail->SMTPAuth   = true;
        $mail->Username   = 'tu_correo@gmail.com'; // Tu usuario SMTP
        $mail->Password   = 'tu_contraseña_de_aplicacion'; // Tu contraseña de aplicación SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Remitente y Destinatario
        $mail->setFrom('no-reply@sistemaregistro.com', 'Sistema Académico');
        $mail->addAddress($email, "$nombre $apellido"); // Se enviará al email que el usuario acaba de registrar

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Confirma tu registro en Sistema Académico';
        $mail->Body    = "
            <h2>¡Hola $nombre! Gracias por registrarte.</h2>
            <p>Por favor, haz clic en el siguiente enlace para activar tu cuenta:</p>
            <p><a href='$verification_link'>Verificar mi cuenta</a></p>
            <p>Si no te registraste en nuestro sitio, puedes ignorar este correo.</p>
        ";
        $mail->AltBody = "Hola $nombre, para activar tu cuenta, copia y pega este enlace en tu navegador: $verification_link";

        $mail->send();

        // Si el correo se envía bien, redirigir a una página de éxito
        header('Location: registration_success.php');
        exit;

    } catch (Exception $e) {
        // Si hay un error enviando el email, es importante notificarlo.
        // Podrías borrar el usuario recién creado para que pueda intentarlo de nuevo o manejarlo de otra forma.
        echo "<p style='color:red;'>El registro fue exitoso, pero no pudimos enviar el correo de verificación. Por favor, contacta a soporte. Error: {$mail->ErrorInfo}</p>";
        // Opcional: Borrar el usuario que no pudo recibir el email de verificación
        $stmt_delete = $mysqli->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt_delete->bind_param('i', $user_id);
        $stmt_delete->execute();
    }
    // ----------------------------------------------------

} else {
    echo "<p style='color:red;'>Error al registrar: " . $stmt->error . "</p>";
    echo "<p><a href='index.html'>Volver</a></p>";
}

?>