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
    <title>Sistema Académico - Calificaciones</title>
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
                    <h1 class="h2">Gestión de Calificaciones</h1>
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

                <!-- Action Buttons - Profesor can edit grades -->
                <div class="row mb-4 editable-by-professor">
                    <div class="col-12 text-end">
                        <button class="btn btn-success me-2 edit-permission-professor" data-bs-toggle="modal" data-bs-target="#newGradeModal">
                            <i class="bi bi-plus-circle"></i> Nueva Calificación
                        </button>
                        
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="courseFilter" class="form-label">Curso</label>
                                <select class="form-select" id="courseFilter">
                                    <option value="" selected>Todos</option>
                                    <option value="mat6">Matemáticas - 6° Secundaria</option>
                                    <option value="fis6">Física - 6° Secundaria</option>
                                    <option value="mat5">Matemáticas - 5° Secundaria</option>
                                    <option value="fis5">Física - 5° Secundaria</option>
                                    <option value="qui5">Química - 5° Secundaria</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="periodFilter" class="form-label">Periodo</label>
                                <select class="form-select" id="periodFilter">
                                    <option value="" selected>Todos</option>
                                    <option value="1">Primer Trimestre</option>
                                    <option value="2">Segundo Trimestre</option>
                                    <option value="3">Tercer Trimestre</option>
                                    <option value="4">Final</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="gradeTypeFilter" class="form-label">Tipo de Evaluación</label>
                                <select class="form-select" id="gradeTypeFilter">
                                    <option value="" selected>Todos</option>
                                    <option value="exam">Examen</option>
                                    <option value="quiz">Prueba Corta</option>
                                    <option value="homework">Tarea</option>
                                    <option value="project">Proyecto</option>
                                    <option value="participation">Participación</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="searchInput" class="form-label">Buscar</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Nombre, ID...">
                                    <button class="btn btn-academic" type="button">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Selection -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Seleccionar Curso para Calificaciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-primary">
                                    <div class="card-body">
                                        <h5 class="card-title">Matemáticas - 6° Secundaria</h5>
                                        <p class="card-text">
                                            <strong>Aula:</strong> 201<br>
                                            <strong>Estudiantes:</strong> 32<br>
                                            <strong>Periodo actual:</strong> Segundo Trimestre
                                        </p>
                                        <button class="btn btn-primary w-100" onclick="window.location.href='#matematicas6'">Ver Calificaciones</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-info">
                                    <div class="card-body">
                                        <h5 class="card-title">Física - 6° Secundaria</h5>
                                        <p class="card-text">
                                            <strong>Aula:</strong> Laboratorio 2<br>
                                            <strong>Estudiantes:</strong> 32<br>
                                            <strong>Periodo actual:</strong> Segundo Trimestre
                                        </p>
                                        <button class="btn btn-info w-100 text-white" onclick="window.location.href='#fisica6'">Ver Calificaciones</button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card h-100 border-success">
                                    <div class="card-body">
                                        <h5 class="card-title">Matemáticas - 5° Secundaria</h5>
                                        <p class="card-text">
                                            <strong>Aula:</strong> 203<br>
                                            <strong>Estudiantes:</strong> 35<br>
                                            <strong>Periodo actual:</strong> Segundo Trimestre
                                        </p>
                                        <button class="btn btn-success w-100" onclick="window.location.href='#matematicas5'">Ver Calificaciones</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grades Table - Matemáticas 6° -->
                <div class="card mb-4" id="matematicas6">
                    <div class="card-header card-header-academic d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-white">Calificaciones: Matemáticas - 6° Secundaria</h5>
                        <div>
                            <button class="btn btn-sm btn-light edit-permission-professor">
                                <i class="bi bi-pencil"></i> Editar Todas
                            </button>
                            <button class="btn btn-sm btn-light ms-2">
                                <i class="bi bi-download"></i> Exportar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>ID</th>
                                        <th>Estudiante</th>
                                        <th>Examen 1 (25%)</th>
                                        <th>Examen 2 (25%)</th>
                                        <th>Tareas (20%)</th>
                                        <th>Proyecto (20%)</th>
                                        <th>Participación (10%)</th>
                                        <th>Promedio</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>EST-2019-001</td>
                                        <td>Ana Gutiérrez</td>
                                        <td>85/100</td>
                                        <td>90/100</td>
                                        <td>88/100</td>
                                        <td>92/100</td>
                                        <td>95/100</td>
                                        <td><strong>89/100</strong></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-professor"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-2019-002</td>
                                        <td>Juan Pérez</td>
                                        <td>78/100</td>
                                        <td>82/100</td>
                                        <td>75/100</td>
                                        <td>80/100</td>
                                        <td>85/100</td>
                                        <td><strong>79/100</strong></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-professor"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-2019-003</td>
                                        <td>Carlos Rodríguez</td>
                                        <td>92/100</td>
                                        <td>95/100</td>
                                        <td>90/100</td>
                                        <td>88/100</td>
                                        <td>90/100</td>
                                        <td><strong>91/100</strong></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-professor"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-2019-004</td>
                                        <td>María Fernández</td>
                                        <td>65/100</td>
                                        <td>70/100</td>
                                        <td>75/100</td>
                                        <td>68/100</td>
                                        <td>80/100</td>
                                        <td><strong>70/100</strong></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-professor"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-2019-005</td>
                                        <td>Laura Martínez</td>
                                        <td>88/100</td>
                                        <td>85/100</td>
                                        <td>90/100</td>
                                        <td>92/100</td>
                                        <td>85/100</td>
                                        <td><strong>88/100</strong></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-professor"><i class="bi bi-pencil"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot class="table-academic">
                                    <tr>
                                        <td colspan="2"><strong>Promedio de Clase</strong></td>
                                        <td>81.6/100</td>
                                        <td>84.4/100</td>
                                        <td>83.6/100</td>
                                        <td>84.0/100</td>
                                        <td>87.0/100</td>
                                        <td><strong>83.4/100</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-outline-primary me-2 edit-permission-professor">
                                <i class="bi bi-plus-circle"></i> Agregar Evaluación
                            </button>
                            <button class="btn btn-outline-secondary edit-permission-professor">
                                <i class="bi bi-gear"></i> Configurar Ponderaciones
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Estadísticas de Rendimiento</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Distribución de Calificaciones</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Rango</th>
                                                <th>Estudiantes</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>90-100 (Excelente)</td>
                                                <td>5</td>
                                                <td>15.6%</td>
                                            </tr>
                                            <tr>
                                                <td>80-89 (Muy Bueno)</td>
                                                <td>12</td>
                                                <td>37.5%</td>
                                            </tr>
                                            <tr>
                                                <td>70-79 (Bueno)</td>
                                                <td>8</td>
                                                <td>25.0%</td>
                                            </tr>
                                            <tr>
                                                <td>60-69 (Regular)</td>
                                                <td>5</td>
                                                <td>15.6%</td>
                                            </tr>
                                            <tr>
                                                <td>0-59 (Insuficiente)</td>
                                                <td>2</td>
                                                <td>6.3%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Comparativa por Evaluación</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Evaluación</th>
                                                <th>Promedio</th>
                                                <th>Nota Más Alta</th>
                                                <th>Nota Más Baja</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Examen 1</td>
                                                <td>81.6/100</td>
                                                <td>92/100</td>
                                                <td>65/100</td>
                                            </tr>
                                            <tr>
                                                <td>Examen 2</td>
                                                <td>84.4/100</td>
                                                <td>95/100</td>
                                                <td>70/100</td>
                                            </tr>
                                            <tr>
                                                <td>Tareas</td>
                                                <td>83.6/100</td>
                                                <td>90/100</td>
                                                <td>75/100</td>
                                            </tr>
                                            <tr>
                                                <td>Proyecto</td>
                                                <td>84.0/100</td>
                                                <td>92/100</td>
                                                <td>68/100</td>
                                            </tr>
                                            <tr>
                                                <td>Participación</td>
                                                <td>87.0/100</td>
                                                <td>95/100</td>
                                                <td>80/100</td>
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

    <!-- New Grade Modal - Only Professor can access -->
    <div class="modal fade" id="newGradeModal" tabindex="-1" aria-labelledby="newGradeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="newGradeModalLabel">Nueva Calificación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="gradeClass" class="form-label">Curso</label>
                                <select class="form-select" id="gradeClass" required>
                                    <option selected disabled value="">Seleccionar curso...</option>
                                    <option>Matemáticas - 6° Secundaria</option>
                                    <option>Física - 6° Secundaria</option>
                                    <option>Matemáticas - 5° Secundaria</option>
                                    <option>Física - 5° Secundaria</option>
                                    <option>Química - 5° Secundaria</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="gradePeriod" class="form-label">Periodo</label>
                                <select class="form-select" id="gradePeriod" required>
                                    <option selected disabled value="">Seleccionar periodo...</option>
                                    <option>Primer Trimestre</option>
                                    <option>Segundo Trimestre</option>
                                    <option>Tercer Trimestre</option>
                                    <option>Final</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="gradeType" class="form-label">Tipo de Evaluación</label>
                                <select class="form-select" id="gradeType" required>
                                    <option selected disabled value="">Seleccionar tipo...</option>
                                    <option>Examen</option>
                                    <option>Prueba Corta</option>
                                    <option>Tarea</option>
                                    <option>Proyecto</option>
                                    <option>Participación</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="gradeDate" class="form-label">Fecha de Evaluación</label>
                                <input type="date" class="form-control" id="gradeDate" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="gradeTitle" class="form-label">Título de la Evaluación</label>
                                <input type="text" class="form-control" id="gradeTitle" placeholder="Ej: Examen Parcial 1" required>
                            </div>
                            <div class="col-md-6">
                                <label for="gradeWeight" class="form-label">Ponderación (%)</label>
                                <input type="number" class="form-control" id="gradeWeight" min="1" max="100" value="25" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="gradeMaxScore" class="form-label">Puntaje Máximo</label>
                                <input type="number" class="form-control" id="gradeMaxScore" min="1" value="100" required>
                            </div>
                            <div class="col-md-6">
                                <label for="gradePassScore" class="form-label">Puntaje Mínimo Aprobatorio</label>
                                <input type="number" class="form-control" id="gradePassScore" min="1" value="60" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="gradeDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="gradeDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Método de Ingreso de Calificaciones</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gradeInputMethod" id="individualInput" value="individual" checked>
                                <label class="form-check-label" for="individualInput">
                                    Ingresar calificaciones individualmente
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gradeInputMethod" id="batchInput" value="batch">
                                <label class="form-check-label" for="batchInput">
                                    Importar calificaciones desde archivo
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="gradeInputMethod" id="templateInput" value="template">
                                <label class="form-check-label" for="templateInput">
                                    Descargar plantilla para llenar
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notifyStudents" checked>
                                <label class="form-check-label" for="notifyStudents">
                                    Notificar a los estudiantes cuando se publiquen las calificaciones
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic">Continuar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
