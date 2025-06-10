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
    <title>Sistema Académico - Profesor Asistencia</title>
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
                    <h1 class="h2">Control de Asistencia</h1>
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
                            <span class="input-group-text">Fecha</span>
                            <input type="date" class="form-control" value="2025-06-02">
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
                            <input type="text" class="form-control" placeholder="Buscar estudiante...">
                            <button class="btn btn-academic" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <button class="btn btn-success me-2">
                            <i class="bi bi-check-all"></i> Marcar Todos Presentes
                        </button>
                        
                    </div>
                </div>

                <!-- Today's Attendance -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-white">Asistencia de Hoy - Matemáticas 6° Secundaria</h5>
                            <span class="badge bg-light text-dark">02/06/2025</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>ID</th>
                                        <th>Estudiante</th>
                                        <th>Estado</th>
                                        <th>Hora de Registro</th>
                                        <th>Observaciones</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>EST-001</td>
                                        <td>Alejandro Gómez</td>
                                        <td>
                                            <select class="form-select form-select-sm">
                                                <option selected value="present">Presente</option>
                                                <option value="late">Tardanza</option>
                                                <option value="absent">Ausente</option>
                                                <option value="justified">Justificado</option>
                                            </select>
                                        </td>
                                        <td>08:15 AM</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" placeholder="Observaciones...">
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-save"></i></button>
                                            <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-002</td>
                                        <td>Carla Mendoza</td>
                                        <td>
                                            <select class="form-select form-select-sm">
                                                <option selected value="present">Presente</option>
                                                <option value="late">Tardanza</option>
                                                <option value="absent">Ausente</option>
                                                <option value="justified">Justificado</option>
                                            </select>
                                        </td>
                                        <td>08:10 AM</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" placeholder="Observaciones...">
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-save"></i></button>
                                            <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-003</td>
                                        <td>Daniel Flores</td>
                                        <td>
                                            <select class="form-select form-select-sm">
                                                <option value="present">Presente</option>
                                                <option selected value="late">Tardanza</option>
                                                <option value="absent">Ausente</option>
                                                <option value="justified">Justificado</option>
                                            </select>
                                        </td>
                                        <td>08:25 AM</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" value="Llegó 15 minutos tarde">
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-save"></i></button>
                                            <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-004</td>
                                        <td>Elena Vargas</td>
                                        <td>
                                            <select class="form-select form-select-sm">
                                                <option selected value="present">Presente</option>
                                                <option value="late">Tardanza</option>
                                                <option value="absent">Ausente</option>
                                                <option value="justified">Justificado</option>
                                            </select>
                                        </td>
                                        <td>08:12 AM</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" placeholder="Observaciones...">
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-save"></i></button>
                                            <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>EST-005</td>
                                        <td>Fernando Quispe</td>
                                        <td>
                                            <select class="form-select form-select-sm">
                                                <option value="present">Presente</option>
                                                <option value="late">Tardanza</option>
                                                <option selected value="absent">Ausente</option>
                                                <option value="justified">Justificado</option>
                                            </select>
                                        </td>
                                        <td>-</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" value="No asistió a clases">
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-save"></i></button>
                                            <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-academic">Guardar Todos los Cambios</button>
                        </div>
                    </div>
                </div>

                <!-- Monthly Attendance -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Registro Mensual - Junio 2025</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Estudiante</th>
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
                                        <td>Alejandro Gómez</td>
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
                                        <td>Carla Mendoza</td>
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
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td>100%</td>
                                    </tr>
                                    <tr>
                                        <td>Daniel Flores</td>
                                        <td class="table-success">P</td>
                                        <td class="table-warning">T</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-warning">T</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-danger">A</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-warning">T</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-warning">T</td>
                                        <td>87%</td>
                                    </tr>
                                    <tr>
                                        <td>Elena Vargas</td>
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
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td>100%</td>
                                    </tr>
                                    <tr>
                                        <td>Fernando Quispe</td>
                                        <td class="table-success">P</td>
                                        <td class="table-danger">A</td>
                                        <td class="table-success">P</td>
                                        <td class="table-danger">A</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-warning">T</td>
                                        <td class="table-success">P</td>
                                        <td class="table-danger">A</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-danger">A</td>
                                        <td class="table-success">P</td>
                                        <td class="table-success">P</td>
                                        <td class="table-danger">A</td>
                                        <td>73%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            <p><small class="text-muted">P: Presente, T: Tardanza, A: Ausente, J: Justificado</small></p>
                        </div>
                    </div>
                </div>

                <!-- Attendance Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Estadísticas de Asistencia</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Estudiante</th>
                                                <th>Presentes</th>
                                                <th>Tardanzas</th>
                                                <th>Ausencias</th>
                                                <th>Justificadas</th>
                                                <th>% Asistencia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Alejandro Gómez</td>
                                                <td>13</td>
                                                <td>1</td>
                                                <td>1</td>
                                                <td>0</td>
                                                <td>93%</td>
                                            </tr>
                                            <tr>
                                                <td>Carla Mendoza</td>
                                                <td>15</td>
                                                <td>0</td>
                                                <td>0</td>
                                                <td>0</td>
                                                <td>100%</td>
                                            </tr>
                                            <tr>
                                                <td>Daniel Flores</td>
                                                <td>11</td>
                                                <td>3</td>
                                                <td>1</td>
                                                <td>0</td>
                                                <td>87%</td>
                                            </tr>
                                            <tr>
                                                <td>Elena Vargas</td>
                                                <td>15</td>
                                                <td>0</td>
                                                <td>0</td>
                                                <td>0</td>
                                                <td>100%</td>
                                            </tr>
                                            <tr>
                                                <td>Fernando Quispe</td>
                                                <td>9</td>
                                                <td>1</td>
                                                <td>5</td>
                                                <td>0</td>
                                                <td>73%</td>
                                            </tr>
                                            <tr class="table-academic">
                                                <td><strong>Promedio</strong></td>
                                                <td><strong>12.6</strong></td>
                                                <td><strong>1.0</strong></td>
                                                <td><strong>1.4</strong></td>
                                                <td><strong>0.0</strong></td>
                                                <td><strong>91%</strong></td>
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
                                <h5 class="mb-0 text-white">Estudiantes con Problemas de Asistencia</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Estudiante</th>
                                                <th>Curso</th>
                                                <th>% Asistencia</th>
                                                <th>Problema</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Fernando Quispe</td>
                                                <td>Matemáticas - 6° Secundaria</td>
                                                <td>73%</td>
                                                <td>Ausencias frecuentes sin justificación</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-warning"><i class="bi bi-chat-dots"></i></button>
                                                    <button class="btn btn-sm btn-outline-info"><i class="bi bi-envelope"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Luis Mamani</td>
                                                <td>Física - 5° Secundaria</td>
                                                <td>68%</td>
                                                <td>Ausencias frecuentes sin justificación</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-warning"><i class="bi bi-chat-dots"></i></button>
                                                    <button class="btn btn-sm btn-outline-info"><i class="bi bi-envelope"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Patricia Flores</td>
                                                <td>Química - 6° Secundaria</td>
                                                <td>75%</td>
                                                <td>Tardanzas frecuentes</td>
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

                <!-- Other Courses -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Resumen de Asistencia por Cursos</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Curso</th>
                                        <th>Grado</th>
                                        <th>Estudiantes</th>
                                        <th>% Asistencia Promedio</th>
                                        <th>Estudiantes con Problemas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Matemáticas</td>
                                        <td>6° Secundaria</td>
                                        <td>25</td>
                                        <td>91%</td>
                                        <td>1</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Ver Detalles</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Matemáticas</td>
                                        <td>5° Secundaria</td>
                                        <td>25</td>
                                        <td>89%</td>
                                        <td>2</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Ver Detalles</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Física</td>
                                        <td>6° Secundaria</td>
                                        <td>25</td>
                                        <td>90%</td>
                                        <td>0</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Ver Detalles</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Física</td>
                                        <td>5° Secundaria</td>
                                        <td>25</td>
                                        <td>87%</td>
                                        <td>1</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Ver Detalles</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Química</td>
                                        <td>6° Secundaria</td>
                                        <td>25</td>
                                        <td>88%</td>
                                        <td>1</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Ver Detalles</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Química</td>
                                        <td>5° Secundaria</td>
                                        <td>25</td>
                                        <td>92%</td>
                                        <td>0</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary">Ver Detalles</button>
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

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
