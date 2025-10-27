<?php
session_start();

// Si no hay sesión activa, volvemos al login
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Conexión
require_once '../config.php';

// 1️⃣ Obtener el ID del estudiante asociado al usuario actual
$stmt = $mysqli->prepare("
    SELECT e.id
    FROM estudiantes e
    JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($estudianteId);
$stmt->fetch();
$stmt->close();

if (empty($estudianteId)) {
    die("<div class='alert alert-danger'>No se encontró el estudiante asociado a este usuario.</div>");
}

// 2️⃣ Consultar los cursos en los que el estudiante está matriculado
$sql = "
    SELECT c.id, c.codigo, c.nombre, c.descripcion, c.creditos, c.capacidad,
           m.nombre AS materia,
           u.nombre AS profesor_nombre, u.apellido AS profesor_apellido
    FROM matriculas ma
    JOIN cursos c ON ma.curso_id = c.id
    JOIN materias m ON c.materia_id = m.id
    JOIN profesores p ON c.profesor_id = p.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE ma.estudiante_id = ?
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $estudianteId);
$stmt->execute();
$result = $stmt->get_result();

// 3️⃣ Obtener nombre y apellido del usuario actual
$stmt = $mysqli->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($nombre, $apellido);
$stmt->fetch();
$stmt->close();

include __DIR__ . '/side_bar_estudiantes.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Cursos Estudiante</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Contenido principal -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Mis Cursos</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="position-relative me-3">
                        <i class="bi bi-bell fs-4"></i>
                        <span class="notification-badge">3</span>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                                id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8') ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <li><a class="dropdown-item" href="estudiante_perfil.php">Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="#">Configuración</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Selector de semestre -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="card-title mb-0">Semestre: 2025-1</h5>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary dropdown-toggle"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            Cambiar Semestre
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">Actual</a></li>
                                            <!--Por ahora no se muestran mas semetres no se puede escoger otro aun-->
                                            <!--<li><a class="dropdown-item" href="#">2024-2</a></li>
                                            <li><a class="dropdown-item" href="#">2024-1</a></li>
                                            <li><a class="dropdown-item" href="#">2023-2</a></li>-->
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tarjetas de cursos -->
            <div class="row mb-4">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($curso = $result->fetch_assoc()): ?>
                        <?php
                        $avance = rand(60, 100);
                        $color = $avance > 85 ? 'bg-success' : ($avance > 70 ? 'bg-warning' : 'bg-danger');
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card course-card h-100">
                                <div class="card-header card-header-academic">
                                    <h5 class="mb-0 text-white"><?= htmlspecialchars($curso['nombre']) ?></h5>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-subtitle mb-2 text-muted">
                                        Prof. <?= htmlspecialchars($curso['profesor_nombre'] . ' ' . $curso['profesor_apellido']) ?>
                                    </h6>
                                    <p class="card-text"><?= htmlspecialchars($curso['descripcion'] ?? 'Sin descripción disponible.') ?></p>

                                    <div class="progress mb-3">
                                        <div class="progress-bar <?= $color ?>" role="progressbar"
                                             style="width: <?= $avance ?>%;" aria-valuenow="<?= $avance ?>"
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?= $avance ?>% completado
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between mb-3">
                                        <span><i class="bi bi-award"></i> Créditos:
                                            <strong><?= $curso['creditos'] ?></strong></span>
                                        <span><i class="bi bi-people"></i> Capacidad:
                                            <strong><?= $curso['capacidad'] ?></strong></span>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button class="btn btn-academic"
                                                onclick="showCourseDetails(<?= $curso['id'] ?>)">
                                            Ver Detalles
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">No estás matriculado en ningún curso.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de detalles -->
<div class="modal fade" id="courseDetailsModal" tabindex="-1"
     aria-labelledby="courseDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-academic text-white">
                <h5 class="modal-title" id="courseDetailsModalLabel">Detalles del Curso</h5>
                <button type="button" class="btn-close btn-close-white"
                        data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-8">
                        <h4 id="courseTitle">Cargando...</h4>
                        <p class="text-muted" id="courseInstructor">Profesor: Cargando...</p>
                        <h5>Descripción del Curso</h5>
                        <p id="courseDescription">Cargando descripción...</p>
                    </div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Resumen</h5>
                            </div>
                            <div class="card-body">
                                <p><strong>Código:</strong> <span id="courseCode">-</span></p>
                                <p><strong>Créditos:</strong> <span id="courseCredits">-</span></p>
                                <p><strong>Capacidad:</strong> <span id="courseStudents">-</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary"
                        data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="../js/jquery-3.3.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>

<script>
    // 🔍 Cargar detalles del curso dinámicamente vía AJAX
    function showCourseDetails(courseId) {
        fetch('../ajax/ajax_detalle_curso.php?id=' + courseId)
            .then(response => response.json())
            .then(course => {
                document.getElementById('courseTitle').textContent = course.nombre;
                document.getElementById('courseInstructor').textContent =
                    'Profesor: ' + course.profesor_nombre + ' ' + course.profesor_apellido;
                document.getElementById('courseDescription').textContent =
                    course.descripcion || 'Sin descripción disponible.';
                document.getElementById('courseCode').textContent = course.codigo;
                document.getElementById('courseCredits').textContent = course.creditos;
                document.getElementById('courseStudents').textContent = course.capacidad;

                new bootstrap.Modal(document.getElementById('courseDetailsModal')).show();
            })
            .catch(err => {
                console.error(err);
                alert('Error al cargar los detalles del curso.');
            });
    }
</script>
</body>
</html>
