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
$stats_generales = ['Presente' => 0, 'Ausente' => 0, 'Tarde' => 0];
$resumen_cursos = [];

// --- LÓGICA DE DATOS Y FILTROS ---

// 1. OBTENER NOMBRE DEL DIRECTOR
$stmt_director = $mysqli->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
$stmt_director->bind_param('i', $_SESSION['user_id']);
$stmt_director->execute();
$stmt_director->bind_result($nombre, $apellido);
$stmt_director->fetch();
$stmt_director->close();

// 2. OBTENER DATOS PARA DROPDOWNS
if ($result = $mysqli->query("SELECT id, nombre FROM grados ORDER BY id")) {
    while ($row = $result->fetch_assoc()) $grados_lista[] = $row;
    $result->free();
}

// --- INICIO: PROCESAMIENTO DE FILTROS ---
$filtro_grado = isset($_GET['grado']) && $_GET['grado'] !== 'all' ? (int)$_GET['grado'] : 0;
$filtro_rango = isset($_GET['rango']) ? $_GET['rango'] : 'thisWeek';
$filtro_estado = isset($_GET['estado']) && $_GET['estado'] !== 'all' ? $_GET['estado'] : '';

$where_clauses = [];
$params = [];
$param_types = '';

// Construir cláusula WHERE para el rango de fechas
$date_where_sql = "YEARWEEK(a.fecha, 1) = YEARWEEK(CURDATE(), 1)"; // Por defecto: esta semana
if ($filtro_rango == 'thisMonth') {
    $date_where_sql = "YEAR(a.fecha) = YEAR(CURDATE()) AND MONTH(a.fecha) = MONTH(CURDATE())";
} elseif ($filtro_rango == 'last30days') {
    $date_where_sql = "a.fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()";
}
$where_clauses[] = $date_where_sql;

// Añadir otros filtros
if ($filtro_grado > 0) {
    $where_clauses[] = "c.grado_id = ?";
    $params[] = $filtro_grado;
    $param_types .= 'i';
}
if (!empty($filtro_estado)) {
    $where_clauses[] = "a.estado = ?";
    $params[] = $filtro_estado;
    $param_types .= 's';
}
$where_sql_stats = " WHERE " . implode(" AND ", $where_clauses);


// 3. ESTADÍSTICAS GENERALES DE ASISTENCIA (TARJETAS DE RESUMEN) - CON FILTROS
$sql_stats = "
    SELECT estado, COUNT(a.id) AS total
    FROM asistencia a
    JOIN matriculas mat ON a.matricula_id = mat.id
    JOIN cursos c ON mat.curso_id = c.id
    $where_sql_stats
    GROUP BY estado;
";
$stmt_stats = $mysqli->prepare($sql_stats);
if(!empty($params)){
    $stmt_stats->bind_param($param_types, ...$params);
}
$stmt_stats->execute();
$result = $stmt_stats->get_result();
while ($row = $result->fetch_assoc()) {
    $stats_generales[$row['estado']] = $row['total'];
}
$stmt_stats->close();

// 4. CONSULTA PRINCIPAL PARA LA TABLA "ASISTENCIA POR CURSO" - CON FILTROS
// Para la tabla principal, quitamos el filtro de estado para ver el resumen completo por curso
$where_clauses_table = [];
$params_table = [];
$param_types_table = '';

$where_clauses_table[] = "a.fecha BETWEEN DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND CURDATE()"; // Rango por defecto para la tabla
if ($filtro_rango == 'thisMonth') {
     $where_clauses_table[0] = "YEAR(a.fecha) = YEAR(CURDATE()) AND MONTH(a.fecha) = MONTH(CURDATE())";
}
if ($filtro_grado > 0) {
    $where_clauses_table[] = "c.grado_id = ?";
    $params_table[] = $filtro_grado;
    $param_types_table .= 'i';
}
$where_sql_table = " WHERE " . implode(" AND ", $where_clauses_table);


$sql_resumen_cursos = "
    SELECT c.id AS curso_id, m.nombre AS nombre_curso, g.nombre AS nombre_grado,
           CONCAT(u_prof.nombre, ' ', u_prof.apellido) AS nombre_profesor,
           SUM(CASE WHEN a.estado = 'Presente' THEN 1 ELSE 0 END) AS presentes,
           SUM(CASE WHEN a.estado = 'Ausente' THEN 1 ELSE 0 END) AS ausentes,
           SUM(CASE WHEN a.estado = 'Tarde' THEN 1 ELSE 0 END) AS tardanzas,
           COUNT(a.id) as total_registros
    FROM cursos c
    JOIN grados g ON c.grado_id = g.id
    JOIN materias m ON c.materia_id = m.id
    JOIN profesores p ON c.profesor_id = p.id
    JOIN usuarios u_prof ON p.usuario_id = u_prof.id
    LEFT JOIN matriculas mat ON c.id = mat.curso_id
    LEFT JOIN asistencia a ON mat.id = a.matricula_id
    $where_sql_table AND c.estatus = 'Activo'
    GROUP BY c.id
    ORDER BY g.id, m.nombre
";
$stmt_resumen = $mysqli->prepare($sql_resumen_cursos);
if(!empty($params_table)){
    $stmt_resumen->bind_param($param_types_table, ...$params_table);
}
$stmt_resumen->execute();
$result = $stmt_resumen->get_result();
while($row = $result->fetch_assoc()) {
    $resumen_cursos[] = $row;
}
$stmt_resumen->close();

// --- FIN LÓGICA ---
include __DIR__ . '/side_bar_director.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- ... (tu <head> sin cambios) ... -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Asistencia</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Control de Asistencia</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($nombre . ' ' . $apellido); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="../index.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Filtros</h5></div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row align-items-end">
                                <div class="col-md-3 mb-2">
                                    <label for="gradeFilter" class="form-label">Grado</label>
                                    <select class="form-select" name="grado">
                                        <option value="all" <?php if($filtro_grado == 0) echo 'selected'; ?>>Todos</option>
                                        <?php foreach($grados_lista as $grado): ?>
                                        <option value="<?php echo $grado['id']; ?>" <?php if($filtro_grado == $grado['id']) echo 'selected'; ?>><?php echo htmlspecialchars($grado['nombre']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="dateRangeFilter" class="form-label">Rango de Fechas</label>
                                    <select class="form-select" name="rango">
                                        <option value="thisWeek" <?php if($filtro_rango == 'thisWeek') echo 'selected'; ?>>Esta semana</option>
                                        <option value="thisMonth" <?php if($filtro_rango == 'thisMonth') echo 'selected'; ?>>Este mes</option>
                                        <option value="last30days" <?php if($filtro_rango == 'last30days') echo 'selected'; ?>>Últimos 30 días</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="statusFilter" class="form-label">Estado</label>
                                    <select class="form-select" name="estado">
                                        <option value="all" <?php if(empty($filtro_estado)) echo 'selected'; ?>>Todos</option>
                                        <option value="Presente" <?php if($filtro_estado == 'Presente') echo 'selected'; ?>>Presente</option>
                                        <option value="Ausente" <?php if($filtro_estado == 'Ausente') echo 'selected'; ?>>Ausente</option>
                                        <option value="Tarde" <?php if($filtro_estado == 'Tarde') echo 'selected'; ?>>Tardanza</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <button type="submit" class="btn btn-academic w-100"><i class="bi bi-filter"></i> Aplicar Filtros</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Attendance Overview -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Resumen de Asistencia (<?php echo htmlspecialchars($_GET['rango'] ?? 'Esta semana'); ?>)</h5></div>
                    <div class="card-body"><div class="row">
                        <div class="col-md-4 mb-3"><div class="card text-white bg-success h-100"><div class="card-body"><h5 class="card-title">Presentes</h5><p class="card-text display-4"><?php echo $stats_generales['Presente'] ?? 0; ?></p></div></div></div>
                        <div class="col-md-4 mb-3"><div class="card text-white bg-danger h-100"><div class="card-body"><h5 class="card-title">Ausentes</h5><p class="card-text display-4"><?php echo $stats_generales['Ausente'] ?? 0; ?></p></div></div></div>
                        <div class="col-md-4 mb-3"><div class="card text-white bg-warning h-100"><div class="card-body"><h5 class="card-title">Tardanzas</h5><p class="card-text display-4"><?php echo $stats_generales['Tarde'] ?? 0; ?></p></div></div></div>
                    </div></div>
                </div>

                <!-- Attendance by Course -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Asistencia por Curso (<?php echo htmlspecialchars($_GET['rango'] ?? 'Esta semana'); ?>)</h5></div>
                    <div class="card-body"><div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-academic">
                                <tr><th>Curso</th><th>Grado</th><th>Profesor</th><th>Presentes</th><th>Ausentes</th><th>Tardanzas</th><th>% Asistencia</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php if(empty($resumen_cursos)): ?>
                                    <tr><td colspan="8" class="text-center">No hay datos de asistencia para los filtros seleccionados.</td></tr>
                                <?php else: ?>
                                    <?php foreach($resumen_cursos as $resumen): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($resumen['nombre_curso']); ?></td>
                                        <td><?php echo htmlspecialchars($resumen['nombre_grado']); ?></td>
                                        <td><?php echo htmlspecialchars($resumen['nombre_profesor']); ?></td>
                                        <td><?php echo $resumen['presentes']; ?></td>
                                        <td><?php echo $resumen['ausentes']; ?></td>
                                        <td><?php echo $resumen['tardanzas']; ?></td>
                                        <td>
                                            <?php
                                            $total_asistencias = $resumen['presentes'] + $resumen['tardanzas'];
                                            $porcentaje_asistencia = ($resumen['total_registros'] > 0) ? ($total_asistencias / $resumen['total_registros']) * 100 : 0;
                                            echo '<strong>' . number_format($porcentaje_asistencia, 1) . '%</strong>';
                                            ?>
                                        </td>
                                        <td><button class="btn btn-sm btn-outline-primary" title="Ver Detalles"><i class="bi bi-eye"></i></button></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>