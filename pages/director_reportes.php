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
    <title>Sistema Académico - Reportes Director</title>
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
                    <h1 class="h2">Reportes Académicos</h1>
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

                <!-- Report Filters -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Filtros de Reportes</h5>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="reportType" class="form-label">Tipo de Reporte</label>
                                    <select class="form-select" id="reportType">
                                        <option value="academic" selected>Rendimiento Académico</option>
                                        <option value="attendance">Asistencia</option>
                                        <option value="enrollment">Matrícula</option>
                                        <option value="teacher">Desempeño Docente</option>
                                        <option value="financial">Financiero</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="reportPeriod" class="form-label">Período</label>
                                    <select class="form-select" id="reportPeriod">
                                        <option value="current" selected>Semestre Actual (2025-1)</option>
                                        <option value="previous">Semestre Anterior (2024-2)</option>
                                        <option value="year">Año Académico 2024-2025</option>
                                        <option value="custom">Personalizado</option>
                                    </select>
                                </div>
                                
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="reportGrade" class="form-label">Grado/Nivel</label>
                                    <select class="form-select" id="reportGrade">
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
                                <div class="col-md-4">
                                    <label for="reportSubject" class="form-label">Asignatura</label>
                                    <select class="form-select" id="reportSubject">
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
                                <div class="col-md-4">
                                    <label for="reportTeacher" class="form-label">Profesor</label>
                                    <select class="form-select" id="reportTeacher">
                                        <option value="all" selected>Todos</option>
                                        <option value="1">María López</option>
                                        <option value="2">Carlos Rodríguez</option>
                                        <option value="3">Ana Martínez</option>
                                        <option value="4">Juan Pérez</option>
                                        <option value="5">Laura Gómez</option>
                                    </select>
                                </div>
                            </div>
                            <div class="text-end">
                                <button type="button" class="btn btn-academic">Generar Reporte</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recent Reports -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Reportes Recientes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Nombre del Reporte</th>
                                        <th>Tipo</th>
                                        <th>Fecha de Generación</th>
                                        <th>Generado por</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Rendimiento Académico - Semestre 2025-1</td>
                                        <td>Académico</td>
                                        <td>01/06/2025</td>
                                        <td>Roberto Sánchez</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Asistencia Docente - Mayo 2025</td>
                                        <td>Asistencia</td>
                                        <td>28/05/2025</td>
                                        <td>Roberto Sánchez</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Matrícula por Grado - 2025</td>
                                        <td>Matrícula</td>
                                        <td>15/05/2025</td>
                                        <td>Sistema</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Evaluación Docente - Semestre 2024-2</td>
                                        <td>Desempeño</td>
                                        <td>10/02/2025</td>
                                        <td>Roberto Sánchez</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></button>
                                            
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Rendimiento por Asignatura - Matemáticas</td>
                                        <td>Académico</td>
                                        <td>05/02/2025</td>
                                        <td>Sistema</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-success"><i class="bi bi-download"></i></button>
                                            
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Report Preview -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Vista Previa: Rendimiento Académico - Unidad Educativa Eduardo Abaroa</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-12 text-center">
                                <h4>Unidad Educativa Eduardo Avaroa III</h4>
                                <p>El Alto, La Paz, Bolivia</p>
                                <h5>Reporte de Rendimiento Académico - Semestre 2025-1</h5>
                                <p>Fecha de generación: 01/06/2025</p>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Promedio General por Nivel</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Nivel</th>
                                                        <th>Promedio</th>
                                                        <th>Comparación Semestre Anterior</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Primaria</td>
                                                        <td>78.4</td>
                                                        <td><span class="text-success">+2.1%</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Secundaria</td>
                                                        <td>75.2</td>
                                                        <td><span class="text-success">+1.5%</span></td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Total</strong></td>
                                                        <td><strong>76.8</strong></td>
                                                        <td><span class="text-success">+1.8%</span></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Distribución de Calificaciones</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Rango</th>
                                                        <th>Estudiantes</th>
                                                        <th>Porcentaje</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>90-100 (Excelente)</td>
                                                        <td>87</td>
                                                        <td>14.5%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>80-89 (Muy Bueno)</td>
                                                        <td>156</td>
                                                        <td>26.0%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>70-79 (Bueno)</td>
                                                        <td>210</td>
                                                        <td>35.0%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>60-69 (Regular)</td>
                                                        <td>105</td>
                                                        <td>17.5%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>0-59 (Insuficiente)</td>
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
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Rendimiento por Asignatura</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Asignatura</th>
                                                        <th>Promedio</th>
                                                        <th>Aprobados</th>
                                                        <th>Reprobados</th>
                                                        <th>Tasa de Aprobación</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Matemáticas</td>
                                                        <td>72.5</td>
                                                        <td>540</td>
                                                        <td>60</td>
                                                        <td>90.0%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Lenguaje</td>
                                                        <td>78.3</td>
                                                        <td>570</td>
                                                        <td>30</td>
                                                        <td>95.0%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Ciencias Naturales</td>
                                                        <td>75.8</td>
                                                        <td>552</td>
                                                        <td>48</td>
                                                        <td>92.0%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Ciencias Sociales</td>
                                                        <td>79.2</td>
                                                        <td>564</td>
                                                        <td>36</td>
                                                        <td>94.0%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Inglés</td>
                                                        <td>76.4</td>
                                                        <td>546</td>
                                                        <td>54</td>
                                                        <td>91.0%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Educación Física</td>
                                                        <td>85.7</td>
                                                        <td>588</td>
                                                        <td>12</td>
                                                        <td>98.0%</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Artes</td>
                                                        <td>83.2</td>
                                                        <td>582</td>
                                                        <td>18</td>
                                                        <td>97.0%</td>
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
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
