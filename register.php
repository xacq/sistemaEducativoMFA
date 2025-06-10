<?php
// register.php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

// 1) Recoger y sanear datos
$nombre   = trim($mysqli->real_escape_string($_POST['nombre']));
$apellido = trim($mysqli->real_escape_string($_POST['apellido']));
$email    = trim($mysqli->real_escape_string($_POST['email']));
$password = $_POST['password'];
$role_id  = (int) $_POST['role_id'];

// 2) Validaciones básicas
$errors = [];
if (empty($nombre) || empty($apellido) || empty($email) || empty($password) || !$role_id) {
    $errors[] = 'Todos los campos son obligatorios.';
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email no válido.';
}

// 3) Si hay errores, mostrarlos
if ($errors) {
    foreach ($errors as $e) echo "<p style='color:red;'>$e</p>";
    echo "<p><a href='index.html'>Volver</a></p>";
    exit;
}

// 4) Hashear la contraseña
$hash = password_hash($password, PASSWORD_DEFAULT);

// 5) Insertar en la BD
$stmt = $mysqli->prepare("
    INSERT INTO usuarios (nombre, apellido, email, password, role_id)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->bind_param('ssssi', $nombre, $apellido, $email, $hash, $role_id);

if ($stmt->execute()) {
    // Registro OK → redirigir a login con flag
    header('Location: index.php?registered=1');
    exit;
} else {
    echo "<p style='color:red;'>Error al registrar: " . $stmt->error . "</p>";
    echo "<p><a href='index.html'>Volver</a></p>";
}
