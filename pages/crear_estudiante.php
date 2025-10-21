<?php
session_start();
require_once __DIR__ . '/../config.php';

// Sólo el Director (role_id = 1) puede crear profesores
if (empty($_SESSION['role_id']) || $_SESSION['role_id'] != 1 && $_SESSION['role_id'] != 2) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso denegado']);
    exit;
}

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
    exit;
}

// --- 1. Recolectar datos del formulario ---
$nombre         = trim($_POST['studentName'] ?? '');
$apellido       = trim($_POST['studentLastName'] ?? '');
$email          = trim($_POST['studentEmail'] ?? '');
$fecha_nac      = $_POST['studentBirthdate'] ?? '';
$genero         = $_POST['studentGender'] ?? '';
$grado_id       = (int)($_POST['grado_id'] ?? 0);
$seccion        = $_POST['studentSection'] ?? '';
$telefono       = trim($_POST['studentPhone'] ?? '');
$direccion      = trim($_POST['studentAddress'] ?? '');
$tutor_nombre   = trim($_POST['parentName'] ?? '');
$tutor_telefono = trim($_POST['parentPhone'] ?? '');
$fecha_insc     = $_POST['enrollmentDate'] ?? date('Y-m-d');
$estado         = $_POST['studentStatus'] ?? 'Activo';
$observaciones  = trim($_POST['studentNotes'] ?? '');
$role_id        = 3; // Rol de estudiante

// 2) Validaciones básicas
if (!$nombre || !$apellido || !$email || !$grado_id|| !$fecha_nac) {
    echo json_encode(['success' => false, 'message' => 'Los campos Nombre, Apellido y Email son obligatorios.']);
    exit;
}

// 3) Verificar email duplicado
$stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'El correo electrónico ya está registrado.']);
    exit;
}
$stmt->close();

// --- 3. Crear usuario con token de activación ---
$hash = password_hash(bin2hex(random_bytes(4)), PASSWORD_DEFAULT);
$token = bin2hex(random_bytes(32));

$stmt = $mysqli->prepare("
    INSERT INTO usuarios (nombre, apellido, email, password, role_id, email_verification_token)
    VALUES (?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('ssssis', $nombre, $apellido, $email, $hash, $role_id, $token);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al crear usuario: ' . $stmt->error]);
    exit;
}
$usuario_id = $mysqli->insert_id;
$stmt->close();

// --- 4. Generar código de estudiante único ---
$codigo_estudiante = 'EST-' . date('Y') . str_pad($usuario_id, 4, '0', STR_PAD_LEFT);

// --- 5. Subida de foto (opcional) ---
$foto_nombre = null;
if (!empty($_FILES['studentPhoto']['name']) && $_FILES['studentPhoto']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['studentPhoto']['name'], PATHINFO_EXTENSION);
    $nuevo_nombre = 'stu_' . time() . '.' . $ext;
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    if (move_uploaded_file($_FILES['studentPhoto']['tmp_name'], $upload_dir . $nuevo_nombre)) {
        $foto_nombre = $nuevo_nombre;
    }
}

// --- 6. Insertar en tabla estudiantes ---
$stmt = $mysqli->prepare("
    INSERT INTO estudiantes (
        usuario_id, codigo_estudiante, fecha_nacimiento, genero, grado_id, seccion, 
        telefono, direccion, tutor_nombre, tutor_telefono, fecha_inscripcion, 
        estado, foto_perfil, observaciones
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param(
    'isssiissssssss',
    $usuario_id,
    $codigo_estudiante,
    $fecha_nac,
    $genero,
    $grado_id,
    $seccion,
    $telefono,
    $direccion,
    $tutor_nombre,
    $tutor_telefono,
    $fecha_insc,
    $estado,
    $foto_nombre,
    $observaciones
);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al registrar estudiante: ' . $stmt->error]);
    exit;
}
$stmt->close();

// --- 7. Generar enlace de activación dinámico ---
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
          . "://" . $_SERVER['HTTP_HOST']
          . rtrim(dirname($_SERVER['PHP_SELF'], 2), '/\\') . '/';
$activation_link = "{$base_url}activar_cuenta.php?token={$token}";

// --- 8. Respuesta JSON para el frontend ---
echo json_encode([
    'success' => true,
    'message' => 'Estudiante registrado correctamente.',
    'activation_link' => $activation_link
]);
exit;
?>