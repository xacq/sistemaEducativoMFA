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
$codigoEstudiante = $estudiante['codigo_estudiante'] ?? null;
$matriculas = $context['matriculas'];

$courseCount = count($matriculas);
$missingEnrollment = !$estudianteId || $courseCount === 0;

$attendancePercentage = student_calculate_attendance($mysqli, $estudianteId);
$averageGrade = student_calculate_average_grade($mysqli, $estudianteId);

$taskBuckets = student_fetch_tasks_with_status($mysqli, $estudianteId);
$allTasks = $taskBuckets['all'];
$pendingTasks = $taskBuckets['pending'];
$pendingTaskCount = count($pendingTasks);
$pendingTasksPreview = array_slice($pendingTasks, 0, 5);

$now = new DateTimeImmutable();
$todaySchedule = student_fetch_today_schedule($mysqli, $estudianteId, $now);

$notifications = [];

foreach ($pendingTasks as $task) {
    if (empty($task['fecha_entrega'])) {
        continue;
    }
    $dueDate = DateTimeImmutable::createFromFormat('Y-m-d', $task['fecha_entrega']);
    if ($dueDate && $dueDate >= $now && $dueDate <= $now->modify('+7 days')) {
        $notifications[] = [
            'timestamp' => (int) $dueDate->format('U'),
            'title' => 'Tarea pendiente',
            'message' => sprintf('%s (%s) vence el %s.', $task['titulo'], $task['curso_nombre'], $dueDate->format('d/m/Y')),
            'icon' => 'bi bi-clipboard-check',
            'badge' => 'bg-warning',
        ];
    }
}

$recentGrades = student_fetch_recent_grades($mysqli, $estudianteId);
foreach ($recentGrades as $grade) {
    $gradeDate = DateTimeImmutable::createFromFormat('Y-m-d', $grade['fecha']);
    $notifications[] = [
        'timestamp' => $gradeDate ? (int) $gradeDate->format('U') : time(),
        'title' => 'Nueva calificación',
        'message' => sprintf(
            'Obtuviste %.1f en %s (%s).',
            $grade['calificacion'],
            $grade['titulo'] ?: 'una evaluación',
            $grade['curso_nombre'] ?: 'Curso'
        ),
        'icon' => 'bi bi-award',
        'badge' => 'bg-primary',
    ];
}

usort($notifications, static function (array $a, array $b): int {
    return $b['timestamp'] <=> $a['timestamp'];
});
$notifications = array_slice($notifications, 0, 5);
$notificationBadge = count($notifications);

include __DIR__ . '/side_bar_estudiantes.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Dashboard Estudiante</title>
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
                    <div>
                        <h1 class="h2">Dashboard Estudiante</h1>
                        <?php if ($codigoEstudiante): ?>
                            <p class="text-muted mb-0">Código estudiante: <?php echo htmlspecialchars($codigoEstudiante, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-3">
                            <i class="bi bi-bell fs-4"></i>
                            <?php if ($notificationBadge > 0): ?>
                                <span class="notification-badge"><?php echo $notificationBadge; ?></span>
                            <?php endif; ?>
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
                        No encontramos una matrícula activa asociada a tu cuenta. Contacta a la secretaría académica para completar tu registro.
                    </div>
                <?php endif; ?>

                <!-- Información institucional -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Información Institucional</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <img src="../img/logo_escuela.png" alt="Logo U.E. Eduardo Avaroa" class="img-fluid mb-3" style="max-height: 120px;">
                            </div>
                            <div class="col-md-9">
                                <h4>Unidad Educativa Eduardo Avaroa III</h4>
                                <p class="text-muted">El Alto, La Paz - Bolivia</p>
                                <p><strong>Fundación:</strong> 1918 (106 años de trayectoria)</p>
                                <p><strong>Niveles:</strong> Primaria y Secundaria</p>
                                <p><strong>Aniversario:</strong> Marzo (Centésimo Sexto Aniversario celebrado en 2024)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Horario de hoy -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Horario de Hoy</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($todaySchedule)): ?>
                            <p class="text-muted mb-0">No tienes clases programadas para hoy.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                        <tr>
                                            <th>Hora</th>
                                            <th>Curso</th>
                                            <th>Profesor</th>
                                            <th>Aula</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($todaySchedule as $sesion): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(substr($sesion['hora_inicio'], 0, 5) . ' - ' . substr($sesion['hora_fin'], 0, 5), ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($sesion['curso_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($sesion['profesor'] ?? 'Por confirmar', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo htmlspecialchars($sesion['aula'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><span class="badge <?php echo $sesion['badge_class']; ?>"><?php echo htmlspecialchars($sesion['status'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Estadísticas principales -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">
                                <i class="bi bi-book-fill text-primary fs-1"></i>
                                <h5 class="card-title mt-3">Mis Cursos</h5>
                                <h2 class="card-text"><?php echo $courseCount; ?></h2>
                                <p class="card-text text-muted">Materias activas</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="estudiante_cursos.php" class="btn btn-sm btn-outline-primary">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center">
                                <i class="bi bi-clipboard-check text-success fs-1"></i>
                                <h5 class="card-title mt-3">Mi Asistencia</h5>
                                <h2 class="card-text"><?php echo $attendancePercentage !== null ? $attendancePercentage . '%' : '--'; ?></h2>
                                <p class="card-text text-muted">Promedio del período</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="estudiante_asistencia.php" class="btn btn-sm btn-outline-success">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-info">
                            <div class="card-body text-center">
                                <i class="bi bi-award text-info fs-1"></i>
                                <h5 class="card-title mt-3">Promedio General</h5>
                                <h2 class="card-text"><?php echo $averageGrade !== null ? $averageGrade : '--'; ?></h2>
                                <p class="card-text text-muted">Trimestre actual</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="estudiante_calificaciones.php" class="btn btn-sm btn-outline-info">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 border-warning">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-check text-warning fs-1"></i>
                                <h5 class="card-title mt-3">Tareas Pendientes</h5>
                                <h2 class="card-text"><?php echo $pendingTaskCount; ?></h2>
                                <p class="card-text text-muted">Por entregar</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <a href="estudiante_tareas.php" class="btn btn-sm btn-outline-warning">Ver Detalles</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tareas pendientes y notificaciones -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Tareas Pendientes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pendingTasksPreview)): ?>
                                    <p class="text-muted mb-0">¡No tienes tareas pendientes en este momento!</p>
                                <?php else: ?>
                                    <?php foreach ($pendingTasksPreview as $task): ?>
                                        <?php
                                        $dueDateObj = !empty($task['fecha_entrega']) ? DateTime::createFromFormat('Y-m-d', $task['fecha_entrega']) : null;
                                        $badgeClass = 'bg-info';
                                        $badgeText = 'Pendiente';
                                        if ($dueDateObj) {
                                            if ($dueDateObj < $now) {
                                                $badgeClass = 'bg-danger';
                                                $badgeText = 'Atrasada';
                                            } elseif ($dueDateObj->format('Y-m-d') === $now->format('Y-m-d')) {
                                                $badgeClass = 'bg-warning';
                                                $badgeText = 'Vence hoy';
                                            } elseif ($dueDateObj <= (clone $now)->modify('+2 days')) {
                                                $badgeClass = 'bg-warning';
                                                $badgeText = 'Próxima';
                                            }
                                        }
                                        ?>
                                        <div class="task-item d-flex align-items-center py-2 border-bottom">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($task['titulo'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($task['curso_nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                                    <?php if ($dueDateObj): ?>
                                                        | Vence: <?php echo $dueDateObj->format('d/m/Y'); ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                            <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="estudiante_tareas.php" class="btn btn-sm btn-outline-primary">Ver Todas las Tareas</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Notificaciones Recientes</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($notifications)): ?>
                                    <p class="text-muted mb-0">Sin notificaciones recientes.</p>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="notification-item d-flex py-2 border-bottom">
                                            <div class="notification-icon text-white rounded-circle me-3 <?php echo $notification['badge']; ?>">
                                                <i class="<?php echo htmlspecialchars($notification['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                                            </div>
                                            <div class="notification-content flex-grow-1">
                                                <div class="d-flex justify-content-between">
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($notification['title'], ENT_QUOTES, 'UTF-8'); ?></h6>
                                                    <small class="text-muted"><?php echo date('d/m/Y', (int) $notification['timestamp']); ?></small>
                                                </div>
                                                <p class="mb-0"><?php echo htmlspecialchars($notification['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Acciones rápidas -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <a class="btn btn-light p-3 w-100 h-100" href="estudiante_tareas.php">
                                    <i class="bi bi-file-earmark-check fs-3 d-block mb-2"></i>
                                    Entregar Tarea
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a class="btn btn-light p-3 w-100 h-100" href="estudiante_cursos.php">
                                    <i class="bi bi-calendar-week fs-3 d-block mb-2"></i>
                                    Ver Horario Completo
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
