<?php
session_start();

// Verificar sesión activa
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Conexión
require_once '../config.php';

// Obtener información real del usuario
$stmt = $mysqli->prepare("
    SELECT nombre, apellido, email, role_id, fecha_creacion
      FROM usuarios
     WHERE id = ?
");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($nombre, $apellido, $email, $rol, $fecha_creacion);
$stmt->fetch();
$stmt->close();

include __DIR__ . '/side_bar_director.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Mi Perfil</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Contenido principal -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Mi Perfil</h1>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="director_configuracion.php">Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>

            <!-- Perfil -->
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header card-header-academic">
                            <h5 class="mb-0 text-white">Información Personal</h5>
                        </div>
                        <div class="card-body text-center">
                            <img src="https://via.placeholder.com/150" class="rounded-circle img-thumbnail mb-3" alt="Foto de perfil">
                            <h4><?php echo htmlspecialchars($nombre . ' ' . $apellido); ?></h4>
                            <p class="text-muted mb-1">
                                <?php echo ($rol == 1 ? 'Director' : 'Usuario del sistema'); ?>
                            </p>
                            <p><small>Miembro desde <?php echo date('d/m/Y', strtotime($fecha_creacion)); ?></small></p>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card mb-4 shadow-sm">
                        <div class="card-header card-header-academic">
                            <h5 class="mb-0 text-white">Datos de Contacto</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Correo electrónico:</label>
                                <p><?php echo htmlspecialchars($email); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Rol del sistema:</label>
                                <p><?php echo ($rol == 1 ? 'Director' : 'Usuario estándar'); ?></p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Fecha de registro:</label>
                                <p><?php echo date('d/m/Y', strtotime($fecha_creacion)); ?></p>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-academic" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="bi bi-key me-1"></i> Cambiar contraseña
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal cambiar contraseña -->
            <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header card-header-academic text-white">
                            <h5 class="modal-title" id="changePasswordModalLabel">Cambiar Contraseña</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <form action="cambiar_contrasena.php" method="POST">
                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Contraseña actual</label>
                                    <input type="password" name="currentPassword" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">Nueva contraseña</label>
                                    <input type="password" name="newPassword" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirmar nueva contraseña</label>
                                    <input type="password" name="confirmPassword" class="form-control" required>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-academic">Guardar cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>
<?php if (isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const msg = <?php echo json_encode($_SESSION['success_message'] ?? $_SESSION['error_message']); ?>;
    const success = <?php echo isset($_SESSION['success_message']) ? 'true' : 'false'; ?>;

    const toast = document.createElement('div');
    toast.className = `position-fixed top-50 start-50 translate-middle text-center p-4 rounded-3 shadow-lg border 
                      ${success ? 'bg-success text-white' : 'bg-danger text-white'}`;
    toast.style.zIndex = '2000';
    toast.style.minWidth = '400px';
    toast.innerHTML = `
        <strong style="font-size:1.1rem;">${success ? '✔ Éxito' : '⚠ Error'}</strong>
        <div style="margin-top:6px;font-size:.95rem;">${msg}</div>
    `;

    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('fade');
        setTimeout(() => toast.remove(), 600);
    }, 2500);
});
</script>
<?php 
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
endif; 
?>


</body>
</html>
