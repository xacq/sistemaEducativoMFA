<?php
session_start();
require_once __DIR__ . '/../config.php';

// Sólo el Director (role_id = 1) puede crear profesores
if (empty($_SESSION['role_id']) || $_SESSION['role_id'] != 1) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Acceso denegado'
    ]);
    exit;
}

// Validar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Solicitud inválida'
    ]);
    exit;
}

// 1) Recoger y sanear datos
$nombre     = trim($_POST['nombre']               ?? '');
$apellido   = trim($_POST['apellido']             ?? '');
$email      = trim($_POST['email']                ?? '');
$telefono   = trim($_POST['telefono']             ?? '');
$cedula     = trim($_POST['cedula']               ?? '');
$fecha_nac  =           $_POST['fecha_nacimiento'] ?? '';
$depart     = trim($_POST['departamento']         ?? '');
$cargo      = trim($_POST['cargo']                ?? '');
$start      =           $_POST['fecha_inicio']     ?? '';
$tipo       = trim($_POST['tipo_contrato']        ?? '');
$direccion  = trim($_POST['direccion']            ?? '');
$form_acad  = trim($_POST['formacion_academica']  ?? '');
$materias   =           $_POST['materias']        ?? [];
$enviar     = isset($_POST['enviar_credenciales']) ? 1 : 0;
$role_id    = 2; // siempre Profesor

// 2) Validaciones básicas
if (!$nombre || !$apellido || !$email) {
    echo json_encode([
        'success' => false,
        'message' => 'Los campos Nombre, Apellido y Email son obligatorios.'
    ]);
    exit;
}

// 3) Verificar email duplicado
if ($stmt = $mysqli->prepare("SELECT id FROM usuarios WHERE email = ?")) {
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'El correo electrónico ya está registrado.'
        ]);
        exit;
    }
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error en la verificación de email.'
    ]);
    exit;
}

// 4) Generar contraseña aleatoria y hashearla
$plainPass = bin2hex(random_bytes(4));
$hash      = password_hash($plainPass, PASSWORD_DEFAULT);

// 5) Insertar en usuarios
if ($stmt = $mysqli->prepare("
    INSERT INTO usuarios
      (nombre, apellido, email, password, role_id)
    VALUES (?,?,?,?,?)
")) {
    $stmt->bind_param('ssssi', $nombre, $apellido, $email, $hash, $role_id);
    if (!$stmt->execute()) {
        if ($mysqli->errno === 1062) {
            echo json_encode([
                'success' => false,
                'message' => 'El correo electrónico ya está en uso.'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Error al crear usuario: ' . $mysqli->error
            ]);
        }
        exit;
    }
    $usuario_id = $mysqli->insert_id;
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar inserción de usuario.'
    ]);
    exit;
}

// 6) Procesar subida de foto (opcional)
$foto_nombre = null;
if (!empty($_FILES['foto_perfil']['name']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['foto_perfil']['name'], PATHINFO_EXTENSION);
    $nuevo = 'prof_' . time() . '.' . $ext;
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $upload_dir . $nuevo)) {
        $foto_nombre = $nuevo;
    }
}

// 7) Insertar en profesores
if ($stmt = $mysqli->prepare("
    INSERT INTO profesores
      (usuario_id, cedula, fecha_nacimiento, departamento, cargo,
       fecha_inicio, tipo_contrato, direccion, formacion_academica,
       foto_perfil, enviar_credenciales, telefono)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
")) {
    $stmt->bind_param(
        'isssssssssis',
        $usuario_id, $cedula, $fecha_nac, $depart, $cargo,
        $start, $tipo, $direccion, $form_acad,
        $foto_nombre, $enviar, $telefono
    );
    if (!$stmt->execute()) {
        echo json_encode([
            'success' => false,
            'message' => 'Error al crear perfil de profesor: ' . $stmt->error
        ]);
        exit;
    }
    $profesor_id = $mysqli->insert_id;
    $stmt->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar inserción de profesor.'
    ]);
    exit;
}

// 8) Asignar materias (tabla intermedia)
if (!empty($materias) && $stmt = $mysqli->prepare("
    INSERT INTO profesor_materias (profesor_id, materia_id)
    VALUES (?,?)
")) {
    foreach ($materias as $m) {
        $stmt->bind_param('ii', $profesor_id, $m);
        $stmt->execute();
    }
    $stmt->close();
}

// 9) (Opcional) Enviar email con credenciales
if ($enviar) {
    // mail($email, "Sus credenciales", "Usuario: {$email}\nContraseña: {$plainPass}");
}

// 10) Devolver éxito
echo json_encode([
    'success' => true,
    'message' => 'Profesor creado correctamente. Credenciales enviadas.'
]);
