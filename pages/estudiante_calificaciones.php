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
    <title>Sistema Académico - Calificaciones Estudiante</title>
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
                    <h1 class="h2">Mis Calificaciones</h1>
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

                <!-- Summary card -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Resumen de Calificaciones</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center mb-3">
                                        <h6>Promedio General</h6>
                                        <div class="display-4 fw-bold text-academic">85.7</div>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <h6>Cursos Aprobados</h6>
                                        <div class="display-4 fw-bold text-success">5</div>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <h6>Cursos en Riesgo</h6>
                                        <div class="display-4 fw-bold text-warning">1</div>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <h6>Cursos Reprobados</h6>
                                        <div class="display-4 fw-bold text-danger">0</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grades table -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Detalle de Calificaciones por Curso</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Curso</th>
                                                <th>Profesor</th>
                                                <th>Parcial 1</th>
                                                <th>Parcial 2</th>
                                                <th>Tareas</th>
                                                <th>Proyecto</th>
                                                <th>Final</th>
                                                <th>Promedio</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Programación Avanzada</td>
                                                <td>Carlos Rodríguez</td>
                                                <td>85</td>
                                                <td>90</td>
                                                <td>88</td>
                                                <td>92</td>
                                                <td>87</td>
                                                <td class="fw-bold grade-good">88.4</td>
                                                <td><span class="badge bg-success">Aprobado</span></td>
                                            </tr>
                                            <tr>
                                                <td>Bases de Datos</td>
                                                <td>Ana Martínez</td>
                                                <td>78</td>
                                                <td>82</td>
                                                <td>85</td>
                                                <td>80</td>
                                                <td>84</td>
                                                <td class="fw-bold grade-good">81.8</td>
                                                <td><span class="badge bg-success">Aprobado</span></td>
                                            </tr>
                                            <tr>
                                                <td>Ingeniería de Software</td>
                                                <td>Luis Gómez</td>
                                                <td>92</td>
                                                <td>88</td>
                                                <td>90</td>
                                                <td>95</td>
                                                <td>91</td>
                                                <td class="fw-bold grade-good">91.2</td>
                                                <td><span class="badge bg-success">Aprobado</span></td>
                                            </tr>
                                            <tr>
                                                <td>Redes de Computadoras</td>
                                                <td>Patricia Vega</td>
                                                <td>75</td>
                                                <td>80</td>
                                                <td>82</td>
                                                <td>78</td>
                                                <td>83</td>
                                                <td class="fw-bold grade-good">79.6</td>
                                                <td><span class="badge bg-success">Aprobado</span></td>
                                            </tr>
                                            <tr>
                                                <td>Inteligencia Artificial</td>
                                                <td>Roberto Méndez</td>
                                                <td>95</td>
                                                <td>92</td>
                                                <td>90</td>
                                                <td>94</td>
                                                <td>96</td>
                                                <td class="fw-bold grade-good">93.4</td>
                                                <td><span class="badge bg-success">Aprobado</span></td>
                                            </tr>
                                            <tr>
                                                <td>Seguridad Informática</td>
                                                <td>Elena Torres</td>
                                                <td>68</td>
                                                <td>72</td>
                                                <td>75</td>
                                                <td>70</td>
                                                <td>-</td>
                                                <td class="fw-bold grade-warning">71.3</td>
                                                <td><span class="badge bg-warning">En curso</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grade history -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Historial de Promedios</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Semestre</th>
                                                <th>Promedio</th>
                                                <th>Cursos Aprobados</th>
                                                <th>Cursos Reprobados</th>
                                                <th>Créditos Acumulados</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>2025-1 (Actual)</td>
                                                <td class="fw-bold grade-good">85.7</td>
                                                <td>5</td>
                                                <td>0</td>
                                                <td>24</td>
                                            </tr>
                                            <tr>
                                                <td>2024-2</td>
                                                <td class="fw-bold grade-good">83.2</td>
                                                <td>6</td>
                                                <td>0</td>
                                                <td>28</td>
                                            </tr>
                                            <tr>
                                                <td>2024-1</td>
                                                <td class="fw-bold grade-good">80.5</td>
                                                <td>5</td>
                                                <td>1</td>
                                                <td>24</td>
                                            </tr>
                                            <tr>
                                                <td>2023-2</td>
                                                <td class="fw-bold grade-good">82.8</td>
                                                <td>6</td>
                                                <td>0</td>
                                                <td>28</td>
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

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
