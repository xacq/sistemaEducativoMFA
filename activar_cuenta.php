<?php
require_once __DIR__ . '/config.php';

// Obtener token desde la URL
$token = $_GET['token'] ?? '';

if (!$token) {
    $error = "Token inválido o caducado.";
} else {
    // Buscar usuario con ese token
    $stmt = $mysqli->prepare("SELECT id, email FROM usuarios WHERE email_verification_token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $error = "El enlace de activación no es válido o ya fue utilizado.";
    }
}

// Si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($user)) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($password !== $confirm) {
        $error = "Las contraseñas no coinciden.";
    } elseif (strlen($password) < 8) {
        $error = "La contraseña debe tener al menos 8 caracteres.";
    } else {
        // Actualizar contraseña y marcar como verificado
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare("
            UPDATE usuarios
            SET password = ?, email_verification_token = NULL, email_verified_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('si', $hash, $user['id']);
        $stmt->execute();
        $stmt->close();

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Activar cuenta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header text-center bg-primary text-white">
                        <h4>Activar cuenta</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php elseif (isset($success) && $success): ?>
                            <div class="alert alert-success text-center">
                                <strong>Contraseña creada correctamente.</strong><br>
                                Ya puede iniciar sesión con su usuario y la nueva contraseña.
                            </div>
                            <div class="text-center mt-3">
                                <a href="index.php" class="btn btn-success">Ir al inicio de sesión</a>
                            </div>
                        <?php elseif (isset($user)): ?>
                            <p class="text-muted">Hola <strong><?php echo htmlspecialchars($user['email']); ?></strong>, por favor establece tu nueva contraseña para activar tu cuenta.</p>
                            <form method="POST" autocomplete="off">
                                <div class="mb-3">
                                    <label for="password" class="form-label">Nueva contraseña</label>
                                    <input type="password" name="password" id="password" class="form-control" required minlength="8">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm" class="form-label">Confirmar contraseña</label>
                                    <input type="password" name="confirm" id="confirm" class="form-control" required minlength="8">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Guardar contraseña</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning">Enlace inválido o expirado.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
