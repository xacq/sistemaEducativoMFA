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
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary btn-view-course" data-id="<?php echo $curso['id']; ?>" title="Ver Detalles">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-secondary btn-edit-course" title="Editar" data-id="<?php echo $curso['id']; ?>" title="Editar">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger btn-disable-course" title="Eliminar" data-id="<?php echo $curso['id']; ?>" title="Deshabilitar">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
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
                            <input type="text" placeholder="generado automaticamente" class="form-control" name="codigo" readonly >
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

<!-- Modal: Ver Detalle del Curso -->
<div class="modal fade" id="viewCourseModal" tabindex="-1" aria-labelledby="viewCourseLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header card-header-academic text-white">
        <h5 class="modal-title" id="viewCourseLabel">Detalle del Curso</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="courseDetails">
          <p class="text-muted text-center">Cargando información...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Modal para editar curso -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header card-header-academic text-white">
        <h5 class="modal-title" id="editCourseLabel">Editar Curso</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditarCurso" action="editar_curso.php" method="POST">
        <div class="modal-body" id="editCourseBody">
          <p class="text-muted text-center">Cargando información...</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-academic">Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal de confirmación para deshabilitar curso -->
<div class="modal fade" id="confirmDisableModal" tabindex="-1" aria-labelledby="confirmDisableLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="confirmDisableLabel">Deshabilitar Curso</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <p>¿Está seguro de que desea <strong>deshabilitar este curso</strong>?<br>
        El curso pasará a estado <strong>Inactivo</strong> y no podrá ser asignado temporalmente.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="confirmDisableBtn" class="btn btn-danger">Deshabilitar</button>
      </div>
    </div>
  </div>
</div>



<script src="../js/jquery-3.3.1.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
<script>
    /* ========= Crear curso ========= */
    document.getElementById('crearCursoForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true; btn.textContent = "Guardando...";

    try {
        const res = await fetch(form.action, { method: 'POST', body: data });
        const result = await res.json();
        if (!result.success) throw new Error(result.message);
        const modal = bootstrap.Modal.getInstance(document.getElementById('addCourseModal'));
        modal.hide();

        const box = document.createElement('div');
        box.className = 'alert alert-info shadow position-fixed top-50 start-50 translate-middle text-center border border-primary';
        box.style.zIndex = 2000;
        box.style.minWidth = '420px';
        box.style.padding = '20px';
        box.innerHTML = `
        <h5 class="mb-2">✅ Curso registrado correctamente</h5>
        <p class="mb-1">Código generado: <strong>${result.codigo}</strong></p>
        <div class="mt-3">
            <button class="btn btn-primary btn-sm" onclick="this.closest('.alert').remove(); location.reload();">Cerrar</button>
        </div>
        `;
        document.body.appendChild(box);
    } catch (err) {
        alert("Error: " + err.message);
    } finally {
        btn.disabled = false; btn.textContent = "Guardar Curso";
    }
    });

    /* ========= Ver curso ========= */
    document.querySelectorAll('.btn-view-course').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const modal = new bootstrap.Modal(document.getElementById('viewCourseModal'));
        const details = document.getElementById('courseDetails');
        details.innerHTML = "<p class='text-muted text-center'>Cargando...</p>";
        modal.show();

        const res = await fetch(`ver_curso.php?id=${id}`);
        const data = await res.json();

        if (!data.success) {
        details.innerHTML = `<p class='text-danger'>${data.message}</p>`;
        return;
        }

        const c = data.curso;
        details.innerHTML = `
        <table class="table table-bordered">
            <tr><th>Código</th><td>${c.codigo}</td></tr>
            <tr><th>Nombre</th><td>${c.nombre}</td></tr>
            <tr><th>Grado</th><td>${c.nombre_grado}</td></tr>
            <tr><th>Profesor</th><td>${c.nombre_profesor}</td></tr>
            <tr><th>Materia</th><td>${c.nombre_materia}</td></tr>
            <tr><th>Capacidad</th><td>${c.capacidad}</td></tr>
            <tr><th>Créditos</th><td>${c.creditos}</td></tr>
            <tr><th>Fechas</th><td>${c.fecha_inicio || '-'} a ${c.fecha_fin || '-'}</td></tr>
            <tr><th>Estado</th><td><span class="badge bg-${c.estatus === 'Activo' ? 'success' : (c.estatus === 'Pendiente' ? 'warning' : 'danger')}">${c.estatus}</span></td></tr>
            <tr><th>Descripción</th><td>${c.descripcion || 'Sin descripción'}</td></tr>
        </table>`;
    });
    });

    /* ========= Editar curso ========= */
    document.querySelectorAll('.btn-edit-course').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.dataset.id;
        const modalElement = document.getElementById('editCourseModal');
        const body = document.getElementById('editCourseBody');
        const modal = new bootstrap.Modal(modalElement);

        body.innerHTML = "<p class='text-muted text-center'>Cargando...</p>";
        modal.show();

        try {
        const res = await fetch(`ver_curso.php?id=${id}`);
        const data = await res.json();

        if (!data.success) {
            body.innerHTML = `<p class='text-danger text-center'>${data.message}</p>`;
            return;
        }

        const c = data.curso;

        body.innerHTML = `
            <input type="hidden" name="id" value="${c.id}">
            <div class="row mb-3">
            <div class="col-md-6">
                <label>Nombre</label>
                <input name="nombre" class="form-control" value="${c.nombre}" required>
            </div>
            <div class="col-md-3">
                <label>Créditos</label>
                <input name="creditos" type="number" class="form-control" value="${c.creditos}" required>
            </div>
            <div class="col-md-3">
                <label>Capacidad</label>
                <input name="capacidad" type="number" class="form-control" value="${c.capacidad}" required>
            </div>
            </div>
            <div class="mb-3">
            <label>Descripción</label>
            <textarea name="descripcion" class="form-control" rows="3">${c.descripcion || ''}</textarea>
            </div>
            <div class="mb-3">
            <label>Estado</label>
            <select name="estatus" class="form-select">
                <option value="Activo" ${c.estatus === 'Activo' ? 'selected' : ''}>Activo</option>
                <option value="Pendiente" ${c.estatus === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                <option value="Inactivo" ${c.estatus === 'Inactivo' ? 'selected' : ''}>Inactivo</option>
            </select>
            </div>
        `;

        // Registrar evento de submit dentro del modal (se garantiza que existe)
        const form = document.getElementById('formEditarCurso');
        form.onsubmit = async (e) => {
            e.preventDefault();
            const dataForm = new FormData(form);
            try {
            const resp = await fetch('editar_curso.php', { method: 'POST', body: dataForm });
            const result = await resp.json();
            if (result.success) {
                const notif = document.createElement('div');
                notif.className = 'alert alert-success shadow position-fixed top-0 start-50 translate-middle-x mt-3 text-center border border-success';
                notif.style.zIndex = 2000;
                notif.style.minWidth = '380px';
                notif.innerHTML = `
                    <strong>✅ Cambios guardados correctamente</strong>
                    <br><small>El curso se actualizó sin problemas.</small>
                `;
                document.body.appendChild(notif);
                setTimeout(() => {
                    notif.classList.add('fade');
                    setTimeout(() => notif.remove(), 500);
                    location.reload();
                }, 2500);
                modal.hide();
            } else {
                const notif = document.createElement('div');
                notif.className = 'alert alert-warning shadow position-fixed top-0 start-50 translate-middle-x mt-3 text-center border border-warning';
                notif.style.zIndex = 2000;
                notif.style.minWidth = '380px';
                notif.innerHTML = `
                    <strong>⚠️ No se pudieron guardar los cambios</strong>
                    <br><small>${result.message}</small>
                `;
                document.body.appendChild(notif);
                setTimeout(() => {
                    notif.classList.add('fade');
                    setTimeout(() => notif.remove(), 500);
                }, 3500);
            }

            } catch (error) {
            alert('❌ Error al guardar: ' + error.message);
            }
        };

        } catch (error) {
        console.error(error);
        body.innerHTML = `<p class="text-danger text-center">Error al cargar los datos del curso.</p>`;
        }
    });
    });

    /* ========= Deshabilitar curso (versión elegante) ========= */
    let cursoAEliminar = null; // variable global temporal

    document.querySelectorAll('.btn-disable-course').forEach(btn => {
    btn.addEventListener('click', () => {
        cursoAEliminar = btn.dataset.id;
        const modal = new bootstrap.Modal(document.getElementById('confirmDisableModal'));
        modal.show();
    });
    });

    document.getElementById('confirmDisableBtn').addEventListener('click', async () => {
    if (!cursoAEliminar) return;

    const formData = new FormData();
    formData.append('id', cursoAEliminar);

    try {
        const res = await fetch('deshabilitar_curso.php', { method: 'POST', body: formData });
        const data = await res.json();

        // Ocultar el modal de confirmación
        const modalEl = document.getElementById('confirmDisableModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();

        // Crear notificación visual
        const notif = document.createElement('div');
        notif.className = `alert ${data.success ? 'alert-success' : 'alert-warning'} shadow position-fixed top-0 start-50 translate-middle-x mt-3 text-center border`;
        notif.style.zIndex = 2000;
        notif.style.minWidth = '380px';
        notif.innerHTML = `
        <strong>${data.success ? '✅ Curso deshabilitado correctamente' : '⚠️ No se pudo deshabilitar el curso'}</strong>
        <br><small>${data.message}</small>
        `;
        document.body.appendChild(notif);

        setTimeout(() => {
        notif.classList.add('fade');
        setTimeout(() => notif.remove(), 500);
        if (data.success) location.reload();
        }, 2500);

    } catch (err) {
        console.error(err);
        alert('Error inesperado al deshabilitar el curso.');
    }
    });

</script>


</body>
</html>