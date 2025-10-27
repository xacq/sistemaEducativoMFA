<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';
require_once __DIR__ . '/helpers/flash.php';

$userId = (int)$_SESSION['user_id'];

$stmt = $mysqli->prepare('SELECT p.id, u.nombre, u.apellido FROM profesores p JOIN usuarios u ON p.usuario_id = u.id WHERE u.id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($profesorId, $nombre, $apellido);
if (!$stmt->fetch()) {
    $stmt->close();
    flash_push('error', 'No se encontró el perfil del profesor.');
    $profesorId = null;
} else {
    $stmt->close();
}

if ($profesorId === null) {
    include __DIR__ . '/side_bar_profesor.php';
    $messages = flash_consume();
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Materiales del profesor</title>
        <link href="../css/bootstrap.min.css" rel="stylesheet">
        <link href="../css/academic.css" rel="stylesheet" type="text/css">
    </head>
    <body>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                    <?php foreach ($messages['error'] as $message): ?>
                        <div class="alert alert-danger mt-4"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$courses = [];
$stmtCursos = $mysqli->prepare('SELECT id, nombre, codigo FROM cursos WHERE profesor_id = ? ORDER BY nombre');
// ===== Tipos disponibles (ENUM fijos) =====
$tipos = ['Documento', 'Presentación', 'Video', 'Enlace', 'Otro'];




$stmtCursos->bind_param('i', $profesorId);
$stmtCursos->execute();
$resultCursos = $stmtCursos->get_result();
while ($row = $resultCursos->fetch_assoc()) {
    $courses[] = $row;
}
$stmtCursos->close();

$courseFilter = isset($_GET['course']) ? (int)$_GET['course'] : 0;
$tipoFilter = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$unidadFilter = isset($_GET['unidad']) ? trim($_GET['unidad']) : '';

$materials = [];
$sqlMateriales = "SELECT m.id, m.titulo, m.descripcion, m.tipo, m.unidad, m.url, m.file_path, m.share_with_students, m.notify_students, m.fecha_subida,
                         c.nombre AS curso_nombre, c.codigo
                  FROM materiales m
                  JOIN cursos c ON m.curso_id = c.id
                  WHERE m.profesor_id = ?";
$params = [$profesorId];
$types = 'i';
if ($courseFilter) {
    $sqlMateriales .= ' AND m.curso_id = ?';
    $params[] = $courseFilter;
    $types .= 'i';
}
if ($tipoFilter !== '') {
    $sqlMateriales .= ' AND m.tipo = ?';
    $params[] = $tipoFilter;
    $types .= 's';
}
if ($unidadFilter !== '') {
    $sqlMateriales .= ' AND m.unidad = ?';
    $params[] = $unidadFilter;
    $types .= 's';
}
$sqlMateriales .= ' ORDER BY m.fecha_subida DESC';


$stmtMateriales = $mysqli->prepare($sqlMateriales);
$stmtMateriales->bind_param($types, ...$params);
$stmtMateriales->execute();
$resultMateriales = $stmtMateriales->get_result();
while ($row = $resultMateriales->fetch_assoc()) {
    $materials[] = $row;
}
$stmtMateriales->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'upload') {
        $cursoId = isset($_POST['curso_id']) ? (int)$_POST['curso_id'] : 0;
        $titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
        $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
        $tipo = $_POST['tipo'] ?? 'Documento';
        $unidad = isset($_POST['unidad']) ? trim($_POST['unidad']) : '';
        $url = isset($_POST['url']) ? trim($_POST['url']) : '';
        $share = isset($_POST['share']) ? 1 : 0;
        $notify = isset($_POST['notify']) ? 1 : 0;

        $redirect = 'profesor_materiales.php' . ($courseFilter ? '?course=' . $courseFilter : '');

        if ($cursoId <= 0) {
            flash_push('error', 'Seleccione un curso válido.');
            header('Location: ' . $redirect);
            exit;
        }

        $validaCurso = $mysqli->prepare('SELECT COUNT(*) FROM cursos WHERE id = ? AND profesor_id = ?');
        $validaCurso->bind_param('ii', $cursoId, $profesorId);
        $validaCurso->execute();
        $validaCurso->bind_result($cursoValido);
        $validaCurso->fetch();
        $validaCurso->close();
        if ($cursoValido === 0) {
            flash_push('error', 'No puede cargar materiales para un curso que no le pertenece.');
            header('Location: ' . $redirect);
            exit;
        }

        if ($titulo === '' || $unidad === '') {
            flash_push('error', 'El título y la unidad son obligatorios.');
            header('Location: ' . $redirect);
            exit;
        }

        $storedFile = null;
        if (!empty($_FILES['archivo']['name'])) {
            $file = $_FILES['archivo'];
            if ($file['error'] !== UPLOAD_ERR_OK) {
                flash_push('error', 'Ocurrió un error al subir el archivo.');
                header('Location: ' . $redirect);
                exit;
            }
            if ($file['size'] > 10 * 1024 * 1024) {
                flash_push('error', 'El archivo supera el tamaño máximo de 10MB.');
                header('Location: ' . $redirect);
                exit;
            }
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $baseName);
            $finalName = $safeName . '_' . time() . ($extension ? '.' . $extension : '');
            $targetDir = dirname(__DIR__) . '/uploads/materiales/';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }
            $targetPath = $targetDir . $finalName;
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                flash_push('error', 'No se pudo guardar el archivo en el servidor.');
                header('Location: ' . $redirect);
                exit;
            }
            $storedFile = 'uploads/materiales/' . $finalName;
        }

        if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
            flash_push('error', 'Ingrese una URL válida.');
            if ($storedFile && file_exists(dirname(__DIR__) . '/' . $storedFile)) {
                unlink(dirname(__DIR__) . '/' . $storedFile);
            }
            header('Location: ' . $redirect);
            exit;
        }

        if ($storedFile === null && $url === '') {
            flash_push('error', 'Debe proporcionar un archivo o un enlace para el material.');
            header('Location: ' . $redirect);
            exit;
        }

        $url_final = $url !== '' ? $url : null;
        $insert = $mysqli->prepare('INSERT INTO materiales (curso_id, profesor_id, titulo, descripcion, tipo, unidad, url, file_path, share_with_students, notify_students) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if ($insert) {
            $insert->bind_param('iissssssii', $cursoId, $profesorId, $titulo, $descripcion, $tipo, $unidad, $url_final, $storedFile, $share, $notify);

            if ($insert->execute()) {
                flash_push('success', 'Material cargado correctamente.');
            } else {
                flash_push('error', 'No se pudo registrar el material.');
                if ($storedFile && file_exists(dirname(__DIR__) . '/' . $storedFile)) {
                    unlink(dirname(__DIR__) . '/' . $storedFile);
                }
            }
            $insert->close();
        } else {
            flash_push('error', 'No se pudo preparar el registro del material.');
            if ($storedFile && file_exists(dirname(__DIR__) . '/' . $storedFile)) {
                unlink(dirname(__DIR__) . '/' . $storedFile);
            }
        }

        header('Location: ' . $redirect);
        exit;
    }

    if ($action === 'delete') {
        $materialId = isset($_POST['material_id']) ? (int)$_POST['material_id'] : 0;
        $redirect = 'profesor_materiales.php' . ($courseFilter ? '?course=' . $courseFilter : '');
        $stmtMat = $mysqli->prepare('SELECT file_path FROM materiales WHERE id = ? AND profesor_id = ?');
        $stmtMat->bind_param('ii', $materialId, $profesorId);
        $stmtMat->execute();
        $stmtMat->bind_result($filePath);
        if ($stmtMat->fetch()) {
            $stmtMat->close();
            $delete = $mysqli->prepare('DELETE FROM materiales WHERE id = ?');
            $delete->bind_param('i', $materialId);
            if ($delete->execute()) {
                flash_push('success', 'Material eliminado correctamente.');
                if ($filePath && file_exists(dirname(__DIR__) . '/' . $filePath)) {
                    unlink(dirname(__DIR__) . '/' . $filePath);
                }
            } else {
                flash_push('error', 'No se pudo eliminar el material.');
            }
            $delete->close();
        } else {
            $stmtMat->close();
            flash_push('error', 'Material no encontrado.');
        }
        header('Location: ' . $redirect);
        exit;
    }

    if ($action === 'toggle_share') {
        $materialId = isset($_POST['material_id']) ? (int)$_POST['material_id'] : 0;
        $redirect = 'profesor_materiales.php' . ($courseFilter ? '?course=' . $courseFilter : '');
        $stmtToggle = $mysqli->prepare('UPDATE materiales SET share_with_students = 1 - share_with_students WHERE id = ? AND profesor_id = ?');
        $stmtToggle->bind_param('ii', $materialId, $profesorId);
        if ($stmtToggle->execute() && $stmtToggle->affected_rows > 0) {
            flash_push('success', 'Preferencia de visibilidad actualizada.');
        } else {
            flash_push('error', 'No se pudo actualizar la visibilidad del material.');
        }
        $stmtToggle->close();
        header('Location: ' . $redirect);
        exit;
    }
}

$messages = flash_consume();
include __DIR__ . '/side_bar_profesor.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Materiales educativos</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Materiales de clase</h1>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="profesor_perfil.php">Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="profesor_configuracion.php">Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                    </ul>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            
            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Materiales Educativos</h1>
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
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

<!-- ================== Filtros ================== -->
<div class="row mb-4">
    <form id="filterForm" class="row gx-2">
        <!-- Curso -->
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text">Curso</span>
                <select class="form-select" id="courseSelect" name="curso">
                    <option value="">Todos los cursos</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo (int)$course['id']; ?>"
                            <?php echo ($courseFilter === (int)$course['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['nombre'] . ' (' . $course['codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Tipo -->
        <div class="col-md-4">
            <div class="input-group">
                <span class="input-group-text">Tipo</span>
                <select class="form-select" id="tipoSelect" name="tipo">
                    <option value="">Todos</option>
                    <?php foreach ($tipos as $t): ?>
                        <option value="<?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>"
                            <?php echo ($tipoFilter === $t) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($t, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Búsqueda -->
        <div class="col-md-4">
            <div class="input-group">
                <input type="text" class="form-control" name="busqueda" id="busquedaInput"
                       placeholder="Buscar material...">
                <button class="btn btn-academic" type="button" id="buscarBtn">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </div>
    </form>
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
                    <h5 class="mb-0"><i class="bi bi-upload me-2"></i>Subir nuevo material</h5>
                </div>
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data" class="row g-3">
                        <input type="hidden" name="action" value="upload">
                        <div class="col-md-4">
                            <label class="form-label" for="cursoId">Curso</label>
                            <select class="form-select" id="cursoId" name="curso_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo (int)$course['id']; ?>" <?php echo $courseFilter === (int)$course['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($course['nombre'] . ' (' . $course['codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="titulo">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" maxlength="150" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="unidad">Unidad</label>
                            <input type="text" class="form-control" id="unidad" name="unidad" maxlength="100" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="tipo">Tipo</label>
                            <select class="form-select" id="tipo" name="tipo">
                                <?php foreach (['Documento','Presentación','Video','Enlace','Otro'] as $tipoItem): ?>
                                    <option value="<?php echo $tipoItem; ?>"><?php echo $tipoItem; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="archivo">Archivo (máx. 10MB)</label>
                            <input type="file" class="form-control" id="archivo" name="archivo">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="url">Enlace externo</label>
                            <input type="url" class="form-control" id="url" name="url" placeholder="https://...">
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="form-check me-4">
                                <input class="form-check-input" type="checkbox" id="share" name="share" checked>
                                <label class="form-check-label" for="share">Compartir con estudiantes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notify" name="notify">
                                <label class="form-check-label" for="notify">Notificar estudiantes</label>
                            </div>
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-success"><i class="bi bi-cloud-upload me-1"></i>Guardar material</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header card-header-academic text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-archive me-2"></i>Mis materiales</h5>
                        <form method="get" class="d-flex align-items-center gap-2">
                            <label class="form-label mb-0" for="filterCourse">Curso:</label>
                            <select class="form-select" id="filterCourse" name="course" onchange="this.form.submit()">
                                <option value="">Todos</option>
                                <?php foreach ($courses as $course): ?>
                                    <option value="<?php echo (int)$course['id']; ?>" <?php echo $courseFilter === (int)$course['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($course['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
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
                                <th>Título</th>
                                <th>Curso</th>
                                <th>Tipo</th>
                                <th>Unidad</th>
                                <th>Compartido</th>
                                <th>Fecha</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($materials)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No tiene materiales registrados.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($materials as $material): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($material['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($material['curso_nombre'] . ' (' . $material['codigo'] . ')', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($material['tipo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($material['unidad'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if ((int)$material['share_with_students'] === 1): ?>
                                            <span class="badge bg-success">Visible</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Oculto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($material['fecha_subida'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <?php if ($material['file_path']): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="../<?php echo htmlspecialchars($material['file_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Descargar archivo">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        <?php elseif ($material['url']): ?>
                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo htmlspecialchars($material['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" title="Abrir enlace">
                                                <i class="bi bi-box-arrow-up-right"></i>
                                            </a>
                                        <?php endif; ?>

                                        <button class="btn btn-sm btn-outline-info btn-ver ms-1" data-id="<?php echo (int)$material['id']; ?>" title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-secondary btn-toggle ms-1" data-id="<?php echo (int)$material['id']; ?>" title="Cambiar visibilidad">
                                            <i class="bi bi-eye<?php echo (int)$material['share_with_students'] ? '' : '-slash'; ?>"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-delete ms-1" data-id="<?php echo (int)$material['id']; ?>" title="Eliminar material">
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

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {

    // Ver detalles del material
    $('.btn-ver').on('click', function() {
        const id = $(this).data('id');
        $.post('../ajax/ajax_materiales.php', { action: 'get', id }, function(data) {
            if (data && !data.error) {
                Swal.fire({
                    title: data.titulo,
                    html: `
                        <p><strong>Curso:</strong> ${data.curso_nombre}</p>
                        <p><strong>Tipo:</strong> ${data.tipo}</p>
                        <p><strong>Unidad:</strong> ${data.unidad}</p>
                        <p><strong>Descripción:</strong> ${data.descripcion || '(Sin descripción)'}</p>
                        ${data.file_path ? `<a href="../${data.file_path}" class="btn btn-academic" target="_blank">📄 Ver archivo</a>` : ''}
                        ${data.url ? `<a href="${data.url}" class="btn btn-outline-info ms-2" target="_blank">🌐 Abrir enlace</a>` : ''}
                    `,
                    confirmButtonText: 'Cerrar',
                    confirmButtonColor: '#3c8dbc'
                });
            } else {
                Swal.fire('Error', data.error || 'No se pudo cargar el material.', 'error');
            }
        }, 'json');
    });

    // Alternar visibilidad
    $('.btn-toggle').on('click', function() {
        const id = $(this).data('id');
        $.post('../ajax/ajax_materiales.php', { action: 'toggle', id }, function(resp) {
            Swal.fire({
                icon: resp.success ? 'success' : 'error',
                title: resp.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        }, 'json');
    });

    // Eliminar material
    $('.btn-delete').on('click', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: '¿Eliminar material?',
            text: 'Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('../ajax/ajax_materiales.php', { action: 'delete', id }, function(resp) {
                    Swal.fire({
                        icon: resp.success ? 'success' : 'error',
                        title: resp.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => location.reload());
                }, 'json');
            }
        });
    });
    // Escucha cambios en los filtros
    $('#filterForm select, #buscarBtn').on('change click', function () {
        const data = {
            action: 'filter',
            curso: $('#courseSelect').val(),
            tipo: $('#tipoSelect').val(),
            busqueda: $('#busquedaInput').val()
        };

        $.post('../ajax/ajax_materiales.php', data, function (resp) {
            if (resp.success) {
                $('.table tbody').html(resp.html);
            } else {
                Swal.fire('Error', resp.message || 'No se pudo aplicar el filtro.', 'error');
            }
        }, 'json');
    });

});
</script>

</body>
</html>
