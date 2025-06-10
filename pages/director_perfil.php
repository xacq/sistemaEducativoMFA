<?php
session_start();

// Si no hay sesión activa, volvemos al login
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Conexión
require_once '../config.php';

// Obtener nombre y apellido
$stmt = $mysqli->prepare("
    SELECT nombre, apellido
      FROM usuarios
     WHERE id = ?
");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($nombre, $apellido);
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
    <title>Sistema Académico - Perfil</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
        

            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mi Perfil</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-3">
                            <i class="bi bi-bell fs-4"></i>
                            <span class="notification-badge">7</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="director_configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../index.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Información Personal</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <img src="https://via.placeholder.com/150" class="rounded-circle img-thumbnail" alt="Foto de perfil">
                                    <h4 class="mt-3">Roberto Sánchez</h4>
                                    <p class="text-muted">Director</p>
                                    <button class="btn btn-sm btn-outline-primary">Cambiar foto</button>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Nombre completo:</label>
                                    <p>Roberto Carlos Sánchez Mendoza</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Correo electrónico:</label>
                                    <p>roberto.sanchez@eduardoavaroa.edu.bo</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Teléfono:</label>
                                    <p>+591 71234567</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Dirección:</label>
                                    <p>Av. 6 de Marzo #1234, El Alto, La Paz</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Fecha de nacimiento:</label>
                                    <p>15 de marzo de 1975</p>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">CI:</label>
                                    <p>4567890 LP</p>
                                </div>
                                <div class="text-center">
                                    <button class="btn btn-academic" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                        <i class="bi bi-pencil-square me-1"></i> Editar información
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Información Profesional</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Cargo actual:</label>
                                        <p>Director General</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Fecha de ingreso:</label>
                                        <p>10 de febrero de 2020</p>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Especialidad:</label>
                                        <p>Administración Educativa</p>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Años de experiencia:</label>
                                        <p>15 años</p>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Formación académica:</label>
                                    <ul class="list-group">
                                        <li class="list-group-item">Licenciatura en Ciencias de la Educación - Universidad Mayor de San Andrés (2000-2004)</li>
                                        <li class="list-group-item">Maestría en Administración Educativa - Universidad Católica Boliviana (2008-2010)</li>
                                        <li class="list-group-item">Diplomado en Gestión Educativa - Universidad Andina Simón Bolívar (2012)</li>
                                    </ul>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Certificaciones:</label>
                                    <ul class="list-group">
                                        <li class="list-group-item">Certificación en Liderazgo Educativo - Ministerio de Educación (2015)</li>
                                        <li class="list-group-item">Certificación en Gestión de Calidad Educativa - AENOR (2018)</li>
                                    </ul>
                                </div>
                                <div class="text-end">
                                    <button class="btn btn-academic" data-bs-toggle="modal" data-bs-target="#editProfessionalModal">
                                        <i class="bi bi-pencil-square me-1"></i> Editar información
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Security Settings -->
                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Seguridad</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Contraseña:</label>
                                    <div class="d-flex align-items-center">
                                        <p class="mb-0">********</p>
                                        <button class="btn btn-sm btn-outline-primary ms-3" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                            Cambiar contraseña
                                        </button>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Autenticación de dos factores:</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                                        <label class="form-check-label" for="twoFactorAuth">Activar autenticación de dos factores</label>
                                    </div>
                                    <small class="text-muted">Mejora la seguridad de tu cuenta añadiendo una capa adicional de protección.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Sesiones activas:</label>
                                    <ul class="list-group">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-laptop me-2"></i>
                                                Windows 10 - Chrome
                                                <small class="d-block text-muted">La Paz, Bolivia - Hace 5 minutos</small>
                                            </div>
                                            <span class="badge bg-success">Actual</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="bi bi-phone me-2"></i>
                                                Android - Chrome Mobile
                                                <small class="d-block text-muted">La Paz, Bolivia - Hace 2 días</small>
                                            </div>
                                            <button class="btn btn-sm btn-outline-danger">Cerrar</button>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Notificaciones</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Preferencias de notificación:</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                                        <label class="form-check-label" for="emailNotifications">
                                            Recibir notificaciones por correo electrónico
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="smsNotifications">
                                        <label class="form-check-label" for="smsNotifications">
                                            Recibir notificaciones por SMS
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="browserNotifications" checked>
                                        <label class="form-check-label" for="browserNotifications">
                                            Recibir notificaciones en el navegador
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Tipos de notificaciones:</label>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="attendanceNotifications" checked>
                                        <label class="form-check-label" for="attendanceNotifications">
                                            Asistencia de estudiantes
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="gradeNotifications" checked>
                                        <label class="form-check-label" for="gradeNotifications">
                                            Calificaciones
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="eventNotifications" checked>
                                        <label class="form-check-label" for="eventNotifications">
                                            Eventos y reuniones
                                        </label>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="systemNotifications" checked>
                                        <label class="form-check-label" for="systemNotifications">
                                            Actualizaciones del sistema
                                        </label>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button class="btn btn-academic">
                                        <i class="bi bi-save me-1"></i> Guardar preferencias
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="editProfileModalLabel">Editar Información Personal</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Nombre completo</label>
                            <input type="text" class="form-control" id="fullName" value="Roberto Carlos Sánchez Mendoza">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo electrónico</label>
                            <input type="email" class="form-control" id="email" value="roberto.sanchez@eduardoavaroa.edu.bo">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="phone" value="+591 71234567">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Dirección</label>
                            <input type="text" class="form-control" id="address" value="Av. 6 de Marzo #1234, El Alto, La Paz">
                        </div>
                        <div class="mb-3">
                            <label for="birthdate" class="form-label">Fecha de nacimiento</label>
                            <input type="date" class="form-control" id="birthdate" value="1975-03-15">
                        </div>
                        <div class="mb-3">
                            <label for="ci" class="form-label">CI</label>
                            <input type="text" class="form-control" id="ci" value="4567890 LP">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Professional Information Modal -->
    <div class="modal fade" id="editProfessionalModal" tabindex="-1" aria-labelledby="editProfessionalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="editProfessionalModalLabel">Editar Información Profesional</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="position" class="form-label">Cargo actual</label>
                            <input type="text" class="form-control" id="position" value="Director General">
                        </div>
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Fecha de ingreso</label>
                            <input type="date" class="form-control" id="startDate" value="2020-02-10">
                        </div>
                        <div class="mb-3">
                            <label for="specialty" class="form-label">Especialidad</label>
                            <input type="text" class="form-control" id="specialty" value="Administración Educativa">
                        </div>
                        <div class="mb-3">
                            <label for="experience" class="form-label">Años de experiencia</label>
                            <input type="number" class="form-control" id="experience" value="15">
                        </div>
                        <div class="mb-3">
                            <label for="education" class="form-label">Formación académica</label>
                            <textarea class="form-control" id="education" rows="3">Licenciatura en Ciencias de la Educación - Universidad Mayor de San Andrés (2000-2004)
Maestría en Administración Educativa - Universidad Católica Boliviana (2008-2010)
Diplomado en Gestión Educativa - Universidad Andina Simón Bolívar (2012)</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="certifications" class="form-label">Certificaciones</label>
                            <textarea class="form-control" id="certifications" rows="2">Certificación en Liderazgo Educativo - Ministerio de Educación (2015)
Certificación en Gestión de Calidad Educativa - AENOR (2018)</textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic">Guardar cambios</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="changePasswordModalLabel">Cambiar Contraseña</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="currentPassword" class="form-label">Contraseña actual</label>
                            <input type="password" class="form-control" id="currentPassword">
                        </div>
                        <div class="mb-3">
                            <label for="newPassword" class="form-label">Nueva contraseña</label>
                            <input type="password" class="form-control" id="newPassword">
                            <div class="form-text">La contraseña debe tener al menos 8 caracteres, incluir letras mayúsculas, minúsculas, números y caracteres especiales.</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirmar nueva contraseña</label>
                            <input type="password" class="form-control" id="confirmPassword">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic">Cambiar contraseña</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
