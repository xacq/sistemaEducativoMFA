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
$cursos_profesor = [];
$horario_semanal = [];
$profesor_id = 0;
$usuario_id = $_SESSION['user_id'];

// --- LÓGICA DE DATOS ---

// 1. OBTENER DATOS DEL USUARIO Y PROFESOR LOGUEADO
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
    die("Acceso denegado. No se encontró un perfil de profesor asociado a este usuario.");
}

// 2. CONSULTA PRINCIPAL PARA OBTENER LOS CURSOS DEL PROFESOR Y SUS ESTADÍSTICAS
$sql_cursos = "
    SELECT
        c.id AS curso_id,
        c.codigo,
        c.estatus,
        m.nombre AS nombre_materia,
        g.nombre AS nombre_grado,
        -- Contar estudiantes matriculados
        (SELECT COUNT(id) FROM matriculas WHERE curso_id = c.id) as total_estudiantes,
        -- Calcular promedio de asistencia para este curso
        (SELECT AVG(CASE WHEN a.estado = 'Presente' OR a.estado = 'Tarde' THEN 1 ELSE 0 END) * 100 
         FROM asistencia a JOIN matriculas mat ON a.matricula_id = mat.id WHERE mat.curso_id = c.id) as promedio_asistencia,
        -- Calcular promedio de notas para este curso
        (SELECT AVG(cal.calificacion) 
         FROM calificaciones cal JOIN evaluaciones ev ON cal.evaluacion_id = ev.id WHERE ev.curso_id = c.id) as promedio_notas
    FROM cursos c
    JOIN materias m ON c.materia_id = m.id
    JOIN grados g ON c.grado_id = g.id
    WHERE c.profesor_id = ? AND c.estatus = 'Activo'
    ORDER BY g.id, m.nombre
";
$stmt_cursos = $mysqli->prepare($sql_cursos);
$stmt_cursos->bind_param('i', $profesor_id);
$stmt_cursos->execute();
$result_cursos = $stmt_cursos->get_result();
while ($row = $result_cursos->fetch_assoc()) {
    $cursos_profesor[] = $row;
}
$stmt_cursos->close();


// 3. OBTENER HORARIO SEMANAL DEL PROFESOR
$sql_horario = "
    SELECT 
        h.dia, h.hora_inicio, h.hora_fin, h.aula,
        m.nombre as nombre_materia,
        g.nombre as nombre_grado
    FROM horarios h
    JOIN cursos c ON h.curso_id = c.id
    JOIN materias m ON c.materia_id = m.id
    JOIN grados g ON c.grado_id = g.id
    WHERE c.profesor_id = ? AND c.estatus = 'Activo'
";
$stmt_horario = $mysqli->prepare($sql_horario);
$stmt_horario->bind_param('i', $profesor_id);
$stmt_horario->execute();
$result_horario = $stmt_horario->get_result();
while ($row = $result_horario->fetch_assoc()) {
    // Agrupar por hora de inicio para construir la tabla
    $horario_semanal[$row['hora_inicio']][$row['dia']] = $row;
}
ksort($horario_semanal); // Ordenar el horario por hora de inicio
$stmt_horario->close();


// --- FIN LÓGICA ---
include __DIR__ . '/side_bar_profesor.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Mis Cursos</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mis Cursos</h1>
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

                <!-- Course Cards -->
                <div class="row mb-4">
                    <?php if(empty($cursos_profesor)): ?>
                        <div class="col-12">
                            <div class="alert alert-info">No tienes cursos activos asignados.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach($cursos_profesor as $curso): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 shadow-sm">
                                <div class="card-header card-header-academic">
                                    <h5 class="mb-0 text-white"><?php echo htmlspecialchars($curso['nombre_materia']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($curso['nombre_grado']); ?></span>
                                        <span class="badge bg-success"><?php echo htmlspecialchars($curso['estatus']); ?></span>
                                    </div>
                                    <p><strong>Código:</strong> <?php echo htmlspecialchars($curso['codigo']); ?></p>
                                    <p><strong>Estudiantes:</strong> <?php echo $curso['total_estudiantes']; ?></p>
                                    <div class="d-flex justify-content-between mt-3">
                                        <span><i class="bi bi-award text-warning"></i> Promedio: <strong><?php echo number_format($curso['promedio_notas'] ?? 0, 1); ?></strong></span>
                                        <span><i class="bi bi-calendar-check text-success"></i> Asistencia: <strong><?php echo number_format($curso['promedio_asistencia'] ?? 0, 1); ?>%</strong></span>
                                    </div>
                                </div>
                                <div class="card-footer bg-light">
                                    <div class="d-grid"><button class="btn btn-academic" type="button">Ver detalles del curso</button></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Course Schedule -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Mi Horario Semanal</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered text-center">
                                <thead class="table-academic">
                                    <tr><th>Hora</th><th>Lunes</th><th>Martes</th><th>Miércoles</th><th>Jueves</th><th>Viernes</th></tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($horario_semanal)): ?>
                                        <tr><td colspan="6">No tienes un horario definido.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($horario_semanal as $hora_inicio => $dias): ?>
                                        <tr>
                                            <td><strong><?php echo date("H:i", strtotime($hora_inicio)); ?></strong></td>
                                            <td><?php echo isset($dias['monday']) ? (htmlspecialchars($dias['monday']['nombre_materia']).'<br><small>'.$dias['monday']['nombre_grado'].'</small>') : ''; ?></td>
                                            <td><?php echo isset($dias['tuesday']) ? (htmlspecialchars($dias['tuesday']['nombre_materia']).'<br><small>'.$dias['tuesday']['nombre_grado'].'</small>') : ''; ?></td>
                                            <td><?php echo isset($dias['wednesday']) ? (htmlspecialchars($dias['wednesday']['nombre_materia']).'<br><small>'.$dias['wednesday']['nombre_grado'].'</small>') : ''; ?></td>
                                            <td><?php echo isset($dias['thursday']) ? (htmlspecialchars($dias['thursday']['nombre_materia']).'<br><small>'.$dias['thursday']['nombre_grado'].'</small>') : ''; ?></td>
                                            <td><?php echo isset($dias['friday']) ? (htmlspecialchars($dias['friday']['nombre_materia']).'<br><small>'.$dias['friday']['nombre_grado'].'</small>') : ''; ?></td>
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
    
    <!-- Modals (si los necesitas) -->

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>