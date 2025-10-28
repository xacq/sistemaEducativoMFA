<?php
session_start();

// Verificación de sesión
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';

// 1️⃣ Obtener ID del estudiante según usuario
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

// 2️⃣ Obtener nombre y apellido
$stmt = $mysqli->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($nombre, $apellido);
$stmt->fetch();
$stmt->close();

// 3️⃣ Consultar calificaciones del estudiante (unifica evaluaciones y tareas)
$sql = "
(
    SELECT 
        c.id AS curso_id,
        c.nombre AS curso_nombre,
        CONCAT(up.nombre, ' ', up.apellido) AS profesor,
        e.titulo AS tipo_evaluacion,
        e.tipo_evaluacion AS categoria,
        ca.calificacion
    FROM matriculas m
    JOIN cursos c ON m.curso_id = c.id
    JOIN profesores p ON c.profesor_id = p.id
    JOIN usuarios up ON p.usuario_id = up.id
    LEFT JOIN calificaciones ca ON ca.matricula_id = m.id
    LEFT JOIN evaluaciones e ON ca.evaluacion_id = e.id
    WHERE m.estudiante_id = ?
)
UNION ALL
(
    SELECT 
        c.id AS curso_id,
        c.nombre AS curso_nombre,
        CONCAT(up.nombre, ' ', up.apellido) AS profesor,
        CONCAT('Tarea: ', t.titulo) AS tipo_evaluacion,
        CASE 
            WHEN t.tipo = '0' THEN 'Parcial 1'
            WHEN t.tipo = '1' THEN 'Parcial 2'
            WHEN t.tipo = '2' THEN 'Proyecto'
            WHEN t.tipo = '3' THEN 'Final'
            ELSE 'Parcial 1'
        END AS categoria,
        ct.calificacion
    FROM matriculas m
    JOIN cursos c ON m.curso_id = c.id
    JOIN profesores p ON c.profesor_id = p.id
    JOIN usuarios up ON p.usuario_id = up.id
    JOIN tareas t ON c.id = t.curso_id
    JOIN tarea_entregas te ON te.tarea_id = t.id AND te.matricula_id = m.id
    LEFT JOIN calificaciones_tareas ct ON ct.tarea_entrega_id = te.id
    WHERE m.estudiante_id = ?
)
ORDER BY curso_id, categoria
";


$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ii", $estudianteId, $estudianteId);
$stmt->execute();
$result = $stmt->get_result();


// 4️⃣ Reorganizar resultados por curso
$cursos = [];

while ($row = $result->fetch_assoc()) {
    $curso = $row['curso_nombre'];
    $categoria = $row['categoria'] ?? 'Sin categoría'; // Parcial 1, Parcial 2, Proyecto, Final, etc.

    if (!isset($cursos[$curso])) {
        $cursos[$curso] = [
            'profesor' => $row['profesor'],
            'evaluaciones' => []
        ];
    }

    // Inicializamos la categoría si no existe
    if (!isset($cursos[$curso]['evaluaciones'][$categoria])) {
        $cursos[$curso]['evaluaciones'][$categoria] = [];
    }

    // Agregamos esta evaluación/tarea dentro de su categoría
    $cursos[$curso]['evaluaciones'][$categoria][] = [
        'nombre' => $row['tipo_evaluacion'],
        'nota' => $row['calificacion']
    ];
}

// 5️⃣ Calcular promedios y estado
foreach ($cursos as $curso => &$data) {
    $notas = [];
    foreach ($data['evaluaciones'] as $categoriaNotas) {
        foreach ($categoriaNotas as $eval) {
            if (!is_null($eval['nota'])) {
                $notas[] = $eval['nota'];
            }
        }
    }
    $data['promedio'] = count($notas) ? round(array_sum($notas) / count($notas), 1) : 0;
    $data['estado'] = $data['promedio'] >= 70 ? 'Aprobado' : ($data['promedio'] > 0 ? 'Reprobado' : 'Sin calificaciones');
}


include __DIR__ . '/side_bar_estudiantes.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Calificaciones Estudiante</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<style>
    .table td ul {
    margin: 0;
    padding-left: 0.5rem;
    }
    .table td ul li {
    margin-bottom: 2px;
    font-size: 0.9rem;
    }
    .table td ul li i {
    font-size: 0.8rem;
    }
    .notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    transform: translate(50%, -50%);
    background-color: red;
    color: white;
    border-radius: 50%;
    padding: 0.25em 0.5em;
    font-size: 0.75rem;
    font-weight: bold;
    }
</style>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Contenido principal -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Mis Calificaciones</h1>
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

            <!-- Tabla de calificaciones -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header card-header-academic">
                            <h5 class="mb-0 text-white">Detalle de Calificaciones por Curso</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-academic">
                                    <tr>
                                        <th>Curso</th>
                                        <th>Profesor</th>
                                        <th>Parcial 1</th>
                                        <th>Parcial 2</th>
                                        <th>Proyecto</th>
                                        <th>Final</th>
                                        <th>Promedio</th>
                                        <th>Estado</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($cursos as $curso => $data): ?>
                                        <?php
                                        $prom = $data['promedio'];
                                        $estado = $data['estado'];
                                        $badge = $estado === 'Aprobado' ? 'bg-success' :
                                                 ($estado === 'Reprobado' ? 'bg-danger' : 'bg-secondary');
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($curso) ?></td>
                                            <td><?= htmlspecialchars($data['profesor']) ?></td>
                                            <td>
                                                <?php if (!empty($data['evaluaciones']['Parcial 1'])): ?>
                                                    <ul class="list-unstyled mb-0">
                                                        <?php foreach ($data['evaluaciones']['Parcial 1'] as $eval): ?>
                                                            <li>
                                                                <i class="bi bi-check-circle text-primary me-1"></i>
                                                                <strong><?= htmlspecialchars($eval['nombre']) ?>:</strong>
                                                                <?= $eval['nota'] ?? '-' ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?php if (!empty($data['evaluaciones']['Parcial 2'])): ?>
                                                    <ul class="list-unstyled mb-0">
                                                        <?php foreach ($data['evaluaciones']['Parcial 2'] as $eval): ?>
                                                            <li>
                                                                <i class="bi bi-check-circle text-success me-1"></i>
                                                                <strong><?= htmlspecialchars($eval['nombre']) ?>:</strong>
                                                                <?= $eval['nota'] ?? '-' ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?php if (!empty($data['evaluaciones']['Proyecto'])): ?>
                                                    <ul class="list-unstyled mb-0">
                                                        <?php foreach ($data['evaluaciones']['Proyecto'] as $eval): ?>
                                                            <li>
                                                                <i class="bi bi-lightbulb text-warning me-1"></i>
                                                                <strong><?= htmlspecialchars($eval['nombre']) ?>:</strong>
                                                                <?= $eval['nota'] ?? '-' ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <?php if (!empty($data['evaluaciones']['Final'])): ?>
                                                    <ul class="list-unstyled mb-0">
                                                        <?php foreach ($data['evaluaciones']['Final'] as $eval): ?>
                                                            <li>
                                                                <i class="bi bi-flag text-danger me-1"></i>
                                                                <strong><?= htmlspecialchars($eval['nombre']) ?>:</strong>
                                                                <?= $eval['nota'] ?? '-' ?>
                                                            </li>
                                                        <?php endforeach; ?>
                                                    </ul>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>

                                            <td class="fw-bold"><?= $prom ?></td>
                                            <td><span class="badge <?= $badge ?>"><?= $estado ?></span></td>
                                        </tr>

                                    <?php endforeach; ?>
                                    <?php if (empty($cursos)): ?>
                                        <tr><td colspan="9" class="text-center text-muted">No hay calificaciones registradas.</td></tr>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
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
