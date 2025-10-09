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
$email = $context['user']['email'];
$estudiante = $context['student'];
$estudianteId = $estudiante['id'] ?? null;
$estudianteData = [
    'codigo_estudiante' => $estudiante['codigo_estudiante'] ?? '',
    'fecha_nacimiento' => $estudiante['fecha_nacimiento'] ?? '',
    'genero' => $estudiante['genero'] ?? '',
    'telefono' => $estudiante['telefono'] ?? '',
    'direccion' => $estudiante['direccion'] ?? '',
    'tutor_nombre' => $estudiante['tutor_nombre'] ?? '',
    'tutor_telefono' => $estudiante['tutor_telefono'] ?? '',
];

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $tutorNombre = trim($_POST['tutor_nombre'] ?? '');
    $tutorTelefono = trim($_POST['tutor_telefono'] ?? '');
    $fechaNacimiento = $_POST['fecha_nacimiento'] ?? '';
    $genero = $_POST['genero'] ?? '';

    $errors = [];

    if ($nombre === '' || $apellido === '') {
        $errors[] = 'Nombre y apellido son obligatorios.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Debes proporcionar un correo electrónico válido.';
    }

    $generosPermitidos = ['Masculino', 'Femenino', 'Otro'];
    if ($genero !== '' && !in_array($genero, $generosPermitidos, true)) {
        $errors[] = 'Selecciona un género válido.';
    }

    if ($fechaNacimiento !== '' && DateTime::createFromFormat('Y-m-d', $fechaNacimiento) === false) {
        $errors[] = 'La fecha de nacimiento no tiene un formato válido (YYYY-MM-DD).';
    }

    if (empty($errors)) {
        if ($stmt = $mysqli->prepare('UPDATE usuarios SET nombre = ?, apellido = ?, email = ? WHERE id = ?')) {
            $stmt->bind_param('sssi', $nombre, $apellido, $email, $userId);
            $stmt->execute();
            $stmt->close();
        }

        if ($estudianteId) {
            if ($stmt = $mysqli->prepare('UPDATE estudiantes SET telefono = ?, direccion = ?, tutor_nombre = ?, tutor_telefono = ?, fecha_nacimiento = ?, genero = ? WHERE id = ?')) {
                $stmt->bind_param('ssssssi', $telefono, $direccion, $tutorNombre, $tutorTelefono, $fechaNacimiento, $genero, $estudianteId);
                $stmt->execute();
                $stmt->close();
            }
        }

        $successMessage = 'Perfil actualizado correctamente.';
        $estudianteData['telefono'] = $telefono;
        $estudianteData['direccion'] = $direccion;
        $estudianteData['tutor_nombre'] = $tutorNombre;
        $estudianteData['tutor_telefono'] = $tutorTelefono;
        $estudianteData['fecha_nacimiento'] = $fechaNacimiento;
        $estudianteData['genero'] = $genero;
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
    <title>Mi Perfil</title>
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
                    <h1 class="h2">Mi Perfil</h1>
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

                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Datos personales</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nombre" class="form-label">Nombre</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="apellido" class="form-label">Apellido</label>
                                    <input type="text" class="form-control" id="apellido" name="apellido" required value="<?php echo htmlspecialchars($apellido, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Correo electrónico</label>
                                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
                                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($estudianteData['fecha_nacimiento'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="genero" class="form-label">Género</label>
                                    <select class="form-select" id="genero" name="genero">
                                        <option value="">Seleccione</option>
                                        <?php foreach (['Masculino', 'Femenino', 'Otro'] as $option): ?>
                                            <option value="<?php echo $option; ?>" <?php echo $estudianteData['genero'] === $option ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Código estudiante</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($estudianteData['codigo_estudiante'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono" class="form-label">Teléfono</label>
                                    <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($estudianteData['telefono'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="direccion" class="form-label">Dirección</label>
                                    <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo htmlspecialchars($estudianteData['direccion'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="tutor_nombre" class="form-label">Nombre del tutor</label>
                                    <input type="text" class="form-control" id="tutor_nombre" name="tutor_nombre" value="<?php echo htmlspecialchars($estudianteData['tutor_nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="tutor_telefono" class="form-label">Teléfono del tutor</label>
                                    <input type="text" class="form-control" id="tutor_telefono" name="tutor_telefono" value="<?php echo htmlspecialchars($estudianteData['tutor_telefono'], ENT_QUOTES, 'UTF-8'); ?>">
                                </div>
                            </div>
                            <div class="mt-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-academic">Guardar cambios</button>
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
