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
    WHERE c.profesor_id = ?
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
<?php
// Rango de fechas (puedes ajustarlo)
$inicio_mes = date('Y-m-01');
$fin_mes = date('Y-m-t');

// Obtenemos asistencia del mes para cada estudiante del curso
$stmt_mes = $mysqli->prepare("
    SELECT 
        e.id AS estudiante_id,
        CONCAT(u.apellido, ', ', u.nombre) AS estudiante,
        a.fecha,
        a.estado
    FROM asistencia a
    JOIN matriculas m ON a.matricula_id = m.id
    JOIN estudiantes e ON m.estudiante_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    WHERE m.curso_id = ? AND a.fecha BETWEEN ? AND ?
    ORDER BY u.apellido, u.nombre, a.fecha
");
$stmt_mes->bind_param('iss', $curso_id_seleccionado, $inicio_mes, $fin_mes);
$stmt_mes->execute();
$result_mes = $stmt_mes->get_result();

$asistencias_mes = [];
while ($row = $result_mes->fetch_assoc()) {
    $asistencias_mes[$row['estudiante_id']]['nombre'] = $row['estudiante'];
    $asistencias_mes[$row['estudiante_id']]['asistencias'][$row['fecha']] = $row['estado'];
}
$stmt_mes->close();

// Generamos cabecera de días (1 al 15, por ejemplo)
$dias_mes = [];
for ($i = 1; $i <= 15; $i++) {
    $dias_mes[] = str_pad($i, 2, '0', STR_PAD_LEFT);
}
?>


                <!-- Monthly Attendance -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Registro Mensual - <?php echo date('F Y'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Estudiante</th>
                                        <?php foreach ($dias_mes as $dia): ?>
                                            <th><?php echo intval($dia); ?></th>
                                        <?php endforeach; ?>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($asistencias_mes as $estudiante_id => $data): 
                                        $total_dias = count($dias_mes);
                                        $presentes = 0;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($data['nombre']); ?></td>
                                            <?php foreach ($dias_mes as $dia): 
                                                $fecha_actual = date('Y-m') . '-' . $dia;
                                                $estado = $data['asistencias'][$fecha_actual] ?? null;
                                                $clase = '';
                                                $simbolo = '';
                                                if ($estado == 'Presente') { $clase = 'table-success'; $simbolo = 'P'; $presentes++; }
                                                elseif ($estado == 'Tarde') { $clase = 'table-warning'; $simbolo = 'T'; $presentes += 0.5; }
                                                elseif ($estado == 'Ausente') { $clase = 'table-danger'; $simbolo = 'A'; }
                                            ?>
                                                <td class="<?php echo $clase; ?>"><?php echo $simbolo; ?></td>
                                            <?php endforeach; ?>
                                            <td><?php echo round(($presentes / $total_dias) * 100, 0) . '%'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>


                <!-- Attendance Statistics -->
                <div class="row mb-4">
                    <!-- Tabla de estadísticas generales -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Estadísticas de Asistencia</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $stmt_estad = $mysqli->prepare("
                                    SELECT 
                                        CONCAT(u.apellido, ', ', u.nombre) AS estudiante,
                                        SUM(a.estado = 'Presente') AS presentes,
                                        SUM(a.estado = 'Tarde') AS tardanzas,
                                        SUM(a.estado = 'Ausente') AS ausencias,
                                        COUNT(a.id) AS total_dias
                                    FROM asistencia a
                                    JOIN matriculas m ON a.matricula_id = m.id
                                    JOIN estudiantes e ON m.estudiante_id = e.id
                                    JOIN usuarios u ON e.usuario_id = u.id
                                    WHERE m.curso_id = ?
                                    GROUP BY e.id
                                    ORDER BY u.apellido
                                ");
                                $stmt_estad->bind_param('i', $curso_id_seleccionado);
                                $stmt_estad->execute();
                                $result_estad = $stmt_estad->get_result();

                                $sum_porcentaje = 0;
                                $total_est = 0;
                                ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Estudiante</th>
                                                <th>Presentes</th>
                                                <th>Tardanzas</th>
                                                <th>Ausencias</th>
                                                <th>% Asistencia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $result_estad->fetch_assoc()): 
                                                $porcentaje = $row['total_dias'] > 0 
                                                    ? round(($row['presentes'] + $row['tardanzas'] * 0.5) / $row['total_dias'] * 100, 0)
                                                    : 0;
                                                $sum_porcentaje += $porcentaje;
                                                $total_est++;
                                            ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['estudiante']); ?></td>
                                                    <td><?php echo $row['presentes']; ?></td>
                                                    <td><?php echo $row['tardanzas']; ?></td>
                                                    <td><?php echo $row['ausencias']; ?></td>
                                                    <td><?php echo $porcentaje . '%'; ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                            <?php if ($total_est > 0): ?>
                                                <tr class="table-academic">
                                                    <td><strong>Promedio</strong></td>
                                                    <td colspan="3"></td>
                                                    <td><strong><?php echo round($sum_porcentaje / $total_est, 0) . '%'; ?></strong></td>
                                                </tr>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center">No hay registros de asistencia</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de estudiantes con problemas -->
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Estudiantes con Problemas de Asistencia</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                // Reutilizamos la misma consulta pero detectamos los problemáticos (<80%)
                                $stmt_problemas = $mysqli->prepare("
                                    SELECT 
                                        CONCAT(u.apellido, ', ', u.nombre) AS estudiante,
                                        c.nombre AS curso,
                                        g.nombre AS grado,
                                        SUM(a.estado = 'Presente') AS presentes,
                                        SUM(a.estado = 'Tarde') AS tardanzas,
                                        SUM(a.estado = 'Ausente') AS ausencias,
                                        COUNT(a.id) AS total_dias
                                    FROM asistencia a
                                    JOIN matriculas m ON a.matricula_id = m.id
                                    JOIN estudiantes e ON m.estudiante_id = e.id
                                    JOIN usuarios u ON e.usuario_id = u.id
                                    JOIN cursos c ON m.curso_id = c.id
                                    JOIN grados g ON c.grado_id = g.id
                                    WHERE c.profesor_id = ?
                                    GROUP BY e.id, c.id
                                    HAVING COUNT(a.id) > 0
                                    AND ((SUM(a.estado = 'Presente') + SUM(a.estado = 'Tarde') * 0.5) / COUNT(a.id) * 100) < 80
                                    ORDER BY ((SUM(a.estado = 'Presente') + SUM(a.estado = 'Tarde') * 0.5) / COUNT(a.id) * 100) ASC
                                ");
                                $stmt_problemas->bind_param('i', $profesor_id);
                                $stmt_problemas->execute();
                                $result_problemas = $stmt_problemas->get_result();
                                $stmt_problemas->close();
                                ?>
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
                                            <?php if ($result_problemas->num_rows > 0): ?>
                                                <?php while ($row = $result_problemas->fetch_assoc()): 
                                                    $porcentaje = round(($row['presentes'] + $row['tardanzas'] * 0.5) / $row['total_dias'] * 100, 0);
                                                    $problema = $row['ausencias'] > ($row['total_dias'] * 0.2)
                                                        ? 'Ausencias frecuentes'
                                                        : 'Tardanzas recurrentes';
                                                ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($row['estudiante']); ?></td>
                                                        <td><?php echo htmlspecialchars($row['curso'] . ' - ' . $row['grado']); ?></td>
                                                        <td><?php echo $porcentaje . '%'; ?></td>
                                                        <td><?php echo $problema; ?></td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-warning" title="Enviar mensaje"><i class="bi bi-chat-dots"></i></button>
                                                            <button class="btn btn-sm btn-outline-info" title="Enviar correo"><i class="bi bi-envelope"></i></button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr><td colspan="5" class="text-center">No se detectaron estudiantes con problemas de asistencia.</td></tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Other Courses -->
                <?php
                $stmt_resumen = $mysqli->prepare("
                    SELECT 
                        c.id,
                        c.nombre AS curso,
                        g.nombre AS grado,
                        COUNT(DISTINCT e.id) AS estudiantes,
                        ROUND(AVG(
                            CASE 
                                WHEN a.estado = 'Presente' THEN 1
                                WHEN a.estado = 'Tarde' THEN 0.5
                                ELSE 0
                            END
                        ) * 100, 0) AS promedio_asistencia
                    FROM cursos c
                    JOIN grados g ON c.grado_id = g.id
                    JOIN matriculas m ON c.id = m.curso_id
                    JOIN estudiantes e ON m.estudiante_id = e.id
                    LEFT JOIN asistencia a ON m.id = a.matricula_id
                    WHERE c.profesor_id = ?
                    GROUP BY c.id
                ");
                $stmt_resumen->bind_param('i', $profesor_id);
                $stmt_resumen->execute();
                $result_resumen = $stmt_resumen->get_result();
                ?>
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result_resumen->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['curso']); ?></td>
                                            <td><?php echo htmlspecialchars($row['grado']); ?></td>
                                            <td><?php echo $row['estudiantes']; ?></td>
                                            <td><?php echo $row['promedio_asistencia'] . '%'; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
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
