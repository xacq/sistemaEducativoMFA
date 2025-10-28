<?php
session_start();

// Verificar sesión
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Conexión
require_once '../config.php';

// 1️⃣ Obtener ID del estudiante logueado
$stmt = $mysqli->prepare("
    SELECT e.id
    FROM estudiantes e
    JOIN usuarios u ON e.usuario_id = u.id
    WHERE u.id = ?
");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($estudianteId);
$stmt->fetch();
$stmt->close();

if (empty($estudianteId)) {
    die("<div class='alert alert-danger'>No se encontró el estudiante asociado.</div>");
}

// 2️⃣ Obtener nombre y apellido del usuario
$stmt = $mysqli->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($nombre, $apellido);
$stmt->fetch();
$stmt->close();

// 3️⃣ Resumen general de asistencia
$resumenSql = "
    SELECT 
        COUNT(*) AS total,
        SUM(estado = 'Presente') AS presentes,
        SUM(estado = 'Ausente') AS ausentes,
        SUM(estado = 'Tarde') AS tardes
    FROM asistencia a
    JOIN matriculas m ON a.matricula_id = m.id
    WHERE m.estudiante_id = ?
";
$stmt = $mysqli->prepare($resumenSql);
$stmt->bind_param('i', $estudianteId);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_assoc();
$stmt->close();

$total = (int)$resumen['total'];
$presentes = (int)$resumen['presentes'];
$ausentes = (int)$resumen['ausentes'];
$tardes = (int)$resumen['tardes'];
$porcentaje = $total > 0 ? round(($presentes / $total) * 100) : 0;

// 4️⃣ Asistencia agrupada por curso
$sql = "
    SELECT 
        c.nombre AS curso,
        CONCAT(u.nombre, ' ', u.apellido) AS profesor,
        COUNT(a.id) AS total_clases,
        SUM(a.estado = 'Presente') AS asistencias,
        SUM(a.estado = 'Tarde') AS justificadas,
        SUM(a.estado = 'Ausente') AS injustificadas
    FROM asistencia a
    JOIN matriculas m ON a.matricula_id = m.id
    JOIN cursos c ON m.curso_id = c.id
    JOIN profesores p ON c.profesor_id = p.id
    JOIN usuarios u ON p.usuario_id = u.id
    WHERE m.estudiante_id = ?
    GROUP BY c.id
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $estudianteId);
$stmt->execute();
$cursos = $stmt->get_result();
$stmt->close();

include __DIR__ . '/side_bar_estudiantes.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Asistencia Estudiante</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Mi Asistencia</h1>
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

            <!-- 🟩 Resumen general -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-header-academic">
                            <h5 class="mb-0 text-white">Resumen de Asistencia</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 text-center mb-3">
                                    <h6>Asistencia General</h6>
                                    <div class="display-4 fw-bold text-success"><?= $porcentaje ?>%</div>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <h6>Clases Asistidas</h6>
                                    <div class="display-4 fw-bold text-academic"><?= $presentes ?></div>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <h6>Faltas Justificadas</h6>
                                    <div class="display-4 fw-bold text-warning"><?= $tardes ?></div>
                                </div>
                                <div class="col-md-3 text-center mb-3">
                                    <h6>Faltas Injustificadas</h6>
                                    <div class="display-4 fw-bold text-danger"><?= $ausentes ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🟨 Asistencia por curso -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-header-academic">
                            <h5 class="mb-0 text-white">Asistencia por Curso</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                    <tr>
                                        <th>Curso</th>
                                        <th>Profesor</th>
                                        <th>Clases Totales</th>
                                        <th>Asistencias</th>
                                        <th>Faltas Justificadas</th>
                                        <th>Faltas Injustificadas</th>
                                        <th>Porcentaje</th>
                                        <th>Estado</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if ($cursos->num_rows > 0): ?>
                                        <?php while ($row = $cursos->fetch_assoc()): ?>
                                            <?php
                                            $porc = $row['total_clases'] > 0 ? round(($row['asistencias'] / $row['total_clases']) * 100) : 0;
                                            $estado = $porc >= 85 ? 'Aprobado' : ($porc >= 70 ? 'Atención' : 'Reprobado');
                                            $badge = $estado === 'Aprobado' ? 'bg-success' : ($estado === 'Atención' ? 'bg-warning' : 'bg-danger');
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['curso']) ?></td>
                                                <td><?= htmlspecialchars($row['profesor']) ?></td>
                                                <td><?= $row['total_clases'] ?></td>
                                                <td><?= $row['asistencias'] ?></td>
                                                <td><?= $row['justificadas'] ?></td>
                                                <td><?= $row['injustificadas'] ?></td>
                                                <td class="fw-bold text-<?= $porc >= 85 ? 'success' : ($porc >= 70 ? 'warning' : 'danger') ?>">
                                                    <?= $porc ?>%
                                                </td>
                                                <td><span class="badge <?= $badge ?>"><?= $estado ?></span></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr><td colspan="8" class="text-center text-muted">No hay registros de asistencia.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🟦 Detalle mensual (versión simplificada dinámica) -->
            <?php
            $mesActual = date('m');
            $anioActual = date('Y');

            // Obtener los días del mes actual
            $numDias = cal_days_in_month(CAL_GREGORIAN, $mesActual, $anioActual);

            // Cargar asistencia detallada por curso y día
            $detalleSql = "
                SELECT c.nombre AS curso, DAY(a.fecha) AS dia, a.estado
                FROM asistencia a
                JOIN matriculas m ON a.matricula_id = m.id
                JOIN cursos c ON m.curso_id = c.id
                WHERE m.estudiante_id = ? 
                  AND MONTH(a.fecha) = ? 
                  AND YEAR(a.fecha) = ?
            ";
            $stmt = $mysqli->prepare($detalleSql);
            $stmt->bind_param('iii', $estudianteId, $mesActual, $anioActual);
            $stmt->execute();
            $detalle = $stmt->get_result();
            $stmt->close();

            $asistenciaPorCurso = [];
            while ($row = $detalle->fetch_assoc()) {
                $asistenciaPorCurso[$row['curso']][$row['dia']] = $row['estado'];
            }
            ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-header-academic">
                            <h5 class="mb-0 text-white">Detalle de Asistencia - <?= date('F Y') ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead class="table-academic">
                                    <tr>
                                        <th>Curso</th>
                                        <?php for ($d = 1; $d <= min($numDias, 10); $d++): ?>
                                            <th><?= str_pad($d, 2, '0', STR_PAD_LEFT) ?>/<?= date('m') ?></th>
                                        <?php endfor; ?>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($asistenciaPorCurso as $curso => $dias): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($curso) ?></td>
                                            <?php for ($d = 1; $d <= min($numDias, 10); $d++): ?>
                                                <td class="text-center">
                                                    <?php
                                                    $estado = $dias[$d] ?? '';
                                                    if ($estado === 'Presente') echo '<i class="bi bi-check-circle-fill text-success"></i>';
                                                    elseif ($estado === 'Ausente') echo '<i class="bi bi-x-circle-fill text-danger"></i>';
                                                    elseif ($estado === 'Tarde') echo '<i class="bi bi-exclamation-circle-fill text-warning"></i>';
                                                    ?>
                                                </td>
                                            <?php endfor; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($asistenciaPorCurso)): ?>
                                        <tr><td colspan="<?= 11 ?>">No hay registros de asistencia para este mes.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="bi bi-check-circle-fill text-success"></i> Presente &nbsp;
                                    <i class="bi bi-x-circle-fill text-danger"></i> Ausente &nbsp;
                                    <i class="bi bi-exclamation-circle-fill text-warning"></i> Tarde
                                </small>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                            <button class="btn btn-academic">Solicitar Justificación</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../js/jquery-3.3.1.min.js"></script>
<script src="../js/popper.min.js"></script>
<script src="../js/bootstrap.min.js"></script>
<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
