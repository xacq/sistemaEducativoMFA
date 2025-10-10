<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';
require_once __DIR__ . '/helpers/student_helpers.php';
require_once __DIR__ . '/helpers/flash.php';

$userId = (int)$_SESSION['user_id'];
$context = student_fetch_context($mysqli, $userId);

if (empty($context['student']['id'])) {
    flash_push('error', 'No se encontró información del estudiante.');
}

$courseFilter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$materials = [];
if (!empty($context['student']['id'])) {
    $sql = "SELECT m.id, m.titulo, m.descripcion, m.tipo, m.unidad, m.url, m.file_path, m.fecha_subida,
                   c.nombre AS curso_nombre, c.codigo AS curso_codigo,
                   CONCAT(u_prof.nombre, ' ', u_prof.apellido) AS profesor
            FROM materiales m
            JOIN cursos c ON m.curso_id = c.id
            JOIN profesores p ON m.profesor_id = p.id
            JOIN usuarios u_prof ON p.usuario_id = u_prof.id
            JOIN matriculas mat ON mat.curso_id = c.id AND mat.estudiante_id = ?
            WHERE m.share_with_students = 1";
    $params = [$context['student']['id']];
    $types = 'i';
    if ($courseFilter) {
        $sql .= ' AND c.id = ?';
        $params[] = $courseFilter;
        $types .= 'i';
    }
    $sql .= ' ORDER BY m.fecha_subida DESC';
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $materials[] = $row;
        }
        $stmt->close();
    }
}

$messages = flash_consume();
include __DIR__ . '/side_bar_estudiantes.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Materiales disponibles</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Materiales de mis cursos</h1>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars(($context['user']['nombre'] ?? '') . ' ' . ($context['user']['apellido'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="estudiante_perfil.php">Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="estudiante_configuracion.php">Configuración</a></li>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-collection me-2"></i>Materiales compartidos</h5>
                        <form method="get" class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0" for="courseFilter">Curso:</label>
                            <select class="form-select" id="courseFilter" name="course" onchange="this.form.submit()">
                                <option value="">Todos</option>
                                <?php foreach ($context['matriculas'] as $matricula): ?>
                                    <option value="<?php echo (int)$matricula['curso_id']; ?>" <?php echo $courseFilter === (int)$matricula['curso_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($matricula['curso_nombre'] . ' (' . $matricula['curso_codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-academic">
                            <tr>
                                <th>Material</th>
                                <th>Curso</th>
                                <th>Unidad</th>
                                <th>Tipo</th>
                                <th>Docente</th>
                                <th>Fecha</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($materials)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No hay materiales disponibles para los filtros seleccionados.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($material['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                        <span class="text-muted small"><?php echo htmlspecialchars($material['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($material['curso_nombre'] . ' (' . $material['curso_codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($material['unidad'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($material['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($material['profesor'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($material['fecha_subida'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <?php if ($material['file_path']): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="../<?php echo htmlspecialchars($material['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                                <i class="bi bi-download"></i> Descargar
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($material['url']): ?>
                                            <a class="btn btn-sm btn-outline-secondary" href="<?php echo htmlspecialchars($material['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                                <i class="bi bi-box-arrow-up-right"></i> Abrir enlace
                                            </a>
                                        <?php endif; ?>
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
