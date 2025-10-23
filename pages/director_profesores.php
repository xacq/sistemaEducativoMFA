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
$stmt_director->bind_result($nombre, $apellido);
$stmt_director->fetch();
$stmt_director->close();

// 2. OBTENER LISTA DE MATERIAS PARA EL MODAL (y otros usos si es necesario)
$materias_lista = [];
if ($result_materias = $mysqli->query("SELECT id, nombre FROM materias ORDER BY nombre")) {
    while ($row = $result_materias->fetch_assoc()) {
        $materias_lista[] = $row;
    }
    $result_materias->free();
}

// 3. PROCESAR FILTROS (búsqueda como ejemplo)
$filtro_busqueda = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_sql = '';
$params = [];
$param_types = '';

if (!empty($filtro_busqueda)) {
    $where_sql = " WHERE (u.nombre LIKE ? OR u.apellido LIKE ? OR p.departamento LIKE ?)";
    $search_term = "%{$filtro_busqueda}%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types = 'sss';
}

// 4. CONFIGURACIÓN DE PAGINACIÓN
$registros_por_pagina = 10;
$pagina_actual = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// 5. CONTAR TOTAL DE PROFESORES (CON FILTROS)
$sql_total = "SELECT COUNT(p.id) FROM profesores p JOIN usuarios u ON p.usuario_id = u.id" . $where_sql;
$stmt_total = $mysqli->prepare($sql_total);
if (!empty($params)) {
    $stmt_total->bind_param($param_types, ...$params);
}
$stmt_total->execute();
$total_profesores = $stmt_total->get_result()->fetch_row()[0];
$total_paginas = ceil($total_profesores / $registros_por_pagina);
$stmt_total->close();

// 6. CONSULTA PRINCIPAL PARA OBTENER PROFESORES DE LA PÁGINA ACTUAL
$profesores = [];
$sql_profesores = "
    SELECT 
        p.id AS profesor_id, p.codigo_empleado, p.departamento, p.fecha_inicio, 
        p.foto_perfil, p.estatus,
        u.nombre, u.apellido, u.email
    FROM profesores p
    JOIN usuarios u ON p.usuario_id = u.id
    $where_sql
    ORDER BY u.apellido, u.nombre
    LIMIT ? OFFSET ?
";

$stmt_profesores = $mysqli->prepare($sql_profesores);
$params_paginacion = $params;
$params_paginacion[] = $registros_por_pagina;
$params_paginacion[] = $offset;
$param_types_paginacion = $param_types . 'ii';

if (!empty($params)) {
    $stmt_profesores->bind_param($param_types_paginacion, ...$params_paginacion);
} else {
    $stmt_profesores->bind_param('ii', $registros_por_pagina, $offset);
}
$stmt_profesores->execute();
$result_profesores = $stmt_profesores->get_result();

// Bucle para obtener cada profesor y sus materias
while ($profesor = $result_profesores->fetch_assoc()) {
    $materias_profesor = [];
    $stmt_materias_profesor = $mysqli->prepare("
        SELECT m.nombre FROM materias m
        JOIN profesor_materias pm ON m.id = pm.materia_id
        WHERE pm.profesor_id = ?
    ");
    $stmt_materias_profesor->bind_param('i', $profesor['profesor_id']);
    $stmt_materias_profesor->execute();
    $result_materias_profesor = $stmt_materias_profesor->get_result();
    while ($materia = $result_materias_profesor->fetch_assoc()) {
        $materias_profesor[] = $materia['nombre'];
    }
    $profesor['materias_asignadas'] = implode(', ', $materias_profesor); // Añadir las materias al array del profesor
    $profesores[] = $profesor; // Añadir el profesor completo a la lista final
    $stmt_materias_profesor->close();
}
$stmt_profesores->close();


// 7. OBTENER ESTADÍSTICAS
// Distribución por departamento
$distribucion_departamentos = [];
$total_general_profesores = $mysqli->query("SELECT COUNT(id) FROM profesores")->fetch_row()[0];
$result_dist_dept = $mysqli->query("SELECT departamento, COUNT(id) as total FROM profesores GROUP BY departamento");
while ($row = $result_dist_dept->fetch_assoc()) {
    $distribucion_departamentos[] = [
        'nombre' => $row['departamento'],
        'total' => $row['total'],
        'porcentaje' => ($total_general_profesores > 0) ? round(($row['total'] / $total_general_profesores) * 100, 1) : 0
    ];
}
$result_dist_dept->free();


// --- FIN: LÓGICA DE DATOS ---

include __DIR__ . '/side_bar_director.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- ... (head sin cambios) ... -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Profesores</title>
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
                    <h1 class="h2">Gestión de Profesores</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
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

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Filtros y Acciones</h5></div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="row align-items-end">
                                <div class="col-md-9 mb-3">
                                    <label for="search" class="form-label">Buscar Profesor</label>
                                    <input type="text" class="form-control" name="search" id="search" value="<?php echo htmlspecialchars($filtro_busqueda); ?>" placeholder="Nombre, apellido o departamento...">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="submit" class="btn btn-academic w-100"><i class="bi bi-filter"></i> Filtrar</button>
                                </div>
                            </div>
                        </form>
                        <div class="text-end mt-2">
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newProfessorModal">
                                <i class="bi bi-person-plus"></i> Nuevo Profesor
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Professors List -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Listado de Profesores (<?php echo $total_profesores; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Departamento</th>
                                        <th>Materias</th>
                                        <th>Años de Servicio</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>                                        
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($profesores)): ?>
                                        <tr><td colspan="5" class="text-center">No se encontraron profesores.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($profesores as $profesor): ?>
                                        <tr>
                                            <td data-id="<?php echo $profesor['profesor_id']; ?>">
                                                <div class="d-flex align-items-center">
                                                    <?php 
                                                    $foto_path = 'https://via.placeholder.com/40';
                                                    if (!empty($profesor['foto_perfil'])) {
                                                        $foto_path = (strpos($profesor['foto_perfil'], 'http') === 0)
                                                            ? $profesor['foto_perfil']
                                                            : '../uploads/profiles/' . $profesor['foto_perfil'];
                                                    }
                                                    ?>
                                                    <img src="<?php echo htmlspecialchars($foto_path); ?>" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($profesor['nombre'] . ' ' . $profesor['apellido']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($profesor['email']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($profesor['departamento']); ?></td>
                                            <td><small><?php echo htmlspecialchars($profesor['materias_asignadas'] ?: 'Ninguna asignada'); ?></small></td>
                                            <td>
                                                <?php
                                                $fecha_inicio = new DateTime($profesor['fecha_inicio']);
                                                $hoy = new DateTime();
                                                echo $hoy->diff($fecha_inicio)->y;
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($profesor['estatus'] === 'Activo'): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" title="Ver Perfil"><i class="bi bi-eye"></i></button>
                                                <button class="btn btn-sm btn-outline-secondary" title="Editar"><i class="bi bi-pencil"></i></button>
                                                <button class="btn btn-sm btn-outline-danger" title="Deshabilitar"><i class="bi bi-trash"></i></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- Paginación -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <?php $query_params = !empty($filtro_busqueda) ? ['search' => $filtro_busqueda] : []; ?>
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

                <!-- Department Statistics -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Estadísticas por Departamento</h5></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-striped">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Departamento</th>
                                        <th>N° Profesores</th>
                                        <th>Porcentaje</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($distribucion_departamentos)): ?>
                                    <tr><td colspan="3" class="text-center">No hay datos para mostrar.</td></tr>
                                    <?php else: ?>
                                    <?php foreach ($distribucion_departamentos as $depto): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($depto['nombre']); ?></td>
                                        <td><?php echo $depto['total']; ?></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" style="width: <?php echo $depto['porcentaje']; ?>%;" aria-valuenow="<?php echo $depto['porcentaje']; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo $depto['porcentaje']; ?>%</div>
                                            </div>
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

    <!-- New Professor Modal (Restaurado y completo) -->
    <div class="modal fade" id="newProfessorModal" tabindex="-1" aria-labelledby="newProfessorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="newProfessorModalLabel">Registrar Nuevo Profesor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formNuevoProfesor" action="./crear_profesor.php" method="POST" enctype="multipart/form-data">
                        <!-- ... (contenido completo del formulario del modal, como en la respuesta anterior) ... -->
                        <h6 class="text-primary">Información de Usuario</h6><hr class="mt-0">
                        <div class="row mb-3"><div class="col-md-4"><label for="prof_nombre" class="form-label">Nombres</label><input type="text" class="form-control" id="prof_nombre" name="nombre" required></div><div class="col-md-4"><label for="prof_apellido" class="form-label">Apellidos</label><input type="text" class="form-control" id="prof_apellido" name="apellido" required></div><div class="col-md-4"><label for="prof_email" class="form-label">Correo Electrónico</label><input type="email" class="form-control" id="prof_email" name="email" required></div></div>
                        <h6 class="text-primary mt-4">Información Personal</h6><hr class="mt-0">
                        <div class="row mb-3"><div class="col-md-4"><label for="prof_cedula" class="form-label">Cédula</label><input type="text" class="form-control" id="prof_cedula" name="cedula" required></div><div class="col-md-4"><label for="prof_fecha_nacimiento" class="form-label">Fecha de Nacimiento</label><input type="date" class="form-control" id="prof_fecha_nacimiento" name="fecha_nacimiento" required></div><div class="col-md-4"><label for="prof_telefono" class="form-label">Teléfono</label><input type="tel" class="form-control" id="prof_telefono" name="telefono"></div></div>
                        <div class="mb-3"><label for="prof_direccion" class="form-label">Dirección</label><input type="text" class="form-control" id="prof_direccion" name="direccion" required></div>
                        <h6 class="text-primary mt-4">Información Profesional</h6><hr class="mt-0">
                        <div class="row mb-3"><div class="col-md-6"><label for="prof_departamento" class="form-label">Departamento</label><input type="text" class="form-control" id="prof_departamento" name="departamento" required></div><div class="col-md-6"><label for="prof_cargo" class="form-label">Cargo</label><input type="text" class="form-control" id="prof_cargo" name="cargo" required></div></div>
                        <div class="row mb-3"><div class="col-md-6"><label for="prof_fecha_inicio" class="form-label">Fecha de Inicio</label><input type="date" class="form-control" id="prof_fecha_inicio" name="fecha_inicio" required></div><div class="col-md-6"><label for="prof_tipo_contrato" class="form-label">Tipo de Contrato</label><select class="form-select" id="prof_tipo_contrato" name="tipo_contrato" required><option selected disabled value="">Seleccionar...</option><option value="Tiempo Completo">Tiempo Completo</option><option value="Medio Tiempo">Medio Tiempo</option></select></div></div>
                        <div class="mb-3"><label for="subjects" class="form-label">Materias</label><select class="form-select" id="subjects" name="materias[]" multiple required><?php foreach ($materias_lista as $materia): ?><option value="<?php echo $materia['id']; ?>"><?php echo htmlspecialchars($materia['nombre']); ?></option><?php endforeach; ?></select><div class="form-text">Use Ctrl/Cmd para seleccionar varias.</div></div>
                        <div class="mb-3"><label for="prof_formacion_academica" class="form-label">Formación Académica</label><textarea class="form-control" id="prof_formacion_academica" name="formacion_academica" rows="3" required></textarea></div>
                        <h6 class="text-primary mt-4">Configuración</h6><hr class="mt-0">
                        <div class="row"><div class="col-md-6 mb-3"><label for="prof_foto_perfil" class="form-label">Foto de Perfil</label><input class="form-control" type="file" id="prof_foto_perfil" name="foto_perfil"></div><div class="col-md-6 mb-3 d-flex align-items-center pt-3"><div class="form-check"><input class="form-check-input" type="checkbox" id="sendCredentials" name="enviar_credenciales" value="1" checked><label class="form-check-label" for="sendCredentials">Enviar credenciales</label></div></div></div>
                        <div class="modal-footer mt-3"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-academic">Guardar Profesor</button></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    // --- Envío AJAX del formulario "Nuevo Profesor" ---
    document.getElementById('formNuevoProfesor').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        const data = new FormData(form);

        // Deshabilitar el botón mientras se guarda
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.textContent = "Guardando...";

        try {
            const response = await fetch(form.action, { method: 'POST', body: data });
            const result = await response.json();

            // Cerrar el modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newProfessorModal'));
            modal.hide();

            // Crear y mostrar mensaje flotante centrado
            const box = document.createElement('div');
            box.className = 'alert alert-info shadow position-fixed top-50 start-50 translate-middle text-center border border-primary';
            box.style.zIndex = 2000;
            box.style.minWidth = '420px';
            box.innerHTML = `
                <h5 class="mb-2">✅ Profesor registrado correctamente</h5>
                <p class="mb-1">Enlace de activación (entregar al profesor):</p>
                <a href="${result.activation_link}" target="_blank">${result.activation_link}</a>
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm" onclick="this.closest('.alert').remove(); location.reload();">Cerrar</button>
                </div>
            `;
            document.body.appendChild(box);
        } catch (err) {
            alert("Ocurrió un error al guardar el profesor. Revisa la consola.");
            console.error(err);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = "Guardar Profesor";
        }
    });
    /* ====== VER PROFESOR ====== */
    document.querySelectorAll('.btn-outline-primary').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.closest('tr').querySelector('td').dataset.id || btn.dataset.id;
        const res = await fetch(`ver_profesor.php?id=${id}`);
        const data = await res.json();

        if (data.error) return alert(data.error);

        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Perfil del Profesor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                <div class="col-md-4 text-center">
                    <img src="${data.foto_perfil ? '../uploads/profiles/'+data.foto_perfil : 'https://via.placeholder.com/150'}" class="rounded-circle img-thumbnail mb-3" width="120">
                    <h5>${data.nombre} ${data.apellido}</h5>
                    <p class="text-muted">${data.email}</p>
                </div>
                <div class="col-md-8">
                    <p><strong>Departamento:</strong> ${data.departamento}</p>
                    <p><strong>Cargo:</strong> ${data.cargo}</p>
                    <p><strong>Dirección:</strong> ${data.direccion}</p>
                    <p><strong>Teléfono:</strong> ${data.telefono || '—'}</p>
                    <p><strong>Formación Académica:</strong><br>${data.formacion_academica}</p>
                </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
            </div>
        </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        modal.addEventListener('hidden.bs.modal', () => modal.remove());
    });
    });

   /* ====== EDITAR PROFESOR ====== */
    document.querySelectorAll('.btn-outline-secondary').forEach(btn => {
    btn.addEventListener('click', async () => {
        const id = btn.closest('tr').querySelector('td').dataset.id;
        const res = await fetch(`ver_profesor.php?id=${id}`);
        const data = await res.json();

        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title">Editar Profesor</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="formEditarProfesor">
                <div class="modal-body">
                <input type="hidden" name="id" value="${id}">
                <div class="row">
                    <div class="col-md-6 mb-2">
                    <label class="form-label">Departamento</label>
                    <input type="text" name="departamento" class="form-control" value="${data.departamento}">
                    </div>
                    <div class="col-md-6 mb-2">
                    <label class="form-label">Cargo</label>
                    <input type="text" name="cargo" class="form-control" value="${data.cargo}">
                    </div>
                    <div class="col-md-6 mb-2">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="${data.telefono || ''}">
                    </div>
                    <div class="col-md-6 mb-2">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" class="form-control" value="${data.direccion}">
                    </div>
                    <div class="mb-3">
                    <label class="form-label">Formación Académica</label>
                    <textarea name="formacion_academica" class="form-control" rows="3">${data.formacion_academica}</textarea>
                    </div>
                    <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select name="estatus" class="form-select">
                        <option value="Activo" ${data.estatus === 'Activo' ? 'selected' : ''}>Activo</option>
                        <option value="Inactivo" ${data.estatus === 'Inactivo' ? 'selected' : ''}>Inactivo</option>
                    </select>
                    </div>
                </div>
                </div>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-academic">Guardar Cambios</button>
                </div>
            </form>
            </div>
        </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        modal.querySelector('#formEditarProfesor').addEventListener('submit', async e => {
        e.preventDefault();
        const form = e.target;
        const data = new FormData(form);
        const res = await fetch('editar_profesor.php', { method: 'POST', body: data });
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

    /* ====== DESHABILITAR PROFESOR ====== */
    document.querySelectorAll('.btn-outline-danger').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.closest('tr').querySelector('td').dataset.id;

        // Modal de confirmación
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirmar deshabilitación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <p>¿Estás seguro de que deseas <strong>deshabilitar</strong> este profesor?<br>
                Su cuenta quedará marcada como inactiva, pero no será eliminada.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-danger" id="confirmDisableBtn">Deshabilitar</button>
            </div>
            </div>
        </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();

        modal.querySelector('#confirmDisableBtn').addEventListener('click', async () => {
        const res = await fetch('deshabilitar_profesor.php', { method: 'POST', body: new URLSearchParams({ id }) });
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