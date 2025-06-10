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
    <title>Sistema Académico - Asistencia Estudiante</title>
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
                    <h1 class="h2">Mi Asistencia</h1>
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
                                <h5 class="mb-0 text-white">Resumen de Asistencia</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center mb-3">
                                        <h6>Asistencia General</h6>
                                        <div class="display-4 fw-bold text-success">92%</div>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <h6>Clases Asistidas</h6>
                                        <div class="display-4 fw-bold text-academic">110</div>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <h6>Faltas Justificadas</h6>
                                        <div class="display-4 fw-bold text-warning">3</div>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <h6>Faltas Injustificadas</h6>
                                        <div class="display-4 fw-bold text-danger">6</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance by course -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Asistencia por Curso</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Curso</th>
                                                <th>Profesor</th>
                                                <th>Clases Totales</th>
                                                <th>Asistencias</th>
                                                <th>Faltas Justificadas</th>
                                                <th>Faltas Injustificadas</th>
                                                <th>Porcentaje</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Programación Avanzada</td>
                                                <td>Carlos Rodríguez</td>
                                                <td>24</td>
                                                <td>22</td>
                                                <td>1</td>
                                                <td>1</td>
                                                <td class="fw-bold text-success">92%</td>
                                                <td><span class="badge bg-success">Aprobado</span></td>
                                            </tr>
                                            <tr>
                                                <td>Bases de Datos</td>
                                                <td>Ana Martínez</td>
                                                <td>24</td>
                                                <td>21</td>
                                                <td>1</td>
                                                <td>2</td>
                                                <td class="fw-bold text-success">88%</td>
                                                <td><span class="badge bg-success">Aprobado</span></td>
                                            </tr>
                                            <tr>
                                                <td>Ingeniería de Software</td>
                                                <td>Luis Gómez</td>
                                                <td>24</td>
                                                <td>24</td>
                                                <td>0</td>
                                                <td>0</td>
                                                <td class="fw-bold text-success">100%</td>
                                                <td><span class="badge bg-success">Aprobado</span></td>
                                            </tr>
                                            <tr>
                                                <td>Redes de Computadoras</td>
                                                <td>Patricia Vega</td>
                                                <td>24</td>
                                                <td>20</td>
                                                <td>1</td>
                                                <td>3</td>
                                                <td class="fw-bold text-warning">83%</td>
                                                <td><span class="badge bg-warning">Atención</span></td>
                                            </tr>
                                            <tr>
                                                <td>Inteligencia Artificial</td>
                                                <td>Roberto Méndez</td>
                                                <td>24</td>
                                                <td>23</td>
                                                <td>0</td>
                                                <td>1</td>
                                                <td class="fw-bold text-success">96%</td>
                                                <td><span class="badge bg-success">Aprobado</span></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed attendance -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Detalle de Asistencia - Junio 2025</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Curso</th>
                                                <th>01/06</th>
                                                <th>02/06</th>
                                                <th>03/06</th>
                                                <th>04/06</th>
                                                <th>05/06</th>
                                                <th>06/06</th>
                                                <th>07/06</th>
                                                <th>08/06</th>
                                                <th>09/06</th>
                                                <th>10/06</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Programación Avanzada</td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                            </tr>
                                            <tr>
                                                <td>Bases de Datos</td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td class="attendance-absent text-center"><i class="bi bi-x-circle-fill text-danger"></i></td>
                                                <td></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td>Ingeniería de Software</td>
                                                <td></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                            </tr>
                                            <tr>
                                                <td>Redes de Computadoras</td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td class="attendance-absent text-center"><i class="bi bi-exclamation-circle-fill text-warning"></i></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                            </tr>
                                            <tr>
                                                <td>Inteligencia Artificial</td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                                <td></td>
                                                <td></td>
                                                <td class="attendance-present text-center"><i class="bi bi-check-circle-fill text-success"></i></td>
                                                <td></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="bi bi-check-circle-fill text-success"></i> Presente &nbsp;
                                        <i class="bi bi-x-circle-fill text-danger"></i> Ausente &nbsp;
                                        <i class="bi bi-exclamation-circle-fill text-warning"></i> Justificado
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                                Cambiar Mes
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><a class="dropdown-item" href="#">Junio 2025 (Actual)</a></li>
                                                <li><a class="dropdown-item" href="#">Mayo 2025</a></li>
                                                <li><a class="dropdown-item" href="#">Abril 2025</a></li>
                                                <li><a class="dropdown-item" href="#">Marzo 2025</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <button class="btn btn-academic">Solicitar Justificación</button>
                                    </div>
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
