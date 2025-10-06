<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';

$profesor_user_id = $_SESSION['user_id'];

// 1. Obtener datos básicos del profesor
$stmt = $mysqli->prepare("
    SELECT p.id as profesor_id, u.nombre, u.apellido
    FROM usuarios u
    JOIN profesores p ON u.id = p.usuario_id
    WHERE u.id = ?
");
$stmt->bind_param('i', $profesor_user_id);
$stmt->execute();
$profesor_data = $stmt->get_result()->fetch_assoc();
$profesor_id = $profesor_data['profesor_id'];
$nombre = $profesor_data['nombre'];
$apellido = $profesor_data['apellido'];
$stmt->close();

// 2. Obtener los cursos del profesor para el selector
$cursos_profesor = [];
$stmt_cursos = $mysqli->prepare("
    SELECT c.id, c.nombre, g.nombre AS grado
    FROM cursos c
    JOIN grados g ON c.grado_id = g.id
    WHERE c.profesor_id = ? AND c.estatus = 'Activo'
    ORDER BY g.id, c.nombre
");
$stmt_cursos->bind_param('i', $profesor_id);
$stmt_cursos->execute();
$result_cursos = $stmt_cursos->get_result();
while ($row = $result_cursos->fetch_assoc()) {
    $cursos_profesor[] = $row;
}
$stmt_cursos->close();

// 3. Determinar el curso y la fecha seleccionados
// Si no hay curso en la URL, se selecciona el primero de la lista. Si no hay fecha, se usa la de hoy.
$curso_id_seleccionado = $_GET['curso_id'] ?? ($cursos_profesor[0]['id'] ?? null);
$fecha_seleccionada = $_GET['fecha'] ?? date('Y-m-d');
$curso_seleccionado_info = null;

// 4. Obtener la lista de estudiantes del curso y su asistencia para la fecha seleccionada
$lista_asistencia = [];
if ($curso_id_seleccionado) {
    // Buscar la información del curso seleccionado
    foreach ($cursos_profesor as $curso) {
        if ($curso['id'] == $curso_id_seleccionado) {
            $curso_seleccionado_info = $curso;
            break;
        }
    }

    $stmt_asistencia = $mysqli->prepare("
        SELECT 
            e.id AS estudiante_id,
            u.nombre,
            u.apellido,
            e.codigo_estudiante,
            m.id AS matricula_id,
            a.estado -- QUITAR a.observaciones de aquí
        FROM matriculas m
        JOIN estudiantes e ON m.estudiante_id = e.id
        JOIN usuarios u ON e.usuario_id = u.id
        LEFT JOIN asistencia a ON m.id = a.matricula_id AND a.fecha = ?
        WHERE m.curso_id = ?
        ORDER BY u.apellido, u.nombre
    ");
    $stmt_asistencia->bind_param('si', $fecha_seleccionada, $curso_id_seleccionado);
    $stmt_asistencia->execute();
    $result_asistencia = $stmt_asistencia->get_result();
    while ($row = $result_asistencia->fetch_assoc()) {
        $lista_asistencia[] = $row;
    }
    $stmt_asistencia->close();
}

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
                    <!-- INICIO: Bloque para mostrar mensajes de sesión -->
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['success_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $_SESSION['error_message']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>
<!-- FIN: Bloque para mostrar mensajes -->
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
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

<!-- Filter and Search -->
<form action="profesor_asistencia.php" method="GET" class="row mb-4">
    <div class="col-md-5">
        <div class="input-group">
            <span class="input-group-text">Curso</span>
            <select class="form-select" id="courseSelect" name="curso_id" onchange="this.form.submit()">
                <?php if (empty($cursos_profesor)): ?>
                    <option disabled selected>No tienes cursos asignados</option>
                <?php else: ?>
                    <?php foreach ($cursos_profesor as $curso): ?>
                        <option value="<?php echo $curso['id']; ?>" <?php echo ($curso_id_seleccionado == $curso['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['grado']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
    </div>
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text">Fecha</span>
            <input type="date" class="form-control" name="fecha" value="<?php echo htmlspecialchars($fecha_seleccionada); ?>">
        </div>
    </div>
    <div class="col-md-3">
        <button type="submit" class="btn btn-academic w-100">
            <i class="bi bi-search"></i> Cargar Asistencia
        </button>
    </div>
</form>

                <!-- Action Buttons -->
                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <button class="btn btn-success me-2">
                            <i class="bi bi-check-all"></i> Marcar Todos Presentes
                        </button>
                        
                    </div>
                </div>

<!-- Daily Attendance -->
<div class="card mb-4">
    <div class="card-header card-header-academic">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white">
                Asistencia: <?php echo htmlspecialchars($curso_seleccionado_info['nombre'] ?? 'Ningún curso seleccionado'); ?>
            </h5>
            <span class="badge bg-light text-dark"><?php echo date('d/m/Y', strtotime($fecha_seleccionada)); ?></span>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($lista_asistencia)): ?>
            <p class="text-center">Por favor, seleccione un curso para tomar la asistencia.</p>
        <?php else: ?>
        <form action="guardar_asistencia.php" method="POST">
            <!-- Campos ocultos para enviar el curso y la fecha -->
            <input type="hidden" name="curso_id" value="<?php echo $curso_id_seleccionado; ?>">
            <input type="hidden" name="fecha" value="<?php echo $fecha_seleccionada; ?>">

            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-academic">
                        <tr>
                            <th>ID Estudiante</th>
                            <th>Nombre</th>
                            <th>Estado</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lista_asistencia as $asistencia): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($asistencia['codigo_estudiante']); ?></td>
                                <td><?php echo htmlspecialchars($asistencia['apellido'] . ', ' . $asistencia['nombre']); ?></td>
                                <td>
                                    <!-- El 'name' del select es un array para poder enviar todos los estados juntos -->
                                    <select class="form-select form-select-sm" name="asistencia[<?php echo $asistencia['matricula_id']; ?>][estado]">
                                        <option value="Presente" <?php if ($asistencia['estado'] == 'Presente' || !$asistencia['estado']) echo 'selected'; ?>>Presente</option>
                                        <option value="Tarde" <?php if ($asistencia['estado'] == 'Tarde') echo 'selected'; ?>>Tardanza</option>
                                        <option value="Ausente" <?php if ($asistencia['estado'] == 'Ausente') echo 'selected'; ?>>Ausente</option>
                                    </select>
                                </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" 
                                            name="asistencia[<?php echo $asistencia['matricula_id']; ?>][observaciones]" 
                                            placeholder="Opcional..."
                                            value="<?php echo htmlspecialchars($asistencia['observaciones'] ?? ''); ?>">
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="submit" class="btn btn-academic">Guardar Todos los Cambios</button>
            </div>
        </form>
        <?php endif; ?>
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
