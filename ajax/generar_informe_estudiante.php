<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesión no válida']);
    exit;
}

$usuario_id = $_SESSION['user_id'];
$rol_id     = $_SESSION['role_id']; // 1=Director, 2=Profesor

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/funciones_informes.php';
require_once __DIR__ . '/../includes/ia.php';

$estudiante_id = intval($_POST['estudiante_id'] ?? 0);
$curso_id      = intval($_POST['curso_id'] ?? 0);

if ($estudiante_id <= 0 || $curso_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}

// DIRECTOR: acceso total
if ($rol_id == 1) {
    $permiso = true;
}
// PROFESOR: verificar que el curso es suyo
else {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM cursos
        WHERE id = :curso_id AND profesor_id = (
            SELECT id FROM profesores WHERE usuario_id = :uid
        )
    ");
    $stmt->execute([
        ':curso_id' => $curso_id,
        ':uid' => $usuario_id
    ]);

    $permiso = intval($stmt->fetchColumn()) > 0;
}

if (!$permiso) {
    echo json_encode(['status' => 'error', 'message' => 'Sin permisos para este curso']);
    exit;
}

// PERFIL NUMÉRICO DEL ESTUDIANTE
$perfil = construirPerfilEstudiante($pdo, $estudiante_id);
$perfil['curso_id'] = $curso_id;

if (isset($perfil['error'])) {
    echo json_encode(['status' => 'error', 'message' => $perfil['error']]);
    exit;
}

// PROMPT
$systemPrompt = "
Eres un orientador psicopedagógico profesional...
(igual que antes)
";

// IA
try {
    $texto = generarInformeIA($systemPrompt, $perfil);

    preg_match('/NIVEL_RIESGO:\s*(Bajo|Medio|Alto)/i', $texto, $m);
    $riesgo = $m ? ucfirst(strtolower($m[1])) : "Bajo";

    // GUARDAR EN BD
    $stmt = $pdo->prepare("
        INSERT INTO informes_estudiantes
        (estudiante_id, curso_id, generado_por_usuario_id,
         modelo_ia, resumen_json, informe_texto, nivel_riesgo)
        VALUES
        (:est, :curso, :uid, 'gpt-4.1-mini', :json, :txt, :riesgo)
    ");

    $stmt->execute([
        ':est' => $estudiante_id,
        ':curso' => $curso_id,
        ':uid' => $usuario_id,
        ':json' => json_encode($perfil, JSON_UNESCAPED_UNICODE),
        ':txt' => $texto,
        ':riesgo' => $riesgo
    ]);

    echo json_encode(['status' => 'ok', 'informe' => nl2br(htmlentities($texto)), 'riesgo' => $riesgo]);

} catch (Exception $e) {

    echo json_encode(['status' => 'error', 'message' => "Error IA: " . $e->getMessage()]);
}
