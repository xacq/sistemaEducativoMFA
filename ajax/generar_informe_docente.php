<?php
session_start();
header('Content-Type: application/json');

// Solo DIRECTOR
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Solo el director puede generar este informe.']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/funciones_informes.php';
require_once __DIR__ . '/../includes/ia.php';

$usuario_id   = $_SESSION['user_id'];
$profesor_id  = intval($_POST['profesor_id'] ?? 0);

if ($profesor_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
    exit;
}

// PERFIL DEL DOCENTE
$perfil = construirPerfilDocente($pdo, $profesor_id);

if (isset($perfil['error'])) {
    echo json_encode(['status' => 'error', 'message' => $perfil['error']]);
    exit;
}

$systemPrompt = "
Eres un asesor pedagógico especializado...
(igual que antes)
";

try {
    $texto = generarInformeIA($systemPrompt, $perfil);

    preg_match('/NIVEL_RIESGO:\s*(Bajo|Medio|Alto)/i', $texto, $m);
    $riesgo = $m ? ucfirst(strtolower($m[1])) : "Bajo";

    // GUARDAR EN BD
    $stmt = $pdo->prepare("
        INSERT INTO informes_docentes
        (profesor_id, generado_por_usuario_id, modelo_ia,
         resumen_json, informe_texto, nivel_riesgo)
        VALUES
        (:pid, :uid, 'gpt-4.1-mini', :json, :txt, :riesgo)
    ");

    $stmt->execute([
        ':pid' => $profesor_id,
        ':uid' => $usuario_id,
        ':json' => json_encode($perfil, JSON_UNESCAPED_UNICODE),
        ':txt' => $texto,
        ':riesgo' => $riesgo
    ]);

    echo json_encode(['status' => 'ok', 'informe' => nl2br(htmlentities($texto)), 'riesgo' => $riesgo]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => "Error IA: " . $e->getMessage()]);
}
