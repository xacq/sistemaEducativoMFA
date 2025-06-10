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
    <title>Sistema Académico - Profesor Estudiantes</title>
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
                    <h1 class="h2">Estudiantes</h1>
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
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Buscar estudiante por nombre, ID o curso...">
                            <button class="btn btn-academic" type="button">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-funnel"></i> Filtrar por Curso
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Todos los cursos</a></li>
                                <li><a class="dropdown-item" href="#">Matemáticas - 6° Secundaria</a></li>
                                <li><a class="dropdown-item" href="#">Matemáticas - 5° Secundaria</a></li>
                                <li><a class="dropdown-item" href="#">Física - 6° Secundaria</a></li>
                                <li><a class="dropdown-item" href="#">Física - 5° Secundaria</a></li>
                                <li><a class="dropdown-item" href="#">Química - 6° Secundaria</a></li>
                                <li><a class="dropdown-item" href="#">Química - 5° Secundaria</a></li>
                            </ul>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-sort-down"></i> Ordenar por
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">Nombre (A-Z)</a></li>
                                <li><a class="dropdown-item" href="#">Nombre (Z-A)</a></li>
                                <li><a class="dropdown-item" href="#">Promedio (Mayor a menor)</a></li>
                                <li><a class="dropdown-item" href="#">Promedio (Menor a mayor)</a></li>
                                <li><a class="dropdown-item" href="#">Asistencia (Mayor a menor)</a></li>
                                <li><a class="dropdown-item" href="#">Asistencia (Menor a mayor)</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-white">Lista de Estudiantes</h5>
                            
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Curso</th>
                                        <th>Grado</th>
                                        <th>Promedio</th>
                                        <th>Asistencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>EST-001</td>
                                        <td>Alejandro Gómez</td>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>86.6</td>
                                        <td>95%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#studentDetailModal"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-002</td>
                                        <td>Carla Mendoza</td>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>93.4</td>
                                        <td>98%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-003</td>
                                        <td>Daniel Flores</td>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>70.0</td>
                                        <td>85%</td>
                                        <td><span class="badge bg-warning">En observación</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-004</td>
                                        <td>Elena Vargas</td>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>82.6</td>
                                        <td>92%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-005</td>
                                        <td>Fernando Quispe</td>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>60.0</td>
                                        <td>78%</td>
                                        <td><span class="badge bg-danger">En riesgo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-006</td>
                                        <td>Gabriela Mamani</td>
                                        <td>Matemáticas</td>
                                        <td>5° Secundaria</td>
                                        <td>88.5</td>
                                        <td>96%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-007</td>
                                        <td>Hugo Condori</td>
                                        <td>Matemáticas</td>
                                        <td>5° Secundaria</td>
                                        <td>75.8</td>
                                        <td>88%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-008</td>
                                        <td>Isabel Choque</td>
                                        <td>Física</td>
                                        <td>6° Secundaria</td>
                                        <td>84.2</td>
                                        <td>93%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-009</td>
                                        <td>Jorge Apaza</td>
                                        <td>Física</td>
                                        <td>6° Secundaria</td>
                                        <td>72.5</td>
                                        <td>85%</td>
                                        <td><span class="badge bg-warning">En observación</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-010</td>
                                        <td>Karen Huanca</td>
                                        <td>Química</td>
                                        <td>5° Secundaria</td>
                                        <td>90.1</td>
                                        <td>97%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
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
                </div>

                <!-- Student Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Distribución de Rendimiento</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Rango de Calificación</th>
                                                <th>Cantidad de Estudiantes</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Excelente (90-100)</td>
                                                <td>15</td>
                                                <td>10%</td>
                                            </tr>
                                            <tr>
                                                <td>Muy Bueno (80-89)</td>
                                                <td>45</td>
                                                <td>30%</td>
                                            </tr>
                                            <tr>
                                                <td>Bueno (70-79)</td>
                                                <td>60</td>
                                                <td>40%</td>
                                            </tr>
                                            <tr>
                                                <td>Regular (60-69)</td>
                                                <td>22</td>
                                                <td>15%</td>
                                            </tr>
                                            <tr>
                                                <td>Insuficiente (0-59)</td>
                                                <td>8</td>
                                                <td>5%</td>
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
                                <h5 class="mb-0 text-white">Distribución de Asistencia</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Rango de Asistencia</th>
                                                <th>Cantidad de Estudiantes</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Excelente (95-100%)</td>
                                                <td>60</td>
                                                <td>40%</td>
                                            </tr>
                                            <tr>
                                                <td>Muy Bueno (90-94%)</td>
                                                <td>45</td>
                                                <td>30%</td>
                                            </tr>
                                            <tr>
                                                <td>Bueno (85-89%)</td>
                                                <td>30</td>
                                                <td>20%</td>
                                            </tr>
                                            <tr>
                                                <td>Regular (80-84%)</td>
                                                <td>10</td>
                                                <td>7%</td>
                                            </tr>
                                            <tr>
                                                <td>Insuficiente (0-79%)</td>
                                                <td>5</td>
                                                <td>3%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students at Risk -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Estudiantes en Riesgo Académico</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Curso</th>
                                        <th>Grado</th>
                                        <th>Promedio</th>
                                        <th>Asistencia</th>
                                        <th>Motivo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>EST-005</td>
                                        <td>Fernando Quispe</td>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>60.0</td>
                                        <td>78%</td>
                                        <td>Bajo rendimiento y asistencia irregular</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-warning"><i class="bi bi-chat-dots"></i></button>
                                            <button class="btn btn-sm btn-outline-info"><i class="bi bi-envelope"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-011</td>
                                        <td>Luis Mamani</td>
                                        <td>Física</td>
                                        <td>5° Secundaria</td>
                                        <td>58.5</td>
                                        <td>82%</td>
                                        <td>Bajo rendimiento</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-warning"><i class="bi bi-chat-dots"></i></button>
                                            <button class="btn btn-sm btn-outline-info"><i class="bi bi-envelope"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-015</td>
                                        <td>Patricia Flores</td>
                                        <td>Química</td>
                                        <td>6° Secundaria</td>
                                        <td>65.2</td>
                                        <td>75%</td>
                                        <td>Asistencia irregular</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
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

    <!-- Student Detail Modal -->
    <div class="modal fade" id="studentDetailModal" tabindex="-1" aria-labelledby="studentDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="studentDetailModalLabel">Detalles del Estudiante: Alejandro Gómez</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="studentTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab" aria-controls="profile" aria-selected="true">Perfil</button>
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
                            <button class="nav-link" id="behavior-tab" data-bs-toggle="tab" data-bs-target="#behavior" type="button" role="tab" aria-controls="behavior" aria-selected="false">Comportamiento</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="notes-tab" data-bs-toggle="tab" data-bs-target="#notes" type="button" role="tab" aria-controls="notes" aria-selected="false">Notas</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3" id="studentTabContent">
                        <div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="text-center mb-4">
                                        <img src="https://via.placeholder.com/150" class="rounded-circle img-thumbnail" alt="Foto de perfil">
                                        <h4 class="mt-2">Alejandro Gómez</h4>
                                        <p class="text-muted">6° Secundaria</p>
                                        <div class="d-flex justify-content-center">
                                            <span class="badge bg-primary me-2">Matemáticas</span>
                                            <span class="badge bg-info me-2">Física</span>
                                            <span class="badge bg-warning">Química</span>
                                        </div>
                                    </div>
                                    <div class="list-group mb-4">
                                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            Promedio General
                                            <span class="badge bg-primary rounded-pill">86.6</span>
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            Asistencia
                                            <span class="badge bg-success rounded-pill">95%</span>
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            Tareas Completadas
                                            <span class="badge bg-info rounded-pill">12/12</span>
                                        </a>
                                        <a href="#" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                            Estado
                                            <span class="badge bg-success">Activo</span>
                                        </a>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <h5>Información Personal</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p><strong>ID:</strong> EST-001</p>
                                            <p><strong>Nombre completo:</strong> Alejandro José Gómez Rodríguez</p>
                                            <p><strong>Fecha de nacimiento:</strong> 15/03/2007</p>
                                            <p><strong>Edad:</strong> 18 años</p>
                                            <p><strong>Género:</strong> Masculino</p>
                                            <p><strong>CI:</strong> 12345678 LP</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Dirección:</strong> Av. 16 de Julio #1234, El Alto</p>
                                            <p><strong>Teléfono:</strong> 71234567</p>
                                            <p><strong>Correo electrónico:</strong> alejandro.gomez@estudiantes.eduardoavaroa.edu.bo</p>
                                            <p><strong>Fecha de ingreso:</strong> 01/02/2019</p>
                                            <p><strong>Años en la institución:</strong> 6 años</p>
                                        </div>
                                    </div>
                                    
                                    <h5>Información Académica</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p><strong>Grado actual:</strong> 6° Secundaria</p>
                                            <p><strong>Paralelo:</strong> A</p>
                                            <p><strong>Tutor:</strong> Prof. Carlos Mendoza</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Promedio general:</strong> 86.6</p>
                                            <p><strong>Asistencia general:</strong> 95%</p>
                                            <p><strong>Posición en la clase:</strong> 5 de 25</p>
                                        </div>
                                    </div>
                                    
                                    <h5>Información de Contacto de Emergencia</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p><strong>Nombre del padre:</strong> Roberto Gómez</p>
                                            <p><strong>Teléfono del padre:</strong> 72345678</p>
                                            <p><strong>Correo del padre:</strong> roberto.gomez@gmail.com</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Nombre de la madre:</strong> María Rodríguez</p>
                                            <p><strong>Teléfono de la madre:</strong> 73456789</p>
                                            <p><strong>Correo de la madre:</strong> maria.rodriguez@gmail.com</p>
                                        </div>
                                    </div>
                                    
                                    <h5>Observaciones</h5>
                                    <p>Alejandro es un estudiante destacado en el área de ciencias. Muestra particular interés en matemáticas y física. Ha participado en olimpiadas científicas representando a la institución. Se recomienda fomentar su participación en actividades extracurriculares relacionadas con ciencias.</p>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="grades" role="tabpanel" aria-labelledby="grades-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Calificaciones por Curso</h5>
                                
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Curso</th>
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
                                            <td>Matemáticas</td>
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
                                            <td>Física</td>
                                            <td>82</td>
                                            <td>85</td>
                                            <td>88</td>
                                            <td>90</td>
                                            <td>92</td>
                                            <td>87.4</td>
                                            <td><span class="badge bg-primary">Bueno</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Química</td>
                                            <td>78</td>
                                            <td>82</td>
                                            <td>85</td>
                                            <td>80</td>
                                            <td>88</td>
                                            <td>82.6</td>
                                            <td><span class="badge bg-primary">Bueno</span></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <h5 class="mt-4">Historial Académico</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Año Escolar</th>
                                            <th>Grado</th>
                                            <th>Promedio General</th>
                                            <th>Posición en la Clase</th>
                                            <th>Observaciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>2024-2025</td>
                                            <td>6° Secundaria</td>
                                            <td>86.6</td>
                                            <td>5 de 25</td>
                                            <td>En curso</td>
                                        </tr>
                                        <tr>
                                            <td>2023-2024</td>
                                            <td>5° Secundaria</td>
                                            <td>85.2</td>
                                            <td>6 de 25</td>
                                            <td>Destacado en matemáticas</td>
                                        </tr>
                                        <tr>
                                            <td>2022-2023</td>
                                            <td>4° Secundaria</td>
                                            <td>83.8</td>
                                            <td>7 de 25</td>
                                            <td>Participó en olimpiada de matemáticas</td>
                                        </tr>
                                        <tr>
                                            <td>2021-2022</td>
                                            <td>3° Secundaria</td>
                                            <td>82.5</td>
                                            <td>8 de 25</td>
                                            <td>Buen desempeño general</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="attendance" role="tabpanel" aria-labelledby="attendance-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Registro de Asistencia</h5>
                                
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">Mes</span>
                                        <select class="form-select">
                                            <option selected>Junio 2025</option>
                                            <option>Mayo 2025</option>
                                            <option>Abril 2025</option>
                                            <option>Marzo 2025</option>
                                            <option>Febrero 2025</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">Curso</span>
                                        <select class="form-select">
                                            <option selected>Todos</option>
                                            <option>Matemáticas</option>
                                            <option>Física</option>
                                            <option>Química</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Curso</th>
                                            <th>1</th>
                                            <th>2</th>
                                            <th>3</th>
                                            <th>4</th>
                                            <th>5</th>
                                            <th>6</th>
                                            <th>7</th>
                                            <th>8</th>
                                            <th>9</th>
                                            <th>10</th>
                                            <th>11</th>
                                            <th>12</th>
                                            <th>13</th>
                                            <th>14</th>
                                            <th>15</th>
                                            <th>%</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Matemáticas</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-warning">T</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-danger">A</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td>93%</td>
                                        </tr>
                                        <tr>
                                            <td>Física</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-danger">A</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td>93%</td>
                                        </tr>
                                        <tr>
                                            <td>Química</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-warning">T</td>
                                            <td class="table-success">P</td>
                                            <td class="table-danger">A</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td class="table-success">P</td>
                                            <td>87%</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <p><small class="text-muted">P: Presente, T: Tardanza, A: Ausente, J: Justificado</small></p>
                            </div>
                            
                            <h5 class="mt-4">Resumen de Asistencia</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-academic">
                                                <tr>
                                                    <th>Curso</th>
                                                    <th>Presentes</th>
                                                    <th>Tardanzas</th>
                                                    <th>Ausencias</th>
                                                    <th>Justificadas</th>
                                                    <th>% Asistencia</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Matemáticas</td>
                                                    <td>13</td>
                                                    <td>1</td>
                                                    <td>1</td>
                                                    <td>0</td>
                                                    <td>93%</td>
                                                </tr>
                                                <tr>
                                                    <td>Física</td>
                                                    <td>14</td>
                                                    <td>0</td>
                                                    <td>1</td>
                                                    <td>0</td>
                                                    <td>93%</td>
                                                </tr>
                                                <tr>
                                                    <td>Química</td>
                                                    <td>13</td>
                                                    <td>1</td>
                                                    <td>1</td>
                                                    <td>0</td>
                                                    <td>87%</td>
                                                </tr>
                                                <tr class="table-academic">
                                                    <td><strong>Total</strong></td>
                                                    <td><strong>40</strong></td>
                                                    <td><strong>2</strong></td>
                                                    <td><strong>3</strong></td>
                                                    <td><strong>0</strong></td>
                                                    <td><strong>91%</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="assignments" role="tabpanel" aria-labelledby="assignments-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Tareas Asignadas</h5>
                                
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Curso</th>
                                            <th>Título</th>
                                            <th>Fecha de Asignación</th>
                                            <th>Fecha de Entrega</th>
                                            <th>Estado</th>
                                            <th>Calificación</th>
                                            <th>Comentarios</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Matemáticas</td>
                                            <td>Ejercicios de Límites</td>
                                            <td>15/05/2025</td>
                                            <td>22/05/2025</td>
                                            <td><span class="badge bg-success">Entregado</span></td>
                                            <td>90/100</td>
                                            <td>Excelente trabajo, muy completo</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Matemáticas</td>
                                            <td>Derivadas Parciales</td>
                                            <td>22/05/2025</td>
                                            <td>29/05/2025</td>
                                            <td><span class="badge bg-success">Entregado</span></td>
                                            <td>85/100</td>
                                            <td>Buen trabajo, algunos errores menores</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Matemáticas</td>
                                            <td>Integrales Definidas</td>
                                            <td>29/05/2025</td>
                                            <td>05/06/2025</td>
                                            <td><span class="badge bg-warning">En progreso</span></td>
                                            <td>-</td>
                                            <td>-</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Física</td>
                                            <td>Leyes de Newton</td>
                                            <td>10/05/2025</td>
                                            <td>17/05/2025</td>
                                            <td><span class="badge bg-success">Entregado</span></td>
                                            <td>92/100</td>
                                            <td>Excelente análisis y aplicación de conceptos</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Física</td>
                                            <td>Movimiento Circular</td>
                                            <td>17/05/2025</td>
                                            <td>24/05/2025</td>
                                            <td><span class="badge bg-success">Entregado</span></td>
                                            <td>88/100</td>
                                            <td>Buen trabajo, faltó profundizar en algunos conceptos</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Química</td>
                                            <td>Tabla Periódica</td>
                                            <td>12/05/2025</td>
                                            <td>19/05/2025</td>
                                            <td><span class="badge bg-success">Entregado</span></td>
                                            <td>85/100</td>
                                            <td>Buen trabajo, presentación clara</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <h5 class="mt-4">Resumen de Tareas</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-academic">
                                                <tr>
                                                    <th>Curso</th>
                                                    <th>Total Asignadas</th>
                                                    <th>Entregadas</th>
                                                    <th>Pendientes</th>
                                                    <th>Promedio</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Matemáticas</td>
                                                    <td>12</td>
                                                    <td>11</td>
                                                    <td>1</td>
                                                    <td>88.5</td>
                                                </tr>
                                                <tr>
                                                    <td>Física</td>
                                                    <td>10</td>
                                                    <td>10</td>
                                                    <td>0</td>
                                                    <td>90.2</td>
                                                </tr>
                                                <tr>
                                                    <td>Química</td>
                                                    <td>8</td>
                                                    <td>8</td>
                                                    <td>0</td>
                                                    <td>86.8</td>
                                                </tr>
                                                <tr class="table-academic">
                                                    <td><strong>Total</strong></td>
                                                    <td><strong>30</strong></td>
                                                    <td><strong>29</strong></td>
                                                    <td><strong>1</strong></td>
                                                    <td><strong>88.5</strong></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="behavior" role="tabpanel" aria-labelledby="behavior-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Registro de Comportamiento</h5>
                                <button class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-circle"></i> Nuevo Registro
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Curso</th>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>Registrado por</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>15/05/2025</td>
                                            <td>Matemáticas</td>
                                            <td><span class="badge bg-success">Positivo</span></td>
                                            <td>Participación destacada en clase, ayudando a compañeros con dificultades.</td>
                                            <td>Prof. María López</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>22/05/2025</td>
                                            <td>Física</td>
                                            <td><span class="badge bg-success">Positivo</span></td>
                                            <td>Excelente presentación en el laboratorio de física.</td>
                                            <td>Prof. Juan Pérez</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>28/05/2025</td>
                                            <td>Matemáticas</td>
                                            <td><span class="badge bg-warning">Observación</span></td>
                                            <td>Uso de celular durante la clase después de una advertencia.</td>
                                            <td>Prof. María López</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="notes" role="tabpanel" aria-labelledby="notes-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Notas y Observaciones</h5>
                                <button class="btn btn-sm btn-success">
                                    <i class="bi bi-plus-circle"></i> Nueva Nota
                                </button>
                            </div>
                            <div class="list-group">
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Reunión con padres</h6>
                                        <small>10/05/2025</small>
                                    </div>
                                    <p class="mb-1">Se realizó reunión con los padres para discutir el progreso académico. Los padres están satisfechos con el desempeño pero preocupados por la carga de trabajo.</p>
                                    <small class="text-muted">Registrado por: Prof. Carlos Mendoza (Tutor)</small>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Editar</button>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Orientación vocacional</h6>
                                        <small>20/05/2025</small>
                                    </div>
                                    <p class="mb-1">Alejandro ha expresado interés en estudiar ingeniería. Se le proporcionó información sobre universidades y programas relacionados.</p>
                                    <small class="text-muted">Registrado por: Lic. Patricia Flores (Orientadora)</small>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Editar</button>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
                                    </div>
                                </div>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Participación en olimpiada</h6>
                                        <small>25/05/2025</small>
                                    </div>
                                    <p class="mb-1">Alejandro ha sido seleccionado para representar al colegio en la Olimpiada Boliviana de Matemática. Se le proporcionará material adicional de preparación.</p>
                                    <small class="text-muted">Registrado por: Prof. María López (Matemáticas)</small>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i> Editar</button>
                                        <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Eliminar</button>
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

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
