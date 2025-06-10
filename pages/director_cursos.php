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
    <title>Sistema Académico - Cursos</title>
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
                    <h1 class="h2">Gestión de Cursos</h1>
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

                <!-- Search and Add Course -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Buscar curso por nombre, código o profesor...">
                            <button class="btn btn-academic" type="button">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-academic" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                            <i class="bi bi-plus-circle"></i> Agregar Curso
                        </button>
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
                                        <label for="subjectFilter" class="form-label">Asignatura</label>
                                        <select class="form-select" id="subjectFilter">
                                            <option value="all" selected>Todas</option>
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
                                        <label for="teacherFilter" class="form-label">Profesor</label>
                                        <select class="form-select" id="teacherFilter">
                                            <option value="all" selected>Todos</option>
                                            <option value="1">María López</option>
                                            <option value="2">Carlos Rodríguez</option>
                                            <option value="3">Ana Martínez</option>
                                            <option value="4">Juan Pérez</option>
                                            <option value="5">Laura Gómez</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label for="statusFilter" class="form-label">Estado</label>
                                        <select class="form-select" id="statusFilter">
                                            <option value="all" selected>Todos</option>
                                            <option value="active">Activo</option>
                                            <option value="inactive">Inactivo</option>
                                            <option value="pending">Pendiente</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courses List -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Cursos (48)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre del Curso</th>
                                        <th>Grado</th>
                                        <th>Profesor</th>
                                        <th>Estudiantes</th>
                                        <th>Horario</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>MAT-6S</td>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>María López</td>
                                        <td>25</td>
                                        <td>Lun, Mié, Vie 08:00-09:30</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>LEN-6S</td>
                                        <td>Lenguaje</td>
                                        <td>6° Secundaria</td>
                                        <td>Ana Martínez</td>
                                        <td>25</td>
                                        <td>Mar, Jue 08:00-09:30</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>FIS-6S</td>
                                        <td>Física</td>
                                        <td>6° Secundaria</td>
                                        <td>Carlos Rodríguez</td>
                                        <td>25</td>
                                        <td>Lun, Mié 10:00-11:30</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>QUI-6S</td>
                                        <td>Química</td>
                                        <td>6° Secundaria</td>
                                        <td>Carlos Rodríguez</td>
                                        <td>25</td>
                                        <td>Mar, Jue 10:00-11:30</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>HIS-6S</td>
                                        <td>Historia</td>
                                        <td>6° Secundaria</td>
                                        <td>Juan Pérez</td>
                                        <td>25</td>
                                        <td>Lun, Vie 13:00-14:30</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>GEO-6S</td>
                                        <td>Geografía</td>
                                        <td>6° Secundaria</td>
                                        <td>Juan Pérez</td>
                                        <td>25</td>
                                        <td>Mié 13:00-14:30</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>ING-6S</td>
                                        <td>Inglés</td>
                                        <td>6° Secundaria</td>
                                        <td>Laura Gómez</td>
                                        <td>25</td>
                                        <td>Mar, Jue 13:00-14:30</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EDF-6S</td>
                                        <td>Educación Física</td>
                                        <td>6° Secundaria</td>
                                        <td>Roberto Fernández</td>
                                        <td>25</td>
                                        <td>Vie 10:00-11:30</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>ART-6S</td>
                                        <td>Artes</td>
                                        <td>6° Secundaria</td>
                                        <td>Laura Gómez</td>
                                        <td>25</td>
                                        <td>Jue 15:00-16:30</td>
                                        <td><span class="badge bg-warning">Pendiente</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
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

                <!-- Course Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Distribución por Grado</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Grado</th>
                                                <th>Cursos</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1° Primaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>2° Primaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>3° Primaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>4° Primaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>5° Primaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>6° Primaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>1° Secundaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>2° Secundaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>3° Secundaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>4° Secundaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>5° Secundaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>6° Secundaria</td>
                                                <td>4</td>
                                                <td>8.3%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Distribución por Asignatura</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Asignatura</th>
                                                <th>Cursos</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Matemáticas</td>
                                                <td>12</td>
                                                <td>25.0%</td>
                                            </tr>
                                            <tr>
                                                <td>Lenguaje</td>
                                                <td>12</td>
                                                <td>25.0%</td>
                                            </tr>
                                            <tr>
                                                <td>Ciencias Naturales</td>
                                                <td>6</td>
                                                <td>12.5%</td>
                                            </tr>
                                            <tr>
                                                <td>Física</td>
                                                <td>3</td>
                                                <td>6.3%</td>
                                            </tr>
                                            <tr>
                                                <td>Química</td>
                                                <td>3</td>
                                                <td>6.3%</td>
                                            </tr>
                                            <tr>
                                                <td>Ciencias Sociales</td>
                                                <td>3</td>
                                                <td>6.3%</td>
                                            </tr>
                                            <tr>
                                                <td>Historia</td>
                                                <td>3</td>
                                                <td>6.3%</td>
                                            </tr>
                                            <tr>
                                                <td>Geografía</td>
                                                <td>3</td>
                                                <td>6.3%</td>
                                            </tr>
                                            <tr>
                                                <td>Inglés</td>
                                                <td>12</td>
                                                <td>25.0%</td>
                                            </tr>
                                            <tr>
                                                <td>Educación Física</td>
                                                <td>12</td>
                                                <td>25.0%</td>
                                            </tr>
                                            <tr>
                                                <td>Artes</td>
                                                <td>12</td>
                                                <td>25.0%</td>
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

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="addCourseModalLabel">Agregar Nuevo Curso</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="courseCode" class="form-label">Código del Curso</label>
                                <input type="text" class="form-control" id="courseCode" required>
                            </div>
                            <div class="col-md-6">
                                <label for="courseName" class="form-label">Nombre del Curso</label>
                                <input type="text" class="form-control" id="courseName" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="courseGrade" class="form-label">Grado</label>
                                <select class="form-select" id="courseGrade" required>
                                    <option value="" selected disabled>Seleccionar grado</option>
                                    <option value="1p">1° Primaria</option>
                                    <option value="2p">2° Primaria</option>
                                    <option value="3p">3° Primaria</option>
                                    <option value="4p">4° Primaria</option>
                                    <option value="5p">5° Primaria</option>
                                    <option value="6p">6° Primaria</option>
                                    <option value="1s">1° Secundaria</option>
                                    <option value="2s">2° Secundaria</option>
                                    <option value="3s">3° Secundaria</option>
                                    <option value="4s">4° Secundaria</option>
                                    <option value="5s">5° Secundaria</option>
                                    <option value="6s">6° Secundaria</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="courseSection" class="form-label">Sección</label>
                                <select class="form-select" id="courseSection" required>
                                    <option value="" selected disabled>Seleccionar sección</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="courseTeacher" class="form-label">Profesor</label>
                                <select class="form-select" id="courseTeacher" required>
                                    <option value="" selected disabled>Seleccionar profesor</option>
                                    <option value="1">María López</option>
                                    <option value="2">Carlos Rodríguez</option>
                                    <option value="3">Ana Martínez</option>
                                    <option value="4">Juan Pérez</option>
                                    <option value="5">Laura Gómez</option>
                                    <option value="6">Roberto Fernández</option>
                                    <option value="7">Patricia Soto</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="courseSubject" class="form-label">Asignatura</label>
                                <select class="form-select" id="courseSubject" required>
                                    <option value="" selected disabled>Seleccionar asignatura</option>
                                    <option value="math">Matemáticas</option>
                                    <option value="language">Lenguaje</option>
                                    <option value="science">Ciencias Naturales</option>
                                    <option value="physics">Física</option>
                                    <option value="chemistry">Química</option>
                                    <option value="social">Ciencias Sociales</option>
                                    <option value="history">Historia</option>
                                    <option value="geography">Geografía</option>
                                    <option value="english">Inglés</option>
                                    <option value="pe">Educación Física</option>
                                    <option value="arts">Artes</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="courseCapacity" class="form-label">Capacidad</label>
                                <input type="number" class="form-control" id="courseCapacity" min="1" required>
                            </div>
                            <div class="col-md-6">
                                <label for="courseCredits" class="form-label">Créditos</label>
                                <input type="number" class="form-control" id="courseCredits" min="1" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="courseStartDate" class="form-label">Fecha de Inicio</label>
                                <input type="date" class="form-control" id="courseStartDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="courseEndDate" class="form-label">Fecha de Finalización</label>
                                <input type="date" class="form-control" id="courseEndDate" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Horario</label>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Día</th>
                                            <th>Hora de Inicio</th>
                                            <th>Hora de Fin</th>
                                            <th>Aula</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                <select class="form-select form-select-sm">
                                                    <option value="monday">Lunes</option>
                                                    <option value="tuesday">Martes</option>
                                                    <option value="wednesday">Miércoles</option>
                                                    <option value="thursday">Jueves</option>
                                                    <option value="friday">Viernes</option>
                                                </select>
                                            </td>
                                            <td><input type="time" class="form-control form-control-sm"></td>
                                            <td><input type="time" class="form-control form-control-sm"></td>
                                            <td><input type="text" class="form-control form-control-sm"></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-2">
                                <button type="button" class="btn btn-sm btn-academic">
                                    <i class="bi bi-plus-circle"></i> Agregar Horario
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="courseDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="courseDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="courseStatus" class="form-label">Estado</label>
                            <select class="form-select" id="courseStatus" required>
                                <option value="active" selected>Activo</option>
                                <option value="inactive">Inactivo</option>
                                <option value="pending">Pendiente</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
