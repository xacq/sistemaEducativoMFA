<?php
session_start();

// Si no hay sesión activa, volvemos al login
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Conexión
require_once '../config.php';

// --- INICIO: LÓGICA DE DATOS, FILTROS Y PAGINACIÓN ---

// 1. OBTENER NOMBRE DEL DIRECTOR
$stmt_director = $mysqli->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
$stmt_director->bind_param('i', $_SESSION['user_id']);
$stmt_director->execute();
$stmt_director->bind_result($nombre_director, $apellido_director);
$stmt_director->fetch();
$stmt_director->close();

// 2. PROCESAR FILTROS Y BÚSQUEDA
$filtro_grado_id = isset($_GET['grado']) ? (int)$_GET['grado'] : 0;
$filtro_busqueda = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clauses = [];
$params = [];
$param_types = '';

if ($filtro_grado_id > 0) {
    $where_clauses[] = "e.grado_id = ?";
    $params[] = $filtro_grado_id;
    $param_types .= 'i';
}
if (!empty($filtro_busqueda)) {
    $where_clauses[] = "(u.nombre LIKE ? OR u.apellido LIKE ? OR e.codigo_estudiante LIKE ?)";
    $search_term = "%{$filtro_busqueda}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= 'sss';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// 3. CONFIGURACIÓN DE PAGINACIÓN
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 4. CONTAR TOTAL DE ESTUDIANTES (CON FILTROS APLICADOS)
$sql_total = "SELECT COUNT(e.id) FROM estudiantes AS e JOIN usuarios AS u ON e.usuario_id = u.id" . $where_sql;
$stmt_total = $mysqli->prepare($sql_total);
if (!empty($params)) {
    $stmt_total->bind_param($param_types, ...$params);
}
$stmt_total->execute();
$total_estudiantes = $stmt_total->get_result()->fetch_row()[0];
$total_paginas = ceil($total_estudiantes / $registros_por_pagina);
$stmt_total->close();

// 5. CONSULTA PRINCIPAL PARA OBTENER LOS ESTUDIANTES DE LA PÁGINA ACTUAL
$estudiantes = [];
$sql_estudiantes = "
    SELECT 
        e.id, e.codigo_estudiante, e.fecha_nacimiento, e.estado AS estado_estudiante,
        u.nombre, u.apellido,
        g.nombre AS nombre_grado
    FROM estudiantes AS e
    JOIN usuarios AS u ON e.usuario_id = u.id
    JOIN grados AS g ON e.grado_id = g.id
    $where_sql
    ORDER BY u.apellido, u.nombre
    LIMIT ? OFFSET ?
";
$stmt_estudiantes = $mysqli->prepare($sql_estudiantes);
$params_paginacion = $params;
$params_paginacion[] = $registros_por_pagina;
$params_paginacion[] = $offset;
$param_types_paginacion = $param_types . 'ii';
if (!empty($params)) {
    $stmt_estudiantes->bind_param($param_types_paginacion, ...$params_paginacion);
} else {
     $stmt_estudiantes->bind_param('ii', $registros_por_pagina, $offset);
}
$stmt_estudiantes->execute();
$result_estudiantes = $stmt_estudiantes->get_result();
while ($row = $result_estudiantes->fetch_assoc()) {
    $estudiantes[] = $row;
}
$stmt_estudiantes->close();

// 6. OBTENER LISTA DE GRADOS PARA FILTROS Y MODAL
$grados_lista = [];
$result_grados = $mysqli->query("SELECT id, nombre FROM grados ORDER BY id");
while ($row = $result_grados->fetch_assoc()) {
    $grados_lista[] = $row;
}
$result_grados->free();

// 7. OBTENER ESTADÍSTICAS
// Distribución por grado
$distribucion_grados = [];
$result_dist_grados = $mysqli->query("
    SELECT g.nombre, COUNT(e.id) as total 
    FROM estudiantes e 
    JOIN grados g ON e.grado_id = g.id 
    GROUP BY g.id ORDER BY g.id
");
$total_general_estudiantes = $mysqli->query("SELECT COUNT(id) FROM estudiantes")->fetch_row()[0];
while ($row = $result_dist_grados->fetch_assoc()) {
    $distribucion_grados[] = [
        'nombre' => $row['nombre'],
        'total' => $row['total'],
        'porcentaje' => ($total_general_estudiantes > 0) ? round(($row['total'] / $total_general_estudiantes) * 100, 1) : 0
    ];
}
$result_dist_grados->free();

// --- FIN: LÓGICA DE DATOS ---

include __DIR__ . '/side_bar_director.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Estudiantes</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
        
            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Estudiantes</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <!-- INICIO: DROPDOWN DE USUARIO RESTAURADO -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($nombre_director . ' ' . $apellido_director, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="director_configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                        <!-- FIN: DROPDOWN DE USUARIO RESTAURADO -->
                    </div>
                </div>
                <!-- Mensajes de sesión -->
                <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <!-- Search, Filters and Add Student Button -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Filtros y Acciones</h5></div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row align-items-end">
                                <div class="col-md-5 mb-3">
                                    <label for="search" class="form-label">Buscar Estudiante</label>
                                    <input type="text" class="form-control" name="search" id="search" value="<?php echo htmlspecialchars($filtro_busqueda); ?>" placeholder="Nombre, apellido o código...">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="grado" class="form-label">Filtrar por Grado</label>
                                    <select class="form-select" name="grado" id="grado">
                                        <option value="0" <?php if ($filtro_grado_id == 0) echo 'selected'; ?>>Todos los grados</option>
                                        <?php foreach ($grados_lista as $grado): ?>
                                        <option value="<?php echo $grado['id']; ?>" <?php if ($filtro_grado_id == $grado['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($grado['nombre']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="submit" class="btn btn-academic w-100"><i class="bi bi-filter"></i> Filtrar</button>
                                </div>
                            </div>
                        </form>
                        <div class="text-end mt-2">
                             <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                                <i class="bi bi-person-plus"></i> Agregar Nuevo Estudiante
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Students List -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Mostrando <?php echo count($estudiantes); ?> de <?php echo $total_estudiantes; ?> Estudiantes</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <!-- ... (código de la tabla dinámica, no necesita cambios) ... -->
                                <thead class="table-academic">
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre Completo</th>
                                        <th>Grado</th>
                                        <th>Edad</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($estudiantes)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No se encontraron estudiantes con los filtros aplicados.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($estudiantes as $estudiante): ?>
                                        <tr data-id="<?php echo $estudiante['id']; ?>">
                                            <td><?php echo htmlspecialchars($estudiante['codigo_estudiante']); ?></td>
                                            <td><?php echo htmlspecialchars($estudiante['apellido'] . ' ' . $estudiante['nombre']); ?></td>
                                            <td><?php echo htmlspecialchars($estudiante['nombre_grado']); ?></td>
                                            <td>
                                                <?php
                                                $fecha_nac = new DateTime($estudiante['fecha_nacimiento']);
                                                $hoy = new DateTime();
                                                echo $hoy->diff($fecha_nac)->y;
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $estado = $estudiante['estado_estudiante'];
                                                $badge_class = ($estado == 'Activo') ? 'bg-success' : (($estado == 'Inactivo') ? 'bg-warning' : 'bg-danger');
                                                echo "<span class='badge {$badge_class}'>{$estado}</span>";
                                                ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" title="Ver Perfil"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación Dinámica (con filtros preservados) -->
                        <nav aria-label="Page navigation">
                            <!-- ... (código de paginación, no necesita cambios) ... -->
                            <ul class="pagination justify-content-center">
                                <?php
                                $query_params = [];
                                if ($filtro_grado_id > 0) $query_params['grado'] = $filtro_grado_id;
                                if (!empty($filtro_busqueda)) $query_params['search'] = $filtro_busqueda;
                                ?>
                                <li class="page-item <?php if ($pagina_actual <= 1) echo 'disabled'; ?>">
                                    <a class="page-link" href="?page=<?php echo $pagina_actual - 1; ?>&<?php echo http_build_query($query_params); ?>">Anterior</a>
                                </li>
                                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                                <li class="page-item <?php if ($pagina_actual == $i) echo 'active'; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($query_params); ?>"><?php echo $i; ?></a>
                                </li>
                                <?php endfor; ?>
                                <li class="page-item <?php if ($pagina_actual >= $total_paginas) echo 'disabled'; ?>">
                                    <a class="page-link" href="?page=<?php echo $pagina_actual + 1; ?>&<?php echo http_build_query($query_params); ?>">Siguiente</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Student Statistics (DINÁMICAS) -->
                <div class="row mb-4">
                    <!-- ... (código de estadísticas, no necesita cambios) ... -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Distribución por Grado</h5></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead>
                                            <tr><th>Grado</th><th>Estudiantes</th><th>Porcentaje</th></tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($distribucion_grados as $dist): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($dist['nombre']); ?></td>
                                                <td><?php echo $dist['total']; ?></td>
                                                <td><?php echo $dist['porcentaje']; ?>%</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Estadísticas Generales</h5></div>
                            <div class="card-body">
                                <p>Total de Estudiantes: <strong><?php echo $total_general_estudiantes; ?></strong></p>
                                <p>Estudiantes Activos: <strong><?php echo $mysqli->query("SELECT COUNT(id) FROM estudiantes WHERE estado = 'Activo'")->fetch_row()[0]; ?></strong></p>
                                <p>Estudiantes Inactivos/Suspendidos: <strong><?php echo $mysqli->query("SELECT COUNT(id) FROM estudiantes WHERE estado != 'Activo'")->fetch_row()[0]; ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- INICIO: MODAL PARA AGREGAR ESTUDIANTE RESTAURADO -->
    <div class="modal fade" id="addStudentModal" tabindex="-1" aria-labelledby="addStudentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="addStudentModalLabel">Agregar Nuevo Estudiante</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formNuevoEstudiante" action="crear_estudiante.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <!-- Nombres y Apellidos -->
                        <div class="row mb-3">
                            <div class="col-md-6"><label for="studentName" class="form-label">Nombres</label><input type="text" class="form-control" name="studentName" required></div>
                            <div class="col-md-6"><label for="studentLastName" class="form-label">Apellidos</label><input type="text" class="form-control" name="studentLastName" required></div>
                        </div>
                        <!-- Código y Email -->
                        <div class="row mb-3">
                            <div class="col-md-6"><label for="studentID"  class="form-label">Código de Estudiante</label><input   class="form-control" name="studentID" readonly></div>
                            <div class="col-md-6"><label for="studentEmail" class="form-label">Correo Electrónico (Login)</label><input type="email" class="form-control" name="studentEmail" required></div>
                        </div>
                        <!-- Fecha Nacimiento y Género -->
                        <div class="row mb-3">
                            <div class="col-md-6"><label for="studentBirthdate" class="form-label">Fecha de Nacimiento</label><input type="date" class="form-control" name="studentBirthdate" required></div>
                            <div class="col-md-6"><label for="studentGender" class="form-label">Género</label><select class="form-select" name="studentGender" required><option value="" selected disabled>Seleccionar...</option><option value="Masculino">Masculino</option><option value="Femenino">Femenino</option><option value="Otro">Otro</option></select></div>
                        </div>
                        <!-- Grado y Sección -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="studentGrade" class="form-label">Grado</label>
                                <select class="form-select" name="grado_id" required>
                                    <option value="" selected disabled>Seleccionar grado...</option>
                                    <?php foreach ($grados_lista as $grado): ?>
                                    <option value="<?php echo $grado['id']; ?>"><?php echo htmlspecialchars($grado['nombre']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6"><label for="studentSection" class="form-label">Sección</label><select class="form-select" name="studentSection" required><option value="" selected disabled>Seleccionar...</option><option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option></select></div>
                        </div>
                        <!-- Teléfono y Dirección -->
                        <div class="row mb-3">
                           <div class="col-md-6"><label for="studentPhone" class="form-label">Teléfono</label><input type="tel" class="form-control" name="studentPhone"></div>
                           <div class="col-md-6"><label for="studentAddress" class="form-label">Dirección</label><input type="text" class="form-control" name="studentAddress" required></div>
                        </div>
                        <!-- Info del Tutor -->
                         <div class="row mb-3">
                            <div class="col-md-6"><label for="parentName" class="form-label">Nombre del Tutor</label><input type="text" class="form-control" name="parentName" required></div>
                            <div class="col-md-6"><label for="parentPhone" class="form-label">Teléfono del Tutor</label><input type="tel" class="form-control" name="parentPhone" required></div>
                        </div>
                        <!-- Fechas y Estado -->
                        <div class="row mb-3">
                            <div class="col-md-6"><label for="enrollmentDate" class="form-label">Fecha de Inscripción</label><input type="date" class="form-control" name="enrollmentDate" required></div>
                            <div class="col-md-6"><label for="studentStatus" class="form-label">Estado</label><select class="form-select" name="studentStatus" required><option value="Activo" selected>Activo</option><option value="Inactivo">Inactivo</option><option value="Suspendido">Suspendido</option></select></div>
                        </div>
                        <!-- Foto y Observaciones -->
                        <div class="mb-3"><label for="studentPhoto" class="form-label">Fotografía</label><input class="form-control" type="file" name="studentPhoto"></div>
                        <div class="mb-3"><label for="studentNotes" class="form-label">Observaciones</label><textarea class="form-control" name="studentNotes" rows="3"></textarea></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-academic">Guardar Estudiante</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- FIN: MODAL PARA AGREGAR ESTUDIANTE RESTAURADO -->

    <!-- Scripts -->
    <script src=".. /js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    // --- Envío AJAX del formulario "Nuevo Estudiante" ---
    document.getElementById('formNuevoEstudiante').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const data = new FormData(form);

        // Deshabilitar botón mientras se guarda
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = "Guardando...";

        try {
            const response = await fetch(form.action, { method: 'POST', body: data });
            const result = await response.json();

            if (!result.success) {
                alert(result.message || "Error al registrar estudiante.");
                submitBtn.disabled = false;
                submitBtn.textContent = "Guardar Estudiante";
                return;
            }

            // Cerrar modal (usa Bootstrap 5)
            const modal = bootstrap.Modal.getInstance(document.getElementById('addStudentModal'));
            modal.hide();

            // Crear mensaje flotante centrado
            const box = document.createElement('div');
            box.className = 'alert alert-info shadow position-fixed top-50 start-50 translate-middle text-center border border-primary';
            box.style.zIndex = 2000;
            box.style.minWidth = '420px';
            box.style.padding = '20px';
            box.innerHTML = `
                <h5 class="mb-2">✅ Estudiante registrado correctamente</h5>
                <p class="mb-1">Enlace de activación (entregar al estudiante):</p>
                <a href="${result.activation_link}" target="_blank">${result.activation_link}</a>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm" onclick="this.closest('.alert').remove(); location.reload();">Cerrar</button>
                </div>
            `;
            document.body.appendChild(box);
        } catch (error) {
            console.error(error);
            alert("Ocurrió un error inesperado al guardar el estudiante.");
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = "Guardar Estudiante";
        }
    });

    /* ========= VER ESTUDIANTE ========= */
    document.querySelectorAll('.btn-outline-primary').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.closest('tr').dataset.id;
        const res = await fetch(`ver_estudiante.php?id=${id}`);
        const data = await res.json();

        if (data.error) {
        alert(data.error);
        return;
        }

        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Información del Estudiante</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Código:</strong> ${data.codigo_estudiante}</p>
                <p><strong>Nombre:</strong> ${data.nombre} ${data.apellido}</p>
                <p><strong>Correo:</strong> ${data.email}</p>
                <p><strong>Grado:</strong> ${data.grado_nombre}</p>
                <p><strong>Teléfono:</strong> ${data.telefono || '—'}</p>
                <p><strong>Dirección:</strong> ${data.direccion || '—'}</p>
                <p><strong>Estado:</strong> <span class="badge ${data.estado === 'Activo' ? 'bg-success' : 'bg-secondary'}">${data.estado}</span></p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
            </div>
        </div>`;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        modal.addEventListener('hidden.bs.modal', () => modal.remove());
    });
    });

    /* ========= EDITAR ESTUDIANTE ========= */
    document.querySelectorAll('.btn-outline-secondary').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.closest('tr').dataset.id;
        const res = await fetch(`ver_estudiante.php?id=${id}`);
        const data = await res.json();

        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">Editar Estudiante</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarEstudiante">
                <div class="modal-body">
                <input type="hidden" name="id" value="${id}">
                <div class="mb-2">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="${data.telefono || ''}">
                </div>
                <div class="mb-2">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="${data.direccion || ''}">
                </div>
                <div class="mb-2">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-select">
                    <option value="Activo" ${data.estado === 'Activo' ? 'selected' : ''}>Activo</option>
                    <option value="Inactivo" ${data.estado === 'Inactivo' ? 'selected' : ''}>Inactivo</option>
                    </select>
                </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-academic">Guardar Cambios</button>
                </div>
            </form>
            </div>
        </div>`;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        modal.querySelector('#formEditarEstudiante').addEventListener('submit', async e => {
        e.preventDefault();
        const form = e.target;
        const dataForm = new FormData(form);
        const res = await fetch('editar_estudiante.php', { method: 'POST', body: dataForm });
        const result = await res.json();

        const notif = document.createElement('div');
        notif.className = `alert ${result.success ? 'alert-success' : 'alert-danger'} shadow position-fixed top-50 start-50 translate-middle text-center border`;
        notif.style.zIndex = 2000;
        notif.style.minWidth = '380px';
        notif.innerHTML = `<strong>${result.message}</strong>`;
        document.body.appendChild(notif);
        setTimeout(() => notif.remove(), 2500);

        if (result.success) {
            bsModal.hide();
            setTimeout(() => location.reload(), 1000);
        }
        });

        modal.addEventListener('hidden.bs.modal', () => modal.remove());
    });
    });

    /* ========= DESHABILITAR ESTUDIANTE ========= */
    document.querySelectorAll('.btn-outline-danger').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.closest('tr').dataset.id;
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar Deshabilitación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>¿Deseas deshabilitar este estudiante?<br>Su cuenta quedará marcada como inactiva.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-danger" id="confirmDisableBtn">Deshabilitar</button>
            </div>
            </div>
        </div>`;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        modal.querySelector('#confirmDisableBtn').addEventListener('click', async () => {
        const res = await fetch('deshabilitar_estudiante.php', { method: 'POST', body: new URLSearchParams({ id }) });
        const data = await res.json();

        bsModal.hide();

        const notif = document.createElement('div');
        notif.className = `alert ${data.success ? 'alert-success' : 'alert-danger'} shadow position-fixed top-50 start-50 translate-middle text-center border`;
        notif.style.zIndex = 2000;
        notif.style.minWidth = '380px';
        notif.innerHTML = `<strong>${data.message}</strong>`;
        document.body.appendChild(notif);
        setTimeout(() => notif.remove(), 2500);

        if (data.success) setTimeout(() => location.reload(), 1000);
        });

        modal.addEventListener('hidden.bs.modal', () => modal.remove());
    });
    });


    </script>

    <style>
    .alert.position-fixed {
        animation: aparecer 0.3s ease-out;
    }
    @keyframes aparecer {
        from { opacity: 0; transform: translate(-50%, -40%); }
        to { opacity: 1; transform: translate(-50%, -50%); }
    }
    </style>

</body>
</html>