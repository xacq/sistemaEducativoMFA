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
include __DIR__ . '/side_bar_estudiantes.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Dashboard Estudiante</title>
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
                    <h1 class="h2">Dashboard Estudiante</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-3">
                            <i class="bi bi-bell fs-4"></i>
                            <span class="notification-badge">3</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="estudiante_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="estudiante_configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../index.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- School Info Card -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Información Institucional</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <img src="../img/logo_escuela.png" alt="Logo U.E. Eduardo Avaroa" class="img-fluid mb-3" style="max-height: 120px;">
                            </div>
                            <div class="col-md-9">
                                <h4>Unidad Educativa Eduardo Avaroa III</h4>
                                <p class="text-muted">El Alto, La Paz - Bolivia</p>
                                <p><strong>Fundación:</strong> 1918 (106 años de trayectoria)</p>
                                <p><strong>Niveles:</strong> Primaria y Secundaria</p>
                                <p><strong>Aniversario:</strong> Marzo (Centésimo Sexto Aniversario celebrado en 2024)</p>
                                <!-- Estudiantes no tienen permisos de edición -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Schedule -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Horario de Hoy</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Hora</th>
                                        <th>Materia</th>
                                        <th>Profesor</th>
                                        <th>Aula</th>
                                        <th>Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>08:00 - 09:30</td>
                                        <td>Matemáticas</td>
                                        <td>Prof. María López</td>
                                        <td>Aula 201</td>
                                        <td><span class="badge bg-success">Completada</span></td>
                                    </tr>
                                    <tr>
                                        <td>09:45 - 11:15</td>
                                        <td>Física</td>
                                        <td>Prof. María López</td>
                                        <td>Laboratorio 2</td>
                                        <td><span class="badge bg-success">Completada</span></td>
                                    </tr>
                                    <tr class="table-active">
                                        <td>11:30 - 13:00</td>
                                        <td>Literatura</td>
                                        <td>Prof. Roberto Flores</td>
                                        <td>Aula 203</td>
                                        <td><span class="badge bg-primary">En curso</span></td>
                                    </tr>
                                    <tr>
                                        <td>14:30 - 16:00</td>
                                        <td>Historia</td>
                                        <td>Prof. Javier Mendoza</td>
                                        <td>Aula 205</td>
                                        <td><span class="badge bg-warning">Pendiente</span></td>
                                    </tr>
                                    <tr>
                                        <td>16:15 - 17:45</td>
                                        <td>Educación Física</td>
                                        <td>Prof. Luis Ramírez</td>
                                        <td>Cancha Deportiva</td>
                                        <td><span class="badge bg-warning">Pendiente</span></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">
                                <i class="bi bi-book-fill text-primary fs-1"></i>
                                <h5 class="card-title mt-3">Mis Cursos</h5>
                                <h2 class="card-text">8</h2>
                                <p class="card-text text-muted">Materias activas</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="estudiante_cursos.php" class="btn btn-sm btn-outline-primary">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center">
                                <i class="bi bi-clipboard-check text-success fs-1"></i>
                                <h5 class="card-title mt-3">Mi Asistencia</h5>
                                <h2 class="card-text">96%</h2>
                                <p class="card-text text-muted">Último mes</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="estudiante_asistencia.php" class="btn btn-sm btn-outline-success">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-info">
                            <div class="card-body text-center">
                                <i class="bi bi-award text-info fs-1"></i>
                                <h5 class="card-title mt-3">Promedio General</h5>
                                <h2 class="card-text">82/100</h2>
                                <p class="card-text text-muted">Trimestre actual</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="estudiante_calificaciones.php" class="btn btn-sm btn-outline-info">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-warning">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-check text-warning fs-1"></i>
                                <h5 class="card-title mt-3">Tareas Pendientes</h5>
                                <h2 class="card-text">5</h2>
                                <p class="card-text text-muted">Por entregar</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="estudiante_tareas.php" class="btn btn-sm btn-outline-warning">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Tasks and Notifications -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Tareas Pendientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="task-item d-flex align-items-center py-2 border-bottom">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">Ensayo de Literatura - "Cien Años de Soledad"</h6>
                                        <small class="text-muted">Vence: 03/06/2025 | Prof. Roberto Flores</small>
                                    </div>
                                    <span class="badge bg-danger">Urgente</span>
                                </div>
                                <div class="task-item d-flex align-items-center py-2 border-bottom">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">Ejercicios de Matemáticas - Cálculo Diferencial</h6>
                                        <small class="text-muted">Vence: 04/06/2025 | Prof. María López</small>
                                    </div>
                                    <span class="badge bg-warning">Media</span>
                                </div>
                                <div class="task-item d-flex align-items-center py-2 border-bottom">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">Informe de Laboratorio - Física</h6>
                                        <small class="text-muted">Vence: 05/06/2025 | Prof. María López</small>
                                    </div>
                                    <span class="badge bg-warning">Media</span>
                                </div>
                                <div class="task-item d-flex align-items-center py-2 border-bottom">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">Presentación - Historia de Bolivia</h6>
                                        <small class="text-muted">Vence: 08/06/2025 | Prof. Javier Mendoza</small>
                                    </div>
                                    <span class="badge bg-info">Normal</span>
                                </div>
                                <div class="task-item d-flex align-items-center py-2">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0">Proyecto Final - Química</h6>
                                        <small class="text-muted">Vence: 15/06/2025 | Prof. Laura Sánchez</small>
                                    </div>
                                    <span class="badge bg-info">Normal</span>
                                </div>
                                <div class="text-center mt-3">
                                    <button class="btn btn-sm btn-outline-primary">Ver Todas las Tareas</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Notificaciones Recientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="notification-item d-flex py-2 border-bottom">
                                    <div class="notification-icon bg-primary text-white rounded-circle me-3">
                                        <i class="bi bi-award"></i>
                                    </div>
                                    <div class="notification-content flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0">Nueva calificación registrada</h6>
                                            <small class="text-muted">Hoy, 10:15</small>
                                        </div>
                                        <p class="mb-0">Prof. María López ha calificado tu examen de Matemáticas: 85/100.</p>
                                    </div>
                                </div>
                                <div class="notification-item d-flex py-2 border-bottom">
                                    <div class="notification-icon bg-success text-white rounded-circle me-3">
                                        <i class="bi bi-file-earmark-plus"></i>
                                    </div>
                                    <div class="notification-content flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0">Nueva tarea asignada</h6>
                                            <small class="text-muted">Ayer, 15:30</small>
                                        </div>
                                        <p class="mb-0">Prof. Roberto Flores ha asignado un nuevo ensayo de Literatura.</p>
                                    </div>
                                </div>
                                <div class="notification-item d-flex py-2 border-bottom">
                                    <div class="notification-icon bg-warning text-white rounded-circle me-3">
                                        <i class="bi bi-calendar-event"></i>
                                    </div>
                                    <div class="notification-content flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0">Recordatorio de evento</h6>
                                            <small class="text-muted">Ayer, 11:20</small>
                                        </div>
                                        <p class="mb-0">Feria de Ciencias programada para el 15/06/2025.</p>
                                    </div>
                                </div>
                                <div class="notification-item d-flex py-2 border-bottom">
                                    <div class="notification-icon bg-info text-white rounded-circle me-3">
                                        <i class="bi bi-book"></i>
                                    </div>
                                    <div class="notification-content flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0">Nuevo material disponible</h6>
                                            <small class="text-muted">31/05/2025</small>
                                        </div>
                                        <p class="mb-0">Prof. María López ha compartido nuevos materiales de estudio para Física.</p>
                                    </div>
                                </div>
                                <div class="notification-item d-flex py-2">
                                    <div class="notification-icon bg-secondary text-white rounded-circle me-3">
                                        <i class="bi bi-gear"></i>
                                    </div>
                                    <div class="notification-content flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-0">Actualización del sistema</h6>
                                            <small class="text-muted">30/05/2025</small>
                                        </div>
                                        <p class="mb-0">Se ha actualizado el sistema a la versión 2.5.0.</p>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <button class="btn btn-sm btn-outline-primary">Ver Todas las Notificaciones</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-6 mb-6">
                                <button class="btn btn-light p-3 w-100 h-100">
                                    <i class="bi bi-file-earmark-check fs-3 d-block mb-2"></i>
                                    Entregar Tarea
                                </button>
                            </div>
                            <div class="col-md-6 mb-6">
                                <button class="btn btn-light p-3 w-100 h-100">
                                    <i class="bi bi-calendar-week fs-3 d-block mb-2"></i>
                                    Ver Horario Completo
                                </button>
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
