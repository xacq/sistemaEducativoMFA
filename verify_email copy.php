// verify_email.php
<?php 
require_once 'config.php';
$token = $_GET['token'] ?? '';

if (empty($token)) { die('Token no proporcionado.'); }

// Buscar el usuario con ese token
$stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE email_verification_token = ? AND email_verified_at IS NULL");
$stmt->bind_param('s', $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Marcar como verificado y limpiar el token
    $stmt_update = $mysqli->prepare("UPDATE usuarios SET email_verified_at = NOW(), email_verification_token = NULL WHERE id = ?");
    $stmt_update->bind_param('i', $user['id']);
    $stmt_update->execute();
    
    echo "¡Tu correo ha sido verificado con éxito! ya puedes <a href='index.php'>iniciar sesión</a>.";
} else {
    echo "Token inválido o el correo ya ha sido verificado.";
}?>