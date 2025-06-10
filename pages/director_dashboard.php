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
    <title>Sistema Académico - Dashboard Director</title>
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
                    <h1 class="h2">Dashboard Director</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-3">
                            <i class="bi bi-bell fs-4"></i>
                            <span class="notification-badge">8</span>
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
                                <div class="mt-2">
                                    <button class="btn btn-sm btn-outline-primary me-2">
                                        <i class="bi bi-pencil-square"></i> Editar Información
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-eye"></i> Ver Detalles Completos
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">
                                <i class="bi bi-people-fill text-primary fs-1"></i>
                                <h5 class="card-title mt-3">Estudiantes</h5>
                                <h2 class="card-text">600</h2>
                                <p class="card-text text-muted">Matriculados</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="director_estudiantes.php" class="btn btn-sm btn-outline-primary">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center">
                                <i class="bi bi-person-badge-fill text-success fs-1"></i>
                                <h5 class="card-title mt-3">Profesores</h5>
                                <h2 class="card-text">41</h2>
                                <p class="card-text text-muted">Activos</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="director_profesores.php" class="btn btn-sm btn-outline-success">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-info">
                            <div class="card-body text-center">
                                <i class="bi bi-person-workspace text-info fs-1"></i>
                                <h5 class="card-title mt-3">Personal Administrativo</h5>
                                <h2 class="card-text">4</h2>
                                <p class="card-text text-muted">Activos</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="#" class="btn btn-sm btn-outline-info">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-warning">
                            <div class="card-body text-center">
                                <i class="bi bi-book-fill text-warning fs-1"></i>
                                <h5 class="card-title mt-3">Cursos</h5>
                                <h2 class="card-text">24</h2>
                                <p class="card-text text-muted">Activos</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="director_cursos.php" class="btn btn-sm btn-outline-warning">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance and Performance -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Asistencia General</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <div>
                                        <h6 class="mb-0">Asistencia Promedio</h6>
                                        <p class="text-muted small">Últimos 30 días</p>
                                    </div>
                                    <div class="text-end">
                                        <h4 class="mb-0 text-success">92%</h4>
                                        <p class="text-muted small"><i class="bi bi-arrow-up-short"></i> 3% vs mes anterior</p>
                                    </div>
                                </div>
                                <div class="progress mb-4" style="height: 10px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 92%;" aria-valuenow="92" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h6>Primaria</h6>
                                        <p class="text-success mb-0">95%</p>
                                    </div>
                                    <div class="col-4">
                                        <h6>Secundaria Inferior</h6>
                                        <p class="text-success mb-0">91%</p>
                                    </div>
                                    <div class="col-4">
                                        <h6>Secundaria Superior</h6>
                                        <p class="text-warning mb-0">88%</p>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="director_asistencia.php" class="btn btn-sm btn-outline-primary">Ver Informe Completo</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Rendimiento Académico</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <div>
                                        <h6 class="mb-0">Promedio General</h6>
                                        <p class="text-muted small">Año escolar actual</p>
                                    </div>
                                    <div class="text-end">
                                        <h4 class="mb-0 text-primary">78/100</h4>
                                        <p class="text-muted small"><i class="bi bi-arrow-up-short"></i> 2 pts vs año anterior</p>
                                    </div>
                                </div>
                                <div class="progress mb-4" style="height: 10px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: 78%;" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <h6>Primaria</h6>
                                        <p class="text-primary mb-0">82/100</p>
                                    </div>
                                    <div class="col-4">
                                        <h6>Secundaria Inferior</h6>
                                        <p class="text-primary mb-0">76/100</p>
                                    </div>
                                    <div class="col-4">
                                        <h6>Secundaria Superior</h6>
                                        <p class="text-primary mb-0">74/100</p>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="director_calificaciones.php" class="btn btn-sm btn-outline-primary">Ver Informe Completo</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities and Calendar -->
                <div class="row mb-4">
                    <div class="col-md-7">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Actividades Recientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="activity-item d-flex py-3 border-bottom">
                                    <div class="activity-icon bg-primary text-white rounded-circle me-3">
                                        <i class="bi bi-person-plus"></i>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1">Nuevo estudiante registrado</h6>
                                            <small class="text-muted">Hoy, 09:15</small>
                                        </div>
                                        <p class="mb-0">Se ha registrado a Juan Pérez en 4° de Secundaria.</p>
                                    </div>
                                </div>
                                <div class="activity-item d-flex py-3 border-bottom">
                                    <div class="activity-icon bg-success text-white rounded-circle me-3">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1">Informe trimestral generado</h6>
                                            <small class="text-muted">Ayer, 15:30</small>
                                        </div>
                                        <p class="mb-0">Se ha generado el informe de rendimiento del primer trimestre.</p>
                                    </div>
                                </div>
                                <div class="activity-item d-flex py-3 border-bottom">
                                    <div class="activity-icon bg-warning text-white rounded-circle me-3">
                                        <i class="bi bi-calendar-event"></i>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1">Reunión de profesores programada</h6>
                                            <small class="text-muted">Ayer, 11:20</small>
                                        </div>
                                        <p class="mb-0">Reunión general de profesores programada para el 05/06/2025.</p>
                                    </div>
                                </div>
                                <div class="activity-item d-flex py-3 border-bottom">
                                    <div class="activity-icon bg-danger text-white rounded-circle me-3">
                                        <i class="bi bi-exclamation-triangle"></i>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1">Alerta de asistencia</h6>
                                            <small class="text-muted">31/05/2025, 14:45</small>
                                        </div>
                                        <p class="mb-0">5 estudiantes de 3° de Secundaria con baja asistencia este mes.</p>
                                    </div>
                                </div>
                                <div class="activity-item d-flex py-3">
                                    <div class="activity-icon bg-info text-white rounded-circle me-3">
                                        <i class="bi bi-gear"></i>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <h6 class="mb-1">Actualización del sistema</h6>
                                            <small class="text-muted">30/05/2025, 09:00</small>
                                        </div>
                                        <p class="mb-0">Se ha actualizado el sistema a la versión 2.5.0.</p>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <button class="btn btn-sm btn-outline-primary">Ver Todas las Actividades</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Calendario de Eventos</h5>
                            </div>
                            <div class="card-body">
                                <div class="calendar-header d-flex justify-content-between align-items-center mb-3">
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></button>
                                    <h5 class="mb-0">Junio 2025</h5>
                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></button>
                                </div>
                                <div class="calendar-event d-flex align-items-center p-2 mb-2 bg-light rounded">
                                    <div class="event-date text-center me-3">
                                        <div class="bg-primary text-white rounded p-1">
                                            <strong>05</strong>
                                        </div>
                                        <small>JUN</small>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Reunión General de Profesores</h6>
                                        <small class="text-muted">14:00 - 16:00, Sala de Conferencias</small>
                                    </div>
                                </div>
                                <div class="calendar-event d-flex align-items-center p-2 mb-2 bg-light rounded">
                                    <div class="event-date text-center me-3">
                                        <div class="bg-success text-white rounded p-1">
                                            <strong>10</strong>
                                        </div>
                                        <small>JUN</small>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Entrega de Boletines - 1er Trimestre</h6>
                                        <small class="text-muted">09:00 - 13:00, Auditorio Principal</small>
                                    </div>
                                </div>
                                <div class="calendar-event d-flex align-items-center p-2 mb-2 bg-light rounded">
                                    <div class="event-date text-center me-3">
                                        <div class="bg-warning text-white rounded p-1">
                                            <strong>15</strong>
                                        </div>
                                        <small>JUN</small>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Feria de Ciencias</h6>
                                        <small class="text-muted">Todo el día, Patio Central</small>
                                    </div>
                                </div>
                                <div class="calendar-event d-flex align-items-center p-2 mb-2 bg-light rounded">
                                    <div class="event-date text-center me-3">
                                        <div class="bg-info text-white rounded p-1">
                                            <strong>22</strong>
                                        </div>
                                        <small>JUN</small>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">Capacitación Docente</h6>
                                        <small class="text-muted">14:30 - 17:30, Laboratorio de Informática</small>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <button class="btn btn-sm btn-outline-primary me-2">
                                        <i class="bi bi-plus-circle"></i> Nuevo Evento
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-calendar3"></i> Ver Calendario
                                    </button>
                                </div>
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
