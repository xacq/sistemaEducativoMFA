<?php
session_start();

// Si no hay sesión activa, volvemos al login
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Conexión
require_once '../config.php';

// Obtener nombre y apellido
$stmt = $mysqli->prepare("
    SELECT nombre, apellido
      FROM usuarios
     WHERE id = ?
");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($nombre, $apellido);
$stmt->fetch();
$stmt->close();
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

            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Configuración del Sistema</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-3">
                            <i class="bi bi-bell fs-4"></i>
                            <span class="notification-badge">7</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="director_configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../index.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Configuration Tabs -->
                <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">General</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="false">Usuarios</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic" type="button" role="tab" aria-controls="academic" aria-selected="false">Académico</button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="configTabsContent">
                    <!-- General Settings -->
                    <div class="tab-pane fade show active" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Información de la Institución</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="schoolName" class="form-label">Nombre de la Institución</label>
                                            <input type="text" class="form-control" id="schoolName" value="Unidad Educativa Eduardo Avaroa III">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="schoolCode" class="form-label">Código de la Institución</label>
                                            <input type="text" class="form-control" id="schoolCode" value="UE-EA-003">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="schoolAddress" class="form-label">Dirección</label>
                                            <input type="text" class="form-control" id="schoolAddress" value="El Alto, La Paz, Bolivia">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="schoolPhone" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="schoolPhone" value="+591 2 2845678">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="schoolEmail" class="form-label">Correo Electrónico</label>
                                            <input type="email" class="form-control" id="schoolEmail" value="info@eduardoavaroa.edu.bo">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="schoolWebsite" class="form-label">Sitio Web</label>
                                            <input type="url" class="form-control" id="schoolWebsite" value="https://www.eduardoavaroa.edu.bo">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="schoolLogo" class="form-label">Logo de la Institución</label>
                                            <input type="file" class="form-control" id="schoolLogo">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="schoolFoundation" class="form-label">Fecha de Fundación</label>
                                            <input type="date" class="form-control" id="schoolFoundation" value="1918-03-15">
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Configuración Regional</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="language" class="form-label">Idioma</label>
                                            <select class="form-select" id="language">
                                                <option value="es" selected>Español</option>
                                                <option value="en">Inglés</option>
                                                <option value="ay">Aymara</option>
                                                <option value="qu">Quechua</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="timezone" class="form-label">Zona Horaria</label>
                                            <select class="form-select" id="timezone">
                                                <option value="America/La_Paz" selected>La Paz (GMT-4)</option>
                                                <option value="America/Lima">Lima (GMT-5)</option>
                                                <option value="America/Santiago">Santiago (GMT-4/GMT-3)</option>
                                                <option value="America/Bogota">Bogotá (GMT-5)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="dateFormat" class="form-label">Formato de Fecha</label>
                                            <select class="form-select" id="dateFormat">
                                                <option value="dd/mm/yyyy" selected>DD/MM/AAAA</option>
                                                <option value="mm/dd/yyyy">MM/DD/AAAA</option>
                                                <option value="yyyy-mm-dd">AAAA-MM-DD</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Apariencia del Sistema</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="theme" class="form-label">Tema</label>
                                            <select class="form-select" id="theme">
                                                <option value="default" selected>Predeterminado</option>
                                                <option value="dark">Oscuro</option>
                                                <option value="light">Claro</option>
                                                <option value="custom">Personalizado</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="primaryColor" class="form-label">Color Principal</label>
                                            <input type="color" class="form-control form-control-color" id="primaryColor" value="#4e73df">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="fontFamily" class="form-label">Fuente</label>
                                            <select class="form-select" id="fontFamily">
                                                <option value="system" selected>Predeterminada del Sistema</option>
                                                <option value="roboto">Roboto</option>
                                                <option value="opensans">Open Sans</option>
                                                <option value="montserrat">Montserrat</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="fontSize" class="form-label">Tamaño de Fuente</label>
                                            <select class="form-select" id="fontSize">
                                                <option value="small">Pequeño</option>
                                                <option value="medium" selected>Mediano</option>
                                                <option value="large">Grande</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                        <button type="button" class="btn btn-outline-secondary">Restaurar Predeterminados</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Users Settings -->
                    <div class="tab-pane fade" id="users" role="tabpanel" aria-labelledby="users-tab">
                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Roles y Permisos</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Rol</th>
                                                <th>Descripción</th>
                                                <th>Usuarios</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Director</td>
                                                <td>Acceso completo a todas las funciones del sistema</td>
                                                <td>4</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Profesor</td>
                                                <td>Gestión de cursos, calificaciones y asistencia</td>
                                                <td>41</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Estudiante</td>
                                                <td>Visualización de calificaciones, asistencia y tareas</td>
                                                <td>600</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Secretaría</td>
                                                <td>Gestión administrativa y reportes</td>
                                                <td>2</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Padre/Tutor</td>
                                                <td>Visualización de información de estudiantes asociados</td>
                                                <td>520</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="button" class="btn btn-academic" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                                        <i class="bi bi-plus-circle"></i> Agregar Rol
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Configuración de Cuentas</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="allowSelfRegistration" checked>
                                            <label class="form-check-label" for="allowSelfRegistration">Permitir auto-registro de padres/tutores</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="requireEmailVerification" checked>
                                            <label class="form-check-label" for="requireEmailVerification">Requerir verificación de correo electrónico</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="allowPasswordReset" checked>
                                            <label class="form-check-label" for="allowPasswordReset">Permitir restablecimiento de contraseña</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="autoDisableInactiveAccounts">
                                            <label class="form-check-label" for="autoDisableInactiveAccounts">Desactivar automáticamente cuentas inactivas</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="inactivePeriod" class="form-label">Período de inactividad (días)</label>
                                        <input type="number" class="form-control" id="inactivePeriod" value="90">
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Settings -->
                    <div class="tab-pane fade" id="academic" role="tabpanel" aria-labelledby="academic-tab">
                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Calendario Académico</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="currentYear" class="form-label">Año Académico Actual</label>
                                            <input type="text" class="form-control" id="currentYear" value="2025">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="currentSemester" class="form-label">Semestre Actual</label>
                                            <select class="form-select" id="currentSemester">
                                                <option value="1" selected>Primer Semestre</option>
                                                <option value="2">Segundo Semestre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="startDate" class="form-label">Fecha de Inicio</label>
                                            <input type="date" class="form-control" id="startDate" value="2025-02-03">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="endDate" class="form-label">Fecha de Finalización</label>
                                            <input type="date" class="form-control" id="endDate" value="2025-07-15">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Períodos de Evaluación</label>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-academic">
                                                    <tr>
                                                        <th>Período</th>
                                                        <th>Fecha de Inicio</th>
                                                        <th>Fecha de Finalización</th>
                                                        <th>Peso (%)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Primer Parcial</td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-03-10"></td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-03-14"></td>
                                                        <td><input type="number" class="form-control form-control-sm" value="30"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Segundo Parcial</td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-05-12"></td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-05-16"></td>
                                                        <td><input type="number" class="form-control form-control-sm" value="30"></td>
                                                    </tr>
                                                    <tr>
                                                        <td>Examen Final</td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-07-01"></td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-07-05"></td>
                                                        <td><input type="number" class="form-control form-control-sm" value="40"></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Días Feriados y Vacaciones</label>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-academic">
                                                    <tr>
                                                        <th>Descripción</th>
                                                        <th>Fecha de Inicio</th>
                                                        <th>Fecha de Finalización</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><input type="text" class="form-control form-control-sm" value="Carnaval"></td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-03-03"></td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-03-04"></td>
                                                        <td><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control form-control-sm" value="Semana Santa"></td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-04-17"></td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-04-18"></td>
                                                        <td><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td>
                                                    </tr>
                                                    <tr>
                                                        <td><input type="text" class="form-control form-control-sm" value="Día del Trabajo"></td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-05-01"></td>
                                                        <td><input type="date" class="form-control form-control-sm" value="2025-05-01"></td>
                                                        <td><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="text-end mt-2">
                                            <button type="button" class="btn btn-sm btn-academic">
                                                <i class="bi bi-plus-circle"></i> Agregar Feriado
                                            </button>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Sistema de Calificación</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="gradingScale" class="form-label">Escala de Calificación</label>
                                            <select class="form-select" id="gradingScale">
                                                <option value="100" selected>0-100</option>
                                                <option value="10">0-10</option>
                                                <option value="5">0-5</option>
                                                <option value="letter">Letras (A, B, C, D, F)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="passingGrade" class="form-label">Nota Mínima de Aprobación</label>
                                            <input type="number" class="form-control" id="passingGrade" value="60">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Rangos de Calificación</label>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-academic">
                                                    <tr>
                                                        <th>Rango</th>
                                                        <th>Descripción</th>
                                                        <th>Equivalencia</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>90-100</td>
                                                        <td>Excelente</td>
                                                        <td>A</td>
                                                    </tr>
                                                    <tr>
                                                        <td>80-89</td>
                                                        <td>Muy Bueno</td>
                                                        <td>B</td>
                                                    </tr>
                                                    <tr>
                                                        <td>70-79</td>
                                                        <td>Bueno</td>
                                                        <td>C</td>
                                                    </tr>
                                                    <tr>
                                                        <td>60-69</td>
                                                        <td>Regular</td>
                                                        <td>D</td>
                                                    </tr>
                                                    <tr>
                                                        <td>0-59</td>
                                                        <td>Insuficiente</td>
                                                        <td>F</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="allowCurve" checked>
                                            <label class="form-check-label" for="allowCurve">Permitir ajuste de curva</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="allowExtraCredit" checked>
                                            <label class="form-check-label" for="allowExtraCredit">Permitir puntos extra</label>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Settings -->
                    <div class="tab-pane fade" id="notifications" role="tabpanel" aria-labelledby="notifications-tab">
                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Configuración de Notificaciones</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label class="form-label">Notificaciones del Sistema</label>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead class="table-academic">
                                                    <tr>
                                                        <th>Tipo de Notificación</th>
                                                        <th>Email</th>
                                                        <th>SMS</th>
                                                        <th>Sistema</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Nuevas calificaciones</td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Asistencia</td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Nuevas tareas</td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Eventos escolares</td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Reuniones de padres</td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>Cambios en el calendario</td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox">
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <div class="form-check form-switch">
                                                                <input class="form-check-input" type="checkbox" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="emailServer" class="form-label">Servidor de Correo Electrónico</label>
                                        <input type="text" class="form-control" id="emailServer" value="smtp.eduardoavaroa.edu.bo">
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="emailPort" class="form-label">Puerto</label>
                                            <input type="number" class="form-control" id="emailPort" value="587">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="emailSecurity" class="form-label">Seguridad</label>
                                            <select class="form-select" id="emailSecurity">
                                                <option value="tls" selected>TLS</option>
                                                <option value="ssl">SSL</option>
                                                <option value="none">Ninguna</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="emailUsername" class="form-label">Usuario</label>
                                            <input type="text" class="form-control" id="emailUsername" value="notificaciones@eduardoavaroa.edu.bo">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="emailPassword" class="form-label">Contraseña</label>
                                            <input type="password" class="form-control" id="emailPassword" value="********">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="smsProvider" class="form-label">Proveedor de SMS</label>
                                        <select class="form-select" id="smsProvider">
                                            <option value="twilio" selected>Twilio</option>
                                            <option value="nexmo">Nexmo</option>
                                            <option value="custom">Personalizado</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="smsApiKey" class="form-label">API Key</label>
                                        <input type="text" class="form-control" id="smsApiKey" value="ak_12345678901234567890">
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                        <button type="button" class="btn btn-outline-secondary">Enviar Prueba</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Security Settings -->
                    <div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Políticas de Contraseña</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label for="minPasswordLength" class="form-label">Longitud Mínima</label>
                                        <input type="number" class="form-control" id="minPasswordLength" value="8">
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="requireUppercase" checked>
                                            <label class="form-check-label" for="requireUppercase">Requerir mayúsculas</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="requireLowercase" checked>
                                            <label class="form-check-label" for="requireLowercase">Requerir minúsculas</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="requireNumbers" checked>
                                            <label class="form-check-label" for="requireNumbers">Requerir números</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="requireSpecialChars">
                                            <label class="form-check-label" for="requireSpecialChars">Requerir caracteres especiales</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="passwordExpiration" class="form-label">Expiración de Contraseña (días)</label>
                                        <input type="number" class="form-control" id="passwordExpiration" value="90">
                                    </div>
                                    <div class="mb-3">
                                        <label for="passwordHistory" class="form-label">Historial de Contraseñas</label>
                                        <input type="number" class="form-control" id="passwordHistory" value="5">
                                        <div class="form-text">Número de contraseñas anteriores que no se pueden reutilizar.</div>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Seguridad de Sesión</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label for="sessionTimeout" class="form-label">Tiempo de Inactividad (minutos)</label>
                                        <input type="number" class="form-control" id="sessionTimeout" value="30">
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enforceIPRestriction">
                                            <label class="form-check-label" for="enforceIPRestriction">Restringir acceso por IP</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="allowedIPs" class="form-label">IPs Permitidas</label>
                                        <textarea class="form-control" id="allowedIPs" rows="3" placeholder="Ingrese una IP por línea"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enable2FA">
                                            <label class="form-check-label" for="enable2FA">Habilitar autenticación de dos factores</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="lockFailedAttempts" checked>
                                            <label class="form-check-label" for="lockFailedAttempts">Bloquear cuenta después de intentos fallidos</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="maxFailedAttempts" class="form-label">Número máximo de intentos</label>
                                        <input type="number" class="form-control" id="maxFailedAttempts" value="5">
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Backup Settings -->
                    <div class="tab-pane fade" id="backup" role="tabpanel" aria-labelledby="backup-tab">
                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Configuración de Respaldo</h5>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="enableAutoBackup" checked>
                                            <label class="form-check-label" for="enableAutoBackup">Habilitar respaldo automático</label>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="backupFrequency" class="form-label">Frecuencia de Respaldo</label>
                                        <select class="form-select" id="backupFrequency">
                                            <option value="daily" selected>Diario</option>
                                            <option value="weekly">Semanal</option>
                                            <option value="monthly">Mensual</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="backupTime" class="form-label">Hora de Respaldo</label>
                                        <input type="time" class="form-control" id="backupTime" value="02:00">
                                    </div>
                                    <div class="mb-3">
                                        <label for="backupRetention" class="form-label">Retención de Respaldos (días)</label>
                                        <input type="number" class="form-control" id="backupRetention" value="30">
                                    </div>
                                    <div class="mb-3">
                                        <label for="backupLocation" class="form-label">Ubicación de Respaldo</label>
                                        <select class="form-select" id="backupLocation">
                                            <option value="local" selected>Servidor Local</option>
                                            <option value="cloud">Nube</option>
                                            <option value="both">Ambos</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="cloudProvider" class="form-label">Proveedor de Nube</label>
                                        <select class="form-select" id="cloudProvider">
                                            <option value="aws" selected>Amazon S3</option>
                                            <option value="gcp">Google Cloud Storage</option>
                                            <option value="azure">Microsoft Azure</option>
                                            <option value="dropbox">Dropbox</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="cloudCredentials" class="form-label">Credenciales de Nube</label>
                                        <textarea class="form-control" id="cloudCredentials" rows="3">************</textarea>
                                    </div>
                                    <div class="text-end">
                                        <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                        <button type="button" class="btn btn-outline-secondary">Realizar Respaldo Manual</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header card-header-academic">
                                <h5 class="mb-0 text-white">Respaldos Recientes</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Tamaño</th>
                                                <th>Tipo</th>
                                                <th>Estado</th>
                                                <th>Ubicación</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>01/06/2025 02:00</td>
                                                <td>1.2 GB</td>
                                                <td>Automático</td>
                                                <td><span class="badge bg-success">Completado</span></td>
                                                <td>Local, Nube</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>31/05/2025 02:00</td>
                                                <td>1.2 GB</td>
                                                <td>Automático</td>
                                                <td><span class="badge bg-success">Completado</span></td>
                                                <td>Local, Nube</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>30/05/2025 02:00</td>
                                                <td>1.1 GB</td>
                                                <td>Automático</td>
                                                <td><span class="badge bg-success">Completado</span></td>
                                                <td>Local, Nube</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>29/05/2025 15:30</td>
                                                <td>1.1 GB</td>
                                                <td>Manual</td>
                                                <td><span class="badge bg-success">Completado</span></td>
                                                <td>Local</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>29/05/2025 02:00</td>
                                                <td>1.1 GB</td>
                                                <td>Automático</td>
                                                <td><span class="badge bg-success">Completado</span></td>
                                                <td>Local, Nube</td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-download"></i></button>
                                                    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></button>
                                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </td>
                                            </tr>
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

    <!-- Add Role Modal -->
    <div class="modal fade" id="addRoleModal" tabindex="-1" aria-labelledby="addRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="addRoleModalLabel">Agregar Nuevo Rol</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="roleName" class="form-label">Nombre del Rol</label>
                            <input type="text" class="form-control" id="roleName" required>
                        </div>
                        <div class="mb-3">
                            <label for="roleDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="roleDescription" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Permisos</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permViewStudents">
                                <label class="form-check-label" for="permViewStudents">Ver estudiantes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permEditStudents">
                                <label class="form-check-label" for="permEditStudents">Editar estudiantes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permViewTeachers">
                                <label class="form-check-label" for="permViewTeachers">Ver profesores</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permEditTeachers">
                                <label class="form-check-label" for="permEditTeachers">Editar profesores</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permViewCourses">
                                <label class="form-check-label" for="permViewCourses">Ver cursos</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permEditCourses">
                                <label class="form-check-label" for="permEditCourses">Editar cursos</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permViewGrades">
                                <label class="form-check-label" for="permViewGrades">Ver calificaciones</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permEditGrades">
                                <label class="form-check-label" for="permEditGrades">Editar calificaciones</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permViewReports">
                                <label class="form-check-label" for="permViewReports">Ver reportes</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="permManageSystem">
                                <label class="form-check-label" for="permManageSystem">Administrar sistema</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
