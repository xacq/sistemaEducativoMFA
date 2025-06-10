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
include __DIR__ . '/side_bar_profesor.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Profesor Perfil</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            
            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mi Perfil</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-3">
                            <i class="bi bi-bell fs-4"></i>
                            <span class="notification-badge">5</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="profesor_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="profesor_configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../index.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <img src="https://via.placeholder.com/150" class="rounded-circle mb-3" alt="Foto de perfil">
                                <h5 class="card-title">María López</h5>
                                <p class="card-text text-muted">Profesora de Matemáticas</p>
                                <p class="card-text">
                                    <span class="badge bg-primary me-1">Matemáticas</span>
                                    <span class="badge bg-info me-1">Física</span>
                                    <span class="badge bg-success">Química</span>
                                </p>
                                <button class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="bi bi-camera"></i> Cambiar Foto
                                </button>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Cursos Asignados
                                    <span class="badge bg-academic rounded-pill">6</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Estudiantes
                                    <span class="badge bg-academic rounded-pill">150</span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    Años de Servicio
                                    <span class="badge bg-academic rounded-pill">12</span>
                                </li>
                            </ul>
                            <div class="card-body">
                                <button class="btn btn-academic w-100">
                                    <i class="bi bi-pencil-square"></i> Editar Perfil
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Información Personal</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="firstName" class="form-label">Nombre</label>
                                            <input type="text" class="form-control" id="firstName" value="María" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="lastName" class="form-label">Apellido</label>
                                            <input type="text" class="form-control" id="lastName" value="López" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Correo Electrónico</label>
                                            <input type="email" class="form-control" id="email" value="maria.lopez@eduardoavaroa.edu.bo" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="phone" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="phone" value="+591 70123456" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="birthDate" class="form-label">Fecha de Nacimiento</label>
                                            <input type="date" class="form-control" id="birthDate" value="1985-06-15" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="idNumber" class="form-label">Número de Identidad</label>
                                            <input type="text" class="form-control" id="idNumber" value="4567890 LP" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Dirección</label>
                                        <input type="text" class="form-control" id="address" value="Av. 6 de Marzo #1234, El Alto" readonly>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Información Académica</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="degree" class="form-label">Título Académico</label>
                                            <input type="text" class="form-control" id="degree" value="Licenciatura en Matemáticas" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="university" class="form-label">Universidad</label>
                                            <input type="text" class="form-control" id="university" value="Universidad Mayor de San Andrés" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="specialization" class="form-label">Especialización</label>
                                            <input type="text" class="form-control" id="specialization" value="Matemática Educativa" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="graduationYear" class="form-label">Año de Graduación</label>
                                            <input type="text" class="form-control" id="graduationYear" value="2010" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="otherDegrees" class="form-label">Otros Títulos</label>
                                        <textarea class="form-control" id="otherDegrees" rows="2" readonly>Maestría en Educación Matemática (2015) - Universidad Pedagógica Nacional
                                    Diplomado en Tecnología Educativa (2018) - Universidad Católica Boliviana</textarea>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Información Laboral</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="position" class="form-label">Cargo</label>
                                            <input type="text" class="form-control" id="position" value="Profesora de Matemáticas" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="department" class="form-label">Departamento</label>
                                            <input type="text" class="form-control" id="department" value="Ciencias Exactas" readonly>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="startDate" class="form-label">Fecha de Inicio</label>
                                            <input type="date" class="form-control" id="startDate" value="2013-02-01" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="employeeId" class="form-label">ID de Empleado</label>
                                            <input type="text" class="form-control" id="employeeId" value="PROF-2013-042" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="courses" class="form-label">Cursos Asignados</label>
                                        <textarea class="form-control" id="courses" rows="3" readonly>Matemáticas - 6° Secundaria
                                        Matemáticas - 5° Secundaria
                                        Física - 6° Secundaria
                                        Física - 5° Secundaria
                                        Química - 6° Secundaria
                                        Química - 5° Secundaria
                                        </textarea>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Cambiar Contraseña</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label for="currentPassword" class="form-label">Contraseña Actual</label>
                                        <input type="password" class="form-control" id="currentPassword">
                                    </div>
                                    <div class="mb-3">
                                        <label for="newPassword" class="form-label">Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="newPassword">
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirmPassword" class="form-label">Confirmar Nueva Contraseña</label>
                                        <input type="password" class="form-control" id="confirmPassword">
                                    </div>
                                    <button type="button" class="btn btn-academic">Cambiar Contraseña</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
