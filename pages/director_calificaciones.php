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
$profesores_lista = [];
$stats_generales = ['excelente' => 0, 'bueno' => 0, 'regular' => 0, 'insuficiente' => 0];
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
if ($result = $mysqli->query("SELECT p.id, u.nombre, u.apellido FROM profesores p JOIN usuarios u ON p.usuario_id = u.id ORDER BY u.apellido, u.nombre")) {
    while ($row = $result->fetch_assoc()) $profesores_lista[] = $row;
    $result->free();
}

// --- INICIO: PROCESAMIENTO DE FILTROS ---
$filtro_grado = isset($_GET['grado']) && !empty($_GET['grado']) ? (int)$_GET['grado'] : 0;
$filtro_profesor = isset($_GET['profesor']) && !empty($_GET['profesor']) ? (int)$_GET['profesor'] : 0;
$filtro_periodo = isset($_GET['periodo']) && !empty($_GET['periodo']) ? $_GET['periodo'] : '';

$where_clauses = [];
$params = [];
$param_types = '';

// Construir la cláusula WHERE dinámicamente
if ($filtro_grado > 0) {
    $where_clauses[] = "c.grado_id = ?";
    $params[] = $filtro_grado;
    $param_types .= 'i';
}
if ($filtro_profesor > 0) {
    $where_clauses[] = "c.profesor_id = ?";
    $params[] = $filtro_profesor;
    $param_types .= 'i';
}
if (!empty($filtro_periodo)) {
    $where_clauses[] = "ev.periodo = ?";
    $params[] = $filtro_periodo;
    $param_types .= 's';
}

// Unir las cláusulas si hay alguna
$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";
// Añadir la condición de curso activo si hay otros filtros, o usar WHERE si es el único
if(!empty($where_sql)){
    $where_sql .= " AND c.estatus = 'Activo'";
} else {
    $where_sql = " WHERE c.estatus = 'Activo'";
}
// --- FIN: PROCESAMIENTO DE FILTROS ---


// 3. ESTADÍSTICAS GENERALES DE CALIFICACIONES (se pueden ajustar con filtros si se desea)
// ... (código de estadísticas sin cambios por ahora para mantener la simplicidad) ...
$sql_stats_generales = "SELECT SUM(CASE WHEN calificacion >= 90 THEN 1 ELSE 0 END) AS excelente, SUM(CASE WHEN calificacion >= 75 AND calificacion < 90 THEN 1 ELSE 0 END) AS bueno, SUM(CASE WHEN calificacion >= 60 AND calificacion < 75 THEN 1 ELSE 0 END) AS regular, SUM(CASE WHEN calificacion < 60 THEN 1 ELSE 0 END) AS insuficiente FROM calificaciones";
if($result = $mysqli->query($sql_stats_generales)){ $stats_generales = $result->fetch_assoc(); $result->free(); }

// 4. CONSULTA PRINCIPAL PARA LA TABLA "CALIFICACIONES POR CURSO" (CON FILTROS)
$sql_resumen_cursos = "
    SELECT
        c.id as curso_id,
        m.nombre AS nombre_curso,
        g.nombre AS nombre_grado,
        CONCAT(u_prof.nombre, ' ', u_prof.apellido) AS nombre_profesor,
        AVG(cal.calificacion) AS promedio,
        SUM(CASE WHEN cal.calificacion >= 90 THEN 1 ELSE 0 END) as excelente_count,
        SUM(CASE WHEN cal.calificacion >= 75 AND calificacion < 90 THEN 1 ELSE 0 END) as bueno_count,
        SUM(CASE WHEN cal.calificacion >= 60 AND calificacion < 75 THEN 1 ELSE 0 END) as regular_count,
        SUM(CASE WHEN cal.calificacion < 60 THEN 1 ELSE 0 END) as insuficiente_count,
        (SELECT COUNT(DISTINCT matricula_id) FROM calificaciones ca JOIN evaluaciones ev ON ca.evaluacion_id = ev.id WHERE ev.curso_id = c.id) as total_estudiantes
    FROM cursos c
    LEFT JOIN grados g ON c.grado_id = g.id
    LEFT JOIN materias m ON c.materia_id = m.id
    LEFT JOIN profesores p ON c.profesor_id = p.id
    LEFT JOIN usuarios u_prof ON p.usuario_id = u_prof.id
    LEFT JOIN evaluaciones ev ON c.id = ev.curso_id
    LEFT JOIN calificaciones cal ON ev.id = cal.evaluacion_id
    $where_sql  -- <<< AQUÍ SE APLICAN LOS FILTROS
    GROUP BY c.id
    ORDER BY g.id, m.nombre
";

$stmt_resumen = $mysqli->prepare($sql_resumen_cursos);
if (!empty($params)) {
    $stmt_resumen->bind_param($param_types, ...$params);
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
    <!-- ... tu <head> ... -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Calificaciones</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Calificaciones</h1>
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

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Filtros</h5></div>
                    <div class="card-body">
                        <!-- INICIO: FORMULARIO DE FILTROS ACTUALIZADO -->
                        <form method="GET" action="">
                            <div class="row align-items-end">
                                <div class="col-md-3 mb-2">
                                    <label for="gradeFilter" class="form-label">Grado</label>
                                    <select class="form-select" name="grado">
                                        <option value="">Todos</option>
                                        <?php foreach($grados_lista as $grado): ?>
                                            <option value="<?php echo $grado['id']; ?>" <?php if($filtro_grado == $grado['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($grado['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="teacherFilter" class="form-label">Profesor</label>
                                    <select class="form-select" name="profesor">
                                        <option value="">Todos</option>
                                        <?php foreach($profesores_lista as $profesor): ?>
                                            <option value="<?php echo $profesor['id']; ?>" <?php if($filtro_profesor == $profesor['id']) echo 'selected'; ?>>
                                                <?php echo htmlspecialchars($profesor['apellido'].', '.$profesor['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label for="periodFilter" class="form-label">Período</label>
                                    <select class="form-select" name="periodo">
                                        <option value="">Todos</option>
                                        <option value="Primer Trimestre" <?php if($filtro_periodo == 'Primer Trimestre') echo 'selected'; ?>>Primer Trimestre</option>
                                        <option value="Segundo Trimestre" <?php if($filtro_periodo == 'Segundo Trimestre') echo 'selected'; ?>>Segundo Trimestre</option>
                                        <option value="Tercer Trimestre" <?php if($filtro_periodo == 'Tercer Trimestre') echo 'selected'; ?>>Tercer Trimestre</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <button type="submit" class="btn btn-academic w-100"><i class="bi bi-filter"></i> Aplicar Filtros</button>
                                </div>
                            </div>
                        </form>
                        <!-- FIN: FORMULARIO DE FILTROS ACTUALIZADO -->
                    </div>
                </div>

                <!-- Grades Overview -->
                <div class="card mb-4">
                    <!-- ... (tarjetas de resumen, sin cambios) ... -->
                </div>

                <!-- Grades by Course -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Calificaciones por Curso</h5></div>
                    <div class="card-body"><div class="table-responsive">
                        <table class="table table-hover">
                            <!-- ... (tabla dinámica, sin cambios) ... -->
                            <thead class="table-academic">
                                <tr><th>Curso</th><th>Grado</th><th>Profesor</th><th>Promedio</th><th>Excelente</th><th>Bueno</th><th>Regular</th><th>Insuficiente</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php if(empty($resumen_cursos)): ?>
                                    <tr><td colspan="9" class="text-center">No se encontraron cursos con los filtros aplicados.</td></tr>
                                <?php else: ?>
                                    <?php foreach($resumen_cursos as $resumen): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($resumen['nombre_curso']); ?></td>
                                        <td><?php echo htmlspecialchars($resumen['nombre_grado']); ?></td>
                                        <td><?php echo htmlspecialchars($resumen['nombre_profesor']); ?></td>
                                        <td><strong><?php echo number_format($resumen['promedio'], 1); ?></strong></td>
                                        <td><?php echo $resumen['excelente_count']; ?> <small class="text-muted">(<?php echo ($resumen['total_estudiantes'] > 0) ? round($resumen['excelente_count'] / $resumen['total_estudiantes'] * 100) : 0; ?>%)</small></td>
                                        <td><?php echo $resumen['bueno_count']; ?> <small class="text-muted">(<?php echo ($resumen['total_estudiantes'] > 0) ? round($resumen['bueno_count'] / $resumen['total_estudiantes'] * 100) : 0; ?>%)</small></td>
                                        <td><?php echo $resumen['regular_count']; ?> <small class="text-muted">(<?php echo ($resumen['total_estudiantes'] > 0) ? round($resumen['regular_count'] / $resumen['total_estudiantes'] * 100) : 0; ?>%)</small></td>
                                        <td><?php echo $resumen['insuficiente_count']; ?> <small class="text-muted">(<?php echo ($resumen['total_estudiantes'] > 0) ? round($resumen['insuficiente_count'] / $resumen['total_estudiantes'] * 100) : 0; ?>%)</small></td>
                                        <td>
                                            <button 
                                                class="btn btn-sm btn-outline-primary btn-ver-calificaciones" 
                                                data-id="<?php echo $resumen['curso_id']; ?>"
                                                title="Ver calificaciones del curso">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </td>
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
    <!-- Modals -->
     <!-- Modal: Detalle de calificaciones -->
    <div class="modal fade" id="detalleCalificacionesModal" tabindex="-1" aria-labelledby="detalleCalificacionesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="detalleCalificacionesLabel">Calificaciones del curso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body" id="detalleCalificacionesBody">
                <p class="text-muted text-center">Cargando calificaciones...</p>
            </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('.btn-ver-calificaciones').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const modalEl = document.getElementById('detalleCalificacionesModal');
        const modal = new bootstrap.Modal(modalEl);
        const body = document.getElementById('detalleCalificacionesBody');
        body.innerHTML = "<p class='text-muted text-center'>Cargando calificaciones...</p>";
        modal.show();

        try {
        const res = await fetch(`ver_calificaciones.php?curso_id=${id}`);
        const data = await res.json();

        if (!data.success) {
            body.innerHTML = `<p class="text-danger text-center">${data.message}</p>`;
            return;
        }

        let html = `
            <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle">
            <thead class="table-academic">
            <tr>
                <th>Estudiante</th>
                <th>Evaluación</th>
                <th>Calificación</th>
                <th>Fecha</th>
                <th>Comentario</th>
            </tr>
            </thead>
            <tbody>`;
        
        data.calificaciones.forEach(c => {
            html += `
            <tr>
                <td>${c.estudiante}</td>
                <td>${c.evaluacion}</td>
                <td><strong>${parseFloat(c.calificacion).toFixed(2)}</strong></td>
                <td>${c.fecha}</td>
                <td>${c.comentario || ''}</td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        body.innerHTML = html;
        } catch (err) {
        console.error(err);
        body.innerHTML = `<p class="text-danger text-center">Error al cargar las calificaciones.</p>`;
        }
    });
    });
    </script>

</body>
</html>