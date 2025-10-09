<?php
// verify_mfa.php
session_start();
require_once 'config.php';

// Si el usuario no ha pasado el primer paso (login), no debería estar aquí.
if (!isset($_SESSION['mfa_user_id'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted_code = $_POST['mfa_code'] ?? '';
    $user_id = $_SESSION['mfa_user_id'];

    if (empty($submitted_code)) {
        $error_message = 'Por favor, introduce el código.';
    } else {
        // 1) Buscar el código y la expiración para el usuario
        $stmt = $mysqli->prepare("SELECT role_id, mfa_code, mfa_expiry FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $current_time = new DateTime();
        $expiry_time = new DateTime($user['mfa_expiry']);

        // 2) Validar el código y la fecha de expiración
        if (!$user || $user['mfa_code'] !== $submitted_code) {
            $error_message = 'El código introducido es incorrecto.';
        } elseif ($current_time > $expiry_time) {
            $error_message = 'El código ha expirado. Por favor, intenta iniciar sesión de nuevo.';
        }else {
            // ¡Éxito! El código es correcto y no ha expirado.

            // 3) Limpiar el código MFA de la base de datos para que no se pueda reutilizar
            $stmt_clear = $mysqli->prepare("UPDATE usuarios SET mfa_code = NULL, mfa_expiry = NULL WHERE id = ?");
            $stmt_clear->bind_param('i', $user_id);
            $stmt_clear->execute();

            // 4) Establecer la sesión de usuario final
            unset($_SESSION['mfa_user_id']); // Limpiar la sesión temporal
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role_id'] = $user['role_id'];

            // 5) Redirigir al dashboard correspondiente
            switch ($user['role_id']) {
                case 1: header('Location: ./pages/director_dashboard.php'); break;
                case 2: header('Location: ./pages/profesor_dashboard.php'); break;
                case 3: header('Location: ./pages/estudiante_dashboard.php'); break;
                default: header('Location: ./index.php'); break;
            }
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificación de Dos Pasos</title>
    <!-- Puedes añadir tus propios estilos aquí -->
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f4f4; }
        .container { background: white; padding: 2em; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); text-align: center; }
        input { padding: 0.5em; margin: 1em 0; width: 200px; text-align: center; font-size: 1.2em; }
        button { padding: 0.7em 1.5em; border: none; background-color: #007bff; color: white; border-radius: 5px; cursor: pointer; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Verificación de Seguridad</h2>
        <p>Hemos enviado un código de 6 dígitos a tu correo electrónico. Ingrésalo a continuación.</p>
        
        <?php if ($error_message): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form method="POST" action="verify_mfa.php">
            <label for="mfa_code">Código de Verificación:</label><br>
            <input type="text" name="mfa_code" id="mfa_code" maxlength="6" pattern="\d{6}" required autofocus>
            <br>
            <button type="submit">Verificar y Entrar</button>
        </form>
        <p><a href="index.php">Volver al inicio de sesión</a></p>
    </div>
</body>
</html>