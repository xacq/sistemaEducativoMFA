<?php
session_start();

// ============================
// VALIDACIÓN DE SESIÓN
// ============================
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config.php';
$usuario_id = $_SESSION['user_id'];

// OBTENER NOMBRE PARA LA BARRA SUPERIOR
$stmtUser = $pdo->prepare("SELECT nombre, apellido FROM usuarios WHERE id = :id LIMIT 1");
$stmtUser->execute([':id' => $usuario_id]);
$user = $stmtUser->fetch();

$nombre = $user['nombre'] ?? 'Director';
$apellido = $user['apellido'] ?? '';

// OBTENER LISTA DE CURSOS
$cursos = $pdo->query("
    SELECT c.id, c.nombre AS curso, m.nombre AS materia
    FROM cursos c
    JOIN materias m ON c.materia_id = m.id
")->fetchAll(PDO::FETCH_ASSOC);

// OBTENER LISTA DE DOCENTES
$profesores = $pdo->query("
    SELECT p.id, u.nombre, u.apellido
    FROM profesores p
    JOIN usuarios u ON p.usuario_id = u.id
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informe Psicopedagógico - Director</title>

    <!-- RUTAS CSS CORRECTAS -->
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/academic.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <!-- JS CORRECTO -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/dashboard_informes.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>

</head>

<body>

<div class="container-fluid">
    <div class="row">

        <!-- SIDEBAR DEL DIRECTOR -->
        <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
            <?php include __DIR__ . './side_bar_director.php'; ?>
        </div>

        <!-- CONTENIDO PRINCIPAL -->
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

            <!-- HEADER -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center 
                        pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Informes Psicopedagógicos</h1>

                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle"
                                data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= htmlspecialchars($nombre . ' ' . $apellido) ?>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                        </ul>
                    </div>
                </div>
            </div>


            <!-- ============================= -->
            <!--   INFORME PARA ESTUDIANTES   -->
            <!-- ============================= -->
            <div class="card mb-4">
                <div class="card-header card-header-academic">
                    <h5 class="mb-0 text-white">Informe para Estudiantes</h5>
                </div>

                <div class="card-body">
                    <label class="form-label">Seleccione un Curso</label>
                    <select id="curso_id" class="form-select">
                        <option value="">Seleccione un curso</option>
                        <?php foreach ($cursos as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= $c['curso'] ?> - <?= $c['materia'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div id="tabla_estudiantes" class="mt-3">
                        <p class="text-muted">Seleccione un curso para ver sus estudiantes.</p>
                    </div>
                </div>
            </div>


            <!-- ============================= -->
            <!--     INFORME PARA DOCENTES    -->
            <!-- ============================= -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informe para Docentes</h5>
                </div>

                <div class="card-body">

                    <label class="form-label">Seleccione un Docente</label>

                    <div class="d-flex" style="gap:10px;">
                        <select id="profesor_id" class="form-select" style="max-width:300px;">
                            <option value="">Seleccione un profesor</option>

                            <?php foreach ($profesores as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= $p['nombre'] . " " . $p['apellido'] ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                        <button id="btn_informe_docente" class="btn btn-primary">
                            <i class="bi bi-file-earmark-text"></i> Generar Informe
                        </button>
                    </div>

                </div>
            </div>


            <!-- ============================= -->
            <!--      PANEL DE RESULTADO      -->
            <!-- ============================= -->
            <div class="card mb-5">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Resultado del Informe</h5>
                </div>

                <div class="card-body" id="panel_informe">
                    <p class="text-muted">Seleccione un estudiante o profesor para ver su informe.</p>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
