<?php
session_start();
require_once __DIR__ . '/../config.php';

// --- 1. Validar sesión y rol ---
if (empty($_SESSION['role_id']) || $_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// --- 2. Validar método ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida.']);
    exit;
}

// --- 3. Recoger datos del formulario ---
$nombre       = trim($_POST['nombre'] ?? '');
$grado_id     = (int)($_POST['grado_id'] ?? 0);
$seccion      = trim($_POST['seccion'] ?? '');
$profesor_id  = (int)($_POST['profesor_id'] ?? 0);
$materia_id   = (int)($_POST['materia_id'] ?? 0);
$capacidad    = (int)($_POST['capacidad'] ?? 0);
$creditos     = (int)($_POST['creditos'] ?? 0);
$descripcion  = trim($_POST['descripcion'] ?? '');
$fecha_inicio = $_POST['fecha_inicio'] ?? null;
$fecha_fin    = $_POST['fecha_fin'] ?? null;
$estatus      = $_POST['estatus'] ?? 'Activo';

// --- 4. Validaciones básicas ---
if (!$nombre || !$grado_id || !$profesor_id || !$materia_id || !$seccion) {
    echo json_encode(['success' => false, 'message' => 'Por favor complete todos los campos obligatorios.']);
    exit;
}

// --- 5. Generar código de curso único ---
$codigo = 'CUR-' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

// --- 6. Insertar en la base de datos ---
$stmt = $mysqli->prepare("
    INSERT INTO cursos (
        codigo, nombre, grado_id, seccion, profesor_id, materia_id,
        capacidad, creditos, descripcion, fecha_inicio, fecha_fin, estatus
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    'ssisiisiisss',
    $codigo,
    $nombre,
    $grado_id,
    $seccion,
    $profesor_id,
    $materia_id,
    $capacidad,
    $creditos,
    $descripcion,
    $fecha_inicio,
    $fecha_fin,
    $estatus
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al crear curso: ' . $stmt->error]);
    exit;
}

$stmt->close();

// --- 7. Respuesta JSON para el frontend ---
echo json_encode([
    'success' => true,
    'message' => 'Curso registrado correctamente.',
    'codigo'  => $codigo
]);
exit;
?>
