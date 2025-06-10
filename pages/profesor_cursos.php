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
    <title>Sistema Académico - Profesor Cursos</title>
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
                    <h1 class="h2">Mis Cursos</h1>
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

                <!-- Search and Filter -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Buscar curso por nombre, código o grado...">
                            <button class="btn btn-academic" type="button">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="dropdown">
                            <button class="btn btn-academic dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel"></i> Filtrar
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                                <li><a class="dropdown-item" href="#">Todos los cursos</a></li>
                                <li><a class="dropdown-item" href="#">Matemáticas</a></li>
                                <li><a class="dropdown-item" href="#">Física</a></li>
                                <li><a class="dropdown-item" href="#">Química</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#">6° Secundaria</a></li>
                                <li><a class="dropdown-item" href="#">5° Secundaria</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Course Cards -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Matemáticas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="badge bg-primary">6° Secundaria</span>
                                    <span class="badge bg-success">Activo</span>
                                </div>
                                <p><strong>Código:</strong> MAT-6S</p>
                                <p><strong>Horario:</strong> Lun, Mié, Vie 08:00-09:30</p>
                                <p><strong>Aula:</strong> 201</p>
                                <p><strong>Estudiantes:</strong> 25</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75% Completado</div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="bi bi-award text-warning"></i> Promedio: 78.5</span>
                                    <span><i class="bi bi-calendar-check text-success"></i> Asistencia: 92%</span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" type="button" data-bs-toggle="modal" data-bs-target="#courseDetailModal">Ver detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Matemáticas</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="badge bg-primary">5° Secundaria</span>
                                    <span class="badge bg-success">Activo</span>
                                </div>
                                <p><strong>Código:</strong> MAT-5S</p>
                                <p><strong>Horario:</strong> Lun, Mié, Vie 13:00-14:30</p>
                                <p><strong>Aula:</strong> 202</p>
                                <p><strong>Estudiantes:</strong> 25</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 70%" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100">70% Completado</div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="bi bi-award text-warning"></i> Promedio: 76.2</span>
                                    <span><i class="bi bi-calendar-check text-success"></i> Asistencia: 90%</span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" type="button">Ver detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Física</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="badge bg-primary">6° Secundaria</span>
                                    <span class="badge bg-success">Activo</span>
                                </div>
                                <p><strong>Código:</strong> FIS-6S</p>
                                <p><strong>Horario:</strong> Lun, Mié 10:00-11:30</p>
                                <p><strong>Aula:</strong> 201</p>
                                <p><strong>Estudiantes:</strong> 25</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 65%" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">65% Completado</div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="bi bi-award text-warning"></i> Promedio: 75.8</span>
                                    <span><i class="bi bi-calendar-check text-success"></i> Asistencia: 88%</span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" type="button">Ver detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Física</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="badge bg-primary">5° Secundaria</span>
                                    <span class="badge bg-success">Activo</span>
                                </div>
                                <p><strong>Código:</strong> FIS-5S</p>
                                <p><strong>Horario:</strong> Mar, Jue 15:00-16:30</p>
                                <p><strong>Aula:</strong> 202</p>
                                <p><strong>Estudiantes:</strong> 25</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100">60% Completado</div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="bi bi-award text-warning"></i> Promedio: 74.3</span>
                                    <span><i class="bi bi-calendar-check text-success"></i> Asistencia: 86%</span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" type="button">Ver detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Química</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="badge bg-primary">6° Secundaria</span>
                                    <span class="badge bg-success">Activo</span>
                                </div>
                                <p><strong>Código:</strong> QUI-6S</p>
                                <p><strong>Horario:</strong> Mar, Jue 10:00-11:30</p>
                                <p><strong>Aula:</strong> 201</p>
                                <p><strong>Estudiantes:</strong> 25</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 55%" aria-valuenow="55" aria-valuemin="0" aria-valuemax="100">55% Completado</div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="bi bi-award text-warning"></i> Promedio: 73.2</span>
                                    <span><i class="bi bi-calendar-check text-success"></i> Asistencia: 90%</span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" type="button">Ver detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Química</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="badge bg-primary">5° Secundaria</span>
                                    <span class="badge bg-success">Activo</span>
                                </div>
                                <p><strong>Código:</strong> QUI-5S</p>
                                <p><strong>Horario:</strong> Mar, Jue 13:00-14:30</p>
                                <p><strong>Aula:</strong> 202</p>
                                <p><strong>Estudiantes:</strong> 25</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">50% Completado</div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span><i class="bi bi-award text-warning"></i> Promedio: 72.5</span>
                                    <span><i class="bi bi-calendar-check text-success"></i> Asistencia: 88%</span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" type="button">Ver detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Statistics -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Estadísticas de Cursos</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Distribución de Estudiantes por Curso</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Curso</th>
                                                <th>Grado</th>
                                                <th>Estudiantes</th>
                                                <th>% del Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Matemáticas</td>
                                                <td>6° Secundaria</td>
                                                <td>25</td>
                                                <td>16.7%</td>
                                            </tr>
                                            <tr>
                                                <td>Matemáticas</td>
                                                <td>5° Secundaria</td>
                                                <td>25</td>
                                                <td>16.7%</td>
                                            </tr>
                                            <tr>
                                                <td>Física</td>
                                                <td>6° Secundaria</td>
                                                <td>25</td>
                                                <td>16.7%</td>
                                            </tr>
                                            <tr>
                                                <td>Física</td>
                                                <td>5° Secundaria</td>
                                                <td>25</td>
                                                <td>16.7%</td>
                                            </tr>
                                            <tr>
                                                <td>Química</td>
                                                <td>6° Secundaria</td>
                                                <td>25</td>
                                                <td>16.7%</td>
                                            </tr>
                                            <tr>
                                                <td>Química</td>
                                                <td>5° Secundaria</td>
                                                <td>25</td>
                                                <td>16.7%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Rendimiento Académico por Curso</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Curso</th>
                                                <th>Grado</th>
                                                <th>Promedio</th>
                                                <th>Asistencia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Matemáticas</td>
                                                <td>6° Secundaria</td>
                                                <td>78.5</td>
                                                <td>92%</td>
                                            </tr>
                                            <tr>
                                                <td>Matemáticas</td>
                                                <td>5° Secundaria</td>
                                                <td>76.2</td>
                                                <td>90%</td>
                                            </tr>
                                            <tr>
                                                <td>Física</td>
                                                <td>6° Secundaria</td>
                                                <td>75.8</td>
                                                <td>88%</td>
                                            </tr>
                                            <tr>
                                                <td>Física</td>
                                                <td>5° Secundaria</td>
                                                <td>74.3</td>
                                                <td>86%</td>
                                            </tr>
                                            <tr>
                                                <td>Química</td>
                                                <td>6° Secundaria</td>
                                                <td>73.2</td>
                                                <td>90%</td>
                                            </tr>
                                            <tr>
                                                <td>Química</td>
                                                <td>5° Secundaria</td>
                                                <td>72.5</td>
                                                <td>88%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Schedule -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Horario Semanal</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Hora</th>
                                        <th>Lunes</th>
                                        <th>Martes</th>
                                        <th>Miércoles</th>
                                        <th>Jueves</th>
                                        <th>Viernes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>08:00 - 09:30</td>
                                        <td class="table-primary">Matemáticas<br>6° Sec - Aula 201</td>
                                        <td></td>
                                        <td class="table-primary">Matemáticas<br>6° Sec - Aula 201</td>
                                        <td></td>
                                        <td class="table-primary">Matemáticas<br>6° Sec - Aula 201</td>
                                    </tr>
                                    <tr>
                                        <td>10:00 - 11:30</td>
                                        <td class="table-info">Física<br>6° Sec - Aula 201</td>
                                        <td class="table-warning">Química<br>6° Sec - Aula 201</td>
                                        <td class="table-info">Física<br>6° Sec - Aula 201</td>
                                        <td class="table-warning">Química<br>6° Sec - Aula 201</td>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td>13:00 - 14:30</td>
                                        <td class="table-primary">Matemáticas<br>5° Sec - Aula 202</td>
                                        <td class="table-warning">Química<br>5° Sec - Aula 202</td>
                                        <td class="table-primary">Matemáticas<br>5° Sec - Aula 202</td>
                                        <td class="table-warning">Química<br>5° Sec - Aula 202</td>
                                        <td class="table-primary">Matemáticas<br>5° Sec - Aula 202</td>
                                    </tr>
                                    <tr>
                                        <td>15:00 - 16:30</td>
                                        <td></td>
                                        <td class="table-info">Física<br>5° Sec - Aula 202</td>
                                        <td></td>
                                        <td class="table-info">Física<br>5° Sec - Aula 202</td>
                                        <td></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Course Detail Modal -->
    <div class="modal fade" id="courseDetailModal" tabindex="-1" aria-labelledby="courseDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="courseDetailModalLabel">Matemáticas - 6° Secundaria</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="courseTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">Información</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="students-tab" data-bs-toggle="tab" data-bs-target="#students" type="button" role="tab" aria-controls="students" aria-selected="false">Estudiantes</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="grades-tab" data-bs-toggle="tab" data-bs-target="#grades" type="button" role="tab" aria-controls="grades" aria-selected="false">Calificaciones</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="attendance-tab" data-bs-toggle="tab" data-bs-target="#attendance" type="button" role="tab" aria-controls="attendance" aria-selected="false">Asistencia</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab" aria-controls="assignments" aria-selected="false">Tareas</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="materials-tab" data-bs-toggle="tab" data-bs-target="#materials" type="button" role="tab" aria-controls="materials" aria-selected="false">Materiales</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3" id="courseTabContent">
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <h5>Información del Curso</h5>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Nombre del curso:</label>
                                        <p>Matemáticas</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Código:</label>
                                        <p>MAT-6S</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Grado:</label>
                                        <p>6° Secundaria</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Aula:</label>
                                        <p>201</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Horario:</label>
                                        <p>Lunes, Miércoles, Viernes 08:00-09:30</p>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Estudiantes:</label>
                                        <p>25</p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h5>Descripción del Curso</h5>
                                    <p>Este curso de Matemáticas para 6° de Secundaria abarca temas avanzados de álgebra, geometría analítica, trigonometría y cálculo diferencial e integral básico. Está diseñado para preparar a los estudiantes para los exámenes de ingreso a la universidad y sentar las bases para estudios superiores en ciencias e ingeniería.</p>
                                    
                                    <h5 class="mt-4">Objetivos del Curso</h5>
                                    <ul>
                                        <li>Desarrollar habilidades de pensamiento lógico y razonamiento matemático.</li>
                                        <li>Aplicar conceptos matemáticos a problemas del mundo real.</li>
                                        <li>Preparar a los estudiantes para los exámenes de ingreso universitario.</li>
                                        <li>Fomentar el trabajo colaborativo y la resolución de problemas.</li>
                                    </ul>
                                    
                                    <h5 class="mt-4">Progreso del Curso</h5>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">75% Completado</div>
                                    </div>
                                    <small class="text-muted">Unidades completadas: 6 de 8</small>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="students" role="tabpanel" aria-labelledby="students-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Lista de Estudiantes</h5>
                                <button class="btn btn-sm btn-academic">
                                    <i class="bi bi-download"></i> Exportar Lista
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nombre</th>
                                            <th>Promedio</th>
                                            <th>Asistencia</th>
                                            <th>Tareas Entregadas</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>EST-001</td>
                                            <td>Alejandro Gómez</td>
                                            <td>86.6</td>
                                            <td>95%</td>
                                            <td>12/12</td>
                                            <td><span class="badge bg-success">Activo</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>EST-002</td>
                                            <td>Carla Mendoza</td>
                                            <td>93.4</td>
                                            <td>98%</td>
                                            <td>12/12</td>
                                            <td><span class="badge bg-success">Activo</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>EST-003</td>
                                            <td>Daniel Flores</td>
                                            <td>70.0</td>
                                            <td>85%</td>
                                            <td>10/12</td>
                                            <td><span class="badge bg-warning">En observación</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>EST-004</td>
                                            <td>Elena Vargas</td>
                                            <td>82.6</td>
                                            <td>92%</td>
                                            <td>11/12</td>
                                            <td><span class="badge bg-success">Activo</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>EST-005</td>
                                            <td>Fernando Quispe</td>
                                            <td>60.0</td>
                                            <td>78%</td>
                                            <td>8/12</td>
                                            <td><span class="badge bg-danger">En riesgo</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Anterior</a>
                                    </li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                                    <li class="page-item"><a class="page-link" href="#">4</a></li>
                                    <li class="page-item"><a class="page-link" href="#">5</a></li>
                                    <li class="page-item">
                                        <a class="page-link" href="#">Siguiente</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <div class="tab-pane fade" id="grades" role="tabpanel" aria-labelledby="grades-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Registro de Calificaciones</h5>
                                <div>
                                    <button class="btn btn-sm btn-success me-2">
                                        <i class="bi bi-plus-circle"></i> Nueva Evaluación
                                    </button>
                                    <button class="btn btn-sm btn-academic">
                                        <i class="bi bi-download"></i> Exportar
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>1er Parcial</th>
                                            <th>2do Parcial</th>
                                            <th>3er Parcial</th>
                                            <th>4to Parcial</th>
                                            <th>Examen Final</th>
                                            <th>Promedio</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Alejandro Gómez</td>
                                            <td>85</td>
                                            <td>78</td>
                                            <td>92</td>
                                            <td>88</td>
                                            <td>90</td>
                                            <td>86.6</td>
                                            <td><span class="badge bg-primary">Bueno</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Carla Mendoza</td>
                                            <td>92</td>
                                            <td>95</td>
                                            <td>90</td>
                                            <td>94</td>
                                            <td>96</td>
                                            <td>93.4</td>
                                            <td><span class="badge bg-success">Excelente</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Daniel Flores</td>
                                            <td>65</td>
                                            <td>70</td>
                                            <td>68</td>
                                            <td>72</td>
                                            <td>75</td>
                                            <td>70.0</td>
                                            <td><span class="badge bg-warning">Regular</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Elena Vargas</td>
                                            <td>78</td>
                                            <td>82</td>
                                            <td>80</td>
                                            <td>85</td>
                                            <td>88</td>
                                            <td>82.6</td>
                                            <td><span class="badge bg-primary">Bueno</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Fernando Quispe</td>
                                            <td>55</td>
                                            <td>60</td>
                                            <td>58</td>
                                            <td>62</td>
                                            <td>65</td>
                                            <td>60.0</td>
                                            <td><span class="badge bg-warning">Regular</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Registro de Asistencia</h5>
                                <div>
                                    <button class="btn btn-sm btn-success me-2">
                                        <i class="bi bi-plus-circle"></i> Registrar Asistencia
                                    </button>
                                    <button class="btn btn-sm btn-academic">
                                        <i class="bi bi-download"></i> Exportar
                                    </button>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">Fecha</span>
                                        <input type="date" class="form-control" value="2025-06-02">
                                        <button class="btn btn-academic" type="button">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Estado</th>
                                            <th>Hora de Entrada</th>
                                            <th>Observaciones</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Alejandro Gómez</td>
                                            <td><span class="badge bg-success">Presente</span></td>
                                            <td>08:00</td>
                                            <td>-</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Carla Mendoza</td>
                                            <td><span class="badge bg-success">Presente</span></td>
                                            <td>08:00</td>
                                            <td>-</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Daniel Flores</td>
                                            <td><span class="badge bg-warning">Tardanza</span></td>
                                            <td>08:15</td>
                                            <td>Problemas de transporte</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Elena Vargas</td>
                                            <td><span class="badge bg-success">Presente</span></td>
                                            <td>08:00</td>
                                            <td>-</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Fernando Quispe</td>
                                            <td><span class="badge bg-danger">Ausente</span></td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="assignments" role="tabpanel" aria-labelledby="assignments-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Tareas Asignadas</h5>
                                <button class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-circle"></i> Nueva Tarea
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Título</th>
                                            <th>Descripción</th>
                                            <th>Fecha de Asignación</th>
                                            <th>Fecha de Entrega</th>
                                            <th>Entregas</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Ejercicios de Límites</td>
                                            <td>Resolver los ejercicios 1-10 del capítulo 3</td>
                                            <td>15/05/2025</td>
                                            <td>22/05/2025</td>
                                            <td>25/25</td>
                                            <td><span class="badge bg-success">Completada</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Derivadas Parciales</td>
                                            <td>Resolver los ejercicios 1-15 del capítulo 4</td>
                                            <td>22/05/2025</td>
                                            <td>29/05/2025</td>
                                            <td>23/25</td>
                                            <td><span class="badge bg-success">Completada</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Integrales Definidas</td>
                                            <td>Resolver los ejercicios 1-12 del capítulo 5</td>
                                            <td>29/05/2025</td>
                                            <td>05/06/2025</td>
                                            <td>20/25</td>
                                            <td><span class="badge bg-warning">En progreso</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Aplicaciones de Integrales</td>
                                            <td>Resolver los problemas 1-8 del capítulo 6</td>
                                            <td>05/06/2025</td>
                                            <td>12/06/2025</td>
                                            <td>0/25</td>
                                            <td><span class="badge bg-secondary">Pendiente</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="materials" role="tabpanel" aria-labelledby="materials-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Materiales del Curso</h5>
                                <button class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-circle"></i> Nuevo Material
                                </button>
                            </div>
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><i class="bi bi-file-pdf me-2 text-danger"></i> Guía de Límites y Continuidad</h6>
                                        <small>Subido: 10/05/2025</small>
                                    </div>
                                    <p class="mb-1">Material de estudio sobre límites y continuidad de funciones.</p>
                                    <small class="text-muted">Tamaño: 2.5 MB</small>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><i class="bi bi-file-pdf me-2 text-danger"></i> Guía de Derivadas</h6>
                                        <small>Subido: 17/05/2025</small>
                                    </div>
                                    <p class="mb-1">Material de estudio sobre derivadas y sus aplicaciones.</p>
                                    <small class="text-muted">Tamaño: 3.2 MB</small>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><i class="bi bi-file-pdf me-2 text-danger"></i> Guía de Integrales</h6>
                                        <small>Subido: 24/05/2025</small>
                                    </div>
                                    <p class="mb-1">Material de estudio sobre integrales definidas e indefinidas.</p>
                                    <small class="text-muted">Tamaño: 4.1 MB</small>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><i class="bi bi-file-earmark-text me-2 text-primary"></i> Ejercicios Adicionales</h6>
                                        <small>Subido: 01/06/2025</small>
                                    </div>
                                    <p class="mb-1">Ejercicios adicionales para práctica y preparación para el examen final.</p>
                                    <small class="text-muted">Tamaño: 1.8 MB</small>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><i class="bi bi-link-45deg me-2 text-success"></i> Recursos en línea</h6>
                                        <small>Subido: 01/06/2025</small>
                                    </div>
                                    <p class="mb-1">Enlaces a recursos en línea para el estudio de cálculo.</p>
                                    <small class="text-muted">10 enlaces</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
