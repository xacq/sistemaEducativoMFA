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
include __DIR__ . '/side_bar_profesor.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Profesor Configuración</title>
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
                    <h1 class="h2">Configuración</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-3">
                            <i class="bi bi-bell fs-4"></i>
                            <span class="notification-badge">5</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="profesor_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="profesor_configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../index.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Configuration Content -->
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="list-group">
                            <a href="#general" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                                <i class="bi bi-gear me-2"></i> General
                            </a>
                            <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="bi bi-bell me-2"></i> Notificaciones
                            </a>
                            <a href="#privacy" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="bi bi-shield-lock me-2"></i> Privacidad
                            </a>
                            <a href="#appearance" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="bi bi-palette me-2"></i> Apariencia
                            </a>
                            <a href="#language" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="bi bi-translate me-2"></i> Idioma
                            </a>
                            <a href="#accessibility" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="bi bi-person-badge me-2"></i> Accesibilidad
                            </a>
                            <a href="#advanced" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="bi bi-sliders me-2"></i> Avanzado
                            </a>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="tab-content">
                            <!-- General Settings -->
                            <div class="tab-pane fade show active" id="general">
                                <div class="card mb-4">
                                    <div class="card-header card-header-academic">
                                        <h5 class="mb-0 text-white">Configuración General</h5>
                                    </div>
                                    <div class="card-body">
                                        <form>
                                            <div class="mb-3">
                                                <label for="timezone" class="form-label">Zona Horaria</label>
                                                <select class="form-select" id="timezone">
                                                    <option selected>América/La_Paz (GMT-4)</option>
                                                    <option>América/Lima (GMT-5)</option>
                                                    <option>América/Santiago (GMT-4)</option>
                                                    <option>América/Buenos_Aires (GMT-3)</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="dateFormat" class="form-label">Formato de Fecha</label>
                                                <select class="form-select" id="dateFormat">
                                                    <option selected>DD/MM/AAAA</option>
                                                    <option>MM/DD/AAAA</option>
                                                    <option>AAAA-MM-DD</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="timeFormat" class="form-label">Formato de Hora</label>
                                                <select class="form-select" id="timeFormat">
                                                    <option selected>12 horas (AM/PM)</option>
                                                    <option>24 horas</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="startPage" class="form-label">Página de Inicio</label>
                                                <select class="form-select" id="startPage">
                                                    <option selected>Dashboard</option>
                                                    <option>Mis Cursos</option>
                                                    <option>Calificaciones</option>
                                                    <option>Asistencia</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="autoLogout" checked>
                                                    <label class="form-check-label" for="autoLogout">Cerrar sesión automáticamente después de 30 minutos de inactividad</label>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Notification Settings -->
                            <div class="tab-pane fade" id="notifications">
                                <div class="card mb-4">
                                    <div class="card-header card-header-academic">
                                        <h5 class="mb-0 text-white">Configuración de Notificaciones</h5>
                                    </div>
                                    <div class="card-body">
                                        <form>
                                            <div class="mb-3">
                                                <label class="form-label">Notificaciones por Correo Electrónico</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="emailNewAssignment" checked>
                                                    <label class="form-check-label" for="emailNewAssignment">
                                                        Nuevas tareas o actividades
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="emailSubmissions" checked>
                                                    <label class="form-check-label" for="emailSubmissions">
                                                        Entregas de estudiantes
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="emailMessages" checked>
                                                    <label class="form-check-label" for="emailMessages">
                                                        Mensajes nuevos
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="emailAnnouncements" checked>
                                                    <label class="form-check-label" for="emailAnnouncements">
                                                        Anuncios del colegio
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="emailReminders" checked>
                                                    <label class="form-check-label" for="emailReminders">
                                                        Recordatorios de eventos
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Notificaciones en la Plataforma</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="platformNewAssignment" checked>
                                                    <label class="form-check-label" for="platformNewAssignment">
                                                        Nuevas tareas o actividades
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="platformSubmissions" checked>
                                                    <label class="form-check-label" for="platformSubmissions">
                                                        Entregas de estudiantes
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="platformMessages" checked>
                                                    <label class="form-check-label" for="platformMessages">
                                                        Mensajes nuevos
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="platformAnnouncements" checked>
                                                    <label class="form-check-label" for="platformAnnouncements">
                                                        Anuncios del colegio
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="platformReminders" checked>
                                                    <label class="form-check-label" for="platformReminders">
                                                        Recordatorios de eventos
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="notificationFrequency" class="form-label">Frecuencia de Notificaciones por Correo</label>
                                                <select class="form-select" id="notificationFrequency">
                                                    <option>Inmediatamente</option>
                                                    <option selected>Resumen diario</option>
                                                    <option>Resumen semanal</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="doNotDisturb">
                                                    <label class="form-check-label" for="doNotDisturb">Modo No Molestar (8:00 PM - 6:00 AM)</label>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Privacy Settings -->
                            <div class="tab-pane fade" id="privacy">
                                <div class="card mb-4">
                                    <div class="card-header card-header-academic">
                                        <h5 class="mb-0 text-white">Configuración de Privacidad</h5>
                                    </div>
                                    <div class="card-body">
                                        <form>
                                            <div class="mb-3">
                                                <label class="form-label">Visibilidad de Perfil</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" name="profileVisibility" id="visibilityAll" checked>
                                                    <label class="form-check-label" for="visibilityAll">
                                                        Visible para todos en la institución
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" name="profileVisibility" id="visibilityColleagues">
                                                    <label class="form-check-label" for="visibilityColleagues">
                                                        Visible solo para colegas y administración
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="profileVisibility" id="visibilityRestricted">
                                                    <label class="form-check-label" for="visibilityRestricted">
                                                        Visible solo para administración
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Información Visible</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="showEmail" checked>
                                                    <label class="form-check-label" for="showEmail">
                                                        Correo electrónico
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="showPhone" checked>
                                                    <label class="form-check-label" for="showPhone">
                                                        Número de teléfono
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="showBirthday">
                                                    <label class="form-check-label" for="showBirthday">
                                                        Fecha de nacimiento
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="showAddress">
                                                    <label class="form-check-label" for="showAddress">
                                                        Dirección
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Actividad en Línea</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="showOnlineStatus" checked>
                                                    <label class="form-check-label" for="showOnlineStatus">
                                                        Mostrar estado en línea
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="showLastSeen" checked>
                                                    <label class="form-check-label" for="showLastSeen">
                                                        Mostrar última vez activo
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="twoFactorAuth">
                                                    <label class="form-check-label" for="twoFactorAuth">Activar autenticación de dos factores</label>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Appearance Settings -->
                            <div class="tab-pane fade" id="appearance">
                                <div class="card mb-4">
                                    <div class="card-header card-header-academic">
                                        <h5 class="mb-0 text-white">Configuración de Apariencia</h5>
                                    </div>
                                    <div class="card-body">
                                        <form>
                                            <div class="mb-3">
                                                <label class="form-label">Tema</label>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" name="theme" id="themeLight" checked>
                                                    <label class="form-check-label" for="themeLight">
                                                        Claro
                                                    </label>
                                                </div>
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="radio" name="theme" id="themeDark">
                                                    <label class="form-check-label" for="themeDark">
                                                        Oscuro
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="radio" name="theme" id="themeSystem">
                                                    <label class="form-check-label" for="themeSystem">
                                                        Usar configuración del sistema
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="colorScheme" class="form-label">Esquema de Color</label>
                                                <select class="form-select" id="colorScheme">
                                                    <option selected>Azul (Predeterminado)</option>
                                                    <option>Verde</option>
                                                    <option>Morado</option>
                                                    <option>Rojo</option>
                                                    <option>Naranja</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="fontSize" class="form-label">Tamaño de Fuente</label>
                                                <select class="form-select" id="fontSize">
                                                    <option>Pequeño</option>
                                                    <option selected>Mediano (Predeterminado)</option>
                                                    <option>Grande</option>
                                                    <option>Muy Grande</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="fontFamily" class="form-label">Tipo de Fuente</label>
                                                <select class="form-select" id="fontFamily">
                                                    <option selected>Sans-serif (Predeterminado)</option>
                                                    <option>Serif</option>
                                                    <option>Monospace</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="animations" checked>
                                                    <label class="form-check-label" for="animations">Mostrar animaciones</label>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                            <button type="button" class="btn btn-outline-secondary">Restaurar Predeterminados</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Language Settings -->
                            <div class="tab-pane fade" id="language">
                                <div class="card mb-4">
                                    <div class="card-header card-header-academic">
                                        <h5 class="mb-0 text-white">Configuración de Idioma</h5>
                                    </div>
                                    <div class="card-body">
                                        <form>
                                            <div class="mb-3">
                                                <label for="interfaceLanguage" class="form-label">Idioma de la Interfaz</label>
                                                <select class="form-select" id="interfaceLanguage">
                                                    <option selected>Español (Bolivia)</option>
                                                    <option>Español (España)</option>
                                                    <option>English (US)</option>
                                                    <option>English (UK)</option>
                                                    <option>Português (Brasil)</option>
                                                    <option>Français</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="contentLanguage" class="form-label">Idioma del Contenido</label>
                                                <select class="form-select" id="contentLanguage">
                                                    <option selected>Español</option>
                                                    <option>English</option>
                                                    <option>Português</option>
                                                    <option>Français</option>
                                                </select>
                                                <div class="form-text">Cuando esté disponible, se mostrará el contenido en este idioma.</div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="spellCheck" checked>
                                                    <label class="form-check-label" for="spellCheck">Activar corrector ortográfico</label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="autoTranslate">
                                                    <label class="form-check-label" for="autoTranslate">Traducir automáticamente contenido no disponible en mi idioma</label>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Accessibility Settings -->
                            <div class="tab-pane fade" id="accessibility">
                                <div class="card mb-4">
                                    <div class="card-header card-header-academic">
                                        <h5 class="mb-0 text-white">Configuración de Accesibilidad</h5>
                                    </div>
                                    <div class="card-body">
                                        <form>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="highContrast">
                                                    <label class="form-check-label" for="highContrast">Modo de alto contraste</label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="reduceMotion">
                                                    <label class="form-check-label" for="reduceMotion">Reducir movimiento</label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="screenReader">
                                                    <label class="form-check-label" for="screenReader">Optimizar para lectores de pantalla</label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="textSpacing" class="form-label">Espaciado de Texto</label>
                                                <select class="form-select" id="textSpacing">
                                                    <option>Compacto</option>
                                                    <option selected>Normal</option>
                                                    <option>Amplio</option>
                                                    <option>Muy amplio</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label for="cursorSize" class="form-label">Tamaño del Cursor</label>
                                                <select class="form-select" id="cursorSize">
                                                    <option>Pequeño</option>
                                                    <option selected>Normal</option>
                                                    <option>Grande</option>
                                                    <option>Muy grande</option>
                                                </select>
                                            </div>
                                            <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Advanced Settings -->
                            <div class="tab-pane fade" id="advanced">
                                <div class="card mb-4">
                                    <div class="card-header card-header-academic">
                                        <h5 class="mb-0 text-white">Configuración Avanzada</h5>
                                    </div>
                                    <div class="card-body">
                                        <form>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="cacheData" checked>
                                                    <label class="form-check-label" for="cacheData">Almacenar datos en caché para acceso sin conexión</label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="autoSave" checked>
                                                    <label class="form-check-label" for="autoSave">Guardar automáticamente cambios en formularios</label>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label for="dataSync" class="form-label">Sincronización de Datos</label>
                                                <select class="form-select" id="dataSync">
                                                    <option selected>Automática (cuando hay conexión)</option>
                                                    <option>Manual</option>
                                                    <option>Solo con Wi-Fi</option>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-outline-warning mb-2">Borrar Caché</button>
                                                <div class="form-text">Libera espacio eliminando datos temporales. No afecta a tus archivos.</div>
                                            </div>
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-outline-danger mb-2">Exportar Mis Datos</button>
                                                <div class="form-text">Descarga una copia de tus datos personales y actividad en el sistema.</div>
                                            </div>
                                            <button type="button" class="btn btn-academic">Guardar Cambios</button>
                                        </form>
                                    </div>
                                </div>
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
