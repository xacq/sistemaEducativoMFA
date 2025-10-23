<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: director_perfil.php');
    exit;
}

$current = $_POST['currentPassword'] ?? '';
$new     = $_POST['newPassword'] ?? '';
$confirm = $_POST['confirmPassword'] ?? '';

// Validar que todos los campos tengan datos
if (empty($current) || empty($new) || empty($confirm)) {
    $_SESSION['error_message'] = 'Todos los campos son obligatorios.';
    header('Location: director_perfil.php');
    exit;
}

// Verificar coincidencia de nueva contraseña
if ($new !== $confirm) {
    $_SESSION['error_message'] = 'Las contraseñas nuevas no coinciden.';
    header('Location: director_perfil.php');
    exit;
}

// Verificar complejidad mínima (8 caracteres, mayúscula, minúscula, número)
if (strlen($new) < 8 || 
    !preg_match('/[A-Z]/', $new) || 
    !preg_match('/[a-z]/', $new) || 
    !preg_match('/\d/', $new)) {
    $_SESSION['error_message'] = 'La nueva contraseña debe tener al menos 8 caracteres e incluir mayúsculas, minúsculas y números.';
    header('Location: director_perfil.php');
    exit;
}

// Buscar contraseña actual del usuario
$stmt = $mysqli->prepare("SELECT password FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($hash);
$stmt->fetch();
$stmt->close();

// Verificar contraseña actual
if (!password_verify($current, $hash)) {
    $_SESSION['error_message'] = 'La contraseña actual no es correcta.';
    header('Location: director_perfil.php');
    exit;
}

// Hashear y guardar la nueva contraseña
$newHash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $mysqli->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
$stmt->bind_param('si', $newHash, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

$_SESSION['success_message'] = 'Contraseña actualizada correctamente.';
header('Location: director_perfil.php');
exit;
?>
