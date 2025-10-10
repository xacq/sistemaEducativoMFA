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

    if ($action === 'remove') {
        $matriculaId = isset($_POST['matricula_id']) ? (int)$_POST['matricula_id'] : 0;
        if ($matriculaId <= 0) {
            flash_push('error', 'La matrícula seleccionada no es válida.');
        } else {
            $stmt = $mysqli->prepare('DELETE FROM matriculas WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $matriculaId);
                if ($stmt->execute()) {
                    flash_push('success', 'La matrícula se eliminó correctamente.');
                } else {
                    flash_push('error', 'No se pudo eliminar la matrícula.');
                }
                $stmt->close();
            } else {
                flash_push('error', 'No se pudo preparar la eliminación.');
            }
        }
        redirect_with_filters($filters);
    }
}

$identity = director_get_identity($mysqli, (int)$_SESSION['user_id']);
$grados = director_get_grades($mysqli);
$cursos = director_get_courses($mysqli, $gradeFilter ?: null);
$estudiantes = director_get_students($mysqli, $gradeFilter ?: null);

$sql = "SELECT m.id, m.fecha_matricula, e.codigo_estudiante, CONCAT(u.nombre, ' ', u.apellido) AS estudiante,
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
                        <div class="col-md-3">
                            <label class="form-label" for="gradoAsignacion">Grado</label>
                            <select class="form-select" id="gradoAsignacion" name="grado_id">
                                <option value="">Seleccione...</option>
                                <?php foreach ($grados as $grado): ?>
                                    <option value="<?php echo (int)$grado['id']; ?>" <?php echo $gradeFilter === (int)$grado['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($grado['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="estudianteSelect">Estudiante</label>
                            <select class="form-select" id="estudianteSelect" name="estudiante_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($estudiantes as $estudiante): ?>
                                    <option value="<?php echo (int)$estudiante['id']; ?>">
                                        <?php echo htmlspecialchars($estudiante['nombre_completo'] . ' - ' . $estudiante['codigo_estudiante'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="cursoSelect">Curso</label>
                            <select class="form-select" id="cursoSelect" name="curso_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?php echo (int)$curso['id']; ?>">
                                        <?php echo htmlspecialchars($curso['nombre'] . ' (' . $curso['codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle me-1"></i>Asignar</button>
                        </div>
                    </form>
                    <p class="text-muted small mt-2">Las opciones se limitan al grado seleccionado para evitar errores de asignación.</p>
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
                                    <td class="text-end">
                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Desea eliminar esta matrícula?');">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="matricula_id" value="<?php echo (int)$matricula['id']; ?>">
                                            <input type="hidden" name="grade_filter" value="<?php echo $gradeFilter; ?>">
                                            <input type="hidden" name="course_filter" value="<?php echo $courseFilter; ?>">
                                            <input type="hidden" name="search_filter" value="<?php echo htmlspecialchars($searchFilter, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
