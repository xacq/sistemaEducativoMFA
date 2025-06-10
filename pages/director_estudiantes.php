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
    <title>Sistema Académico - Estudiantes</title>
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
                    <h1 class="h2">Gestión de Estudiantes</h1>
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

                <!-- Search and Add Student -->
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Buscar estudiante por nombre, grado o ID...">
                            <button class="btn btn-academic" type="button">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" class="btn btn-academic" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                            <i class="bi bi-person-plus"></i> Agregar Estudiante
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
                                        <label for="statusFilter" class="form-label">Estado</label>
                                        <select class="form-select" id="statusFilter">
                                            <option value="all" selected>Todos</option>
                                            <option value="active">Activo</option>
                                            <option value="inactive">Inactivo</option>
                                            <option value="suspended">Suspendido</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label for="performanceFilter" class="form-label">Rendimiento</label>
                                        <select class="form-select" id="performanceFilter">
                                            <option value="all" selected>Todos</option>
                                            <option value="excellent">Excelente (90-100)</option>
                                            <option value="good">Bueno (70-89)</option>
                                            <option value="average">Regular (60-69)</option>
                                            <option value="poor">Insuficiente (0-59)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label for="sortBy" class="form-label">Ordenar por</label>
                                        <select class="form-select" id="sortBy">
                                            <option value="name" selected>Nombre</option>
                                            <option value="grade">Grado</option>
                                            <option value="performance">Rendimiento</option>
                                            <option value="attendance">Asistencia</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students List -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Estudiantes (600)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Grado</th>
                                        <th>Edad</th>
                                        <th>Promedio</th>
                                        <th>Asistencia</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>E001</td>
                                        <td>Juan Pérez</td>
                                        <td>6° Secundaria</td>
                                        <td>17</td>
                                        <td>85.7</td>
                                        <td>92%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>E002</td>
                                        <td>Ana García</td>
                                        <td>5° Secundaria</td>
                                        <td>16</td>
                                        <td>92.3</td>
                                        <td>95%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>E003</td>
                                        <td>Carlos Rodríguez</td>
                                        <td>4° Secundaria</td>
                                        <td>15</td>
                                        <td>78.5</td>
                                        <td>88%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>E004</td>
                                        <td>María López</td>
                                        <td>6° Secundaria</td>
                                        <td>17</td>
                                        <td>89.2</td>
                                        <td>94%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>E005</td>
                                        <td>Pedro Martínez</td>
                                        <td>3° Secundaria</td>
                                        <td>14</td>
                                        <td>65.8</td>
                                        <td>78%</td>
                                        <td><span class="badge bg-warning">Suspendido</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>E006</td>
                                        <td>Laura Gómez</td>
                                        <td>5° Secundaria</td>
                                        <td>16</td>
                                        <td>91.5</td>
                                        <td>97%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>E007</td>
                                        <td>Roberto Sánchez</td>
                                        <td>4° Secundaria</td>
                                        <td>15</td>
                                        <td>82.7</td>
                                        <td>90%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>E008</td>
                                        <td>Patricia Torres</td>
                                        <td>6° Primaria</td>
                                        <td>12</td>
                                        <td>88.3</td>
                                        <td>93%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>E009</td>
                                        <td>Miguel Ángel Flores</td>
                                        <td>5° Primaria</td>
                                        <td>11</td>
                                        <td>79.5</td>
                                        <td>85%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>E010</td>
                                        <td>Sofía Ramírez</td>
                                        <td>4° Primaria</td>
                                        <td>10</td>
                                        <td>94.2</td>
                                        <td>98%</td>
                                        <td><span class="badge bg-success">Activo</span></td>
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

                <!-- Student Statistics -->
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
                                                <th>Estudiantes</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>1° Primaria</td>
                                                <td>50</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>2° Primaria</td>
                                                <td>48</td>
                                                <td>8.0%</td>
                                            </tr>
                                            <tr>
                                                <td>3° Primaria</td>
                                                <td>52</td>
                                                <td>8.7%</td>
                                            </tr>
                                            <tr>
                                                <td>4° Primaria</td>
                                                <td>55</td>
                                                <td>9.2%</td>
                                            </tr>
                                            <tr>
                                                <td>5° Primaria</td>
                                                <td>50</td>
                                                <td>8.3%</td>
                                            </tr>
                                            <tr>
                                                <td>6° Primaria</td>
                                                <td>45</td>
                                                <td>7.5%</td>
                                            </tr>
                                            <tr>
                                                <td>1° Secundaria</td>
                                                <td>60</td>
                                                <td>10.0%</td>
                                            </tr>
                                            <tr>
                                                <td>2° Secundaria</td>
                                                <td>58</td>
                                                <td>9.7%</td>
                                            </tr>
                                            <tr>
                                                <td>3° Secundaria</td>
                                                <td>62</td>
                                                <td>10.3%</td>
                                            </tr>
                                            <tr>
                                                <td>4° Secundaria</td>
                                                <td>55</td>
                                                <td>9.2%</td>
                                            </tr>
                                            <tr>
                                                <td>5° Secundaria</td>
                                                <td>40</td>
                                                <td>6.7%</td>
                                            </tr>
                                            <tr>
                                                <td>6° Secundaria</td>
                                                <td>25</td>
                                                <td>4.2%</td>
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
                                <h5 class="mb-0 text-white">Estadísticas de Rendimiento</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Nivel de Rendimiento</th>
                                                <th>Estudiantes</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Excelente (90-100)</td>
                                                <td>87</td>
                                                <td>14.5%</td>
                                            </tr>
                                            <tr>
                                                <td>Muy Bueno (80-89)</td>
                                                <td>156</td>
                                                <td>26.0%</td>
                                            </tr>
                                            <tr>
                                                <td>Bueno (70-79)</td>
                                                <td>210</td>
                                                <td>35.0%</td>
                                            </tr>
                                            <tr>
                                                <td>Regular (60-69)</td>
                                                <td>105</td>
                                                <td>17.5%</td>
                                            </tr>
                                            <tr>
                                                <td>Insuficiente (0-59)</td>
                                                <td>42</td>
                                                <td>7.0%</td>
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

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="addStudentModalLabel">Agregar Nuevo Estudiante</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="studentName" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="studentName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="studentID" class="form-label">ID de Estudiante</label>
                                <input type="text" class="form-control" id="studentID" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="studentBirthdate" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="studentBirthdate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="studentGender" class="form-label">Género</label>
                                <select class="form-select" id="studentGender" required>
                                    <option value="" selected disabled>Seleccionar género</option>
                                    <option value="male">Masculino</option>
                                    <option value="female">Femenino</option>
                                    <option value="other">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="studentGrade" class="form-label">Grado</label>
                                <select class="form-select" id="studentGrade" required>
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
                                <label for="studentSection" class="form-label">Sección</label>
                                <select class="form-select" id="studentSection" required>
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
                                <label for="studentEmail" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="studentEmail">
                            </div>
                            <div class="col-md-6">
                                <label for="studentPhone" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="studentPhone">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="studentAddress" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="studentAddress" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="parentName" class="form-label">Nombre del Padre/Madre/Tutor</label>
                                <input type="text" class="form-control" id="parentName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="parentPhone" class="form-label">Teléfono del Padre/Madre/Tutor</label>
                                <input type="tel" class="form-control" id="parentPhone" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="enrollmentDate" class="form-label">Fecha de Inscripción</label>
                                <input type="date" class="form-control" id="enrollmentDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="studentStatus" class="form-label">Estado</label>
                                <select class="form-select" id="studentStatus" required>
                                    <option value="active" selected>Activo</option>
                                    <option value="inactive">Inactivo</option>
                                    <option value="suspended">Suspendido</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="studentPhoto" class="form-label">Fotografía</label>
                            <input class="form-control" type="file" id="studentPhoto">
                        </div>
                        <div class="mb-3">
                            <label for="studentNotes" class="form-label">Observaciones</label>
                            <textarea class="form-control" id="studentNotes" rows="3"></textarea>
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
