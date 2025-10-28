<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';

$profesor_user_id = $_SESSION['user_id'];

// 1. Obtener datos básicos del profesor
$stmt_profesor = $mysqli->prepare("
    SELECT p.id as profesor_id, u.nombre, u.apellido
    FROM usuarios u
    JOIN profesores p ON u.id = p.usuario_id
    WHERE u.id = ?
");
$stmt_profesor->bind_param('i', $profesor_user_id);
$stmt_profesor->execute();
$profesor_data = $stmt_profesor->get_result()->fetch_assoc();
$profesor_id = $profesor_data['profesor_id'];
$nombre = $profesor_data['nombre'];
$apellido = $profesor_data['apellido'];
$stmt_profesor->close();

// 2. Obtener los cursos del profesor para el filtro
$cursos_profesor = [];
$stmt_cursos = $mysqli->prepare("
    SELECT c.id, c.nombre, c.seccion, g.nombre AS grado
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


// 3. Lógica de filtrado
$curso_filtrado_id = $_GET['curso_id'] ?? null;
$where_curso_sql = "";
$params = [$profesor_id];
$types = 'i';

if (!empty($curso_filtrado_id) && is_numeric($curso_filtrado_id)) {
    $where_curso_sql = "AND t.curso_id = ?";
    $params[] = $curso_filtrado_id;
    $types .= 'i';
}


// 4. Obtener las tareas del profesor con estadísticas de entrega
$tareas = [];
$sql_tareas = "
    SELECT 
        t.id as tarea_id, t.titulo, t.tipo, t.fecha_asignacion, t.fecha_entrega,
        c.nombre AS curso_nombre,
        g.nombre AS grado_nombre,
        (SELECT COUNT(*) FROM matriculas WHERE curso_id = c.id) AS total_estudiantes,
        (SELECT COUNT(*) FROM tarea_entregas te WHERE te.tarea_id = t.id) AS total_entregas,
        (SELECT COUNT(*) 
        FROM calificaciones_tareas ct 
        JOIN tarea_entregas te ON ct.tarea_entrega_id = te.id 
        WHERE te.tarea_id = t.id
        ) AS total_calificadas
    FROM tareas t
    JOIN cursos c ON t.curso_id = c.id
    JOIN grados g ON c.grado_id = g.id
    WHERE c.profesor_id = ? {$where_curso_sql}
    ORDER BY c.nombre, t.fecha_entrega DESC
";

$stmt_tareas = $mysqli->prepare($sql_tareas);
$stmt_tareas->bind_param($types, ...$params);
$stmt_tareas->execute();
$result_tareas = $stmt_tareas->get_result();
while ($row = $result_tareas->fetch_assoc()) {
    $tareas[] = $row;
}
$stmt_tareas->close();

// 5. Obtener las tareas pendientes de calificar
$pendientes_calificar = [];
$sql_pendientes = "
    SELECT 
        te.id as entrega_id,
        t.id as tarea_id, t.titulo as tarea_titulo,
        c.nombre as curso_nombre, 
        g.nombre as grado_nombre, -- Ahora 'g' es conocido
        u.nombre as estudiante_nombre, u.apellido as estudiante_apellido,
        te.fecha_envio
    FROM tarea_entregas te
    JOIN tareas t ON te.tarea_id = t.id
    JOIN cursos c ON t.curso_id = c.id
    JOIN grados g ON c.grado_id = g.id  -- <<<<<<< AÑADIR ESTA LÍNEA DEL JOIN
    JOIN matriculas m ON te.matricula_id = m.id
    JOIN estudiantes e ON m.estudiante_id = e.id
    JOIN usuarios u ON e.usuario_id = u.id
    WHERE c.profesor_id = ? 
      AND NOT EXISTS (SELECT 1 FROM calificaciones_tareas ct WHERE ct.tarea_entrega_id = te.id)
    ORDER BY te.fecha_envio ASC
";

$stmt_pendientes = $mysqli->prepare($sql_pendientes);
$stmt_pendientes->bind_param('i', $profesor_id);
$stmt_pendientes->execute();
$result_pendientes = $stmt_pendientes->get_result();
while ($row = $result_pendientes->fetch_assoc()) {
    $pendientes_calificar[] = $row;
}
$stmt_pendientes->close();

// === 6. Generar estadísticas de tareas por curso (desde $tareas) ===
$estadisticas_cursos = [];
$curso_stats = [];

foreach ($tareas as $t) {
    $curso = $t['curso_nombre'] . ' - ' . $t['grado_nombre'];

    if (!isset($curso_stats[$curso])) {
        $curso_stats[$curso] = [
            'curso' => $curso,
            'total_tareas' => 0,
            'activas' => 0,
            'completadas' => 0,
            'promedio' => 0,
            'sum_promedio' => 0,
            'cuenta' => 0
        ];
    }

    $curso_stats[$curso]['total_tareas']++;

    $fecha_entrega = new DateTime($t['fecha_entrega']);
    $hoy = new DateTime();

    if ($fecha_entrega >= $hoy) {
        $curso_stats[$curso]['activas']++;
    } else {
        $curso_stats[$curso]['completadas']++;
    }

    $total_estudiantes = (int)$t['total_estudiantes'];
    $total_entregas = (int)$t['total_entregas'];
    $porcentaje = $total_estudiantes > 0 ? round(($total_entregas / $total_estudiantes) * 100, 1) : 0;
    $curso_stats[$curso]['sum_promedio'] += $porcentaje;
    $curso_stats[$curso]['cuenta']++;
}

// Calcular promedio final
foreach ($curso_stats as &$c) {
    $c['promedio'] = $c['cuenta'] > 0 ? round($c['sum_promedio'] / $c['cuenta'], 1) : 0;
    unset($c['sum_promedio'], $c['cuenta']);
}

$estadisticas_cursos = array_values($curso_stats);

// === 7. Estudiantes con tareas pendientes (simples) ===
$pendientes = [];
foreach ($pendientes_calificar as $p) {
    $nombre_estudiante = $p['estudiante_apellido'] . ', ' . $p['estudiante_nombre'];
    $curso = $p['curso_nombre'] . ' - ' . $p['grado_nombre'];
    $clave = $nombre_estudiante . '|' . $curso;

    if (!isset($pendientes[$clave])) {
        $pendientes[$clave] = [
            'estudiante' => $nombre_estudiante,
            'curso' => $curso,
            'tareas_pendientes' => 0,
            'tareas_vencidas' => 0
        ];
    }

    $pendientes[$clave]['tareas_pendientes']++;
    $fecha_envio = new DateTime($p['fecha_envio']);
    $hoy = new DateTime();
    if ($fecha_envio < $hoy) {
        $pendientes[$clave]['tareas_vencidas']++;
    }
}
$pendientes = array_values($pendientes);


include __DIR__ . '/side_bar_profesor.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Profesor Tareas</title>
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
                    <h1 class="h2">Gestión de Tareas</h1>
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

<!-- Filter and Search -->
<form action="profesor_tareas.php" method="GET" class="row mb-4">
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text">Curso</span>
            <!-- 'onchange' ELIMINADO -->
            <select class="form-select" name="curso_id">
                <option value="">Todos mis cursos</option>
                <?php foreach ($cursos_profesor as $curso): ?>
                    <option value="<?php echo $curso['id']; ?>" <?php if(isset($_GET['curso_id']) && $_GET['curso_id'] == $curso['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['grado'] . ' ' . $curso['seccion']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="col-md-8">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Buscar tarea por título..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
            <button class="btn btn-academic" type="submit"><i class="bi bi-search"></i> Filtrar</button>
            <a href="profesor_tareas.php" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </div>
</form>

                <!-- Action Buttons -->
                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#newAssignmentModal">
                            <i class="bi bi-plus-circle"></i> Nueva Tarea
                        </button>
                        
                    </div>
                </div>

<!-- Assignments List -->
<div class="card mb-4">
    <div class="card-header card-header-academic">
        <h5 class="mb-0 text-white">
            <?php 
                $titulo_tabla = "Tareas Asignadas";
                if ($curso_filtrado_id && !empty($cursos_profesor)) {
                    foreach($cursos_profesor as $curso) {
                        if ($curso['id'] == $curso_filtrado_id) {
                            $titulo_tabla .= ' - ' . htmlspecialchars($curso['nombre'] . ' ' . $curso['grado']);
                            break;
                        }
                    }
                }
                echo $titulo_tabla;
            ?>
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-academic">
                    <tr>
                        <th>Título</th>
                        <th>Curso</th>
                        <th>Fecha de Entrega</th>
                        <th>Estado</th>
                        <th>Entregas</th>
                        <th>Calificadas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tareas)): ?>
                        <tr>
                            <td colspan="7" class="text-center">No hay tareas que coincidan con los filtros.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tareas as $tarea): ?>
                            <?php
                                // Lógica para determinar el estado de la tarea
                                $estado = '';
                                $badge_class = '';
                                $hoy = new DateTime();
                                $fecha_entrega = new DateTime($tarea['fecha_entrega']);
                                $total_estudiantes = (int) $tarea['total_estudiantes'];
                                $total_entregas = (int) $tarea['total_entregas'];
                                $total_calificadas = (int) $tarea['total_calificadas'];
                                
                                if ($total_entregas == $total_estudiantes && $total_entregas == $total_calificadas && $total_estudiantes > 0) {
                                    $estado = 'Completada';
                                    $badge_class = 'bg-success';
                                } elseif ($total_entregas > 0 && $total_calificadas < $total_entregas) {
                                    $estado = 'Calificando';
                                    $badge_class = 'bg-primary';
                                } elseif ($hoy > $fecha_entrega) {
                                    $estado = 'Vencida';
                                    $badge_class = 'bg-danger';
                                } else {
                                    $estado = 'Activa';
                                    $badge_class = 'bg-warning text-dark';
                                }
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tarea['titulo']); ?></td>
                                <td><?php echo htmlspecialchars($tarea['curso_nombre'] . ' ' . $tarea['grado_nombre']); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($tarea['fecha_entrega'])); ?></td>
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo $estado; ?></span></td>
                                <td><?php echo $total_entregas . '/' . $total_estudiantes; ?></td>
                                <td><?php echo $total_calificadas . '/' . $total_entregas; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary btn-ver" data-id="<?php echo $tarea['tarea_id']; ?>" title="Ver Detalles y Entregas"><i class="bi bi-eye"></i></button>
                                    <button class="btn btn-sm btn-outline-secondary btn-editar" data-id="<?php echo $tarea['tarea_id']; ?>" title="Editar Tarea"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-danger btn-deshabilitar" data-id="<?php echo $tarea['tarea_id']; ?>" title="Deshabilitar Tarea"><i class="bi bi-trash"></i></button>
                                </td>

                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Pending Grading -->
<div class="card mb-4">
    <div class="card-header card-header-academic">
        <h5 class="mb-0 text-white">Tareas Pendientes de Calificar</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-academic">
                    <tr>
                        <th>Curso</th>
                        <th>Tarea</th>
                        <th>Estudiante</th>
                        <th>Fecha de Entrega</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendientes_calificar)): ?>
                        <tr>
                            <td colspan="5" class="text-center">¡Buen trabajo! No hay tareas pendientes de calificar.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendientes_calificar as $pendiente): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($pendiente['curso_nombre'] . ' - ' . $pendiente['grado_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($pendiente['tarea_titulo']); ?></td>
                                <td><?php echo htmlspecialchars($pendiente['estudiante_apellido'] . ', ' . $pendiente['estudiante_nombre']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($pendiente['fecha_envio'])); ?></td>
                                <td>
                                    <!-- Este botón debería abrir un modal para calificar esa entrega específica -->
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#gradeAssignmentModal" data-entrega-id="<?php echo $pendiente['entrega_id']; ?>">
                                        <i class="bi bi-check-circle"></i> Calificar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

                <!-- Assignment Statistics -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Estadísticas de Tareas</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Curso</th>
                                                <th>Total Tareas</th>
                                                <th>Activas</th>
                                                <th>Completadas</th>
                                                <th>Promedio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                    <?php if (empty($estadisticas_cursos)): ?>
                                        <tr><td colspan="5" class="text-center text-muted">No hay tareas registradas.</td></tr>
                                    <?php else: ?>
                                        <?php 
                                            $total_tareas = $total_activas = $total_completadas = $promedio_general = 0;
                                            foreach ($estadisticas_cursos as $curso):
                                                $total_tareas += $curso['total_tareas'];
                                                $total_activas += $curso['activas'];
                                                $total_completadas += $curso['completadas'];
                                                $promedio_general += $curso['promedio'];
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($curso['curso']); ?></td>
                                                <td><?php echo $curso['total_tareas']; ?></td>
                                                <td><?php echo $curso['activas']; ?></td>
                                                <td><?php echo $curso['completadas']; ?></td>
                                                <td><?php echo $curso['promedio']; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-academic">
                                            <td><strong>Total</strong></td>
                                            <td><strong><?php echo $total_tareas; ?></strong></td>
                                            <td><strong><?php echo $total_activas; ?></strong></td>
                                            <td><strong><?php echo $total_completadas; ?></strong></td>
                                            <td><strong><?php echo $total_tareas > 0 ? round($promedio_general / count($estadisticas_cursos), 1) : 0; ?></strong></td>
                                        </tr>
                                    <?php endif; ?>
                                    </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Estudiantes con Tareas Pendientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Estudiante</th>
                                                <th>Curso</th>
                                                <th>Tareas Pendientes</th>
                                                <th>Tareas Vencidas</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (empty($pendientes)): ?>
                                            <tr><td colspan="5" class="text-center text-muted">Todos los estudiantes están al día 🎉</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($pendientes as $p): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($p['estudiante']); ?></td>
                                                    <td><?php echo htmlspecialchars($p['curso']); ?></td>
                                                    <td><?php echo $p['tareas_pendientes']; ?></td>
                                                    <td><?php echo $p['tareas_vencidas']; ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-warning" title="Enviar recordatorio"><i class="bi bi-chat-dots"></i></button>
                                                        <button class="btn btn-sm btn-outline-info" title="Enviar correo"><i class="bi bi-envelope"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
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

    <!-- New Assignment Modal -->
    <div class="modal fade" id="newAssignmentModal" tabindex="-1" aria-labelledby="newAssignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="newAssignmentModalLabel">Nueva Tarea</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <!-- INICIO DEL FORMULARIO MODIFICADO -->
                <form action="guardar_tarea.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="assignmentTitle" class="form-label">Título</label>
                                <input type="text" class="form-control" id="assignmentTitle" name="assignmentTitle" placeholder="Ej: Ejercicios de Límites" required>
                            </div>
                            <div class="col-md-6">
                                <label for="assignmentCourse" class="form-label">Curso</label>
                                <select class="form-select" id="assignmentCourse" name="assignmentCourse" required>
                                    <option selected disabled value="">Seleccionar curso...</option>
                                    <?php foreach ($cursos_profesor as $curso): ?>
                                        <option value="<?php echo $curso['id']; ?>">
                                            <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['grado'] . $curso['seccion']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="assignmentType" class="form-label">Tipo</label>
                                <select class="form-select" id="assignmentType" name="assignmentType" required>
                                    <option selected disabled value="">Seleccionar tipo...</option>
                                    <option value="Tarea">Tarea</option>
                                    <option value="Práctica">Práctica</option>
                                    <option value="Proyecto">Proyecto</option>
                                    <option value="Investigación">Investigación</option>
                                    <option value="Cuestionario">Cuestionario</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="assignmentWeight" class="form-label">Ponderación (%)</label>
                                <input type="number" class="form-control" id="assignmentWeight" name="assignmentWeight" min="0" max="100" value="10" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="assignmentStartDate" class="form-label">Fecha de Asignación</label>
                                <input type="date" class="form-control" id="assignmentStartDate" name="assignmentStartDate" required>
                            </div>
                            <div class="col-md-6">
                                <label for="assignmentDueDate" class="form-label">Fecha de Entrega</label>
                                <input type="date" class="form-control" id="assignmentDueDate" name="assignmentDueDate" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="assignmentDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="assignmentDescription" name="assignmentDescription" rows="3" placeholder="Descripción detallada de la tarea..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="assignmentInstructions" class="form-label">Instrucciones</label>
                            <textarea class="form-control" id="assignmentInstructions" name="assignmentInstructions" rows="3" placeholder="Instrucciones específicas para completar la tarea..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="assignmentResources" class="form-label">Recursos</label>
                            <textarea class="form-control" id="assignmentResources" name="assignmentResources" rows="2" placeholder="Enlaces, libros, o materiales de referencia..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="assignmentAttachments" class="form-label">Archivos Adjuntos</label>
                            <!-- 'multiple' permite seleccionar varios archivos. El '[]' en el name es VITAL. -->
                            <input class="form-control" type="file" id="assignmentAttachments" name="assignmentAttachments[]" multiple>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="assignmentMaxScore" class="form-label">Puntaje Máximo</label>
                                <input type="number" class="form-control" id="assignmentMaxScore" name="assignmentMaxScore" min="1" value="100" required>
                            </div>
                            <div class="col-md-6">
                                <label for="assignmentSubmissionType" class="form-label">Tipo de Entrega</label>
                                <select class="form-select" id="assignmentSubmissionType" name="assignmentSubmissionType" required>
                                    <option value="Archivo" selected>Archivo</option>
                                    <option value="Texto en línea">Texto en línea</option>
                                    <option value="Ambos">Ambos</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notifyStudentsAssignment" name="notifyStudentsAssignment" value="1" checked>
                                <label class="form-check-label" for="notifyStudentsAssignment">
                                    Notificar a los estudiantes
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <!-- Cambiado a type="submit" -->
                        <button type="submit" class="btn btn-academic">Guardar Tarea</button>
                    </div>
                </form>
                <!-- FIN DEL FORMULARIO MODIFICADO -->
            </div>
        </div>
    </div>
    <!-- View Assignment Modal -->
    <div class="modal fade" id="viewAssignmentModal" tabindex="-1" aria-labelledby="viewAssignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="viewAssignmentModalLabel">Ejercicios de Límites</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="assignmentTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="true">Detalles</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="submissions-tab" data-bs-toggle="tab" data-bs-target="#submissions" type="button" role="tab" aria-controls="submissions" aria-selected="false">Entregas</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="grades-tab" data-bs-toggle="tab" data-bs-target="#grades" type="button" role="tab" aria-controls="grades" aria-selected="false">Calificaciones</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="statistics-tab" data-bs-toggle="tab" data-bs-target="#statistics" type="button" role="tab" aria-controls="statistics" aria-selected="false">Estadísticas</button>
                        </li>
                    </ul>
                    <div class="tab-content pt-3" id="assignmentTabContent">
                        <div class="tab-pane fade show active" id="details" role="tabpanel" aria-labelledby="details-tab">
                            <div class="row">
                                <div class="col-md-8">
                                    <h5>Información General</h5>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <p><strong>Curso:</strong> Matemáticas - 6° Secundaria</p>
                                            <p><strong>Tipo:</strong> Práctica</p>
                                            <p><strong>Fecha de Asignación:</strong> 15/05/2025</p>
                                            <p><strong>Fecha de Entrega:</strong> 22/05/2025</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Estado:</strong> <span class="badge bg-success">Completada</span></p>
                                            <p><strong>Ponderación:</strong> 10%</p>
                                            <p><strong>Puntaje Máximo:</strong> 100</p>
                                            <p><strong>Tipo de Entrega:</strong> Archivo</p>
                                        </div>
                                    </div>
                                    
                                    <h5>Descripción</h5>
                                    <p>Esta tarea consiste en resolver una serie de ejercicios sobre límites y continuidad de funciones. Los estudiantes deberán aplicar las propiedades de los límites y las técnicas de cálculo para resolver problemas de diferentes niveles de dificultad.</p>
                                    
                                    <h5>Instrucciones</h5>
                                    <ol>
                                        <li>Resolver todos los ejercicios propuestos en el documento adjunto.</li>
                                        <li>Mostrar todos los pasos del procedimiento de manera clara y ordenada.</li>
                                        <li>Utilizar la notación matemática correcta.</li>
                                        <li>Entregar el trabajo en formato PDF.</li>
                                        <li>Nombrar el archivo con el siguiente formato: Apellido_Nombre_Limites.pdf</li>
                                    </ol>
                                    
                                    <h5>Recursos</h5>
                                    <ul>
                                        <li>Libro de texto: Cálculo Diferencial e Integral, Capítulo 2</li>
                                        <li>Material de clase: Presentación sobre límites y continuidad</li>
                                        <li>Videos explicativos disponibles en la plataforma</li>
                                    </ul>
                                </div>
                                <div class="col-md-4">
                                    <div class="card mb-3">
                                        <div class="card-header card-header-academic">
                                            <h6 class="mb-0 text-white">Archivos Adjuntos</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-group">
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="bi bi-file-pdf me-2"></i>
                                                        Ejercicios_Limites.pdf
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button>
                                                </li>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="bi bi-file-earmark-text me-2"></i>
                                                        Guia_Solucion.docx
                                                    </div>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    
                                    <div class="card mb-3">
                                        <div class="card-header card-header-academic">
                                            <h6 class="mb-0 text-white">Resumen de Entregas</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Total de Estudiantes:</span>
                                                <strong>25</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Entregas Recibidas:</span>
                                                <strong>25/25 (100%)</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Entregas Calificadas:</span>
                                                <strong>25/25 (100%)</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Entregas a Tiempo:</span>
                                                <strong>23/25 (92%)</strong>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span>Entregas Tardías:</span>
                                                <strong>2/25 (8%)</strong>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Promedio de Calificación:</span>
                                                <strong>85.6/100</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="submissions" role="tabpanel" aria-labelledby="submissions-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Entregas de Estudiantes</h5>
                                <button class="btn btn-sm btn-academic">
                                    <i class="bi bi-download"></i> Descargar Todas
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Fecha de Entrega</th>
                                            <th>Estado</th>
                                            <th>Archivo</th>
                                            <th>Calificación</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Alejandro Gómez</td>
                                            <td>21/05/2025 15:45</td>
                                            <td><span class="badge bg-success">A tiempo</span></td>
                                            <td>
                                                <a href="#"><i class="bi bi-file-pdf me-1"></i>Gomez_Alejandro_Limites.pdf</a>
                                            </td>
                                            <td>90/100</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Carla Mendoza</td>
                                            <td>20/05/2025 10:30</td>
                                            <td><span class="badge bg-success">A tiempo</span></td>
                                            <td>
                                                <a href="#"><i class="bi bi-file-pdf me-1"></i>Mendoza_Carla_Limites.pdf</a>
                                            </td>
                                            <td>95/100</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Daniel Flores</td>
                                            <td>22/05/2025 23:15</td>
                                            <td><span class="badge bg-warning">Último minuto</span></td>
                                            <td>
                                                <a href="#"><i class="bi bi-file-pdf me-1"></i>Flores_Daniel_Limites.pdf</a>
                                            </td>
                                            <td>75/100</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Elena Vargas</td>
                                            <td>21/05/2025 18:20</td>
                                            <td><span class="badge bg-success">A tiempo</span></td>
                                            <td>
                                                <a href="#"><i class="bi bi-file-pdf me-1"></i>Vargas_Elena_Limites.pdf</a>
                                            </td>
                                            <td>88/100</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Fernando Quispe</td>
                                            <td>23/05/2025 10:05</td>
                                            <td><span class="badge bg-danger">Tardía</span></td>
                                            <td>
                                                <a href="#"><i class="bi bi-file-pdf me-1"></i>Quispe_Fernando_Limites.pdf</a>
                                            </td>
                                            <td>65/100</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-info"><i class="bi bi-chat-dots"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="grades" role="tabpanel" aria-labelledby="grades-tab">
                            <div class="d-flex justify-content-between mb-3">
                                <h5>Calificaciones</h5>
                                <div>
                                    <button class="btn btn-sm btn-primary me-2">
                                        <i class="bi bi-download"></i> Exportar
                                    </button>
                                    <button class="btn btn-sm btn-secondary">
                                        <i class="bi bi-printer"></i> Imprimir
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Estudiante</th>
                                            <th>Calificación</th>
                                            <th>Porcentaje</th>
                                            <th>Estado</th>
                                            <th>Comentarios</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Alejandro Gómez</td>
                                            <td>90/100</td>
                                            <td>90%</td>
                                            <td><span class="badge bg-success">Excelente</span></td>
                                            <td>Excelente trabajo, muy completo y bien presentado.</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Carla Mendoza</td>
                                            <td>95/100</td>
                                            <td>95%</td>
                                            <td><span class="badge bg-success">Excelente</span></td>
                                            <td>Trabajo sobresaliente, con soluciones creativas y bien fundamentadas.</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Daniel Flores</td>
                                            <td>75/100</td>
                                            <td>75%</td>
                                            <td><span class="badge bg-warning">Regular</span></td>
                                            <td>Trabajo aceptable, pero con algunos errores conceptuales.</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Elena Vargas</td>
                                            <td>88/100</td>
                                            <td>88%</td>
                                            <td><span class="badge bg-primary">Bueno</span></td>
                                            <td>Buen trabajo, con procedimientos claros y bien explicados.</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Fernando Quispe</td>
                                            <td>65/100</td>
                                            <td>65%</td>
                                            <td><span class="badge bg-danger">Insuficiente</span></td>
                                            <td>Trabajo incompleto y con varios errores. Entrega tardía.</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot class="table-academic">
                                        <tr>
                                            <td><strong>Promedio</strong></td>
                                            <td><strong>82.6/100</strong></td>
                                            <td><strong>82.6%</strong></td>
                                            <td colspan="3"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="statistics" role="tabpanel" aria-labelledby="statistics-tab">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header card-header-academic">
                                            <h6 class="mb-0 text-white">Distribución de Calificaciones</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead class="table-academic">
                                                        <tr>
                                                            <th>Rango</th>
                                                            <th>Categoría</th>
                                                            <th>Estudiantes</th>
                                                            <th>Porcentaje</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>90-100</td>
                                                            <td><span class="badge bg-success">Excelente</span></td>
                                                            <td>5</td>
                                                            <td>20%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>80-89</td>
                                                            <td><span class="badge bg-primary">Bueno</span></td>
                                                            <td>10</td>
                                                            <td>40%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>70-79</td>
                                                            <td><span class="badge bg-info">Satisfactorio</span></td>
                                                            <td>6</td>
                                                            <td>24%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>60-69</td>
                                                            <td><span class="badge bg-warning">Regular</span></td>
                                                            <td>3</td>
                                                            <td>12%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>0-59</td>
                                                            <td><span class="badge bg-danger">Insuficiente</span></td>
                                                            <td>1</td>
                                                            <td>4%</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card mb-4">
                                        <div class="card-header card-header-academic">
                                            <h6 class="mb-0 text-white">Estadísticas de Entrega</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="table-responsive">
                                                <table class="table table-sm">
                                                    <thead class="table-academic">
                                                        <tr>
                                                            <th>Categoría</th>
                                                            <th>Cantidad</th>
                                                            <th>Porcentaje</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>Entregas Anticipadas (>24h)</td>
                                                            <td>15</td>
                                                            <td>60%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Entregas a Tiempo (último día)</td>
                                                            <td>8</td>
                                                            <td>32%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>Entregas Tardías</td>
                                                            <td>2</td>
                                                            <td>8%</td>
                                                        </tr>
                                                        <tr>
                                                            <td>No Entregadas</td>
                                                            <td>0</td>
                                                            <td>0%</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-4">
                                <div class="card-header card-header-academic">
                                    <h6 class="mb-0 text-white">Análisis de Dificultad</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead class="table-academic">
                                                <tr>
                                                    <th>Ejercicio</th>
                                                    <th>Promedio</th>
                                                    <th>% de Acierto</th>
                                                    <th>Dificultad</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Ejercicio 1: Límites algebraicos</td>
                                                    <td>9.2/10</td>
                                                    <td>92%</td>
                                                    <td><span class="badge bg-success">Fácil</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Ejercicio 2: Límites trigonométricos</td>
                                                    <td>8.5/10</td>
                                                    <td>85%</td>
                                                    <td><span class="badge bg-primary">Moderado</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Ejercicio 3: Límites con indeterminaciones</td>
                                                    <td>7.2/10</td>
                                                    <td>72%</td>
                                                    <td><span class="badge bg-primary">Moderado</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Ejercicio 4: Límites laterales</td>
                                                    <td>8.8/10</td>
                                                    <td>88%</td>
                                                    <td><span class="badge bg-primary">Moderado</span></td>
                                                </tr>
                                                <tr>
                                                    <td>Ejercicio 5: Continuidad de funciones</td>
                                                    <td>6.5/10</td>
                                                    <td>65%</td>
                                                    <td><span class="badge bg-warning">Difícil</span></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-academic">Generar Reporte</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Grade Assignment Modal -->
    <div class="modal fade" id="gradeAssignmentModal" tabindex="-1" aria-labelledby="gradeAssignmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="gradeAssignmentModalLabel">Calificar Tarea: Derivadas Parciales</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Información de la Entrega</h6>
                            <p><strong>Estudiante:</strong> Alejandro Gómez</p>
                            <p><strong>Fecha de Entrega:</strong> 28/05/2025 15:30</p>
                            <p><strong>Estado:</strong> <span class="badge bg-success">A tiempo</span></p>
                            <p><strong>Archivo:</strong> <a href="#"><i class="bi bi-file-pdf me-1"></i>Gomez_Alejandro_Derivadas.pdf</a></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Información de la Tarea</h6>
                            <p><strong>Título:</strong> Derivadas Parciales</p>
                            <p><strong>Curso:</strong> Matemáticas - 6° Secundaria</p>
                            <p><strong>Fecha Límite:</strong> 29/05/2025</p>
                            <p><strong>Puntaje Máximo:</strong> 100</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Vista Previa del Documento</h6>
                                </div>
                                <div class="card-body text-center">
                                    <p class="text-muted">Vista previa del documento PDF</p>
                                    <img src="https://via.placeholder.com/600x400" class="img-fluid border" alt="Vista previa del documento">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gradeValue" class="form-label">Calificación</label>
                                <input type="number" class="form-control" id="gradeValue" min="0" max="100" value="85">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="gradeStatus" class="form-label">Estado</label>
                                <select class="form-select" id="gradeStatus">
                                    <option value="excellent">Excelente (90-100)</option>
                                    <option selected value="good">Bueno (80-89)</option>
                                    <option value="satisfactory">Satisfactorio (70-79)</option>
                                    <option value="regular">Regular (60-69)</option>
                                    <option value="insufficient">Insuficiente (0-59)</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="gradeComments" class="form-label">Comentarios</label>
                        <textarea class="form-control" id="gradeComments" rows="4" placeholder="Comentarios para el estudiante...">Buen trabajo en general. Los ejercicios 1-3 están perfectos. En el ejercicio 4 hay un error en el cálculo de la derivada parcial respecto a y. El ejercicio 5 está incompleto. Revisa los conceptos de derivadas parciales de segundo orden.</textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="notifyStudent" checked>
                            <label class="form-check-label" for="notifyStudent">
                                Notificar al estudiante
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btnGuardarCalificacion" class="btn btn-academic">Guardar Calificación</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    $(document).ready(function() {
        // Ver tarea
        $('.btn-ver').on('click', function() {
            const id = $(this).data('id');
            $.get('ver_tarea.php', { id }, function(data) {
                if (data) {
                    Swal.fire({
                        title: data.titulo,
                        html: `
                            <p><strong>Curso:</strong> ${data.curso_nombre} - ${data.grado_nombre} ${data.seccion}</p>
                            <p><strong>Tipo:</strong> ${data.tipo}</p>
                            <p><strong>Fecha de entrega:</strong> ${data.fecha_entrega}</p>
                            <p><strong>Descripción:</strong> ${data.descripcion || '(Sin descripción)'}</p>
                            <p><strong>Instrucciones:</strong> ${data.instrucciones || '(No especificadas)'}</p>
                            <p><strong>Recursos:</strong> ${data.recursos || '(Ninguno)'}</p>
                        `,
                        icon: 'info',
                        confirmButtonText: 'Cerrar',
                        confirmButtonColor: '#3c8dbc'
                    });
                }
            }, 'json');
        });

        // Editar tarea
        $('.btn-editar').on('click', function() {
            const id = $(this).data('id');
            $.get('ver_tarea.php', { id }, function(data) {
                if (data) {
                    Swal.fire({
                        title: 'Editar Tarea',
                        html: `
                            <input type="hidden" id="edit-id" value="${data.id}">
                            <input id="edit-titulo" class="swal2-input" value="${data.titulo}">
                            <textarea id="edit-descripcion" class="swal2-textarea">${data.descripcion || ''}</textarea>
                            <input id="edit-fecha" type="date" class="swal2-input" value="${data.fecha_entrega}">
                        `,
                        showCancelButton: true,
                        confirmButtonText: 'Guardar',
                        cancelButtonText: 'Cancelar',
                        preConfirm: () => {
                            return {
                                id: $('#edit-id').val(),
                                titulo: $('#edit-titulo').val(),
                                descripcion: $('#edit-descripcion').val(),
                                fecha_entrega: $('#edit-fecha').val()
                            };
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post('editar_tarea.php', result.value, function(resp) {
                                Swal.fire({
                                    icon: resp.success ? 'success' : 'error',
                                    title: resp.message,
                                    timer: 2000,
                                    showConfirmButton: false
                                }).then(() => location.reload());
                            }, 'json');
                        }
                    });
                }
            }, 'json');
        });

        // Deshabilitar tarea
        $('.btn-deshabilitar').on('click', function() {
            const id = $(this).data('id');
            Swal.fire({
                title: '¿Deshabilitar tarea?',
                text: 'Esta acción marcará la tarea como deshabilitada.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, deshabilitar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.post('deshabilitar_tarea.php', { id }, function(resp) {
                        Swal.fire({
                            icon: resp.success ? 'success' : 'error',
                            title: resp.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    }, 'json');
                }
            });
        });
    });
    
    // Guardar calificación de tarea
    $('#btnGuardarCalificacion').on('click', function () {
        const entregaId = $('#gradeAssignmentModal').data('entrega-id') || 0;
        const calificacion = $('#gradeValue').val();
        const comentario = $('#gradeComments').val();

        if (!entregaId) {
        Swal.fire({icon:'error', title:'Falta el ID de entrega', text:'Vuelve a abrir el modal desde el botón Calificar.'});
        return;
        }

        $.post('guardar_calificacion_tarea.php', {
        tarea_entrega_id: entregaId,
        calificacion,
        comentario
        }, function (resp) {
        // éxito con JSON válido
        Swal.fire({
            icon: resp.success ? 'success' : 'error',
            title: resp.message,
            timer: 1800,
            showConfirmButton: false
        }).then(() => {
            if (resp.success) location.reload();
        });
        }, 'json')
        .fail(function (xhr) {
        // fallo de red o el PHP no devolvió JSON válido
        console.error('Respuesta del servidor:', xhr.responseText);
        Swal.fire({
            icon:'error',
            title:'No se pudo guardar',
            text:'Revisa la consola (F12 → Console) y Network. Puede ser 404 o un warning en PHP.'
        });
        });
    });
    // Copia el data-entrega-id del botón al modal cuando se abre
    $('#gradeAssignmentModal').on('show.bs.modal', function (e) {
    const trigger = $(e.relatedTarget); // botón que abrió el modal
    const entregaId = trigger.data('entrega-id');
    $(this).data('entrega-id', entregaId);
    });
    </script>


</body>
</html>
