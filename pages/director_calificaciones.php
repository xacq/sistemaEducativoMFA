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
    <title>Sistema Académico - Calificaciones</title>
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
                    <h1 class="h2">Gestión de Calificaciones</h1>
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
                                        <label for="periodFilter" class="form-label">Período</label>
                                        <select class="form-select" id="periodFilter">
                                            <option value="all" selected>Todos</option>
                                            <option value="1">1er Bimestre</option>
                                            <option value="2">2do Bimestre</option>
                                            <option value="3">3er Bimestre</option>
                                            <option value="4">4to Bimestre</option>
                                            <option value="final">Final</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grades Overview -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Resumen de Calificaciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-success">
                                    <div class="card-body">
                                        <h5 class="card-title">Excelente (90-100)</h5>
                                        <p class="card-text display-4">152</p>
                                        <p class="card-text">25.3% de estudiantes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-primary">
                                    <div class="card-body">
                                        <h5 class="card-title">Bueno (75-89)</h5>
                                        <p class="card-text display-4">287</p>
                                        <p class="card-text">47.8% de estudiantes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-warning">
                                    <div class="card-body">
                                        <h5 class="card-title">Regular (60-74)</h5>
                                        <p class="card-text display-4">98</p>
                                        <p class="card-text">16.3% de estudiantes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="card text-white bg-danger">
                                    <div class="card-body">
                                        <h5 class="card-title">Insuficiente (<60)</h5>
                                        <p class="card-text display-4">63</p>
                                        <p class="card-text">10.5% de estudiantes</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grades by Course -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Calificaciones por Curso</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Curso</th>
                                        <th>Grado</th>
                                        <th>Profesor</th>
                                        <th>Promedio</th>
                                        <th>Excelente</th>
                                        <th>Bueno</th>
                                        <th>Regular</th>
                                        <th>Insuficiente</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>María López</td>
                                        <td>78.5</td>
                                        <td>5 (20%)</td>
                                        <td>12 (48%)</td>
                                        <td>5 (20%)</td>
                                        <td>3 (12%)</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Lenguaje</td>
                                        <td>6° Secundaria</td>
                                        <td>Ana Martínez</td>
                                        <td>82.3</td>
                                        <td>7 (28%)</td>
                                        <td>13 (52%)</td>
                                        <td>3 (12%)</td>
                                        <td>2 (8%)</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Física</td>
                                        <td>6° Secundaria</td>
                                        <td>Carlos Rodríguez</td>
                                        <td>75.8</td>
                                        <td>4 (16%)</td>
                                        <td>11 (44%)</td>
                                        <td>6 (24%)</td>
                                        <td>4 (16%)</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Química</td>
                                        <td>6° Secundaria</td>
                                        <td>Carlos Rodríguez</td>
                                        <td>73.2</td>
                                        <td>3 (12%)</td>
                                        <td>10 (40%)</td>
                                        <td>8 (32%)</td>
                                        <td>4 (16%)</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Historia</td>
                                        <td>6° Secundaria</td>
                                        <td>Juan Pérez</td>
                                        <td>80.1</td>
                                        <td>6 (24%)</td>
                                        <td>12 (48%)</td>
                                        <td>5 (20%)</td>
                                        <td>2 (8%)</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                           
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Geografía</td>
                                        <td>6° Secundaria</td>
                                        <td>Juan Pérez</td>
                                        <td>79.5</td>
                                        <td>5 (20%)</td>
                                        <td>13 (52%)</td>
                                        <td>4 (16%)</td>
                                        <td>3 (12%)</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Inglés</td>
                                        <td>6° Secundaria</td>
                                        <td>Laura Gómez</td>
                                        <td>81.7</td>
                                        <td>7 (28%)</td>
                                        <td>12 (48%)</td>
                                        <td>4 (16%)</td>
                                        <td>2 (8%)</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Educación Física</td>
                                        <td>6° Secundaria</td>
                                        <td>Roberto Fernández</td>
                                        <td>85.3</td>
                                        <td>9 (36%)</td>
                                        <td>13 (52%)</td>
                                        <td>2 (8%)</td>
                                        <td>1 (4%)</td>
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

                <!-- Course Detail Modal -->
                <div class="modal fade" id="courseDetailModal" tabindex="-1" aria-labelledby="courseDetailModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header card-header-academic text-white">
                                <h5 class="modal-title" id="courseDetailModalLabel">Calificaciones: Matemáticas - 6° Secundaria</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
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
                                <h5 class="modal-title" id="exportModalLabel">Exportar Calificaciones</h5>
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
                                        <label for="exportPeriod" class="form-label">Período</label>
                                        <select class="form-select" id="exportPeriod">
                                            <option value="all" selected>Todos</option>
                                            <option value="1">1er Bimestre</option>
                                            <option value="2">2do Bimestre</option>
                                            <option value="3">3er Bimestre</option>
                                            <option value="4">4to Bimestre</option>
                                            <option value="final">Final</option>
                                        </select>
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
