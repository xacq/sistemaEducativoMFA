<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';

// --- 1. INICIALIZACIÓN DE VARIABLES ---
$nombre = '';
$apellido = '';
$grados_lista = [];
$profesores_lista = [];
$materias_lista = [];
$cursos = [];
$stats_por_grado = [];
$stats_por_materia = [];
$total_cursos = 0;
$total_paginas = 0;

// --- 2. LÓGICA DE LA APLICACIÓN ---

// OBTENER NOMBRE DEL DIRECTOR
$stmt_director = $mysqli->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
$stmt_director->bind_param('i', $_SESSION['user_id']);
$stmt_director->execute();
$stmt_director->bind_result($nombre, $apellido);
$stmt_director->fetch();
$stmt_director->close();

// CARGAR DATOS PARA DROPDOWNS Y FILTROS
if ($result = $mysqli->query("SELECT id, nombre FROM grados ORDER BY id")) {
    while ($row = $result->fetch_assoc()) $grados_lista[] = $row;
    $result->free();
}
$sql_profesores = "SELECT p.id, u.nombre, u.apellido FROM profesores p JOIN usuarios u ON p.usuario_id = u.id ORDER BY u.apellido, u.nombre";
if ($result = $mysqli->query($sql_profesores)) {
    while ($row = $result->fetch_assoc()) $profesores_lista[] = $row;
    $result->free();
}
if ($result = $mysqli->query("SELECT id, nombre FROM materias ORDER BY nombre")) {
    while ($row = $result->fetch_assoc()) $materias_lista[] = $row;
    $result->free();
}

// LÓGICA DE FILTROS Y PAGINACIÓN
$filtro_busqueda = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clauses = [];
$params = [];
$param_types = '';

if (!empty($filtro_busqueda)) {
    $where_clauses[] = "(c.nombre LIKE ? OR c.codigo LIKE ? OR CONCAT(u.nombre, ' ', u.apellido) LIKE ?)";
    $search_term = "%{$filtro_busqueda}%";
    array_push($params, $search_term, $search_term, $search_term);
    $param_types .= 'sss';
}
$where_sql = !empty($where_clauses) ? " WHERE " . implode(" AND ", $where_clauses) : "";

$registros_por_pagina = 10;
$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

$sql_total = "SELECT COUNT(c.id) FROM cursos c LEFT JOIN profesores p ON c.profesor_id = p.id LEFT JOIN usuarios u ON p.usuario_id = u.id" . $where_sql;
$stmt_total = $mysqli->prepare($sql_total);
if (!empty($params)) {
    $stmt_total->bind_param($param_types, ...$params);
}
$stmt_total->execute();
$total_cursos = $stmt_total->get_result()->fetch_row()[0];
$total_paginas = ceil($total_cursos / $registros_por_pagina);
$stmt_total->close();

// CONSULTA PRINCIPAL PARA OBTENER LOS CURSOS
$sql_cursos = "
    SELECT c.id, c.codigo, c.nombre, c.estatus, g.nombre AS nombre_grado, CONCAT(u.nombre, ' ', u.apellido) AS nombre_profesor,
           (SELECT COUNT(id) FROM matriculas WHERE curso_id = c.id) AS estudiantes_inscritos
    FROM cursos c
    LEFT JOIN grados g ON c.grado_id = g.id
    LEFT JOIN profesores p ON c.profesor_id = p.id
    LEFT JOIN usuarios u ON p.usuario_id = u.id
    $where_sql
    ORDER BY g.id, c.nombre
    LIMIT ?, ?
";
$stmt_cursos = $mysqli->prepare($sql_cursos);
$params_paginacion = $params;
array_push($params_paginacion, $offset, $registros_por_pagina);
$param_types_paginacion = $param_types . 'ii';
$stmt_cursos->bind_param($param_types_paginacion, ...$params_paginacion);
$stmt_cursos->execute();
$result_cursos = $stmt_cursos->get_result();
while ($row = $result_cursos->fetch_assoc()) {
    $cursos[] = $row;
}
$stmt_cursos->close();

// ESTADÍSTICAS
$sql_stats_grado = "SELECT g.nombre, COUNT(c.id) as total FROM cursos c JOIN grados g ON c.grado_id = g.id GROUP BY g.id ORDER BY g.id";
if($result = $mysqli->query($sql_stats_grado)){ while($row = $result->fetch_assoc()) $stats_por_grado[] = $row; $result->free(); }
$sql_stats_materia = "SELECT m.nombre, COUNT(c.id) as total FROM cursos c JOIN materias m ON c.materia_id = m.id GROUP BY m.id ORDER BY total DESC LIMIT 10";
if($result = $mysqli->query($sql_stats_materia)){ while($row = $result->fetch_assoc()) $stats_por_materia[] = $row; $result->free(); }

include __DIR__ . '/side_bar_director.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Cursos</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Cursos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="director_configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Filtros y Acciones</h5></div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row align-items-end">
                                <div class="col-md-9 mb-3">
                                    <label for="search" class="form-label">Buscar Curso</label>
                                    <input type="text" class="form-control" name="search" id="search" value="<?php echo htmlspecialchars($filtro_busqueda); ?>" placeholder="Nombre, código o profesor...">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="submit" class="btn btn-academic w-100"><i class="bi bi-filter"></i> Filtrar</button>
                                </div>
                            </div>
                        </form>
                        <div class="text-end mt-2">
                             <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseModal"><i class="bi bi-plus-circle"></i> Agregar Nuevo Curso</button>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Listado de Cursos (<?php echo $total_cursos; ?>)</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-academic">
                                    <tr><th>Código</th><th>Nombre del Curso</th><th>Grado</th><th>Profesor</th><th>Inscritos</th><th>Estado</th><th>Acciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cursos)): ?>
                                        <tr><td colspan="7" class="text-center">No se encontraron cursos con los criterios de búsqueda.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($cursos as $curso): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($curso['codigo']); ?></td>
                                            <td><?php echo htmlspecialchars($curso['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($curso['nombre_grado'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($curso['nombre_profesor'] ?? 'No asignado'); ?></td>
                                            <td><span class="badge bg-info"><?php echo $curso['estudiantes_inscritos']; ?></span></td>
                                            <td>
                                                <?php $estado = $curso['estatus']; $badge_class = ($estado == 'Activo') ? 'bg-success' : (($estado == 'Pendiente') ? 'bg-warning' : 'bg-danger'); echo "<span class='badge {$badge_class}'>{$estado}</span>"; ?>
                                            </td>
                                            <td><button class="btn btn-sm btn-outline-primary" title="Ver"><i class="bi bi-eye"></i></button><button class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php $query_params = !empty($filtro_busqueda) ? ['search' => $filtro_busqueda] : []; ?>
                                <li class="page-item <?php if ($pagina_actual <= 1) echo 'disabled'; ?>"><a class="page-link" href="?page=<?php echo $pagina_actual - 1; ?>&<?php echo http_build_query($query_params); ?>">Anterior</a></li>
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php if ($pagina_actual == $i) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($query_params); ?>"><?php echo $i; ?></a></li>
                                <?php endfor; ?>
                                <li class="page-item <?php if ($pagina_actual >= $total_paginas) echo 'disabled'; ?>"><a class="page-link" href="?page=<?php echo $pagina_actual + 1; ?>&<?php echo http_build_query($query_params); ?>">Siguiente</a></li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Cursos por Grado</h5></div>
                            <div class="card-body"><div class="table-responsive"><table class="table table-sm"><tbody>
                                <?php foreach($stats_por_grado as $stat): ?><tr><td><?php echo htmlspecialchars($stat['nombre']); ?></td><td><span class="badge bg-primary"><?php echo $stat['total']; ?> cursos</span></td></tr><?php endforeach; ?>
                            </tbody></table></div></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Materias más Ofertadas</h5></div>
                            <div class="card-body"><div class="table-responsive"><table class="table table-sm"><tbody>
                                <?php foreach($stats_por_materia as $stat): ?><tr><td><?php echo htmlspecialchars($stat['nombre']); ?></td><td><span class="badge bg-info"><?php echo $stat['total']; ?> cursos</span></td></tr><?php endforeach; ?>
                            </tbody></table></div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Add Course Modal - Versión Final Verificada -->
<div class="modal fade" id="addCourseModal" tabindex="-1" aria-labelledby="addCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header card-header-academic text-white">
                <h5 class="modal-title" id="addCourseModalLabel">Agregar Nuevo Curso</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- El action apunta al script que guarda los datos. -->
            <form id="crearCursoForm" action="crear_curso.php" method="POST">
                <div class="modal-body">
                    <!-- Fila: Código y Nombre -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="courseCode" class="form-label">Código del Curso</label>
                            <input type="text" class="form-control" name="codigo" required>
                        </div>
                        <div class="col-md-6">
                            <label for="courseName" class="form-label">Nombre del Curso</label>
                            <input type="text" class="form-control" name="nombre" required>
                        </div>
                    </div>
                    <!-- Fila: Grado y Sección -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="courseGrade" class="form-label">Grado</label>
                            <select class="form-select" name="grado_id" required>
                                <option value="" selected disabled>Seleccionar grado...</option>
                                <?php foreach ($grados_lista as $grado): ?>
                                    <option value="<?php echo $grado['id']; ?>"><?php echo htmlspecialchars($grado['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="courseSection" class="form-label">Sección</label>
                            <select class="form-select" name="seccion" required>
                                <option value="" selected disabled>Seleccionar sección...</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                    </div>
                    <!-- Fila: Profesor y Materia -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="courseTeacher" class="form-label">Profesor</label>
                            <select class="form-select" name="profesor_id" required>
                                <option value="" selected disabled>Seleccionar profesor...</option>
                                <?php foreach ($profesores_lista as $profesor): ?>
                                    <option value="<?php echo $profesor['id']; ?>"><?php echo htmlspecialchars($profesor['apellido'] . ', ' . $profesor['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="courseSubject" class="form-label">Materia</label>
                             <select class="form-select" name="materia_id" required>
                                <option value="" selected disabled>Seleccionar materia...</option>
                                <?php foreach ($materias_lista as $materia): ?>
                                    <option value="<?php echo $materia['id']; ?>"><?php echo htmlspecialchars($materia['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <!-- Fila: Capacidad y Créditos -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="courseCapacity" class="form-label">Capacidad (N° de Estudiantes)</label>
                            <input type="number" class="form-control" name="capacidad" min="1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="courseCredits" class="form-label">Créditos</label>
                            <input type="number" class="form-control" name="creditos" min="0" step="1" required>
                        </div>
                    </div>
                    <!-- INICIO: CAMPOS RESTAURADOS -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="courseStartDate" class="form-label">Fecha de Inicio</label>
                            <input type="date" class="form-control" name="fecha_inicio">
                        </div>
                        <div class="col-md-6">
                            <label for="courseEndDate" class="form-label">Fecha de Finalización</label>
                            <input type="date" class="form-control" name="fecha_fin">
                        </div>
                    </div>
                    <!-- FIN: CAMPOS RESTAURADOS -->
                    <div class="mb-3">
                        <label for="courseDescription" class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" rows="2"></textarea>
                    </div>
                     <!-- INICIO: CAMPO RESTAURADO -->
                    <div class="mb-3">
                        <label for="courseStatus" class="form-label">Estado</label>
                        <select class="form-select" name="estatus" required>
                            <option value="Activo" selected>Activo</option>
                            <option value="Pendiente">Pendiente</option>
                            <option value="Inactivo">Inactivo</option>
                        </select>
                    </div>
                    <!-- FIN: CAMPO RESTAURADO -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-academic">Guardar Curso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../js/jquery-3.3.1.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>