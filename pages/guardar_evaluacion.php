<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json; charset=UTF-8');

// Verificar que el usuario esté logueado
if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesión expirada.']);
    exit;
}

try {
    // Validar campos obligatorios
    $required = ['curso_id', 'periodo', 'tipo_evaluacion', 'fecha', 'titulo', 'ponderacion', 'puntaje_maximo', 'puntaje_aprobatorio'];
    foreach ($required as $campo) {
        if (empty($_POST[$campo])) {
            echo json_encode(['success' => false, 'message' => "Falta el campo obligatorio: $campo"]);
            exit;
        }
    }

    // Asignar variables desde POST
    $curso_id = (int) $_POST['curso_id'];
    $periodo = $_POST['periodo'];
    $tipo_evaluacion = $_POST['tipo_evaluacion'];
    $fecha = $_POST['fecha'];
    $titulo = trim($_POST['titulo']);
    $ponderacion = (int) $_POST['ponderacion'];
    $puntaje_maximo = (int) $_POST['puntaje_maximo'];
    $puntaje_aprobatorio = (int) $_POST['puntaje_aprobatorio'];
    $descripcion = $_POST['descripcion'] ?? null;
    $metodo_ingreso = $_POST['metodo_ingreso'] ?? 'individual';
    $notificar_estudiantes = isset($_POST['notificar_estudiantes']) ? 1 : 0;

    // Insertar en la tabla evaluaciones
    $stmt = $mysqli->prepare("
        INSERT INTO evaluaciones 
        (curso_id, periodo, tipo_evaluacion, fecha, titulo, ponderacion, puntaje_maximo, puntaje_aprobatorio, descripcion, metodo_ingreso, notificar_estudiantes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'issssiiissi',
        $curso_id,
        $periodo,
        $tipo_evaluacion,
        $fecha,
        $titulo,
        $ponderacion,
        $puntaje_maximo,
        $puntaje_aprobatorio,
        $descripcion,
        $metodo_ingreso,
        $notificar_estudiantes
    );
    $stmt->execute();

    $evaluacion_id = $mysqli->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Evaluación guardada correctamente.',
        'evaluacion_id' => $evaluacion_id
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
