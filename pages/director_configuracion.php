<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';

// --- LÓGICA DE DATOS ---

// 1. OBTENER NOMBRE DEL DIRECTOR
$stmt_director = $mysqli->prepare("SELECT nombre, apellido FROM usuarios WHERE id = ?");
$stmt_director->bind_param('i', $_SESSION['user_id']);
$stmt_director->execute();
$stmt_director->bind_result($nombre, $apellido);
$stmt_director->fetch();
$stmt_director->close();

// 2. CARGAR TODA LA CONFIGURACIÓN DE LA BASE DE DATOS
$config = [];
if ($result = $mysqli->query("SELECT llave, valor FROM configuracion")) {
    while ($row = $result->fetch_assoc()) {
        $config[$row['llave']] = $row['valor'];
    }
    $result->free();
}

// --- FIN LÓGICA ---
include __DIR__ . '/side_bar_director.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Configuración</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Configuración del Sistema</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($nombre . ' ' . $apellido); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['config_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['config_message']; unset($_SESSION['config_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                    <li class="nav-item" role="presentation"><button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">General</button></li>
                    <!-- Las otras pestañas están comentadas por ahora, nos enfocamos en la general -->
                    <!-- <li class="nav-item" role="presentation"><button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button">Usuarios</button></li> -->
                </ul>

                <div class="tab-content" id="configTabsContent">
                    <!-- General Settings -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <form action="guardar_configuracion.php" method="POST" enctype="multipart/form-data">
                            <div class="card mb-4">
                                <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Información de la Institución</h5></div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6"><label for="schoolName" class="form-label">Nombre</label><input type="text" class="form-control" name="config[schoolName]" value="<?php echo htmlspecialchars($config['schoolName'] ?? ''); ?>"></div>
                                        <div class="col-md-6"><label for="schoolCode" class="form-label">Código</label><input type="text" class="form-control" name="config[schoolCode]" value="<?php echo htmlspecialchars($config['schoolCode'] ?? ''); ?>"></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6"><label for="schoolAddress" class="form-label">Dirección</label><input type="text" class="form-control" name="config[schoolAddress]" value="<?php echo htmlspecialchars($config['schoolAddress'] ?? ''); ?>"></div>
                                        <div class="col-md-6"><label for="schoolPhone" class="form-label">Teléfono</label><input type="tel" class="form-control" name="config[schoolPhone]" value="<?php echo htmlspecialchars($config['schoolPhone'] ?? ''); ?>"></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6"><label for="schoolEmail" class="form-label">Correo Electrónico</label><input type="email" class="form-control" name="config[schoolEmail]" value="<?php echo htmlspecialchars($config['schoolEmail'] ?? ''); ?>"></div>
                                        <div class="col-md-6"><label for="schoolWebsite" class="form-label">Sitio Web</label><input type="url" class="form-control" name="config[schoolWebsite]" value="<?php echo htmlspecialchars($config['schoolWebsite'] ?? ''); ?>"></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6"><label for="schoolFoundation" class="form-label">Fecha de Fundación</label><input type="date" class="form-control" name="config[schoolFoundation]" value="<?php echo htmlspecialchars($config['schoolFoundation'] ?? ''); ?>"></div>
                                        <div class="col-md-6"><label for="schoolLogo" class="form-label">Logo (subir nuevo)</label><input type="file" class="form-control" name="schoolLogo"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-4">
                                <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Configuración Regional</h5></div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4"><label for="language" class="form-label">Idioma</label><select class="form-select" name="config[language]"><option value="es" <?php if(($config['language'] ?? '') == 'es') echo 'selected'; ?>>Español</option><option value="en" <?php if(($config['language'] ?? '') == 'en') echo 'selected'; ?>>Inglés</option></select></div>
                                        <div class="col-md-4"><label for="timezone" class="form-label">Zona Horaria</label><select class="form-select" name="config[timezone]"><option value="America/La_Paz" <?php if(($config['timezone'] ?? '') == 'America/La_Paz') echo 'selected'; ?>>La Paz (GMT-4)</option></select></div>
                                        <div class="col-md-4"><label for="dateFormat" class="form-label">Formato de Fecha</label><select class="form-select" name="config[dateFormat]"><option value="d/m/Y" <?php if(($config['dateFormat'] ?? '') == 'd/m/Y') echo 'selected'; ?>>DD/MM/AAAA</option><option value="m/d/Y" <?php if(($config['dateFormat'] ?? '') == 'm/d/Y') echo 'selected'; ?>>MM/DD/AAAA</option><option value="Y-m-d" <?php if(($config['dateFormat'] ?? '') == 'Y-m-d') echo 'selected'; ?>>AAAA-MM-DD</option></select></div>
                                    </div>
                                </div>
                            </div>

                            <div class="text-end mb-4">
                                <button type="submit" class="btn btn-academic btn-lg">Guardar Toda la Configuración</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>