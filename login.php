<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password =             $_POST['password'] ?? '';

if (!$email || !$password) {
    echo '<p style="color:red;">Email y contraseña son obligatorios.</p>';
    echo '<p><a href="index.php">Volver al login</a></p>';
    exit;
}

// 1) Buscar usuario por email
$stmt = $mysqli->prepare("
    SELECT id, password, role_id
      FROM usuarios
     WHERE email = ?
");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    // usuario no existe
    bad();
}

$stmt->bind_result($user_id, $hash, $role_id);
$stmt->fetch();

// 2) Verificar contraseña
if (!password_verify($password, $hash)) {
    bad();
}

// 3) Login exitoso: guardamos sesión
$_SESSION['user_id'] = $user_id;
$_SESSION['role_id'] = $role_id;

// 4) Redirigir según rol

switch ($role_id) {
    case 1: // Director
        header('Location: pages/director_dashboard.php');
        break;
    case 2: // Profesor
        header('Location: pages/profesor_dashboard.php');
        break;
    case 3: // Estudiante
        header('Location: pages/estudiante_dashboard.php');
        break;
    default:
        header('Location: index.php');
        break;
}
exit;


function bad() {
    echo '<p style="color:red;">Credenciales inválidas.</p>';
    echo '<p><a href="index.php">Intentar nuevamente</a></p>';
    exit;
}
