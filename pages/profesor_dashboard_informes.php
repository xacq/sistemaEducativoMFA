<?php
session_start();

// ============================
// VALIDACIÓN DE SESIÓN
// ============================
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config.php';

// ============================
// OBTENER DATOS DEL PROFESOR
// ============================
$usuario_id = $_SESSION['user_id'];

$stmt_prof = $pdo->prepare("
    SELECT u.nombre, u.apellido, p.id AS profesor_id
    FROM usuarios u
    JOIN profesores p ON u.id = p.usuario_id
    WHERE u.id = :id
");
$stmt_prof->execute([':id' => $usuario_id]);
$prof = $stmt_prof->fetch();

$nombre      = $prof['nombre']    ?? '';
$apellido    = $prof['apellido']  ?? '';
$profesor_id = $prof['profesor_id'] ?? 0;


// ============================
// OBTENER CURSOS DEL PROFESOR
// ============================
$stmtCursos = $pdo->prepare("
    SELECT c.id, c.nombre AS curso, m.nombre AS materia
    FROM cursos c
    JOIN materias m ON c.materia_id = m.id
    WHERE c.profesor_id = :pid
");
$stmtCursos->execute([':pid' => $profesor_id]);
$cursos = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informe Psicopedagógico</title>

    <!-- CSS CORRECTO -->
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <!-- JS CORRECTO -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/dashboard_informes.js"></script>
</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <?php include __DIR__ . '/side_bar_profesor.php'; ?>
        </div>

        <!-- CONTENIDO -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

            <!-- ENCABEZADO -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center 
                        pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Informe Psicopedagógico</h1>

                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($nombre . ' ' . $apellido) ?>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profesor_perfil.php">Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>


            <!-- SELECCIÓN DE CURSO -->
            <div class="card mb-4">
                <div class="card-header card-header-academic">
                    <h5 class="mb-0 text-white">Seleccionar Curso</h5>
                </div>

                <div class="card-body">
                    <select id="curso_id" class="form-select">
                        <option value="">Seleccione un curso</option>
                        <?php foreach ($cursos as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= $c['curso'] ?> - <?= $c['materia'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>


            <!-- LISTA DE ESTUDIANTES -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Estudiantes del Curso</h5>
                </div>

                <div class="card-body" id="tabla_estudiantes">
                    <p class="text-muted">Seleccione un curso para ver los estudiantes.</p>
                </div>
            </div>


            <!-- INFORME GENERADO -->
            <div class="card mb-5">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Informe Psicopedagógico del Estudiante</h5>
                </div>

                <div class="card-body" id="panel_informe">
                    <p class="text-muted">Seleccione un estudiante para generar su informe.</p>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="../js/bootstrap.bundle.min.js"></script>

</body>
</html>
