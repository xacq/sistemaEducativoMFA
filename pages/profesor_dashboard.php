<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';

// --- INICIALIZACIÓN DE VARIABLES ---
$nombre = '';
$apellido = '';
$config = [];
$clases_hoy = [];
$stats = [
    'total_estudiantes' => 0,
    'total_cursos' => 0,
    'asistencia_promedio' => 0,
    'promedio_notas' => 0
];
$profesor_id = 0;

// --- LÓGICA DE DATOS ---

// 1. OBTENER DATOS DEL USUARIO Y PROFESOR LOGUEADO
$usuario_id = $_SESSION['user_id'];
$stmt_profesor = $mysqli->prepare("
    SELECT u.nombre, u.apellido, p.id AS profesor_id
    FROM usuarios u
    JOIN profesores p ON u.id = p.usuario_id
    WHERE u.id = ?
");
$stmt_profesor->bind_param('i', $usuario_id);
$stmt_profesor->execute();
$result_profesor = $stmt_profesor->get_result();
if ($profesor_data = $result_profesor->fetch_assoc()) {
    $nombre = $profesor_data['nombre'];
    $apellido = $profesor_data['apellido'];
    $profesor_id = $profesor_data['profesor_id'];
}
$stmt_profesor->close();

if ($profesor_id === 0) {
    // Manejar el caso en que el usuario no sea un profesor
    die("Acceso denegado. No se encontró un perfil de profesor asociado a este usuario.");
}

// 2. OBTENER CONFIGURACIÓN INSTITUCIONAL
if ($result_config = $mysqli->query("SELECT llave, valor FROM configuracion")) {
    while ($row = $result_config->fetch_assoc()) {
        $config[$row['llave']] = $row['valor'];
    }
    $result_config->free();
}

// 3. OBTENER CLASES DE HOY PARA ESTE PROFESOR
$dia_semana_en = strtolower(date('l')); // 'l' devuelve el nombre completo del día en inglés
$sql_clases_hoy = "
    SELECT 
        h.hora_inicio, h.hora_fin, h.aula,
        c.nombre as nombre_curso, g.nombre as nombre_grado,
        (SELECT COUNT(id) FROM matriculas WHERE curso_id = c.id) as total_estudiantes
    FROM horarios h
    JOIN cursos c ON h.curso_id = c.id
    JOIN grados g ON c.grado_id = g.id
    WHERE h.dia = ? AND c.profesor_id = ? AND c.estatus = 'Activo'
    ORDER BY h.hora_inicio
";
$stmt_clases = $mysqli->prepare($sql_clases_hoy);
$stmt_clases->bind_param('si', $dia_semana_en, $profesor_id);
$stmt_clases->execute();
$result_clases = $stmt_clases->get_result();
while ($row = $result_clases->fetch_assoc()) {
    $clases_hoy[] = $row;
}
$stmt_clases->close();


// 4. CALCULAR ESTADÍSTICAS PARA LAS TARJETAS
// a) Total de estudiantes y cursos
$sql_est_cursos = "
    SELECT 
        COUNT(DISTINCT m.estudiante_id) as total_estudiantes,
        COUNT(DISTINCT c.id) as total_cursos
    FROM cursos c
    JOIN matriculas m ON c.id = m.curso_id
    WHERE c.profesor_id = ?
";
$stmt_stats1 = $mysqli->prepare($sql_est_cursos);
$stmt_stats1->bind_param('i', $profesor_id);
$stmt_stats1->execute();
$result_stats1 = $stmt_stats1->get_result();
if ($row = $result_stats1->fetch_assoc()) {
    $stats['total_estudiantes'] = $row['total_estudiantes'];
    $stats['total_cursos'] = $row['total_cursos'];
}
$stmt_stats1->close();

// b) Asistencia promedio (últimos 30 días)
$sql_asistencia = "
    SELECT AVG(CASE WHEN a.estado = 'Presente' OR a.estado = 'Tarde' THEN 1 ELSE 0 END) * 100 as promedio_asistencia
    FROM asistencia a
    JOIN matriculas m ON a.matricula_id = m.id
    JOIN cursos c ON m.curso_id = c.id
    WHERE c.profesor_id = ? AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";
$stmt_stats2 = $mysqli->prepare($sql_asistencia);
$stmt_stats2->bind_param('i', $profesor_id);
$stmt_stats2->execute();
$result_stats2 = $stmt_stats2->get_result();
if ($row = $result_stats2->fetch_assoc()) {
    $stats['asistencia_promedio'] = $row['promedio_asistencia'];
}
$stmt_stats2->close();

// c) Promedio de notas
$sql_notas = "
    SELECT AVG(cal.calificacion) as promedio_notas
    FROM calificaciones cal
    JOIN evaluaciones ev ON cal.evaluacion_id = ev.id
    JOIN cursos c ON ev.curso_id = c.id
    WHERE c.profesor_id = ?
";
$stmt_stats3 = $mysqli->prepare($sql_notas);
$stmt_stats3->bind_param('i', $profesor_id);
$stmt_stats3->execute();
$result_stats3 = $stmt_stats3->get_result();
if ($row = $result_stats3->fetch_assoc()) {
    $stats['promedio_notas'] = $row['promedio_notas'];
}
$stmt_stats3->close();


// --- FIN LÓGICA ---
include __DIR__ . '/side_bar_profesor.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Dashboard Profesor</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard Profesor</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($nombre . ' ' . $apellido); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profesor_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- School Info Card -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Información Institucional</h5></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center"><img src="../img/logo_escuela.png" alt="Logo" class="img-fluid mb-3" style="max-height: 120px;"></div>
                            <div class="col-md-9">
                                <h4><?php echo htmlspecialchars($config['schoolName'] ?? 'Nombre no configurado'); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($config['schoolAddress'] ?? 'Dirección no configurada'); ?></p>
                                <p><strong>Fundación:</strong> <?php echo date("Y", strtotime($config['schoolFoundation'] ?? 'now')); ?> (<?php echo date('Y') - date("Y", strtotime($config['schoolFoundation'] ?? 'now')); ?> años)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My Classes -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Mis Clases de Hoy (<?php echo date('d/m/Y'); ?>)</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic"><tr><th>Hora</th><th>Curso</th><th>Aula</th><th>Estudiantes</th><th>Acciones</th></tr></thead>
                                <tbody>
                                    <?php if(empty($clases_hoy)): ?>
                                        <tr><td colspan="5" class="text-center">No tienes clases programadas para hoy.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($clases_hoy as $clase): ?>
                                        <tr>
                                            <td><?php echo date("H:i", strtotime($clase['hora_inicio'])) . ' - ' . date("H:i", strtotime($clase['hora_fin'])); ?></td>
                                            <td><?php echo htmlspecialchars($clase['nombre_curso'] . ' - ' . $clase['nombre_grado']); ?></td>
                                            <td><?php echo htmlspecialchars($clase['aula']); ?></td>
                                            <td><?php echo $clase['total_estudiantes']; ?></td>
                                            <td><button class="btn btn-sm btn-primary"><i class="bi bi-clipboard-check"></i> Tomar Asistencia</button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4"><div class="card h-100 border-primary"><div class="card-body text-center"><i class="bi bi-people-fill text-primary fs-1"></i><h5 class="card-title mt-3">Mis Estudiantes</h5><h2 class="card-text"><?php echo $stats['total_estudiantes']; ?></h2><p class="card-text text-muted">En <?php echo $stats['total_cursos']; ?> cursos</p></div><div class="card-footer bg-transparent border-0 text-center"><a href="profesor_estudiantes.php" class="btn btn-sm btn-outline-primary">Ver Detalles</a></div></div></div>
                    <div class="col-md-3 mb-4"><div class="card h-100 border-success"><div class="card-body text-center"><i class="bi bi-clipboard-check text-success fs-1"></i><h5 class="card-title mt-3">Asistencia Promedio</h5><h2 class="card-text"><?php echo number_format($stats['asistencia_promedio'], 1); ?>%</h2><p class="card-text text-muted">Últimos 30 días</p></div><div class="card-footer bg-transparent border-0 text-center"><a href="profesor_asistencia.php" class="btn btn-sm btn-outline-success">Ver Detalles</a></div></div></div>
                    <div class="col-md-3 mb-4"><div class="card h-100 border-info"><div class="card-body text-center"><i class="bi bi-award text-info fs-1"></i><h5 class="card-title mt-3">Promedio de Notas</h5><h2 class="card-text"><?php echo number_format($stats['promedio_notas'], 1); ?>/100</h2><p class="card-text text-muted">General</p></div><div class="card-footer bg-transparent border-0 text-center"><a href="profesor_calificaciones.php" class="btn btn-sm btn-outline-info">Ver Detalles</a></div></div></div>
                    <div class="col-md-3 mb-4"><div class="card h-100 border-warning"><div class="card-body text-center"><i class="bi bi-file-earmark-check text-warning fs-1"></i><h5 class="card-title mt-3">Tareas Pendientes</h5><h2 class="card-text">8</h2><p class="card-text text-muted">Por revisar</p></div><div class="card-footer bg-transparent border-0 text-center"><a href="profesor_tareas.php" class="btn btn-sm btn-outline-warning">Ver Detalles</a></div></div></div>
                </div>

                <!-- ... (sección de Tareas y Notificaciones, sin cambios) ... -->
            </div>
        </div>
    </div>
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>