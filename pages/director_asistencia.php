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
    <title>Sistema Académico - Asistencia</title>
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
                    <h1 class="h2">Control de Asistencia</h1>
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

                <!-- Search and Filters -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Buscar por estudiante, curso o profesor...">
                            <button class="btn btn-academic" type="button">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Filtros</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <label for="gradeFilter" class="form-label">Grado</label>
                                        <select class="form-select" id="gradeFilter">
                                            <option value="all" selected>Todos</option>
                                            <option value="primary">Primaria</option>
                                            <option value="secondary">Secundaria</option>
                                            <option value="grade1">1° Grado</option>
                                            <option value="grade2">2° Grado</option>
                                            <option value="grade3">3° Grado</option>
                                            <option value="grade4">4° Grado</option>
                                            <option value="grade5">5° Grado</option>
                                            <option value="grade6">6° Grado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label for="courseFilter" class="form-label">Curso</label>
                                        <select class="form-select" id="courseFilter">
                                            <option value="all" selected>Todos</option>
                                            <option value="math">Matemáticas</option>
                                            <option value="language">Lenguaje</option>
                                            <option value="science">Ciencias Naturales</option>
                                            <option value="social">Ciencias Sociales</option>
                                            <option value="english">Inglés</option>
                                            <option value="pe">Educación Física</option>
                                            <option value="arts">Artes</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label for="dateRangeFilter" class="form-label">Rango de Fechas</label>
                                        <select class="form-select" id="dateRangeFilter">
                                            <option value="today">Hoy</option>
                                            <option value="yesterday">Ayer</option>
                                            <option value="thisWeek" selected>Esta semana</option>
                                            <option value="lastWeek">Semana pasada</option>
                                            <option value="thisMonth">Este mes</option>
                                            <option value="lastMonth">Mes pasado</option>
                                            <option value="custom">Personalizado</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label for="statusFilter" class="form-label">Estado</label>
                                        <select class="form-select" id="statusFilter">
                                            <option value="all" selected>Todos</option>
                                            <option value="present">Presente</option>
                                            <option value="absent">Ausente</option>
                                            <option value="late">Tardanza</option>
                                            <option value="justified">Justificado</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row mt-2 d-none" id="customDateRange">
                                    <div class="col-md-6">
                                        <label for="startDate" class="form-label">Fecha Inicio</label>
                                        <input type="date" class="form-control" id="startDate">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="endDate" class="form-label">Fecha Fin</label>
                                        <input type="date" class="form-control" id="endDate">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Overview -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Resumen de Asistencia (Esta semana)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-success">
                                    <div class="card-body">
                                        <h5 class="card-title">Presentes</h5>
                                        <p class="card-text display-4">2,580</p>
                                        <p class="card-text">86% de asistencias</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-danger">
                                    <div class="card-body">
                                        <h5 class="card-title">Ausentes</h5>
                                        <p class="card-text display-4">240</p>
                                        <p class="card-text">8% de asistencias</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-warning">
                                    <div class="card-body">
                                        <h5 class="card-title">Tardanzas</h5>
                                        <p class="card-text display-4">120</p>
                                        <p class="card-text">4% de asistencias</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-info">
                                    <div class="card-body">
                                        <h5 class="card-title">Justificados</h5>
                                        <p class="card-text display-4">60</p>
                                        <p class="card-text">2% de asistencias</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance by Course -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Asistencia por Curso</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Curso</th>
                                        <th>Grado</th>
                                        <th>Profesor</th>
                                        <th>Presentes</th>
                                        <th>Ausentes</th>
                                        <th>Tardanzas</th>
                                        <th>Justificados</th>
                                        <th>% Asistencia</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>María López</td>
                                        <td>110</td>
                                        <td>10</td>
                                        <td>5</td>
                                        <td>0</td>
                                        <td>88%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lenguaje</td>
                                        <td>6° Secundaria</td>
                                        <td>Ana Martínez</td>
                                        <td>115</td>
                                        <td>5</td>
                                        <td>5</td>
                                        <td>0</td>
                                        <td>92%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Física</td>
                                        <td>6° Secundaria</td>
                                        <td>Carlos Rodríguez</td>
                                        <td>105</td>
                                        <td>15</td>
                                        <td>5</td>
                                        <td>0</td>
                                        <td>84%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Química</td>
                                        <td>6° Secundaria</td>
                                        <td>Carlos Rodríguez</td>
                                        <td>100</td>
                                        <td>15</td>
                                        <td>10</td>
                                        <td>0</td>
                                        <td>80%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Historia</td>
                                        <td>6° Secundaria</td>
                                        <td>Juan Pérez</td>
                                        <td>112</td>
                                        <td>8</td>
                                        <td>5</td>
                                        <td>0</td>
                                        <td>90%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Geografía</td>
                                        <td>6° Secundaria</td>
                                        <td>Juan Pérez</td>
                                        <td>110</td>
                                        <td>10</td>
                                        <td>5</td>
                                        <td>0</td>
                                        <td>88%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Inglés</td>
                                        <td>6° Secundaria</td>
                                        <td>Laura Gómez</td>
                                        <td>115</td>
                                        <td>5</td>
                                        <td>5</td>
                                        <td>0</td>
                                        <td>92%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Educación Física</td>
                                        <td>6° Secundaria</td>
                                        <td>Roberto Fernández</td>
                                        <td>120</td>
                                        <td>5</td>
                                        <td>0</td>
                                        <td>0</td>
                                        <td>96%</td>
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

                <!-- Daily Attendance -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Asistencia Diaria</h5>
                    </div>
                    <div class="card-body">
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
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">Grado</span>
                                    <select class="form-select">
                                        <option value="all" selected>Todos</option>
                                        <option value="6s">6° Secundaria</option>
                                        <option value="5s">5° Secundaria</option>
                                        <option value="4s">4° Secundaria</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text">Curso</span>
                                    <select class="form-select">
                                        <option value="all" selected>Todos</option>
                                        <option value="math">Matemáticas</option>
                                        <option value="language">Lenguaje</option>
                                        <option value="physics">Física</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Hora</th>
                                        <th>Curso</th>
                                        <th>Grado</th>
                                        <th>Profesor</th>
                                        <th>Presentes</th>
                                        <th>Ausentes</th>
                                        <th>Tardanzas</th>
                                        <th>Justificados</th>
                                        <th>% Asistencia</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>08:00 - 09:30</td>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>María López</td>
                                        <td>22</td>
                                        <td>2</td>
                                        <td>1</td>
                                        <td>0</td>
                                        <td>88%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>08:00 - 09:30</td>
                                        <td>Lenguaje</td>
                                        <td>5° Secundaria</td>
                                        <td>Ana Martínez</td>
                                        <td>23</td>
                                        <td>1</td>
                                        <td>1</td>
                                        <td>0</td>
                                        <td>92%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>10:00 - 11:30</td>
                                        <td>Física</td>
                                        <td>6° Secundaria</td>
                                        <td>Carlos Rodríguez</td>
                                        <td>21</td>
                                        <td>3</td>
                                        <td>1</td>
                                        <td>0</td>
                                        <td>84%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>10:00 - 11:30</td>
                                        <td>Química</td>
                                        <td>5° Secundaria</td>
                                        <td>Carlos Rodríguez</td>
                                        <td>20</td>
                                        <td>3</td>
                                        <td>2</td>
                                        <td>0</td>
                                        <td>80%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>13:00 - 14:30</td>
                                        <td>Historia</td>
                                        <td>6° Secundaria</td>
                                        <td>Juan Pérez</td>
                                        <td>22</td>
                                        <td>2</td>
                                        <td>1</td>
                                        <td>0</td>
                                        <td>88%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>13:00 - 14:30</td>
                                        <td>Geografía</td>
                                        <td>5° Secundaria</td>
                                        <td>Juan Pérez</td>
                                        <td>22</td>
                                        <td>2</td>
                                        <td>1</td>
                                        <td>0</td>
                                        <td>88%</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Course Detail Modal -->
                <div class="modal fade" id="courseDetailModal" tabindex="-1" aria-labelledby="courseDetailModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header card-header-academic text-white">
                                <h5 class="modal-title" id="courseDetailModalLabel">Asistencia: Matemáticas - 6° Secundaria</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
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
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                <button type="button" class="btn btn-academic">Exportar</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Modal -->
                <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header card-header-academic text-white">
                                <h5 class="modal-title" id="exportModalLabel">Exportar Asistencia</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form>
                                    <div class="mb-3">
                                        <label for="exportGrade" class="form-label">Grado</label>
                                        <select class="form-select" id="exportGrade">
                                            <option value="all" selected>Todos</option>
                                            <option value="primary">Primaria</option>
                                            <option value="secondary">Secundaria</option>
                                            <option value="grade1">1° Grado</option>
                                            <option value="grade2">2° Grado</option>
                                            <option value="grade3">3° Grado</option>
                                            <option value="grade4">4° Grado</option>
                                            <option value="grade5">5° Grado</option>
                                            <option value="grade6">6° Grado</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="exportCourse" class="form-label">Curso</label>
                                        <select class="form-select" id="exportCourse">
                                            <option value="all" selected>Todos</option>
                                            <option value="math">Matemáticas</option>
                                            <option value="language">Lenguaje</option>
                                            <option value="science">Ciencias Naturales</option>
                                            <option value="social">Ciencias Sociales</option>
                                            <option value="english">Inglés</option>
                                            <option value="pe">Educación Física</option>
                                            <option value="arts">Artes</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="exportDateRange" class="form-label">Rango de Fechas</label>
                                        <select class="form-select" id="exportDateRange">
                                            <option value="today">Hoy</option>
                                            <option value="yesterday">Ayer</option>
                                            <option value="thisWeek" selected>Esta semana</option>
                                            <option value="lastWeek">Semana pasada</option>
                                            <option value="thisMonth">Este mes</option>
                                            <option value="lastMonth">Mes pasado</option>
                                            <option value="custom">Personalizado</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 d-none" id="exportCustomDateRange">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <label for="exportStartDate" class="form-label">Fecha Inicio</label>
                                                <input type="date" class="form-control" id="exportStartDate">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="exportEndDate" class="form-label">Fecha Fin</label>
                                                <input type="date" class="form-control" id="exportEndDate">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="exportFormat" class="form-label">Formato</label>
                                        <select class="form-select" id="exportFormat">
                                            <option value="excel">Excel (.xlsx)</option>
                                            <option value="pdf">PDF</option>
                                            <option value="csv">CSV</option>
                                        </select>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-academic">Exportar</button>
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
