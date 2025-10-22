<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';
require_once __DIR__ . '/helpers/director_helpers.php';

$gradeFilter = isset($_GET['grade']) ? (int)$_GET['grade'] : 0;
$courseFilter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$searchFilter = isset($_GET['search']) ? trim($_GET['search']) : '';

function redirect_with_filters(array $filters): void
{
    $params = [];
    if (!empty($filters['grade'])) {
        $params['grade'] = (int)$filters['grade'];
    }
    if (!empty($filters['course'])) {
        $params['course'] = (int)$filters['course'];
    }
    if (!empty($filters['search'])) {
        $params['search'] = trim($filters['search']);
    }
    $target = 'director_matriculas.php' . ($params ? '?' . http_build_query($params) : '');
    header('Location: ' . $target);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $filters = [
        'grade' => $_POST['grade_filter'] ?? null,
        'course' => $_POST['course_filter'] ?? null,
        'search' => $_POST['search_filter'] ?? null,
    ];

    if ($action === 'assign') {
        $estudianteId = isset($_POST['estudiante_id']) ? (int)$_POST['estudiante_id'] : 0;
        $cursoId = isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : 0;

        if ($estudianteId <= 0 || $cursoId <= 0) {
            flash_push('error', 'Seleccione un estudiante y un curso válidos.');
            redirect_with_filters($filters);
        }

        $stmtEst = $mysqli->prepare('SELECT grado_id FROM estudiantes WHERE id = ?');
        $stmtEst->bind_param('i', $estudianteId);
        $stmtEst->execute();
        $stmtEst->bind_result($gradoEstudiante);
        if (!$stmtEst->fetch()) {
            $stmtEst->close();
            flash_push('error', 'El estudiante seleccionado no existe.');
            redirect_with_filters($filters);
        }
        $stmtEst->close();

        $stmtCurso = $mysqli->prepare('SELECT grado_id, capacidad FROM cursos WHERE id = ?');
        $stmtCurso->bind_param('i', $cursoId);
        $stmtCurso->execute();
        $stmtCurso->bind_result($gradoCurso, $capacidadCurso);
        if (!$stmtCurso->fetch()) {
            $stmtCurso->close();
            flash_push('error', 'El curso seleccionado no existe.');
            redirect_with_filters($filters);
        }
        $stmtCurso->close();

        if ($gradoEstudiante !== $gradoCurso) {
            flash_push('error', 'El grado del estudiante no coincide con el grado del curso.');
            redirect_with_filters($filters);
        }

        $stmtExiste = $mysqli->prepare('SELECT id FROM matriculas WHERE estudiante_id = ? AND curso_id = ?');
        $stmtExiste->bind_param('ii', $estudianteId, $cursoId);
        $stmtExiste->execute();
        $stmtExiste->store_result();
        if ($stmtExiste->num_rows > 0) {
            $stmtExiste->close();
            flash_push('warning', 'El estudiante ya está matriculado en el curso seleccionado.');
            redirect_with_filters($filters);
        }
        $stmtExiste->close();

        $stmtCupo = $mysqli->prepare('SELECT COUNT(*) FROM matriculas WHERE curso_id = ?');
        $stmtCupo->bind_param('i', $cursoId);
        $stmtCupo->execute();
        $stmtCupo->bind_result($inscritos);
        $stmtCupo->fetch();
        $stmtCupo->close();
        if ($inscritos >= $capacidadCurso) {
            flash_push('error', 'El curso ya alcanzó su capacidad máxima.');
            redirect_with_filters($filters);
        }

        if ($mensajePrerequisito = director_check_prerequisitos($mysqli, $estudianteId, $cursoId)) {
            flash_push('error', $mensajePrerequisito);
            redirect_with_filters($filters);
        }

        $insert = $mysqli->prepare('INSERT INTO matriculas (estudiante_id, curso_id, fecha_matricula) VALUES (?, ?, CURDATE())');
        if ($insert) {
            $insert->bind_param('ii', $estudianteId, $cursoId);
            if ($insert->execute()) {
                flash_push('success', 'Matrícula creada correctamente.');
            } else {
                flash_push('error', 'No se pudo registrar la matrícula.');
            }
            $insert->close();
        } else {
            flash_push('error', 'No se pudo preparar la inserción de la matrícula.');
        }

        redirect_with_filters($filters);
    }
}

$identity = director_get_identity($mysqli, (int)$_SESSION['user_id']);
$grados = director_get_grades($mysqli);
$cursos = director_get_courses($mysqli, $gradeFilter ?: null);
$estudiantes = director_get_students($mysqli, $gradeFilter ?: null);

$sql = "SELECT m.id, m.fecha_matricula, m.estatus, e.codigo_estudiante, CONCAT(u.nombre, ' ', u.apellido) AS estudiante,
               g.nombre AS grado, c.nombre AS curso, c.codigo AS codigo_curso, c.capacidad,
               (SELECT COUNT(*) FROM matriculas mi WHERE mi.curso_id = c.id) AS inscritos
        FROM matriculas m
        JOIN estudiantes e ON m.estudiante_id = e.id
        JOIN usuarios u ON e.usuario_id = u.id
        JOIN cursos c ON m.curso_id = c.id
        JOIN grados g ON c.grado_id = g.id";
$where = [];
$params = [];
$types = '';
if ($gradeFilter) {
    $where[] = 'c.grado_id = ?';
    $params[] = $gradeFilter;
    $types .= 'i';
}
if ($courseFilter) {
    $where[] = 'c.id = ?';
    $params[] = $courseFilter;
    $types .= 'i';
}
if ($searchFilter !== '') {
    $where[] = "(u.nombre LIKE ? OR u.apellido LIKE ? OR e.codigo_estudiante LIKE ? OR c.nombre LIKE ?)";
    $term = "%{$searchFilter}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= 'ssss';
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY g.id, c.nombre, u.apellido, u.nombre';
$stmt = $mysqli->prepare($sql);
$matriculas = [];
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $matriculas[] = $row;
    }
    $stmt->close();
}

$messages = flash_consume();
include __DIR__ . '/side_bar_director.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Matrículas</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Matrículas</h1>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars(trim(($identity['nombre'] ?? '') . ' ' . ($identity['apellido'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="director_configuracion.php">Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>

            <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $type => $bootstrap): ?>
                <?php foreach ($messages[$type] as $message): ?>
                    <div class="alert alert-<?php echo $bootstrap; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="card mb-4">
                <div class="card-header card-header-academic text-white">
                    <h5 class="mb-0"><i class="bi bi-filter me-2"></i>Filtros</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" for="gradeFilter">Grado</label>
                            <select class="form-select" id="gradeFilter" name="grade">
                                <option value="">Todos</option>
                                <?php foreach ($grados as $grado): ?>
                                    <option value="<?php echo (int)$grado['id']; ?>" <?php echo $gradeFilter === (int)$grado['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($grado['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="courseFilter">Curso</label>
                            <select class="form-select" id="courseFilter" name="course">
                                <option value="">Todos</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?php echo (int)$curso['id']; ?>" <?php echo $courseFilter === (int)$curso['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($curso['nombre'] . ' (' . $curso['codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="searchFilter">Búsqueda</label>
                            <input type="text" class="form-control" id="searchFilter" name="search" value="<?php echo htmlspecialchars($searchFilter, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Nombre de estudiante, código o curso">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-academic w-100"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header card-header-academic text-white">
                    <h5 class="mb-0"><i class="bi bi-person-plus me-2"></i>Nueva matrícula</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                    <input type="hidden" name="action" value="assign">
                    <input type="hidden" name="grade_filter" value="<?php echo $gradeFilter; ?>">
                    <input type="hidden" name="course_filter" value="<?php echo $courseFilter; ?>">
                    <input type="hidden" name="search_filter" value="<?php echo htmlspecialchars($searchFilter, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="col-md-5 position-relative">
                        <label class="form-label" for="buscarEstudiante">Buscar estudiante</label>
                        <input type="text" class="form-control" id="buscarEstudiante" placeholder="Escriba nombre o apellido..." autocomplete="off">
                        <div id="sugerenciasEstudiantes" class="list-group position-absolute w-100 shadow-sm" style="z-index: 1050; max-height: 260px; overflow:auto;"></div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="gradoAsignacion">Grado</label>
                        <input type="text" class="form-control" id="gradoAsignacion" readonly>
                        <input type="hidden" name="grado_id" id="gradoId">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label" for="cursoSelect">Curso</label>
                        <select class="form-select" id="cursoSelect" name="curso_id" required>
                        <option value="">Seleccione...</option>
                        </select>
                    </div>

                    <input type="hidden" name="estudiante_id" id="estudianteId">

                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle me-1"></i></button>
                    </div>
                    </form>
                    <p class="text-muted small mt-2">Seleccione un estudiante para cargar su grado y los cursos disponibles.</p>
                </div>
            </div>


            <div class="card">
                <div class="card-header card-header-academic text-white">
                    <h5 class="mb-0"><i class="bi bi-people me-2"></i>Matrículas registradas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-academic">
                            <tr>
                                <th>Estudiante</th>
                                <th>Código</th>
                                <th>Curso</th>
                                <th>Grado</th>
                                <th>Cupo</th>
                                <th>Fecha</th>
                                <th>Estatus</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($matriculas)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No se encontraron matrículas con los filtros aplicados.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($matriculas as $matricula): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($matricula['estudiante'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($matricula['codigo_estudiante'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($matricula['curso'] . ' (' . $matricula['codigo_curso'] . ')', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($matricula['grado'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int)$matricula['inscritos'] . '/' . (int)$matricula['capacidad']; ?></td>
                                    <td><?php echo htmlspecialchars($matricula['fecha_matricula'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ($matricula['estatus'] === 'Activa'): ?>
                                            <span class="badge bg-success">Activa</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactiva</span>
                                        <?php endif; ?>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-danger btn-disable-matricula" 
                                                data-id="<?php echo (int)$matricula['id']; ?>" 
                                                title="Deshabilitar matrícula">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal de confirmación para deshabilitar matrícula -->
<div class="modal fade" id="confirmDisableMatriculaModal" tabindex="-1" aria-labelledby="confirmDisableMatriculaLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="confirmDisableMatriculaLabel">Deshabilitar matrícula</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body text-center">
        <p>¿Está seguro de que desea <strong>deshabilitar esta matrícula</strong>?<br>
        El registro permanecerá en el sistema, pero no se considerará activo.</p>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="confirmDisableMatriculaBtn" class="btn btn-danger">Deshabilitar</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // --- Búsqueda dinámica de estudiantes ---
    const inputBuscar = document.getElementById('buscarEstudiante');
    const listaSugerencias = document.getElementById('sugerenciasEstudiantes');
    const inputGrado = document.getElementById('gradoAsignacion');
    const inputGradoId = document.getElementById('gradoId');
    const inputEstudianteId = document.getElementById('estudianteId');
    const selectCurso = document.getElementById('cursoSelect');

    let abortCtrl = null;

    // --- Buscar estudiantes dinámicamente ---
    inputBuscar.addEventListener('input', async () => {
    const q = inputBuscar.value.trim();
    listaSugerencias.innerHTML = '';
    if (q.length < 2) return;

    // Cancela solicitud previa si el usuario sigue tecleando
    if (abortCtrl) abortCtrl.abort();
    abortCtrl = new AbortController();

    listaSugerencias.innerHTML = '<div class="list-group-item text-muted">Cargando...</div>';

    try {
        const res = await fetch(`buscar_estudiantes.php?q=${encodeURIComponent(q)}`, { signal: abortCtrl.signal });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const data = await res.json();

        if (!Array.isArray(data) || data.length === 0) {
        listaSugerencias.innerHTML = '<div class="list-group-item text-muted">Sin resultados</div>';
        return;
        }

        listaSugerencias.innerHTML = '';
        data.forEach(est => {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'list-group-item list-group-item-action';
        item.textContent = `${est.nombre_completo} (${est.codigo_estudiante}) - ${est.grado_nombre}`;
        item.dataset.id = est.estudiante_id;
        item.dataset.grado = est.grado_id;
        item.dataset.gradonombre = est.grado_nombre;
        listaSugerencias.appendChild(item);
        });
    } catch (err) {
        if (err.name !== 'AbortError') {
        console.error('Error buscando estudiantes:', err);
        listaSugerencias.innerHTML = '<div class="list-group-item text-danger">Error al buscar</div>';
        }
    }
    });

    // Cierra sugerencias si se hace clic fuera
    document.addEventListener('click', (e) => {
    if (!e.target.closest('#buscarEstudiante') && !e.target.closest('#sugerenciasEstudiantes')) {
        listaSugerencias.innerHTML = '';
    }
    });

    // --- Al seleccionar un estudiante ---
    listaSugerencias.addEventListener('click', async e => {
    const btn = e.target.closest('.list-group-item');
    if (!btn || !btn.dataset.id) return;

    const id = btn.dataset.id;
    const gradoId = btn.dataset.grado;
    const gradoNombre = btn.dataset.gradonombre;

    inputBuscar.value = btn.textContent.split('(')[0].trim();
    listaSugerencias.innerHTML = '';

    inputGrado.value = gradoNombre;
    inputGradoId.value = gradoId;
    inputEstudianteId.value = id;

    // Cargar cursos del grado
    selectCurso.innerHTML = '<option value="">Cargando cursos...</option>';
    try {
        const res = await fetch(`obtener_cursos_por_grado.php?grado_id=${encodeURIComponent(gradoId)}`);
        if (!res.ok) throw new Error('HTTP ' + res.status);
        const cursos = await res.json();

        selectCurso.innerHTML = '<option value="">Seleccione...</option>';
        if (Array.isArray(cursos) && cursos.length) {
        cursos.forEach(curso => {
            const opt = document.createElement('option');
            opt.value = curso.id;
            opt.textContent = `${curso.nombre} (${curso.codigo})`;
            selectCurso.appendChild(opt);
        });
        } else {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'No hay cursos activos en este grado';
        selectCurso.appendChild(opt);
        }
    } catch (err) {
        console.error('Error cargando cursos por grado:', err);
        selectCurso.innerHTML = '<option value="">Error al cargar cursos</option>';
    }
    });


        /* ========= Deshabilitar matrícula ========= */
    let matriculaAEliminar = null;

    document.querySelectorAll('.btn-disable-matricula').forEach(btn => {
    btn.addEventListener('click', () => {
        matriculaAEliminar = btn.dataset.id;
        const modal = new bootstrap.Modal(document.getElementById('confirmDisableMatriculaModal'));
        modal.show();
    });
    });

    document.getElementById('confirmDisableMatriculaBtn').addEventListener('click', async () => {
    if (!matriculaAEliminar) return;

    const formData = new FormData();
    formData.append('id', matriculaAEliminar);

    try {
        const res = await fetch('deshabilitar_matricula.php', { method: 'POST', body: formData });
        const data = await res.json();

        const modalEl = document.getElementById('confirmDisableMatriculaModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();

        const notif = document.createElement('div');
        notif.className = `alert ${data.success ? 'alert-success' : 'alert-warning'} shadow position-fixed top-0 start-50 translate-middle-x mt-3 text-center border`;
        notif.style.zIndex = 2000;
        notif.style.minWidth = '380px';
        notif.innerHTML = `
        <strong>${data.success ? '✅ Matrícula deshabilitada' : '⚠️ No se pudo deshabilitar'}</strong><br>
        <small>${data.message}</small>
        `;
        document.body.appendChild(notif);

        setTimeout(() => {
        notif.classList.add('fade');
        setTimeout(() => notif.remove(), 500);
        if (data.success) location.reload();
        }, 2500);

    } catch (err) {
        console.error(err);
        alert('Error inesperado al deshabilitar la matrícula.');
    }
    });

</script>

</body>
</html>
