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
$grados_lista = [];
$materias_lista = [];
$profesores_lista = [];
$stats_generales = ['excelente' => 0, 'bueno' => 0, 'regular' => 0, 'insuficiente' => 0, 'total_calificaciones' => 0];
$promedio_niveles = ['Primaria' => 0, 'Secundaria' => 0, 'Total' => 0];
$rendimiento_asignaturas = [];
$report_title = "Reporte de Rendimiento Académico General"; // Título por defecto

// --- OBTENER NOMBRE DEL DIRECTOR ---
$stmt_director = $mysqli->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
$stmt_director->bind_param('i', $_SESSION['user_id']);
$stmt_director->execute();
$stmt_director->bind_result($nombre, $apellido);
$stmt_director->fetch();
$stmt_director->close();

// --- CARGAR DATOS PARA DROPDOWNS ---
if ($result = $mysqli->query("SELECT id, nombre FROM grados ORDER BY id")) { while ($row = $result->fetch_assoc()) $grados_lista[] = $row; $result->free(); }
if ($result = $mysqli->query("SELECT id, nombre FROM materias ORDER BY nombre")) { while ($row = $result->fetch_assoc()) $materias_lista[] = $row; $result->free(); }
if ($result = $mysqli->query("SELECT p.id, u.nombre, u.apellido FROM profesores p JOIN usuarios u ON p.usuario_id = u.id ORDER BY u.apellido")) { while ($row = $result->fetch_assoc()) $profesores_lista[] = $row; $result->free(); }

// --- INICIO: PROCESAMIENTO DE FILTROS ---
$filtro_grado_id = isset($_GET['grado']) && !empty($_GET['grado']) ? (int)$_GET['grado'] : 0;
$filtro_materia_id = isset($_GET['materia']) && !empty($_GET['materia']) ? (int)$_GET['materia'] : 0;
$filtro_profesor_id = isset($_GET['profesor']) && !empty($_GET['profesor']) ? (int)$_GET['profesor'] : 0;

$where_clauses = [];
$params = [];
$param_types = '';
$report_title_parts = [];

if ($filtro_grado_id > 0) {
    $where_clauses[] = "c.grado_id = ?";
    $params[] = $filtro_grado_id;
    $param_types .= 'i';
    // Buscar el nombre del grado para el título del reporte
    foreach($grados_lista as $g) { if($g['id'] == $filtro_grado_id) {$report_title_parts[] = "Grado: " . $g['nombre']; break;} }
}
if ($filtro_materia_id > 0) {
    $where_clauses[] = "c.materia_id = ?";
    $params[] = $filtro_materia_id;
    $param_types .= 'i';
    foreach($materias_lista as $m) { if($m['id'] == $filtro_materia_id) {$report_title_parts[] = "Materia: " . $m['nombre']; break;} }
}
if ($filtro_profesor_id > 0) {
    $where_clauses[] = "c.profesor_id = ?";
    $params[] = $filtro_profesor_id;
    $param_types .= 'i';
    foreach($profesores_lista as $p) { if($p['id'] == $filtro_profesor_id) {$report_title_parts[] = "Profesor: " . $p['apellido']; break;} }
}

// Unir cláusulas para la consulta SQL
$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";
if(!empty($report_title_parts)){ $report_title = "Reporte Específico - " . implode(' | ', $report_title_parts); }

// --- CONSULTAS DINÁMICAS BASADAS EN FILTROS ---

// 3. ESTADÍSTICAS GENERALES DE CALIFICACIONES (TARJETAS)
$sql_stats_generales = "
    SELECT 
        SUM(CASE WHEN cal.calificacion >= 90 THEN 1 ELSE 0 END) AS excelente,
        SUM(CASE WHEN cal.calificacion >= 75 AND cal.calificacion < 90 THEN 1 ELSE 0 END) AS bueno,
        SUM(CASE WHEN cal.calificacion >= 60 AND cal.calificacion < 75 THEN 1 ELSE 0 END) AS regular,
        SUM(CASE WHEN cal.calificacion < 60 THEN 1 ELSE 0 END) AS insuficiente,
        COUNT(cal.id) as total_calificaciones
    FROM calificaciones cal
    JOIN evaluaciones ev ON cal.evaluacion_id = ev.id
    JOIN cursos c ON ev.curso_id = c.id
    $where_sql";
$stmt_stats = $mysqli->prepare($sql_stats_generales);
if(!empty($params)) { $stmt_stats->bind_param($param_types, ...$params); }
$stmt_stats->execute();
if($result = $stmt_stats->get_result()){ $stats_generales = $result->fetch_assoc(); $result->free(); }
$stmt_stats->close();


// 4. PROMEDIO GENERAL POR NIVEL (con filtros)
$sql_prom_niveles = "
    SELECT g.nivel, AVG(cal.calificacion) as promedio
    FROM calificaciones cal
    JOIN evaluaciones ev ON cal.evaluacion_id = ev.id
    JOIN cursos c ON ev.curso_id = c.id
    JOIN grados g ON c.grado_id = g.id
    $where_sql
    GROUP BY g.nivel";
$stmt_prom = $mysqli->prepare($sql_prom_niveles);
if(!empty($params)) { $stmt_prom->bind_param($param_types, ...$params); }
$stmt_prom->execute();
if($result = $stmt_prom->get_result()){
    while($row = $result->fetch_assoc()){ $promedio_niveles[$row['nivel']] = $row['promedio']; }
    $result->free();
}
$stmt_prom->close();
// Calcular el promedio total (con filtros)
$sql_prom_total = "SELECT AVG(cal.calificacion) FROM calificaciones cal JOIN evaluaciones ev ON cal.evaluacion_id = ev.id JOIN cursos c ON ev.curso_id = c.id $where_sql";
$stmt_prom_total = $mysqli->prepare($sql_prom_total);
if(!empty($params)) { $stmt_prom_total->bind_param($param_types, ...$params); }
$stmt_prom_total->execute();
if($result = $stmt_prom_total->get_result()){ $promedio_niveles['Total'] = $result->fetch_row()[0]; $result->free(); }
$stmt_prom_total->close();


// 5. RENDIMIENTO POR ASIGNATURA (con filtros)
$sql_rend_asig = "
    SELECT m.nombre, AVG(cal.calificacion) as promedio,
           SUM(CASE WHEN cal.calificacion >= 60 THEN 1 ELSE 0 END) as aprobados,
           SUM(CASE WHEN cal.calificacion < 60 THEN 1 ELSE 0 END) as reprobados,
           COUNT(cal.id) as total
    FROM calificaciones cal
    JOIN evaluaciones ev ON cal.evaluacion_id = ev.id
    JOIN cursos c ON ev.curso_id = c.id
    JOIN materias m ON c.materia_id = m.id
    $where_sql
    GROUP BY m.id ORDER BY m.nombre";
$stmt_rend = $mysqli->prepare($sql_rend_asig);
if(!empty($params)) { $stmt_rend->bind_param($param_types, ...$params); }
$stmt_rend->execute();
if($result = $stmt_rend->get_result()){
    while($row = $result->fetch_assoc()) $rendimiento_asignaturas[] = $row;
    $result->free();
}
$stmt_rend->close();


// --- FIN LÓGICA ---
include __DIR__ . '/side_bar_director.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- ... tu <head> ... -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Reportes</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Reportes Académicos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($nombre . ' ' . $apellido); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Filtros de Reportes</h5></div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row mb-3 align-items-end">
                                <div class="col-md-3">
                                    <label for="reportGrade" class="form-label">Grado/Nivel</label>
                                    <select class="form-select" name="grado">
                                        <option value="">Todos</option>
                                        <?php foreach($grados_lista as $grado): ?><option value="<?php echo $grado['id']; ?>" <?php if($filtro_grado_id == $grado['id']) echo 'selected'; ?>><?php echo htmlspecialchars($grado['nombre']); ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="reportSubject" class="form-label">Asignatura</label>
                                    <select class="form-select" name="materia">
                                        <option value="">Todas</option>
                                        <?php foreach($materias_lista as $materia): ?><option value="<?php echo $materia['id']; ?>" <?php if($filtro_materia_id == $materia['id']) echo 'selected'; ?>><?php echo htmlspecialchars($materia['nombre']); ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="reportTeacher" class="form-label">Profesor</label>
                                    <select class="form-select" name="profesor">
                                        <option value="">Todos</option>
                                        <?php foreach($profesores_lista as $profesor): ?><option value="<?php echo $profesor['id']; ?>" <?php if($filtro_profesor_id == $profesor['id']) echo 'selected'; ?>><?php echo htmlspecialchars($profesor['apellido'].', '.$profesor['nombre']); ?></option><?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-academic w-100"><i class="bi bi-bar-chart-line-fill"></i> Generar Reporte</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Report Preview -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Vista Previa: <?php echo htmlspecialchars($report_title); ?></h5></div>
                    <div class="card-body">
                        <!-- ... (contenido de la vista previa) ... -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card h-100"><div class="card-header"><h6 class="mb-0">Promedio General por Nivel</h6></div>
                                <div class="card-body"><div class="table-responsive"><table class="table table-sm">
                                    <thead><tr><th>Nivel</th><th>Promedio</th></tr></thead>
                                    <tbody>
                                        <tr><td>Primaria</td><td><strong><?php echo number_format($promedio_niveles['Primaria'] ?? 0, 2); ?></strong></td></tr>
                                        <tr><td>Secundaria</td><td><strong><?php echo number_format($promedio_niveles['Secundaria'] ?? 0, 2); ?></strong></td></tr>
                                        <tr class="table-group-divider"><td class="fw-bold">Total General</td><td class="fw-bold"><?php echo number_format($promedio_niveles['Total'] ?? 0, 2); ?></td></tr>
                                    </tbody>
                                </table></div></div></div>
                            </div>
                            <div class="col-md-6">
                                <div class="card h-100"><div class="card-header"><h6 class="mb-0">Distribución de Calificaciones</h6></div>
                                <div class="card-body"><div class="table-responsive"><table class="table table-sm">
                                    <thead><tr><th>Rango</th><th>Calificaciones</th><th>Porcentaje</th></tr></thead>
                                    <tbody>
                                        <tr><td>90-100 (Excelente)</td><td><?php echo $stats_generales['excelente'] ?? 0; ?></td><td><?php echo ($stats_generales['total_calificaciones'] > 0) ? number_format(($stats_generales['excelente'] / $stats_generales['total_calificaciones']) * 100, 1) : 0; ?>%</td></tr>
                                        <tr><td>75-89 (Bueno)</td><td><?php echo $stats_generales['bueno'] ?? 0; ?></td><td><?php echo ($stats_generales['total_calificaciones'] > 0) ? number_format(($stats_generales['bueno'] / $stats_generales['total_calificaciones']) * 100, 1) : 0; ?>%</td></tr>
                                        <tr><td>60-74 (Regular)</td><td><?php echo $stats_generales['regular'] ?? 0; ?></td><td><?php echo ($stats_generales['total_calificaciones'] > 0) ? number_format(($stats_generales['regular'] / $stats_generales['total_calificaciones']) * 100, 1) : 0; ?>%</td></tr>
                                        <tr><td>0-59 (Insuficiente)</td><td><?php echo $stats_generales['insuficiente'] ?? 0; ?></td><td><?php echo ($stats_generales['total_calificaciones'] > 0) ? number_format(($stats_generales['insuficiente'] / $stats_generales['total_calificaciones']) * 100, 1) : 0; ?>%</td></tr>
                                    </tbody>
                                </table></div></div></div>
                            </div>
                        </div>
                        <div class="row mb-4">
                            <div class="col-12"><div class="card"><div class="card-header"><h6 class="mb-0">Rendimiento por Asignatura</h6></div>
                            <div class="card-body"><div class="table-responsive"><table class="table">
                                <thead><tr><th>Asignatura</th><th>Promedio</th><th>Aprobados</th><th>Reprobados</th><th>Tasa de Aprobación</th></tr></thead>
                                <tbody>
                                    <?php if(empty($rendimiento_asignaturas)): ?>
                                        <tr><td colspan="5" class="text-center">No hay datos para los filtros seleccionados.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($rendimiento_asignaturas as $asignatura): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($asignatura['nombre']); ?></td>
                                            <td><?php echo number_format($asignatura['promedio'], 1); ?></td>
                                            <td><?php echo $asignatura['aprobados']; ?></td>
                                            <td><?php echo $asignatura['reprobados']; ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <?php $tasa_aprobacion = ($asignatura['total'] > 0) ? ($asignatura['aprobados'] / $asignatura['total']) * 100 : 0; ?>
                                                    <div class="progress-bar <?php if($tasa_aprobacion < 70) echo 'bg-warning'; else echo 'bg-success'; ?>" role="progressbar" style="width: <?php echo $tasa_aprobacion; ?>%;" aria-valuenow="<?php echo $tasa_aprobacion; ?>"><?php echo number_format($tasa_aprobacion, 1); ?>%</div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table></div></div></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html> 