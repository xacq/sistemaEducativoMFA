<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['role_id'] != 2) {
    die("Acceso denegado.");
}

require_once '../../config.php';

$usuario_id = $_SESSION['usuario_id'];

// Obtener cursos del profesor
$stmt = $pdo->prepare("
    SELECT c.id, c.nombre, m.nombre AS materia
    FROM cursos c
    INNER JOIN profesores p ON c.profesor_id = p.id
    INNER JOIN usuarios u ON p.usuario_id = u.id
    INNER JOIN materias m ON c.materia_id = m.id
    WHERE u.id = :usuario
");
$stmt->execute([':usuario' => $usuario_id]);
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Informe Psicopedagógico - Profesor</title>
    <script src="/js/jquery.min.js"></script>
    <script src="/js/informes.js"></script>
</head>
<body>

<h2>Generar Informe Psicopedagógico (Estudiantes)</h2>

<label>Seleccione un curso:</label>
<select id="curso_id">
    <option value="">Seleccionar</option>
    <?php foreach ($cursos as $c): ?>
        <option value="<?= $c['id'] ?>">
            <?= $c['nombre'] ?> - <?= $c['materia'] ?>
        </option>
    <?php endforeach; ?>
</select>

<div id="contenedor_estudiantes"></div>

<hr>

<h3>Informe generado</h3>
<div id="resultado_informe" style="padding:20px;border:1px solid #ccc;"></div>

</body>
</html>
