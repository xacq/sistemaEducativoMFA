<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';
require_once __DIR__ . '/helpers/student_helpers.php';

$userId = (int) $_SESSION['user_id'];
$context = student_fetch_context($mysqli, $userId);
$nombre = $context['user']['nombre'];
$apellido = $context['user']['apellido'];
$estudiante = $context['student'];
$estudianteId = $estudiante['id'] ?? null;
$missingEnrollment = !$estudianteId || empty($context['matriculas']);

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taskId = isset($_POST['tarea_id']) ? (int) $_POST['tarea_id'] : 0;
    $comentario = trim($_POST['comentario'] ?? '');
    $errors = [];

    if (!$estudianteId) {
        $errors[] = 'No se pudo identificar al estudiante.';
    }

    if ($taskId <= 0) {
        $errors[] = 'La tarea seleccionada no es válida.';
    }

    $matriculaId = empty($errors) ? student_find_matricula_for_task($mysqli, (int) $estudianteId, $taskId) : null;
    if (empty($matriculaId) && empty($errors)) {
        $errors[] = 'No se encontró una matrícula para esta tarea.';
    }

    if (empty($errors)) {
        if (!isset($_FILES['task_file']) || $_FILES['task_file']['error'] === UPLOAD_ERR_NO_FILE) {
            $errors[] = 'Debes seleccionar un archivo para entregar.';
        } elseif ($_FILES['task_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'No se pudo cargar el archivo. Intenta nuevamente.';
        } else {
            $allowedExtensions = ['pdf', 'doc', 'docx', 'zip'];
            $fileInfo = pathinfo($_FILES['task_file']['name']);
            $extension = strtolower($fileInfo['extension'] ?? '');
            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'Formato de archivo no permitido. Usa PDF, DOC, DOCX o ZIP.';
            }
            $maxSize = 10 * 1024 * 1024; // 10MB
            if ($_FILES['task_file']['size'] > $maxSize) {
                $errors[] = 'El archivo supera el tamaño máximo permitido (10MB).';
            }
            if (empty($errors)) {
                $mimeMap = [
                    'pdf' => ['application/pdf'],
                    'doc' => ['application/msword', 'application/vnd.ms-office'],
                    'docx' => [
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/zip',
                        'application/octet-stream',
                    ],
                    'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
                ];
                $finfo = class_exists('finfo') ? new finfo(FILEINFO_MIME_TYPE) : null;
                $mimeType = $finfo ? $finfo->file($_FILES['task_file']['tmp_name']) : null;
                if ($mimeType && isset($mimeMap[$extension]) && !in_array($mimeType, $mimeMap[$extension], true)) {
                    $errors[] = 'El tipo de archivo subido no coincide con la extensión indicada.';
                }
            }
        }
    }

    if (empty($errors)) {
        $uploadsDir = __DIR__ . '/uploads/tareas';
        if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0775, true) && !is_dir($uploadsDir)) {
            $errors[] = 'No se pudo preparar la carpeta de cargas.';
        } else {
            $uniqueName = sprintf('task_%d_student_%d_%s.%s', $taskId, $estudianteId, uniqid('', true), $extension);
            $targetPath = $uploadsDir . '/' . $uniqueName;
            if (!move_uploaded_file($_FILES['task_file']['tmp_name'], $targetPath)) {
                $errors[] = 'No se pudo guardar el archivo cargado.';
            } else {
                $relativePath = 'uploads/tareas/' . $uniqueName;

                // Verificar si ya existe una entrega
                $existingId = null;
                $existingFile = null;
                if ($stmt = $mysqli->prepare('SELECT id, file_path FROM tarea_entregas WHERE tarea_id = ? AND matricula_id = ? LIMIT 1')) {
                    $stmt->bind_param('ii', $taskId, $matriculaId);
                    $stmt->execute();
                    $stmt->bind_result($existingId, $existingFile);
                    $stmt->fetch();
                    $stmt->close();
                }

                if ($existingId) {
                    // Eliminar archivo previo si existe
                    if ($existingFile) {
                        $existingAbsolute = realpath(__DIR__ . '/' . ltrim($existingFile, '/'));
                        $uploadsRoot = realpath(__DIR__ . '/uploads');
                        if ($existingAbsolute && $uploadsRoot && strpos($existingAbsolute, $uploadsRoot) === 0 && file_exists($existingAbsolute)) {
                            @unlink($existingAbsolute);
                        }
                    }
                    if ($stmt = $mysqli->prepare('UPDATE tarea_entregas SET comentario = ?, file_path = ?, fecha_envio = NOW() WHERE id = ?')) {
                        $stmt->bind_param('ssi', $comentario, $relativePath, $existingId);
                        $stmt->execute();
                        $stmt->close();
                    }
                } else {
                    if ($stmt = $mysqli->prepare('INSERT INTO tarea_entregas (tarea_id, matricula_id, comentario, file_path, fecha_envio) VALUES (?, ?, ?, ?, NOW())')) {
                        $stmt->bind_param('iiss', $taskId, $matriculaId, $comentario, $relativePath);
                        $stmt->execute();
                        $stmt->close();
                    }
                }

                $successMessage = 'Tu entrega se registró correctamente.';
            }
        }
    }

    if (!empty($errors)) {
        $errorMessage = implode(' ', $errors);
    }
}

$taskBuckets = student_fetch_tasks_with_status($mysqli, $estudianteId);
$tasks = $taskBuckets['all'];
$pendingTasks = $taskBuckets['pending'];
$submittedTasks = $taskBuckets['submitted'];
$gradedTasks = $taskBuckets['graded'];

include __DIR__ . '/side_bar_estudiantes.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Tareas Estudiante</title>
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
                    <h1 class="h2">Mis Tareas</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-3">
                            <i class="bi bi-bell fs-4"></i>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars(trim($nombre . ' ' . $apellido), ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="estudiante_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="estudiante_configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php if ($missingEnrollment): ?>
                    <div class="alert alert-warning" role="alert">
                        No encontramos una matrícula activa asociada a tu cuenta. No podrás enviar entregas hasta completar el proceso de inscripción.
                    </div>
                <?php endif; ?>

                <?php if ($successMessage): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if ($errorMessage): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <ul class="nav nav-tabs mb-4" id="taskTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending" type="button" role="tab" aria-controls="pending" aria-selected="true">Pendientes (<?php echo count($pendingTasks); ?>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="submitted-tab" data-bs-toggle="tab" data-bs-target="#submitted" type="button" role="tab" aria-controls="submitted" aria-selected="false">Entregadas (<?php echo count($submittedTasks); ?>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="graded-tab" data-bs-toggle="tab" data-bs-target="#graded" type="button" role="tab" aria-controls="graded" aria-selected="false">Calificadas (<?php echo count($gradedTasks); ?>)</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="false">Todas (<?php echo count($tasks); ?>)</button>
                    </li>
                </ul>

                <div class="tab-content" id="taskTabsContent">
                    <div class="tab-pane fade show active" id="pending" role="tabpanel" aria-labelledby="pending-tab">
                        <?php $tasksContext = $pendingTasks; include __DIR__ . '/partials/tabla_tareas.php'; ?>
                    </div>
                    <div class="tab-pane fade" id="submitted" role="tabpanel" aria-labelledby="submitted-tab">
                        <?php $tasksContext = $submittedTasks; include __DIR__ . '/partials/tabla_tareas.php'; ?>
                    </div>
                    <div class="tab-pane fade" id="graded" role="tabpanel" aria-labelledby="graded-tab">
                        <?php $tasksContext = $gradedTasks; include __DIR__ . '/partials/tabla_tareas.php'; ?>
                    </div>
                    <div class="tab-pane fade" id="all" role="tabpanel" aria-labelledby="all-tab">
                        <?php $tasksContext = $tasks; include __DIR__ . '/partials/tabla_tareas.php'; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de entrega -->
    <div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-academic text-white">
                    <h5 class="modal-title" id="taskModalLabel">Entregar Tarea</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="taskSubmissionForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="tarea_id" id="modalTaskId">
                        <div class="mb-3">
                            <label class="form-label">Tarea</label>
                            <input type="text" class="form-control" id="modalTaskTitle" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Curso</label>
                            <input type="text" class="form-control" id="modalTaskCourse" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" id="modalTaskDescription" rows="4" readonly></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="modalTaskComment">Comentario (opcional)</label>
                            <textarea class="form-control" name="comentario" id="modalTaskComment" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="modalTaskFile">Archivo</label>
                            <input class="form-control" type="file" name="task_file" id="modalTaskFile" required>
                            <div class="form-text">Formatos permitidos: PDF, DOC, DOCX, ZIP. Tamaño máximo: 10MB</div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" form="taskSubmissionForm" class="btn btn-academic">Entregar Tarea</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal detalle de calificación -->
    <div class="modal fade" id="gradeModal" tabindex="-1" aria-labelledby="gradeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header bg-success text-white">
            <h5 class="modal-title" id="gradeModalLabel">Detalle de calificación</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
            <div class="mb-2"><strong>Tarea:</strong> <span id="gmTitulo"></span></div>
            <div class="mb-2"><strong>Curso:</strong> <span id="gmCurso"></span></div>
            <div class="mb-2"><strong>Calificación:</strong> <span id="gmCalificacion"></span></div>
            <div class="mb-2"><strong>Profesor:</strong> <span id="gmProfesor"></span></div>
            <div class="mb-2"><strong>Fecha:</strong> <span id="gmFecha"></span></div>
            <div class="mb-2"><strong>Comentario:</strong><br><span id="gmComentario"></span></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
        </div>
    </div>
    </div>



    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
        function openTaskModal(button) {
            const data = button.dataset;
            document.getElementById('modalTaskId').value = data.taskId;
            document.getElementById('modalTaskTitle').value = data.taskTitle || '';
            document.getElementById('modalTaskCourse').value = data.taskCourse || '';
            document.getElementById('modalTaskDescription').value = data.taskDescription || '';
            document.getElementById('modalTaskComment').value = data.taskComment || '';
            document.getElementById('modalTaskFile').value = '';

            const modal = new bootstrap.Modal(document.getElementById('taskModal'));
            modal.show();
        }

    // Rellena el modal de calificación
    const gradeModal = document.getElementById('gradeModal');
    gradeModal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;
        document.getElementById('gmTitulo').textContent = btn.getAttribute('data-titulo') || '';
        document.getElementById('gmCurso').textContent = btn.getAttribute('data-curso') || '';
        document.getElementById('gmCalificacion').textContent = btn.getAttribute('data-calificacion') || '—';
        document.getElementById('gmProfesor').textContent = btn.getAttribute('data-profesor') || '—';
        document.getElementById('gmFecha').textContent = btn.getAttribute('data-fecha') || '—';
        document.getElementById('gmComentario').textContent = btn.getAttribute('data-comentario') || '—';
    });
    </script>
</body>
</html>
