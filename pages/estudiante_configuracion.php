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

$defaultPreferences = [
    'language' => 'es',
    'timezone' => 'America/La_Paz',
    'theme' => 'light',
    'notifications_email' => '1',
    'notifications_system' => '1',
];

$preferences = $defaultPreferences;
$prefix = 'student_' . $userId . '_';

if ($stmt = $mysqli->prepare('SELECT llave, valor FROM configuracion WHERE llave LIKE ?')) {
    $like = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $key = substr($row['llave'], strlen($prefix));
        if (array_key_exists($key, $preferences)) {
            $preferences[$key] = $row['valor'];
        }
    }
    $stmt->close();
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $language = $_POST['language'] ?? $preferences['language'];
    $timezone = $_POST['timezone'] ?? $preferences['timezone'];
    $theme = $_POST['theme'] ?? $preferences['theme'];
    $notificationsEmail = isset($_POST['notifications_email']) ? '1' : '0';
    $notificationsSystem = isset($_POST['notifications_system']) ? '1' : '0';

    $errors = [];
    $allowedLanguages = ['es', 'en'];
    $allowedThemes = ['light', 'dark', 'system'];

    if (!in_array($language, $allowedLanguages, true)) {
        $errors[] = 'Idioma no válido seleccionado.';
    }

    if (!in_array($theme, $allowedThemes, true)) {
        $errors[] = 'Tema no válido seleccionado.';
    }

    if (empty($timezone)) {
        $errors[] = 'Debes seleccionar una zona horaria.';
    }

    if (empty($errors)) {
        $preferences = [
            'language' => $language,
            'timezone' => $timezone,
            'theme' => $theme,
            'notifications_email' => $notificationsEmail,
            'notifications_system' => $notificationsSystem,
        ];

        foreach ($preferences as $key => $value) {
            $llave = $prefix . $key;
            if ($stmt = $mysqli->prepare('INSERT INTO configuracion (llave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')) {
                $stmt->bind_param('ss', $llave, $value);
                $stmt->execute();
                $stmt->close();
            }
        }

        $successMessage = 'Preferencias actualizadas correctamente.';
    } else {
        $errorMessage = implode(' ', $errors);
    }
}

include __DIR__ . '/side_bar_estudiantes.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->

            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Configuración</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars(trim($nombre . ' ' . $apellido), ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="estudiante_dashboard.php">Dashboard</a></li>
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

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

                <form method="POST">
                    <div class="card mb-4">
                        <div class="card-header card-header-academic">
                            <h5 class="mb-0 text-white">Preferencias generales</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="language" class="form-label">Idioma</label>
                                    <select class="form-select" id="language" name="language">
                                        <option value="es" <?php echo $preferences['language'] === 'es' ? 'selected' : ''; ?>>Español</option>
                                        <option value="en" <?php echo $preferences['language'] === 'en' ? 'selected' : ''; ?>>Inglés</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="timezone" class="form-label">Zona horaria</label>
                                    <select class="form-select" id="timezone" name="timezone">
                                        <option value="America/La_Paz" <?php echo $preferences['timezone'] === 'America/La_Paz' ? 'selected' : ''; ?>>America/La_Paz</option>
                                        <option value="America/Bogota" <?php echo $preferences['timezone'] === 'America/Bogota' ? 'selected' : ''; ?>>America/Bogota</option>
                                        <option value="America/Mexico_City" <?php echo $preferences['timezone'] === 'America/Mexico_City' ? 'selected' : ''; ?>>America/Mexico_City</option>
                                        <option value="Europe/Madrid" <?php echo $preferences['timezone'] === 'Europe/Madrid' ? 'selected' : ''; ?>>Europe/Madrid</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="theme" class="form-label">Tema</label>
                                    <select class="form-select" id="theme" name="theme">
                                        <option value="light" <?php echo $preferences['theme'] === 'light' ? 'selected' : ''; ?>>Claro</option>
                                        <option value="dark" <?php echo $preferences['theme'] === 'dark' ? 'selected' : ''; ?>>Oscuro</option>
                                        <option value="system" <?php echo $preferences['theme'] === 'system' ? 'selected' : ''; ?>>Seguir al sistema</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header card-header-academic">
                            <h5 class="mb-0 text-white">Notificaciones</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="notifications_email" name="notifications_email" value="1" <?php echo $preferences['notifications_email'] === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notifications_email">Recibir notificaciones por correo electrónico</label>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notifications_system" name="notifications_system" value="1" <?php echo $preferences['notifications_system'] === '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notifications_system">Mostrar recordatorios dentro del sistema</label>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mb-5">
                        <button type="submit" class="btn btn-academic">Guardar preferencias</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
