<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';

$profesor_user_id = $_SESSION['user_id'];

// 1. OBTENER DATOS BÁSICOS DEL PROFESOR
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

// === INICIO DE LA NUEVA LÓGICA DE FILTROS ===

// 2. LEER LOS PARÁMETROS DE FILTRO DESDE LA URL (GET)
$curso_id_filtro = $_GET['curso_id'] ?? null;
$periodo_filtro = $_GET['periodo'] ?? null;
$tipo_evaluacion_filtro = $_GET['tipo_evaluacion'] ?? null;
$busqueda_filtro = $_GET['q'] ?? null;

// 3. CONSTRUIR DINÁMICAMENTE LA CLÁUSULA WHERE PARA LA CONSULTA SQL
$where_clauses = ["p.id = ?"]; // La condición base siempre es el ID del profesor
$params = [$profesor_id];
$types = 'i';

if (!empty($curso_id_filtro)) {
    $where_clauses[] = "c.id = ?";
    $params[] = $curso_id_filtro;
    $types .= 'i';
}
if (!empty($periodo_filtro)) {
    // Si se filtra por periodo, también filtramos las evaluaciones
    $where_clauses[] = "e.periodo = ?";
    $params[] = $periodo_filtro;
    $types .= 's';
}
if (!empty($tipo_evaluacion_filtro)) {
    $where_clauses[] = "e.tipo_evaluacion = ?";
    $params[] = $tipo_evaluacion_filtro;
    $types .= 's';
}
if (!empty($busqueda_filtro)) {
    $where_clauses[] = "CONCAT(u.nombre, ' ', u.apellido) LIKE ?";
    $params[] = '%' . $busqueda_filtro . '%';
    $types .= 's';
}

$where_sql = implode(' AND ', $where_clauses);

// === FIN DE LA NUEVA LÓGICA DE FILTROS ===

// 4. OBTENER DATOS DE CALIFICACIONES ESTRUCTURADOS (Consulta modificada)
$cursos_data = [];
$sql = "
SELECT 
    c.id AS curso_id, c.nombre AS curso_nombre, g.nombre AS grado_nombre, c.seccion,
    est.id AS estudiante_id, u.nombre AS estudiante_nombre, u.apellido AS estudiante_apellido,
    e.id AS evaluacion_id, e.titulo AS evaluacion_titulo, e.ponderacion AS evaluacion_ponderacion,
    cal.calificacion
FROM cursos c
JOIN profesores p ON c.profesor_id = p.id
JOIN grados g ON c.grado_id = g.id
JOIN matriculas m ON c.id = m.curso_id
JOIN estudiantes est ON m.estudiante_id = est.id
JOIN usuarios u ON est.usuario_id = u.id
LEFT JOIN evaluaciones e ON c.id = e.curso_id
LEFT JOIN calificaciones cal ON e.id = cal.evaluacion_id AND m.id = cal.matricula_id
WHERE {$where_sql} AND c.estatus = 'Activo' -- Usamos el WHERE dinámico
ORDER BY c.id, u.apellido, u.nombre, e.fecha;
";

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// El resto de la lógica para procesar los datos sigue igual
while ($row = $result->fetch_assoc()) {
    $curso_id = $row['curso_id'];
    $estudiante_id = $row['estudiante_id'];
    $evaluacion_id = $row['evaluacion_id'];
    
    if (!isset($cursos_data[$curso_id])) {
        $cursos_data[$curso_id] = [
            'id' => $curso_id, 'nombre' => $row['curso_nombre'], 'grado' => $row['grado_nombre'], 
            'seccion' => $row['seccion'], 'estudiantes' => [], 'evaluaciones' => []
        ];
    }
    if (!isset($cursos_data[$curso_id]['estudiantes'][$estudiante_id])) {
        $cursos_data[$curso_id]['estudiantes'][$estudiante_id] = [
            'id' => $estudiante_id, 'nombre_completo' => $row['estudiante_nombre'] . ' ' . $row['estudiante_apellido'], 
            'calificaciones' => []
        ];
    }
    if ($evaluacion_id && !isset($cursos_data[$curso_id]['evaluaciones'][$evaluacion_id])) {
        $cursos_data[$curso_id]['evaluaciones'][$evaluacion_id] = [
            'id' => $evaluacion_id, 'titulo' => $row['evaluacion_titulo'], 
            'ponderacion' => $row['evaluacion_ponderacion']
        ];
    }
    if ($evaluacion_id) {
        $cursos_data[$curso_id]['estudiantes'][$estudiante_id]['calificaciones'][$evaluacion_id] = $row['calificacion'];
    }
}
$stmt->close();


// --- Lógica para el modal de "Nueva Calificación" (ya estaba bien) ---
$cursos_profesor_modal = [];
$stmt_cursos_modal = $mysqli->prepare("SELECT c.id, c.nombre, g.nombre as grado, c.seccion FROM cursos c JOIN grados g ON c.grado_id = g.id WHERE c.profesor_id = ? AND c.estatus = 'Activo'");
$stmt_cursos_modal->bind_param('i', $profesor_id);
$stmt_cursos_modal->execute();
$result_cursos_modal = $stmt_cursos_modal->get_result();
while($row = $result_cursos_modal->fetch_assoc()) {
    $cursos_profesor_modal[] = $row;
}
$stmt_cursos_modal->close();

include __DIR__ . '/side_bar_profesor.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
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
            <!-- Sidebar -->
            

            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Calificaciones</h1>
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

                <!-- Action Buttons - Profesor can edit grades -->
                <div class="row mb-4 editable-by-professor">
                    <div class="col-12 text-end">
                        <button class="btn btn-success me-2 edit-permission-professor" data-bs-toggle="modal" data-bs-target="#newGradeModal">
                            <i class="bi bi-plus-circle"></i> Nueva Calificación
                        </button>
                        
                    </div>
                </div>

<!-- Filters (AHORA FUNCIONALES) -->
<div class="card mb-4">
    <div class="card-header card-header-academic">
        <h5 class="mb-0 text-white">Filtros de Búsqueda</h5>
    </div>
    <div class="card-body">
        <!-- El formulario envía los datos a la misma página usando el método GET -->
        <form action="profesor_calificaciones.php" method="GET">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="courseFilter" class="form-label">Curso</label>
                    <!-- El 'name' es 'curso_id' para coincidir con la DB -->
                    <select class="form-select" id="courseFilter" name="curso_id">
                        <option value="">Todos mis cursos</option>
                        <!-- PHP llenará estas opciones dinámicamente -->
                        <?php foreach ($cursos_data as $curso): ?>
                            <option value="<?php echo $curso['id']; ?>" 
                                    <?php if (isset($_GET['curso_id']) && $_GET['curso_id'] == $curso['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['grado']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="periodFilter" class="form-label">Periodo</label>
                    <!-- El 'name' es 'periodo' -->
                    <select class="form-select" id="periodFilter" name="periodo">
                        <option value="">Todos</option>
                        <option value="Primer Trimestre" <?php if (isset($_GET['periodo']) && $_GET['periodo'] == 'Primer Trimestre') echo 'selected'; ?>>Primer Trimestre</option>
                        <option value="Segundo Trimestre" <?php if (isset($_GET['periodo']) && $_GET['periodo'] == 'Segundo Trimestre') echo 'selected'; ?>>Segundo Trimestre</option>
                        <option value="Tercer Trimestre" <?php if (isset($_GET['periodo']) && $_GET['periodo'] == 'Tercer Trimestre') echo 'selected'; ?>>Tercer Trimestre</option>
                        <option value="Final" <?php if (isset($_GET['periodo']) && $_GET['periodo'] == 'Final') echo 'selected'; ?>>Final</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="gradeTypeFilter" class="form-label">Tipo de Evaluación</label>
                    <!-- El 'name' es 'tipo_evaluacion' -->
                    <select class="form-select" id="gradeTypeFilter" name="tipo_evaluacion">
                        <option value="">Todos</option>
                        <option value="Examen" <?php if (isset($_GET['tipo_evaluacion']) && $_GET['tipo_evaluacion'] == 'Examen') echo 'selected'; ?>>Examen</option>
                        <option value="Prueba Corta" <?php if (isset($_GET['tipo_evaluacion']) && $_GET['tipo_evaluacion'] == 'Prueba Corta') echo 'selected'; ?>>Prueba Corta</option>
                        <option value="Tarea" <?php if (isset($_GET['tipo_evaluacion']) && $_GET['tipo_evaluacion'] == 'Tarea') echo 'selected'; ?>>Tarea</option>
                        <option value="Proyecto" <?php if (isset($_GET['tipo_evaluacion']) && $_GET['tipo_evaluacion'] == 'Proyecto') echo 'selected'; ?>>Proyecto</option>
                        <option value="Participación" <?php if (isset($_GET['tipo_evaluacion']) && $_GET['tipo_evaluacion'] == 'Participación') echo 'selected'; ?>>Participación</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="searchInput" class="form-label">Buscar por Estudiante</label>
                    <!-- El 'name' es 'q' (de query) -->
                    <div class="input-group">
                        <input type="text" class="form-control" id="searchInput" name="q" placeholder="Nombre o apellido..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 text-end">
                    <!-- Botón para aplicar los filtros (es el submit del form) -->
                    <button type="submit" class="btn btn-academic">
                        <i class="bi bi-search"></i> Aplicar Filtros
                    </button>
                    <!-- Botón para limpiar los filtros (simplemente un enlace a la misma página sin parámetros) -->
                    <a href="profesor_calificaciones.php" class="btn btn-outline-secondary">
                        <i class="bi bi-eraser"></i> Limpiar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Course Selection -->
<div class="card mb-4">
    <div class="card-header card-header-academic">
        <h5 class="mb-0 text-white">Seleccionar Curso para Calificaciones</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <?php if (empty($cursos_data)): ?>
                <div class="col-12"><p class="text-center">No tiene cursos activos asignados.</p></div>
            <?php else: ?>
                <?php foreach ($cursos_data as $curso): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-primary">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['grado'] . ' ' . $curso['seccion']); ?></h5>
                                <p class="card-text">
                                    <strong>Estudiantes:</strong> <?php echo count($curso['estudiantes']); ?><br>
                                </p>
                                <a class="btn btn-primary w-100 mt-auto" href="#curso-<?php echo $curso['id']; ?>">Ver Calificaciones</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Grades Tables - Dynamic Section -->
<?php foreach ($cursos_data as $curso): ?>
    <div class="card mb-4" id="curso-<?php echo $curso['id']; ?>">
        <div class="card-header card-header-academic d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-white">Calificaciones: <?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['grado'] . ' ' . $curso['seccion']); ?></h5>

        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-academic">
                        <tr>
                            <th>Estudiante</th>
                            <?php foreach ($curso['evaluaciones'] as $evaluacion): ?>
                                <th><?php echo htmlspecialchars($evaluacion['titulo']); ?> (<?php echo $evaluacion['ponderacion']; ?>%)</th>
                            <?php endforeach; ?>
                            <th>Promedio Ponderado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($curso['estudiantes'] as $estudiante): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($estudiante['nombre_completo']); ?></td>
                                <?php
                                    $promedio_ponderado = 0;
                                    $total_ponderacion = 0;
                                ?>
                                <?php foreach ($curso['evaluaciones'] as $evaluacion): ?>
                                    <?php
                                        $calificacion = $estudiante['calificaciones'][$evaluacion['id']] ?? null;
                                        if (is_numeric($calificacion)) {
                                            $promedio_ponderado += ($calificacion * ($evaluacion['ponderacion'] / 100));
                                            $total_ponderacion += $evaluacion['ponderacion'];
                                        }
                                    ?>
                                    <td><?php echo is_numeric($calificacion) ? number_format($calificacion, 1) : '-'; ?></td>
                                <?php endforeach; ?>
                                <?php 
                                    // Para evitar división por cero si no hay ponderaciones
                                    if ($total_ponderacion > 0) {
                                        // Normalizamos el promedio si la ponderación no suma 100
                                        $promedio_final = ($promedio_ponderado / $total_ponderacion) * 100;
                                    } else {
                                        $promedio_final = 0;
                                    }
                                ?>
                                <td><strong><?php echo number_format($promedio_final, 1); ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endforeach; ?>

                <!-- Statistics -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Estadísticas de Rendimiento</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Distribución de Calificaciones</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Rango</th>
                                                <th>Estudiantes</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>90-100 (Excelente)</td>
                                                <td>5</td>
                                                <td>15.6%</td>
                                            </tr>
                                            <tr>
                                                <td>80-89 (Muy Bueno)</td>
                                                <td>12</td>
                                                <td>37.5%</td>
                                            </tr>
                                            <tr>
                                                <td>70-79 (Bueno)</td>
                                                <td>8</td>
                                                <td>25.0%</td>
                                            </tr>
                                            <tr>
                                                <td>60-69 (Regular)</td>
                                                <td>5</td>
                                                <td>15.6%</td>
                                            </tr>
                                            <tr>
                                                <td>0-59 (Insuficiente)</td>
                                                <td>2</td>
                                                <td>6.3%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Comparativa por Evaluación</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Evaluación</th>
                                                <th>Promedio</th>
                                                <th>Nota Más Alta</th>
                                                <th>Nota Más Baja</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Examen 1</td>
                                                <td>81.6/100</td>
                                                <td>92/100</td>
                                                <td>65/100</td>
                                            </tr>
                                            <tr>
                                                <td>Examen 2</td>
                                                <td>84.4/100</td>
                                                <td>95/100</td>
                                                <td>70/100</td>
                                            </tr>
                                            <tr>
                                                <td>Tareas</td>
                                                <td>83.6/100</td>
                                                <td>90/100</td>
                                                <td>75/100</td>
                                            </tr>
                                            <tr>
                                                <td>Proyecto</td>
                                                <td>84.0/100</td>
                                                <td>92/100</td>
                                                <td>68/100</td>
                                            </tr>
                                            <tr>
                                                <td>Participación</td>
                                                <td>87.0/100</td>
                                                <td>95/100</td>
                                                <td>80/100</td>
                                            </tr>
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

    <!-- New Grade Modal - Only Professor can access -->

<div class="modal fade" id="newGradeModal" tabindex="-1" aria-labelledby="newGradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header card-header-academic text-white">
                <h5 class="modal-title" id="newGradeModalLabel">Crear Nueva Calificación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- INICIO DEL FORMULARIO MODIFICADO -->
            <form action="guardar_evaluacion.php" method="POST">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="gradeClass" class="form-label">Curso</label>
                            <select class="form-select" id="gradeClass" name="curso_id" required>
                                <option selected disabled value="">Seleccionar curso...</option>
                                <?php foreach ($cursos_profesor_modal as $curso_modal): ?>
                                    <option value="<?php echo $curso_modal['id']; ?>">
                                        <?php echo htmlspecialchars($curso_modal['nombre'] . ' - ' . $curso_modal['grado'] . ' ' . $curso_modal['seccion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="gradePeriod" class="form-label">Periodo</label>
                            <select class="form-select" id="gradePeriod" name="periodo" required>
                                <option selected disabled value="">Seleccionar periodo...</option>
                                <option value="Primer Trimestre">Primer Trimestre</option>
                                <option value="Segundo Trimestre">Segundo Trimestre</option>
                                <option value="Tercer Trimestre">Tercer Trimestre</option>
                                <option value="Final">Final</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="gradeType" class="form-label">Tipo de Evaluación</label>
                            <select class="form-select" id="gradeType" name="tipo_evaluacion" required>
                                <option selected disabled value="">Seleccionar tipo...</option>
                                <option value="Examen">Examen</option>
                                <option value="Prueba Corta">Prueba Corta</option>
                                <option value="Tarea">Tarea</option>
                                <option value="Proyecto">Proyecto</option>
                                <option value="Participación">Participación</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="gradeDate" class="form-label">Fecha de Evaluación</label>
                            <input type="date" class="form-control" id="gradeDate" name="fecha" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="gradeTitle" class="form-label">Título de la Evaluación</label>
                            <input type="text" class="form-control" id="gradeTitle" name="titulo" placeholder="Ej: Examen Parcial 1" required>
                        </div>
                        <div class="col-md-6">
                            <label for="gradeWeight" class="form-label">Ponderación (%)</label>
                            <input type="number" class="form-control" id="gradeWeight" name="ponderacion" min="0" max="100" value="25" required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="gradeMaxScore" class="form-label">Puntaje Máximo</label>
                            <input type="number" class="form-control" id="gradeMaxScore" name="puntaje_maximo" min="1" value="100" required>
                        </div>
                        <div class="col-md-6">
                            <label for="gradePassScore" class="form-label">Puntaje Mínimo Aprobatorio</label>
                            <input type="number" class="form-control" id="gradePassScore" name="puntaje_aprobatorio" min="1" value="60" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="gradeDescription" class="form-label">Descripción</label>
                        <textarea class="form-control" id="gradeDescription" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método de Ingreso de Calificaciones</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metodo_ingreso" id="individualInput" value="individual" checked>
                            <label class="form-check-label" for="individualInput">
                                Ingresar calificaciones individualmente
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metodo_ingreso" id="batchInput" value="batch">
                            <label class="form-check-label" for="batchInput">
                                Importar calificaciones desde archivo
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metodo_ingreso" id="templateInput" value="template">
                            <label class="form-check-label" for="templateInput">
                                Descargar plantilla para llenar
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="notifyStudents" name="notificar_estudiantes" value="1" checked>
                            <label class="form-check-label" for="notifyStudents">
                                Notificar a los estudiantes cuando se publiquen las calificaciones
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <!-- Cambiado a type="submit". El texto "Continuar" implica que después de esto, se pasaría a ingresar las notas. -->
                    <button type="submit" class="btn btn-academic">Guardar y Continuar</button>
                </div>
            </form>
            <!-- FIN DEL FORMULARIO MODIFICADO -->
        </div>
    </div>
</div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
