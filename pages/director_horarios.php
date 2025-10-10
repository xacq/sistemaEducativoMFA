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
$dayFilter = isset($_GET['day']) ? $_GET['day'] : '';
$dias = ['monday' => 'Lunes', 'tuesday' => 'Martes', 'wednesday' => 'Miércoles', 'thursday' => 'Jueves', 'friday' => 'Viernes'];

function build_redirect(array $params): string
{
    $clean = [];
    if (!empty($params['grade'])) {
        $clean['grade'] = (int)$params['grade'];
    }
    if (!empty($params['course'])) {
        $clean['course'] = (int)$params['course'];
    }
    if (!empty($params['day']) && in_array($params['day'], ['monday','tuesday','wednesday','thursday','friday'], true)) {
        $clean['day'] = $params['day'];
    }
    return 'director_horarios.php' . ($clean ? '?' . http_build_query($clean) : '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $redirectUrl = build_redirect([
        'grade' => $_POST['grade_filter'] ?? null,
        'course' => $_POST['course_filter'] ?? null,
        'day' => $_POST['day_filter'] ?? null,
    ]);

    if ($action === 'create' || $action === 'update') {
        $cursoId = isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : 0;
        $dia = isset($_POST['dia']) ? $_POST['dia'] : '';
        $horaInicio = isset($_POST['hora_inicio']) ? $_POST['hora_inicio'] : '';
        $horaFin = isset($_POST['hora_fin']) ? $_POST['hora_fin'] : '';
        $aula = isset($_POST['aula']) ? trim($_POST['aula']) : '';
        $horarioId = isset($_POST['id']) ? (int)$_POST['id'] : null;

        if (!in_array($dia, array_keys($dias), true)) {
            flash_push('error', 'Seleccione un día válido.');
        } elseif ($cursoId <= 0) {
            flash_push('error', 'Seleccione un curso.');
        } elseif ($horaInicio === '' || $horaFin === '' || $aula === '') {
            flash_push('error', 'Todos los campos del horario son obligatorios.');
        } else {
            $errores = director_validate_horario($mysqli, $cursoId, $dia, $horaInicio, $horaFin, $aula, $action === 'update' ? $horarioId : null);
            if (!empty($errores)) {
                foreach ($errores as $error) {
                    flash_push('error', $error);
                }
            } else {
                if ($action === 'create') {
                    $stmt = $mysqli->prepare('INSERT INTO horarios (curso_id, dia, hora_inicio, hora_fin, aula) VALUES (?, ?, ?, ?, ?)');
                    if ($stmt) {
                        $stmt->bind_param('issss', $cursoId, $dia, $horaInicio, $horaFin, $aula);
                        if ($stmt->execute()) {
                            flash_push('success', 'Horario registrado correctamente.');
                        } else {
                            flash_push('error', 'No se pudo guardar el horario.');
                        }
                        $stmt->close();
                    } else {
                        flash_push('error', 'No se pudo preparar la inserción del horario.');
                    }
                } else {
                    if ($horarioId && $horarioId > 0) {
                        $stmt = $mysqli->prepare('UPDATE horarios SET curso_id = ?, dia = ?, hora_inicio = ?, hora_fin = ?, aula = ? WHERE id = ?');
                        if ($stmt) {
                            $stmt->bind_param('issssi', $cursoId, $dia, $horaInicio, $horaFin, $aula, $horarioId);
                            if ($stmt->execute()) {
                                flash_push('success', 'Horario actualizado correctamente.');
                            } else {
                                flash_push('error', 'No se pudo actualizar el horario.');
                            }
                            $stmt->close();
                        } else {
                            flash_push('error', 'No se pudo preparar la actualización del horario.');
                        }
                    } else {
                        flash_push('error', 'El identificador del horario no es válido.');
                    }
                }
            }
        }
        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            flash_push('error', 'El horario a eliminar no es válido.');
        } else {
            $stmt = $mysqli->prepare('DELETE FROM horarios WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $id);
                if ($stmt->execute()) {
                    flash_push('success', 'Horario eliminado correctamente.');
                } else {
                    flash_push('error', 'No se pudo eliminar el horario.');
                }
                $stmt->close();
            } else {
                flash_push('error', 'No se pudo preparar la eliminación del horario.');
            }
        }
        header('Location: ' . $redirectUrl);
        exit;
    }
}

$identity = director_get_identity($mysqli, (int)$_SESSION['user_id']);
$grados = director_get_grades($mysqli);
$cursos = director_get_courses($mysqli, $gradeFilter ?: null);
$todosLosCursos = director_get_courses($mysqli, null);

$params = [];
$sql = "SELECT h.id, h.dia, h.hora_inicio, h.hora_fin, h.aula, c.id AS curso_id, c.nombre AS curso_nombre, c.codigo, g.nombre AS grado_nombre,
               CONCAT(u.nombre, ' ', u.apellido) AS profesor
        FROM horarios h
        JOIN cursos c ON h.curso_id = c.id
        JOIN grados g ON c.grado_id = g.id
        JOIN profesores p ON c.profesor_id = p.id
        JOIN usuarios u ON p.usuario_id = u.id";
$where = [];
if ($gradeFilter) {
    $where[] = 'c.grado_id = ?';
    $params[] = $gradeFilter;
}
if ($courseFilter) {
    $where[] = 'c.id = ?';
    $params[] = $courseFilter;
}
if ($dayFilter && isset($dias[$dayFilter])) {
    $where[] = 'h.dia = ?';
    $params[] = $dayFilter;
}
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " ORDER BY g.id, c.nombre, FIELD(h.dia, 'monday','tuesday','wednesday','thursday','friday'), h.hora_inicio";

$stmt = $mysqli->prepare($sql);
$horarios = [];
if ($stmt) {
    if (!empty($params)) {
        $types = '';
        $bindParams = [];
        if ($gradeFilter) {
            $types .= 'i';
            $bindParams[] = $gradeFilter;
        }
        if ($courseFilter) {
            $types .= 'i';
            $bindParams[] = $courseFilter;
        }
        if ($dayFilter && isset($dias[$dayFilter])) {
            $types .= 's';
            $bindParams[] = $dayFilter;
        }
        if ($bindParams) {
            $stmt->bind_param($types, ...$bindParams);
        }
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $horarios[] = $row;
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
    <title>Gestión de Horarios</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Horarios académicos</h1>
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
                        <div class="col-md-4">
                            <label class="form-label" for="gradeFilter">Grado</label>
                            <select class="form-select" id="gradeFilter" name="grade">
                                <option value="">Todos</option>
                                <?php foreach ($grados as $grado): ?>
                                    <option value="<?php echo (int)$grado['id']; ?>" <?php echo $gradeFilter === (int)$grado['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($grado['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="courseFilter">Curso</label>
                            <select class="form-select" id="courseFilter" name="course">
                                <option value="">Todos</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?php echo (int)$curso['id']; ?>" <?php echo $courseFilter === (int)$curso['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($curso['nombre'] . ' (' . $curso['codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="dayFilter">Día</label>
                            <select class="form-select" id="dayFilter" name="day">
                                <option value="">Todos</option>
                                <?php foreach ($dias as $clave => $texto): ?>
                                    <option value="<?php echo $clave; ?>" <?php echo $dayFilter === $clave ? 'selected' : ''; ?>><?php echo $texto; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="submit" class="btn btn-academic w-100">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header card-header-academic text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Registrar horario</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="create">
                        <input type="hidden" name="grade_filter" value="<?php echo $gradeFilter; ?>">
                        <input type="hidden" name="course_filter" value="<?php echo $courseFilter; ?>">
                        <input type="hidden" name="day_filter" value="<?php echo htmlspecialchars($dayFilter, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="col-md-4">
                            <label class="form-label" for="cursoId">Curso</label>
                            <select class="form-select" id="cursoId" name="curso_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($cursos as $curso): ?>
                                    <option value="<?php echo (int)$curso['id']; ?>"><?php echo htmlspecialchars($curso['nombre'] . ' - ' . $curso['codigo'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="dia">Día</label>
                            <select class="form-select" id="dia" name="dia" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($dias as $clave => $texto): ?>
                                    <option value="<?php echo $clave; ?>"><?php echo $texto; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="horaInicio">Hora inicio</label>
                            <input type="time" class="form-control" id="horaInicio" name="hora_inicio" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="horaFin">Hora fin</label>
                            <input type="time" class="form-control" id="horaFin" name="hora_fin" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="aula">Aula</label>
                            <input type="text" class="form-control" id="aula" name="aula" maxlength="50" required>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Guardar horario</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header card-header-academic text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Horarios registrados</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-academic">
                            <tr>
                                <th>Curso</th>
                                <th>Grado</th>
                                <th>Profesor</th>
                                <th>Día</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                                <th>Aula</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($horarios)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No se encontraron horarios con los filtros aplicados.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($horarios as $horario): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($horario['curso_nombre'] . ' (' . $horario['codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($horario['grado_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($horario['profesor'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo $dias[$horario['dia']] ?? $horario['dia']; ?></td>
                                    <td><?php echo htmlspecialchars(substr($horario['hora_inicio'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(substr($horario['hora_fin'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($horario['aula'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#editar-<?php echo (int)$horario['id']; ?>" aria-expanded="false">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="post" class="d-inline" onsubmit="return confirm('¿Desea eliminar este horario?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int)$horario['id']; ?>">
                                            <input type="hidden" name="grade_filter" value="<?php echo $gradeFilter; ?>">
                                            <input type="hidden" name="course_filter" value="<?php echo $courseFilter; ?>">
                                            <input type="hidden" name="day_filter" value="<?php echo htmlspecialchars($dayFilter, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger ms-1">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <tr class="collapse" id="editar-<?php echo (int)$horario['id']; ?>">
                                    <td colspan="8">
                                        <form method="post" class="row g-3 border rounded p-3 bg-light">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?php echo (int)$horario['id']; ?>">
                                            <input type="hidden" name="grade_filter" value="<?php echo $gradeFilter; ?>">
                                            <input type="hidden" name="course_filter" value="<?php echo $courseFilter; ?>">
                                            <input type="hidden" name="day_filter" value="<?php echo htmlspecialchars($dayFilter, ENT_QUOTES, 'UTF-8'); ?>">
                                            <div class="col-md-4">
                                                <label class="form-label">Curso</label>
                                                <select class="form-select" name="curso_id" required>
                                                    <?php foreach ($todosLosCursos as $curso): ?>
                                                        <option value="<?php echo (int)$curso['id']; ?>" <?php echo (int)$curso['id'] === (int)$horario['curso_id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($curso['nombre'] . ' (' . $curso['codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Día</label>
                                                <select class="form-select" name="dia" required>
                                                    <?php foreach ($dias as $clave => $texto): ?>
                                                        <option value="<?php echo $clave; ?>" <?php echo $horario['dia'] === $clave ? 'selected' : ''; ?>><?php echo $texto; ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Hora inicio</label>
                                                <input type="time" class="form-control" name="hora_inicio" value="<?php echo htmlspecialchars(substr($horario['hora_inicio'], 0, 5), ENT_QUOTES, 'UTF-8'); ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Hora fin</label>
                                                <input type="time" class="form-control" name="hora_fin" value="<?php echo htmlspecialchars(substr($horario['hora_fin'], 0, 5), ENT_QUOTES, 'UTF-8'); ?>" required>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Aula</label>
                                                <input type="text" class="form-control" name="aula" maxlength="50" value="<?php echo htmlspecialchars($horario['aula'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                            </div>
                                            <div class="col-12 text-end">
                                                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Guardar cambios</button>
                                            </div>
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
