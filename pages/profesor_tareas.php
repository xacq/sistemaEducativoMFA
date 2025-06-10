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
    <title>Sistema Académico - Profesor Tareas</title>
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
                    <h1 class="h2">Gestión de Tareas</h1>
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

                <!-- Filter and Search -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text">Curso</span>
                            <select class="form-select" id="courseSelect">
                                <option selected>Matemáticas - 6° Secundaria</option>
                                <option>Matemáticas - 5° Secundaria</option>
                                <option>Física - 6° Secundaria</option>
                                <option>Física - 5° Secundaria</option>
                                <option>Química - 6° Secundaria</option>
                                <option>Química - 5° Secundaria</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text">Estado</span>
                            <select class="form-select">
                                <option selected>Todos</option>
                                <option>Activas</option>
                                <option>Pendientes de calificar</option>
                                <option>Calificadas</option>
                                <option>Vencidas</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text">Periodo</span>
                            <select class="form-select">
                                <option selected>Junio 2025</option>
                                <option>Mayo 2025</option>
                                <option>Abril 2025</option>
                                <option>Marzo 2025</option>
                                <option>Febrero 2025</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Buscar tarea...">
                            <button class="btn btn-academic" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                            <i class="bi bi-plus-circle"></i> Nueva Tarea
                        </button>
                        
                    </div>
                </div>

                <!-- Active Assignments -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Tareas Activas - Matemáticas 6° Secundaria</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Título</th>
                                        <th>Tipo</th>
                                        <th>Fecha de Asignación</th>
                                        <th>Fecha de Entrega</th>
                                        <th>Estado</th>
                                        <th>Entregas</th>
                                        <th>Calificadas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Ejercicios de Límites</td>
                                        <td>Práctica</td>
                                        <td>15/05/2025</td>
                                        <td>22/05/2025</td>
                                        <td><span class="badge bg-success">Completada</span></td>
                                        <td>25/25</td>
                                        <td>25/25</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewAssignmentModal"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Derivadas Parciales</td>
                                        <td>Tarea</td>
                                        <td>22/05/2025</td>
                                        <td>29/05/2025</td>
                                        <td><span class="badge bg-primary">Calificando</span></td>
                                        <td>23/25</td>
                                        <td>15/23</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Integrales Definidas</td>
                                        <td>Proyecto</td>
                                        <td>29/05/2025</td>
                                        <td>05/06/2025</td>
                                        <td><span class="badge bg-warning">Activa</span></td>
                                        <td>10/25</td>
                                        <td>0/10</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Aplicaciones de Integrales</td>
                                        <td>Tarea</td>
                                        <td>01/06/2025</td>
                                        <td>08/06/2025</td>
                                        <td><span class="badge bg-warning">Activa</span></td>
                                        <td>5/25</td>
                                        <td>0/5</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Ecuaciones Diferenciales</td>
                                        <td>Proyecto</td>
                                        <td>05/06/2025</td>
                                        <td>19/06/2025</td>
                                        <td><span class="badge bg-info">Programada</span></td>
                                        <td>0/25</td>
                                        <td>0/0</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pending Grading -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Tareas Pendientes de Calificar</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Curso</th>
                                        <th>Tarea</th>
                                        <th>Estudiante</th>
                                        <th>Fecha de Entrega</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Matemáticas - 6° Secundaria</td>
                                        <td>Derivadas Parciales</td>
                                        <td>Alejandro Gómez</td>
                                        <td>28/05/2025</td>
                                        <td><span class="badge bg-warning">Pendiente</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#gradeAssignmentModal"><i class="bi bi-check-circle"></i> Calificar</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Matemáticas - 6° Secundaria</td>
                                        <td>Derivadas Parciales</td>
                                        <td>Carla Mendoza</td>
                                        <td>27/05/2025</td>
                                        <td><span class="badge bg-warning">Pendiente</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check-circle"></i> Calificar</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Matemáticas - 6° Secundaria</td>
                                        <td>Derivadas Parciales</td>
                                        <td>Daniel Flores</td>
                                        <td>29/05/2025</td>
                                        <td><span class="badge bg-warning">Pendiente</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check-circle"></i> Calificar</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Matemáticas - 6° Secundaria</td>
                                        <td>Derivadas Parciales</td>
                                        <td>Elena Vargas</td>
                                        <td>28/05/2025</td>
                                        <td><span class="badge bg-warning">Pendiente</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check-circle"></i> Calificar</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Matemáticas - 6° Secundaria</td>
                                        <td>Derivadas Parciales</td>
                                        <td>Fernando Quispe</td>
                                        <td>29/05/2025</td>
                                        <td><span class="badge bg-warning">Pendiente</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-check-circle"></i> Calificar</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Assignment Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Estadísticas de Tareas</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Curso</th>
                                                <th>Total Tareas</th>
                                                <th>Activas</th>
                                                <th>Completadas</th>
                                                <th>Promedio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Matemáticas - 6° Secundaria</td>
                                                <td>12</td>
                                                <td>3</td>
                                                <td>9</td>
                                                <td>85.6</td>
                                            </tr>
                                            <tr>
                                                <td>Matemáticas - 5° Secundaria</td>
                                                <td>10</td>
                                                <td>2</td>
                                                <td>8</td>
                                                <td>83.2</td>
                                            </tr>
                                            <tr>
                                                <td>Física - 6° Secundaria</td>
                                                <td>8</td>
                                                <td>2</td>
                                                <td>6</td>
                                                <td>82.5</td>
                                            </tr>
                                            <tr>
                                                <td>Física - 5° Secundaria</td>
                                                <td>8</td>
                                                <td>1</td>
                                                <td>7</td>
                                                <td>80.8</td>
                                            </tr>
                                            <tr>
                                                <td>Química - 6° Secundaria</td>
                                                <td>7</td>
                                                <td>2</td>
                                                <td>5</td>
                                                <td>81.3</td>
                                            </tr>
                                            <tr>
                                                <td>Química - 5° Secundaria</td>
                                                <td>7</td>
                                                <td>1</td>
                                                <td>6</td>
                                                <td>79.5</td>
                                            </tr>
                                            <tr class="table-academic">
                                                <td><strong>Total</strong></td>
                                                <td><strong>52</strong></td>
                                                <td><strong>11</strong></td>
                                                <td><strong>41</strong></td>
                                                <td><strong>82.2</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Estudiantes con Tareas Pendientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Estudiante</th>
                                                <th>Curso</th>
                                                <th>Tareas Pendientes</th>
                                                <th>Tareas Vencidas</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Fernando Quispe</td>
                                                <td>Matemáticas - 6° Secundaria</td>
                                                <td>2</td>
                                                <td>1</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-warning"><i class="bi bi-chat-dots"></i></button>
                                                    <button class="btn btn-sm btn-outline-info"><i class="bi bi-envelope"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Luis Mamani</td>
                                                <td>Física - 5° Secundaria</td>
                                                <td>3</td>
                                                <td>2</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-warning"><i class="bi bi-chat-dots"></i></button>
                                                    <button class="btn btn-sm btn-outline-info"><i class="bi bi-envelope"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Patricia Flores</td>
                                                <td>Química - 6° Secundaria</td>
                                                <td>2</td>
                                                <td>0</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-warning"><i class="bi bi-chat-dots"></i></button>
                                                    <button class="btn btn-sm btn-outline-info"><i class="bi bi-envelope"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Jorge Apaza</td>
                                                <td>Física - 6° Secundaria</td>
                                                <td>1</td>
                                                <td>1</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-warning"><i class="bi bi-chat-dots"></i></button>
                                                    <button class="btn btn-sm btn-outline-info"><i class="bi bi-envelope"></i></button>
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
        </div>
    </div>

    <!-- New Assignment Modal -->
    <div class="modal fade" id="newAssignmentModal" tabindex="-1" aria-labelledby="newAssignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="newAssignmentModalLabel">Nueva Tarea</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="assignmentTitle" class="form-label">Título</label>
                                <input type="text" class="form-control" id="assignmentTitle" placeholder="Ej: Ejercicios de Límites" required>
                            </div>
                            <div class="col-md-6">
                                <label for="assignmentCourse" class="form-label">Curso</label>
                                <select class="form-select" id="assignmentCourse" required>
                                    <option selected disabled value="">Seleccionar curso...</option>
                                    <option>Matemáticas - 6° Secundaria</option>
                                    <option>Matemáticas - 5° Secundaria</option>
                                    <option>Física - 6° Secundaria</option>
                                    <option>Física - 5° Secundaria</option>
                                    <option>Química - 6° Secundaria</option>
                                    <option>Química - 5° Secundaria</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="assignmentType" class="form-label">Tipo</label>
                                <select class="form-select" id="assignmentType" required>
                                    <option selected disabled value="">Seleccionar tipo...</option>
                                    <option>Tarea</option>
                                    <option>Práctica</option>
                                    <option>Proyecto</option>
                                    <option>Investigación</option>
                                    <option>Cuestionario</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="assignmentWeight" class="form-label">Ponderación (%)</label>
                                <input type="number" class="form-control" id="assignmentWeight" min="1" max="100" value="10" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="assignmentStartDate" class="form-label">Fecha de Asignación</label>
                                <input type="date" class="form-control" id="assignmentStartDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="assignmentDueDate" class="form-label">Fecha de Entrega</label>
                                <input type="date" class="form-control" id="assignmentDueDate" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="assignmentDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="assignmentDescription" rows="3" placeholder="Descripción detallada de la tarea..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="assignmentInstructions" class="form-label">Instrucciones</label>
                            <textarea class="form-control" id="assignmentInstructions" rows="3" placeholder="Instrucciones específicas para completar la tarea..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="assignmentResources" class="form-label">Recursos</label>
                            <textarea class="form-control" id="assignmentResources" rows="2" placeholder="Enlaces, libros, o materiales de referencia..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="assignmentAttachments" class="form-label">Archivos Adjuntos</label>
                            <input class="form-control" type="file" id="assignmentAttachments" multiple>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="assignmentMaxScore" class="form-label">Puntaje Máximo</label>
                                <input type="number" class="form-control" id="assignmentMaxScore" min="1" value="100" required>
                            </div>
                            <div class="col-md-6">
                                <label for="assignmentSubmissionType" class="form-label">Tipo de Entrega</label>
                                <select class="form-select" id="assignmentSubmissionType" required>
                                    <option selected>Archivo</option>
                                    <option>Texto en línea</option>
                                    <option>Ambos</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notifyStudentsAssignment" checked>
                                <label class="form-check-label" for="notifyStudentsAssignment">
                                    Notificar a los estudiantes
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic">Guardar Tarea</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Assignment Modal -->
    <div class="modal fade" id="viewAssignmentModal" tabindex="-1" aria-labelledby="viewAssignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="viewAssignmentModalLabel">Ejercicios de Límites</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="assignmentTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">Detalles</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="submissions-tab" data-bs-toggle="tab" data-bs-target="#submissions" type="button" role="tab" aria-controls="submissions" aria-selected="false">Entregas</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="grades-tab" data-bs-toggle="tab" data-bs-target="#grades" type="button" role="tab" aria-controls="grades" aria-selected="false">Calificaciones</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="statistics-tab" data-bs-toggle="tab" data-bs-target="#statistics" type="button" role="tab" aria-controls="statistics" aria-selected="false">Estadísticas</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3" id="assignmentTabContent">
                        <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5>Información General</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p><strong>Curso:</strong> Matemáticas - 6° Secundaria</p>
                                            <p><strong>Tipo:</strong> Práctica</p>
                                            <p><strong>Fecha de Asignación:</strong> 15/05/2025</p>
                                            <p><strong>Fecha de Entrega:</strong> 22/05/2025</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Estado:</strong> <span class="badge bg-success">Completada</span></p>
                                            <p><strong>Ponderación:</strong> 10%</p>
                                            <p><strong>Puntaje Máximo:</strong> 100</p>
                                            <p><strong>Tipo de Entrega:</strong> Archivo</p>
                                        </div>
                                    </div>
                                    
                                    <h5>Descripción</h5>
                                    <p>Esta tarea consiste en resolver una serie de ejercicios sobre límites y continuidad de funciones. Los estudiantes deberán aplicar las propiedades de los límites y las técnicas de cálculo para resolver problemas de diferentes niveles de dificultad.</p>
                                    
                                    <h5>Instrucciones</h5>
                                    <ol>
                                        <li>Resolver todos los ejercicios propuestos en el documento adjunto.</li>
                                        <li>Mostrar todos los pasos del procedimiento de manera clara y ordenada.</li>
                                        <li>Utilizar la notación matemática correcta.</li>
                                        <li>Entregar el trabajo en formato PDF.</li>
                                        <li>Nombrar el archivo con el siguiente formato: Apellido_Nombre_Limites.pdf</li>
                                    </ol>
                                    
                                    <h5>Recursos</h5>
                                    <ul>
                                        <li>Libro de texto: Cálculo Diferencial e Integral, Capítulo 2</li>
                                        <li>Material de clase: Presentación sobre límites y continuidad</li>
                                        <li>Videos explicativos disponibles en la plataforma</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <div class="card mb-3">
                                        <div class="card-header card-header-academic">
                                            <h6 class="mb-0 text-white">Archivos Adjuntos</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group">
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="bi bi-file-pdf me-2"></i>
                                                        Ejercicios_Limites.pdf
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="bi bi-file-earmark-text me-2"></i>
                                                        Guia_Solucion.docx
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="card mb-3">
                                        <div class="card-header card-header-academic">
                                            <h6 class="mb-0 text-white">Resumen de Entregas</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Total de Estudiantes:</span>
                                                <strong>25</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Entregas Recibidas:</span>
                                                <strong>25/25 (100%)</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Entregas Calificadas:</span>
                                                <strong>25/25 (100%)</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Entregas a Tiempo:</span>
                                                <strong>23/25 (92%)</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Entregas Tardías:</span>
                                                <strong>2/25 (8%)</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Promedio de Calificación:</span>
                                                <strong>85.6/100</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="submissions" role="tabpanel" aria-labelledby="submissions-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Entregas de Estudiantes</h5>
                                <button class="btn btn-sm btn-academic">
                                    <i class="bi bi-download"></i> Descargar Todas
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Fecha de Entrega</th>
                                            <th>Estado</th>
                                            <th>Archivo</th>
                                            <th>Calificación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Alejandro Gómez</td>
                                            <td>21/05/2025 15:45</td>
                                            <td><span class="badge bg-success">A tiempo</span></td>
                                            <td>
                                                <a href="#"><i class="bi bi-file-pdf me-1"></i>Gomez_Alejandro_Limites.pdf</a>
                                            </td>
                                            <td>90/100</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Carla Mendoza</td>
                                            <td>20/05/2025 10:30</td>
                                            <td><span class="badge bg-success">A tiempo</span></td>
                                            <td>
                                                <a href="#"><i class="bi bi-file-pdf me-1"></i>Mendoza_Carla_Limites.pdf</a>
                                            </td>
                                            <td>95/100</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Daniel Flores</td>
                                            <td>22/05/2025 23:15</td>
                                            <td><span class="badge bg-warning">Último minuto</span></td>
                                            <td>
                                                <a href="#"><i class="bi bi-file-pdf me-1"></i>Flores_Daniel_Limites.pdf</a>
                                            </td>
                                            <td>75/100</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Elena Vargas</td>
                                            <td>21/05/2025 18:20</td>
                                            <td><span class="badge bg-success">A tiempo</span></td>
                                            <td>
                                                <a href="#"><i class="bi bi-file-pdf me-1"></i>Vargas_Elena_Limites.pdf</a>
                                            </td>
                                            <td>88/100</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Fernando Quispe</td>
                                            <td>23/05/2025 10:05</td>
                                            <td><span class="badge bg-danger">Tardía</span></td>
                                            <td>
                                                <a href="#"><i class="bi bi-file-pdf me-1"></i>Quispe_Fernando_Limites.pdf</a>
                                            </td>
                                            <td>65/100</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="grades" role="tabpanel" aria-labelledby="grades-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Calificaciones</h5>
                                <div>
                                    <button class="btn btn-sm btn-primary me-2">
                                        <i class="bi bi-download"></i> Exportar
                                    </button>
                                    <button class="btn btn-sm btn-secondary">
                                        <i class="bi bi-printer"></i> Imprimir
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Calificación</th>
                                            <th>Porcentaje</th>
                                            <th>Estado</th>
                                            <th>Comentarios</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Alejandro Gómez</td>
                                            <td>90/100</td>
                                            <td>90%</td>
                                            <td><span class="badge bg-success">Excelente</span></td>
                                            <td>Excelente trabajo, muy completo y bien presentado.</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Carla Mendoza</td>
                                            <td>95/100</td>
                                            <td>95%</td>
                                            <td><span class="badge bg-success">Excelente</span></td>
                                            <td>Trabajo sobresaliente, con soluciones creativas y bien fundamentadas.</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Daniel Flores</td>
                                            <td>75/100</td>
                                            <td>75%</td>
                                            <td><span class="badge bg-warning">Regular</span></td>
                                            <td>Trabajo aceptable, pero con algunos errores conceptuales.</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Elena Vargas</td>
                                            <td>88/100</td>
                                            <td>88%</td>
                                            <td><span class="badge bg-primary">Bueno</span></td>
                                            <td>Buen trabajo, con procedimientos claros y bien explicados.</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Fernando Quispe</td>
                                            <td>65/100</td>
                                            <td>65%</td>
                                            <td><span class="badge bg-danger">Insuficiente</span></td>
                                            <td>Trabajo incompleto y con varios errores. Entrega tardía.</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-academic">
                                        <tr>
                                            <td><strong>Promedio</strong></td>
                                            <td><strong>82.6/100</strong></td>
                                            <td><strong>82.6%</strong></td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="statistics" role="tabpanel" aria-labelledby="statistics-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header card-header-academic">
                                            <h6 class="mb-0 text-white">Distribución de Calificaciones</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead class="table-academic">
                                                        <tr>
                                                            <th>Rango</th>
                                                            <th>Categoría</th>
                                                            <th>Estudiantes</th>
                                                            <th>Porcentaje</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>90-100</td>
                                                            <td><span class="badge bg-success">Excelente</span></td>
                                                            <td>5</td>
                                                            <td>20%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>80-89</td>
                                                            <td><span class="badge bg-primary">Bueno</span></td>
                                                            <td>10</td>
                                                            <td>40%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>70-79</td>
                                                            <td><span class="badge bg-info">Satisfactorio</span></td>
                                                            <td>6</td>
                                                            <td>24%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>60-69</td>
                                                            <td><span class="badge bg-warning">Regular</span></td>
                                                            <td>3</td>
                                                            <td>12%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>0-59</td>
                                                            <td><span class="badge bg-danger">Insuficiente</span></td>
                                                            <td>1</td>
                                                            <td>4%</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header card-header-academic">
                                            <h6 class="mb-0 text-white">Estadísticas de Entrega</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead class="table-academic">
                                                        <tr>
                                                            <th>Categoría</th>
                                                            <th>Cantidad</th>
                                                            <th>Porcentaje</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Entregas Anticipadas (>24h)</td>
                                                            <td>15</td>
                                                            <td>60%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Entregas a Tiempo (último día)</td>
                                                            <td>8</td>
                                                            <td>32%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Entregas Tardías</td>
                                                            <td>2</td>
                                                            <td>8%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>No Entregadas</td>
                                                            <td>0</td>
                                                            <td>0%</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4">
                                <div class="card-header card-header-academic">
                                    <h6 class="mb-0 text-white">Análisis de Dificultad</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-academic">
                                                <tr>
                                                    <th>Ejercicio</th>
                                                    <th>Promedio</th>
                                                    <th>% de Acierto</th>
                                                    <th>Dificultad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Ejercicio 1: Límites algebraicos</td>
                                                    <td>9.2/10</td>
                                                    <td>92%</td>
                                                    <td><span class="badge bg-success">Fácil</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Ejercicio 2: Límites trigonométricos</td>
                                                    <td>8.5/10</td>
                                                    <td>85%</td>
                                                    <td><span class="badge bg-primary">Moderado</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Ejercicio 3: Límites con indeterminaciones</td>
                                                    <td>7.2/10</td>
                                                    <td>72%</td>
                                                    <td><span class="badge bg-primary">Moderado</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Ejercicio 4: Límites laterales</td>
                                                    <td>8.8/10</td>
                                                    <td>88%</td>
                                                    <td><span class="badge bg-primary">Moderado</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Ejercicio 5: Continuidad de funciones</td>
                                                    <td>6.5/10</td>
                                                    <td>65%</td>
                                                    <td><span class="badge bg-warning">Difícil</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-academic">Generar Reporte</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Grade Assignment Modal -->
    <div class="modal fade" id="gradeAssignmentModal" tabindex="-1" aria-labelledby="gradeAssignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="gradeAssignmentModalLabel">Calificar Tarea: Derivadas Parciales</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Información de la Entrega</h6>
                            <p><strong>Estudiante:</strong> Alejandro Gómez</p>
                            <p><strong>Fecha de Entrega:</strong> 28/05/2025 15:30</p>
                            <p><strong>Estado:</strong> <span class="badge bg-success">A tiempo</span></p>
                            <p><strong>Archivo:</strong> <a href="#"><i class="bi bi-file-pdf me-1"></i>Gomez_Alejandro_Derivadas.pdf</a></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Información de la Tarea</h6>
                            <p><strong>Título:</strong> Derivadas Parciales</p>
                            <p><strong>Curso:</strong> Matemáticas - 6° Secundaria</p>
                            <p><strong>Fecha Límite:</strong> 29/05/2025</p>
                            <p><strong>Puntaje Máximo:</strong> 100</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Vista Previa del Documento</h6>
                                </div>
                                <div class="card-body text-center">
                                    <p class="text-muted">Vista previa del documento PDF</p>
                                    <img src="https://via.placeholder.com/600x400" class="img-fluid border" alt="Vista previa del documento">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gradeValue" class="form-label">Calificación</label>
                                <input type="number" class="form-control" id="gradeValue" min="0" max="100" value="85">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gradeStatus" class="form-label">Estado</label>
                                <select class="form-select" id="gradeStatus">
                                    <option value="excellent">Excelente (90-100)</option>
                                    <option selected value="good">Bueno (80-89)</option>
                                    <option value="satisfactory">Satisfactorio (70-79)</option>
                                    <option value="regular">Regular (60-69)</option>
                                    <option value="insufficient">Insuficiente (0-59)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="gradeComments" class="form-label">Comentarios</label>
                        <textarea class="form-control" id="gradeComments" rows="4" placeholder="Comentarios para el estudiante...">Buen trabajo en general. Los ejercicios 1-3 están perfectos. En el ejercicio 4 hay un error en el cálculo de la derivada parcial respecto a y. El ejercicio 5 está incompleto. Revisa los conceptos de derivadas parciales de segundo orden.</textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="notifyStudent" checked>
                            <label class="form-check-label" for="notifyStudent">
                                Notificar al estudiante
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic">Guardar Calificación</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
