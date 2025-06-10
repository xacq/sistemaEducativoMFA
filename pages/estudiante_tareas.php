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
    <title>Sistema Académico - Tareas Estudiante</title>
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
                    <h1 class="h2">Mis Tareas</h1>
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

                <!-- Tabs for task categories -->
                <ul class="nav nav-tabs mb-4" id="taskTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">Pendientes (4)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="submitted-tab" data-bs-toggle="tab" data-bs-target="#submitted" type="button" role="tab" aria-controls="submitted" aria-selected="false">Entregadas (8)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="graded-tab" data-bs-toggle="tab" data-bs-target="#graded" type="button" role="tab" aria-controls="graded" aria-selected="false">Calificadas (12)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="false">Todas (24)</button>
                    </li>
                </ul>

                <!-- Tab content -->
                <div class="tab-content" id="taskTabsContent">
                    <!-- Pending tasks -->
                    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header card-header-academic">
                                        <h5 class="mb-0 text-white">Tareas Pendientes</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-academic">
                                                    <tr>
                                                        <th>Tarea</th>
                                                        <th>Curso</th>
                                                        <th>Fecha de Entrega</th>
                                                        <th>Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Proyecto Final</td>
                                                        <td>Programación Avanzada</td>
                                                        <td>04/06/2025 (23:59)</td>
                                                        <td><span class="badge bg-danger">Urgente</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-academic" onclick="showTaskModal('Proyecto Final', 'Programación Avanzada')">Entregar</button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Informe de Laboratorio 5</td>
                                                        <td>Redes de Computadoras</td>
                                                        <td>07/06/2025 (23:59)</td>
                                                        <td><span class="badge bg-warning">Próximo</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-academic" onclick="showTaskModal('Informe de Laboratorio 5', 'Redes de Computadoras')">Entregar</button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Ensayo sobre Ética en IA</td>
                                                        <td>Inteligencia Artificial</td>
                                                        <td>10/06/2025 (23:59)</td>
                                                        <td><span class="badge bg-info">Pendiente</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-academic" onclick="showTaskModal('Ensayo sobre Ética en IA', 'Inteligencia Artificial')">Entregar</button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Diseño de Base de Datos</td>
                                                        <td>Bases de Datos</td>
                                                        <td>12/06/2025 (23:59)</td>
                                                        <td><span class="badge bg-info">Pendiente</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-academic" onclick="showTaskModal('Diseño de Base de Datos', 'Bases de Datos')">Entregar</button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submitted tasks -->
                    <div class="tab-pane fade" id="submitted" role="tabpanel" aria-labelledby="submitted-tab">
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header card-header-academic">
                                        <h5 class="mb-0 text-white">Tareas Entregadas</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-academic">
                                                    <tr>
                                                        <th>Tarea</th>
                                                        <th>Curso</th>
                                                        <th>Fecha de Entrega</th>
                                                        <th>Fecha de Envío</th>
                                                        <th>Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Análisis de Algoritmos</td>
                                                        <td>Programación Avanzada</td>
                                                        <td>28/05/2025</td>
                                                        <td>27/05/2025</td>
                                                        <td><span class="badge bg-primary">Entregado</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-secondary">Ver Entrega</button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Diagrama de Clases</td>
                                                        <td>Ingeniería de Software</td>
                                                        <td>25/05/2025</td>
                                                        <td>24/05/2025</td>
                                                        <td><span class="badge bg-primary">Entregado</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-secondary">Ver Entrega</button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Consultas SQL</td>
                                                        <td>Bases de Datos</td>
                                                        <td>20/05/2025</td>
                                                        <td>19/05/2025</td>
                                                        <td><span class="badge bg-primary">Entregado</span></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-secondary">Ver Entrega</button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Graded tasks -->
                    <div class="tab-pane fade" id="graded" role="tabpanel" aria-labelledby="graded-tab">
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header card-header-academic">
                                        <h5 class="mb-0 text-white">Tareas Calificadas</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-academic">
                                                    <tr>
                                                        <th>Tarea</th>
                                                        <th>Curso</th>
                                                        <th>Fecha de Entrega</th>
                                                        <th>Calificación</th>
                                                        <th>Comentarios</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Laboratorio 4</td>
                                                        <td>Programación Avanzada</td>
                                                        <td>15/05/2025</td>
                                                        <td class="fw-bold grade-good">95/100</td>
                                                        <td><i class="bi bi-chat-left-text"></i> 2</td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-secondary">Ver Detalles</button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Casos de Uso</td>
                                                        <td>Ingeniería de Software</td>
                                                        <td>10/05/2025</td>
                                                        <td class="fw-bold grade-good">88/100</td>
                                                        <td><i class="bi bi-chat-left-text"></i> 3</td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-secondary">Ver Detalles</button>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Normalización</td>
                                                        <td>Bases de Datos</td>
                                                        <td>05/05/2025</td>
                                                        <td class="fw-bold grade-warning">75/100</td>
                                                        <td><i class="bi bi-chat-left-text"></i> 4</td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-secondary">Ver Detalles</button>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All tasks -->
                    <div class="tab-pane fade" id="all" role="tabpanel" aria-labelledby="all-tab">
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header card-header-academic">
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <h5 class="mb-0 text-white">Todas las Tareas</h5>
                                            </div>
                                            <div class="col-md-6">
                                                <input type="text" class="form-control form-control-sm" placeholder="Buscar tarea..." id="taskSearch">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-academic">
                                                    <tr>
                                                        <th>Tarea</th>
                                                        <th>Curso</th>
                                                        <th>Fecha de Entrega</th>
                                                        <th>Estado</th>
                                                        <th>Calificación</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <!-- Contenido combinado de todas las pestañas -->
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Task submission modal -->
    <div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-academic text-white">
                    <h5 class="modal-title" id="taskModalLabel">Entregar Tarea</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="taskSubmissionForm">
                        <div class="mb-3">
                            <label for="taskTitle" class="form-label">Tarea</label>
                            <input type="text" class="form-control" id="taskTitle" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="taskCourse" class="form-label">Curso</label>
                            <input type="text" class="form-control" id="taskCourse" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="taskDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="taskDescription" rows="3" readonly>Esta es la descripción detallada de la tarea asignada. Incluye los requisitos, formato esperado y criterios de evaluación.</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="taskComment" class="form-label">Comentario (opcional)</label>
                            <textarea class="form-control" id="taskComment" rows="2" placeholder="Añade un comentario para tu profesor..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="taskFile" class="form-label">Archivo</label>
                            <input class="form-control" type="file" id="taskFile">
                            <div class="form-text">Formatos permitidos: PDF, DOC, DOCX, ZIP. Tamaño máximo: 10MB</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic" onclick="simulateTaskSubmission()">Entregar Tarea</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success alert modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">¡Tarea Entregada!</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tu tarea ha sido entregada correctamente. Recibirás una notificación cuando sea calificada.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para mostrar el modal de entrega de tarea
        function showTaskModal(taskName, courseName) {
            document.getElementById('taskTitle').value = taskName;
            document.getElementById('taskCourse').value = courseName;
            
            // Crear y mostrar el modal usando Bootstrap
            var taskModal = new bootstrap.Modal(document.getElementById('taskModal'));
            taskModal.show();
        }
        
        // Función para simular la entrega de una tarea
        function simulateTaskSubmission() {
            // Cerrar el modal de tarea
            var taskModal = bootstrap.Modal.getInstance(document.getElementById('taskModal'));
            taskModal.hide();
            
            // Mostrar el modal de éxito
            setTimeout(function() {
                var successModal = new bootstrap.Modal(document.getElementById('successModal'));
                successModal.show();
            }, 500);
        }
    </script>
</body>
</html>
