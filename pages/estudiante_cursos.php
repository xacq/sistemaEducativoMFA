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
    <title>Sistema Académico - Cursos Estudiante</title>
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
                            <span class="notification-badge">3</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="estudiante_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="#">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../index.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Semester selector -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="card-title mb-0">Semestre: 2025-1</h5>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                Cambiar Semestre
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#">2025-1 (Actual)</a></li>
                                                <li><a class="dropdown-item" href="#">2024-2</a></li>
                                                <li><a class="dropdown-item" href="#">2024-1</a></li>
                                                <li><a class="dropdown-item" href="#">2023-2</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course cards -->
                <div class="row mb-4">
                    <!-- Course 1 -->
                    <div class="col-md-4 mb-4">
                        <div class="card course-card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Programación Avanzada</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Prof. Carlos Rodríguez</h6>
                                <p class="card-text">Curso avanzado de programación con enfoque en patrones de diseño y arquitectura de software.</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 85%;" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100">85% completado</div>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span><i class="bi bi-award"></i> Calificación: <strong class="grade-good">88.4</strong></span>
                                    <span><i class="bi bi-calendar-check"></i> Asistencia: <strong class="grade-good">92%</strong></span>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" onclick="showCourseDetails('Programación Avanzada')">Ver Detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Course 2 -->
                    <div class="col-md-4 mb-4">
                        <div class="card course-card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Bases de Datos</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Prof. Ana Martínez</h6>
                                <p class="card-text">Diseño y administración de bases de datos relacionales y NoSQL con aplicaciones prácticas.</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 72%;" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100">72% completado</div>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span><i class="bi bi-award"></i> Calificación: <strong class="grade-good">81.8</strong></span>
                                    <span><i class="bi bi-calendar-check"></i> Asistencia: <strong class="grade-good">88%</strong></span>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" onclick="showCourseDetails('Bases de Datos')">Ver Detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Course 3 -->
                    <div class="col-md-4 mb-4">
                        <div class="card course-card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Ingeniería de Software</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Prof. Luis Gómez</h6>
                                <p class="card-text">Metodologías ágiles, gestión de proyectos y ciclo de vida del desarrollo de software.</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: 90%;" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100">90% completado</div>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span><i class="bi bi-award"></i> Calificación: <strong class="grade-good">91.2</strong></span>
                                    <span><i class="bi bi-calendar-check"></i> Asistencia: <strong class="grade-good">100%</strong></span>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" onclick="showCourseDetails('Ingeniería de Software')">Ver Detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Course 4 -->
                    <div class="col-md-4 mb-4">
                        <div class="card course-card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Redes de Computadoras</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Prof. Patricia Vega</h6>
                                <p class="card-text">Fundamentos de redes, protocolos de comunicación y configuración de dispositivos de red.</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 68%;" aria-valuenow="68" aria-valuemin="0" aria-valuemax="100">68% completado</div>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span><i class="bi bi-award"></i> Calificación: <strong class="grade-good">79.6</strong></span>
                                    <span><i class="bi bi-calendar-check"></i> Asistencia: <strong class="grade-warning">83%</strong></span>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" onclick="showCourseDetails('Redes de Computadoras')">Ver Detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Course 5 -->
                    <div class="col-md-4 mb-4">
                        <div class="card course-card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Inteligencia Artificial</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Prof. Roberto Méndez</h6>
                                <p class="card-text">Introducción a la IA, aprendizaje automático y redes neuronales con aplicaciones prácticas.</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 95%;" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100">95% completado</div>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span><i class="bi bi-award"></i> Calificación: <strong class="grade-good">93.4</strong></span>
                                    <span><i class="bi bi-calendar-check"></i> Asistencia: <strong class="grade-good">96%</strong></span>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" onclick="showCourseDetails('Inteligencia Artificial')">Ver Detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Course 6 -->
                    <div class="col-md-4 mb-4">
                        <div class="card course-card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Seguridad Informática</h5>
                            </div>
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Prof. Elena Torres</h6>
                                <p class="card-text">Principios de seguridad, criptografía, análisis de vulnerabilidades y protección de sistemas.</p>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 65%;" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100">65% completado</div>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span><i class="bi bi-award"></i> Calificación: <strong class="grade-warning">71.3</strong></span>
                                    <span><i class="bi bi-calendar-check"></i> Asistencia: <strong class="grade-good">89%</strong></span>
                                </div>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-academic" onclick="showCourseDetails('Seguridad Informática')">Ver Detalles</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Course details modal -->
    <div class="modal fade" id="courseDetailsModal" tabindex="-1" aria-labelledby="courseDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header bg-academic text-white">
                    <h5 class="modal-title" id="courseDetailsModalLabel">Detalles del Curso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="courseDetailsTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab" aria-controls="info" aria-selected="true">Información</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="materials-tab" data-bs-toggle="tab" data-bs-target="#materials" type="button" role="tab" aria-controls="materials" aria-selected="false">Materiales</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="assignments-tab" data-bs-toggle="tab" data-bs-target="#assignments" type="button" role="tab" aria-controls="assignments" aria-selected="false">Tareas</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="grades-tab" data-bs-toggle="tab" data-bs-target="#grades" type="button" role="tab" aria-controls="grades" aria-selected="false">Calificaciones</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="forum-tab" data-bs-toggle="tab" data-bs-target="#forum" type="button" role="tab" aria-controls="forum" aria-selected="false">Foro</button>
                        </li>
                    </ul>
                    <div class="tab-content p-3" id="courseDetailsTabsContent">
                        <!-- Info tab -->
                        <div class="tab-pane fade show active" id="info" role="tabpanel" aria-labelledby="info-tab">
                            <div class="row">
                                <div class="col-md-8">
                                    <h4 id="courseTitle">Cargando...</h4>
                                    <p class="text-muted" id="courseInstructor">Profesor: Cargando...</p>
                                    <h5>Descripción del Curso</h5>
                                    <p id="courseDescription">Cargando descripción del curso...</p>
                                    <h5>Objetivos</h5>
                                    <ul id="courseObjectives">
                                        <li>Cargando objetivos...</li>
                                    </ul>
                                    <h5>Horario</h5>
                                    <p id="courseSchedule">Cargando horario...</p>
                                </div>
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header card-header-academic">
                                            <h5 class="mb-0 text-white">Resumen</h5>
                                        </div>
                                        <div class="card-body">
                                            <p><strong>Código:</strong> <span id="courseCode">XXX-123</span></p>
                                            <p><strong>Créditos:</strong> <span id="courseCredits">4</span></p>
                                            <p><strong>Aula:</strong> <span id="courseRoom">305B</span></p>
                                            <p><strong>Estudiantes:</strong> <span id="courseStudents">32</span></p>
                                            <p><strong>Progreso:</strong> <span id="courseProgress">85%</span></p>
                                            <p><strong>Calificación actual:</strong> <span id="courseGrade">88.4</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Materials tab -->
                        <div class="tab-pane fade" id="materials" role="tabpanel" aria-labelledby="materials-tab">
                            <h4>Materiales del Curso</h4>
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><i class="bi bi-file-pdf"></i> Unidad 1 - Introducción</h5>
                                        <small class="text-muted">01/03/2025</small>
                                    </div>
                                    <p class="mb-1">Material introductorio del curso.</p>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><i class="bi bi-file-pdf"></i> Unidad 2 - Fundamentos</h5>
                                        <small class="text-muted">15/03/2025</small>
                                    </div>
                                    <p class="mb-1">Conceptos fundamentales y teoría básica.</p>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><i class="bi bi-file-pdf"></i> Unidad 3 - Aplicaciones</h5>
                                        <small class="text-muted">01/04/2025</small>
                                    </div>
                                    <p class="mb-1">Aplicaciones prácticas y casos de estudio.</p>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><i class="bi bi-file-pdf"></i> Unidad 4 - Avanzado</h5>
                                        <small class="text-muted">15/04/2025</small>
                                    </div>
                                    <p class="mb-1">Temas avanzados y técnicas especializadas.</p>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1"><i class="bi bi-file-pdf"></i> Unidad 5 - Proyecto Final</h5>
                                        <small class="text-muted">01/05/2025</small>
                                    </div>
                                    <p class="mb-1">Guía para el proyecto final del curso.</p>
                                </a>
                            </div>
                        </div>
                        
                        <!-- Assignments tab -->
                        <div class="tab-pane fade" id="assignments" role="tabpanel" aria-labelledby="assignments-tab">
                            <h4>Tareas del Curso</h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Tarea</th>
                                            <th>Fecha de Entrega</th>
                                            <th>Estado</th>
                                            <th>Calificación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Laboratorio 1</td>
                                            <td>10/03/2025</td>
                                            <td><span class="badge bg-success">Entregado</span></td>
                                            <td>90/100</td>
                                            <td><button class="btn btn-sm btn-outline-secondary">Ver Detalles</button></td>
                                        </tr>
                                        <tr>
                                            <td>Laboratorio 2</td>
                                            <td>24/03/2025</td>
                                            <td><span class="badge bg-success">Entregado</span></td>
                                            <td>85/100</td>
                                            <td><button class="btn btn-sm btn-outline-secondary">Ver Detalles</button></td>
                                        </tr>
                                        <tr>
                                            <td>Laboratorio 3</td>
                                            <td>07/04/2025</td>
                                            <td><span class="badge bg-success">Entregado</span></td>
                                            <td>92/100</td>
                                            <td><button class="btn btn-sm btn-outline-secondary">Ver Detalles</button></td>
                                        </tr>
                                        <tr>
                                            <td>Laboratorio 4</td>
                                            <td>21/04/2025</td>
                                            <td><span class="badge bg-success">Entregado</span></td>
                                            <td>88/100</td>
                                            <td><button class="btn btn-sm btn-outline-secondary">Ver Detalles</button></td>
                                        </tr>
                                        <tr>
                                            <td>Proyecto Final</td>
                                            <td>04/06/2025</td>
                                            <td><span class="badge bg-danger">Pendiente</span></td>
                                            <td>-</td>
                                            <td><button class="btn btn-sm btn-academic">Entregar</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Grades tab -->
                        <div class="tab-pane fade" id="grades" role="tabpanel" aria-labelledby="grades-tab">
                            <h4>Calificaciones del Curso</h4>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Evaluación</th>
                                            <th>Porcentaje</th>
                                            <th>Calificación</th>
                                            <th>Ponderado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Parcial 1</td>
                                            <td>20%</td>
                                            <td>85/100</td>
                                            <td>17.0</td>
                                        </tr>
                                        <tr>
                                            <td>Parcial 2</td>
                                            <td>20%</td>
                                            <td>90/100</td>
                                            <td>18.0</td>
                                        </tr>
                                        <tr>
                                            <td>Laboratorios</td>
                                            <td>30%</td>
                                            <td>88/100</td>
                                            <td>26.4</td>
                                        </tr>
                                        <tr>
                                            <td>Proyecto Final</td>
                                            <td>20%</td>
                                            <td>Pendiente</td>
                                            <td>-</td>
                                        </tr>
                                        <tr>
                                            <td>Examen Final</td>
                                            <td>10%</td>
                                            <td>Pendiente</td>
                                            <td>-</td>
                                        </tr>
                                        <tr class="table-active">
                                            <td colspan="2"><strong>Total Actual</strong></td>
                                            <td colspan="2"><strong>61.4/70 (87.7%)</strong></td>
                                        </tr>
                                        <tr class="table-active">
                                            <td colspan="2"><strong>Proyección Final</strong></td>
                                            <td colspan="2"><strong>88.4/100</strong></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Forum tab -->
                        <div class="tab-pane fade" id="forum" role="tabpanel" aria-labelledby="forum-tab">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4>Foro del Curso</h4>
                                <button class="btn btn-academic">Nuevo Tema</button>
                            </div>
                            <div class="list-group">
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Dudas sobre el Proyecto Final</h5>
                                        <small class="text-muted">Hace 2 días</small>
                                    </div>
                                    <p class="mb-1">Tengo algunas dudas sobre los requerimientos del proyecto final...</p>
                                    <small><i class="bi bi-chat-left-text"></i> 8 respuestas</small>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Error en el Laboratorio 4</h5>
                                        <small class="text-muted">Hace 5 días</small>
                                    </div>
                                    <p class="mb-1">Estoy teniendo un problema con el código del Laboratorio 4...</p>
                                    <small><i class="bi bi-chat-left-text"></i> 12 respuestas</small>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Recursos adicionales para la Unidad 3</h5>
                                        <small class="text-muted">Hace 1 semana</small>
                                    </div>
                                    <p class="mb-1">Comparto algunos recursos que encontré sobre los temas de la Unidad 3...</p>
                                    <small><i class="bi bi-chat-left-text"></i> 5 respuestas</small>
                                </a>
                                <a href="#" class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h5 class="mb-1">Grupo de estudio para el examen final</h5>
                                        <small class="text-muted">Hace 2 semanas</small>
                                    </div>
                                    <p class="mb-1">¿Alguien interesado en formar un grupo de estudio para el examen final?</p>
                                    <small><i class="bi bi-chat-left-text"></i> 15 respuestas</small>
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

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        // Datos simulados para los cursos
        const courseData = {
            "Programación Avanzada": {
                title: "Programación Avanzada",
                instructor: "Prof. Carlos Rodríguez",
                description: "Este curso profundiza en conceptos avanzados de programación, incluyendo patrones de diseño, arquitectura de software, programación concurrente y optimización de algoritmos. Los estudiantes desarrollarán habilidades para crear aplicaciones robustas y eficientes.",
                objectives: [
                    "Implementar patrones de diseño en aplicaciones reales",
                    "Desarrollar software utilizando principios SOLID",
                    "Optimizar algoritmos para mejorar rendimiento",
                    "Crear aplicaciones concurrentes y paralelas"
                ],
                schedule: "Lunes y Miércoles, 10:00 AM - 12:00 PM, Aula 305B",
                code: "CS-301",
                credits: 4,
                room: "305B",
                students: 32,
                progress: "85%",
                grade: "88.4"
            },
            "Bases de Datos": {
                title: "Bases de Datos",
                instructor: "Prof. Ana Martínez",
                description: "Curso enfocado en el diseño, implementación y administración de bases de datos relacionales y NoSQL. Se abordan temas como normalización, optimización de consultas, transacciones y seguridad de datos.",
                objectives: [
                    "Diseñar bases de datos normalizadas",
                    "Implementar consultas SQL eficientes",
                    "Administrar sistemas de bases de datos",
                    "Trabajar con bases de datos NoSQL"
                ],
                schedule: "Martes y Jueves, 2:00 PM - 4:00 PM, Aula 210A",
                code: "CS-302",
                credits: 4,
                room: "210A",
                students: 28,
                progress: "72%",
                grade: "81.8"
            },
            "Ingeniería de Software": {
                title: "Ingeniería de Software",
                instructor: "Prof. Luis Gómez",
                description: "Estudio de metodologías, técnicas y herramientas para el desarrollo de software de calidad. Incluye gestión de proyectos, requisitos, diseño, implementación, pruebas y mantenimiento.",
                objectives: [
                    "Aplicar metodologías ágiles en proyectos de software",
                    "Gestionar requisitos y documentación técnica",
                    "Implementar pruebas automatizadas",
                    "Desarrollar software siguiendo estándares de calidad"
                ],
                schedule: "Miércoles y Viernes, 8:00 AM - 10:00 AM, Aula 405",
                code: "CS-303",
                credits: 4,
                room: "405",
                students: 35,
                progress: "90%",
                grade: "91.2"
            },
            "Redes de Computadoras": {
                title: "Redes de Computadoras",
                instructor: "Prof. Patricia Vega",
                description: "Fundamentos de redes de computadoras, protocolos de comunicación, arquitecturas de red, seguridad y configuración de dispositivos. Incluye laboratorios prácticos de configuración.",
                objectives: [
                    "Comprender los modelos OSI y TCP/IP",
                    "Configurar dispositivos de red",
                    "Implementar protocolos de enrutamiento",
                    "Diseñar redes seguras y eficientes"
                ],
                schedule: "Lunes y Jueves, 4:00 PM - 6:00 PM, Laboratorio 110",
                code: "CS-304",
                credits: 4,
                room: "110",
                students: 30,
                progress: "68%",
                grade: "79.6"
            },
            "Inteligencia Artificial": {
                title: "Inteligencia Artificial",
                instructor: "Prof. Roberto Méndez",
                description: "Introducción a los conceptos fundamentales de la inteligencia artificial, incluyendo aprendizaje automático, redes neuronales, procesamiento de lenguaje natural y visión por computadora.",
                objectives: [
                    "Implementar algoritmos de aprendizaje automático",
                    "Desarrollar redes neuronales para clasificación",
                    "Aplicar técnicas de IA a problemas reales",
                    "Evaluar y optimizar modelos de IA"
                ],
                schedule: "Martes y Viernes, 10:00 AM - 12:00 PM, Aula 405",
                code: "CS-305",
                credits: 4,
                room: "405",
                students: 29,
                progress: "95%",
                grade: "93.4"
            },
            "Seguridad Informática": {
                title: "Seguridad Informática",
                instructor: "Prof. Elena Torres",
                description: "Estudio de principios y prácticas de seguridad informática, incluyendo criptografía, análisis de vulnerabilidades, protección de sistemas y respuesta a incidentes.",
                objectives: [
                    "Implementar mecanismos de seguridad en sistemas",
                    "Realizar análisis de vulnerabilidades",
                    "Aplicar técnicas criptográficas",
                    "Desarrollar planes de respuesta a incidentes"
                ],
                schedule: "Lunes y Jueves, 2:00 PM - 4:00 PM, Aula 110",
                code: "CS-306",
                credits: 4,
                room: "110",
                students: 35,
                progress: "65%",
                grade: "71.3"
            }
        };

        // Función para mostrar los detalles del curso
        function showCourseDetails(courseName) {
            const course = courseData[courseName];
            
            // Actualizar la información del curso en el modal
            document.getElementById('courseTitle').textContent = course.title;
            document.getElementById('courseInstructor').textContent = `Profesor: ${course.instructor}`;
            document.getElementById('courseDescription').textContent = course.description;
            
            // Actualizar los objetivos
            const objectivesList = document.getElementById('courseObjectives');
            objectivesList.innerHTML = '';
            course.objectives.forEach(objective => {
                const li = document.createElement('li');
                li.textContent = objective;
                objectivesList.appendChild(li);
            });
            
            document.getElementById('courseSchedule').textContent = course.schedule;
            document.getElementById('courseCode').textContent = course.code;
            document.getElementById('courseCredits').textContent = course.credits;
            document.getElementById('courseRoom').textContent = course.room;
            document.getElementById('courseStudents').textContent = course.students;
            document.getElementById('courseProgress').textContent = course.progress;
            document.getElementById('courseGrade').textContent = course.grade;
            
            // Mostrar el modal
            var courseModal = new bootstrap.Modal(document.getElementById('courseDetailsModal'));
            courseModal.show();
        }
    </script>
</body>
</html>
