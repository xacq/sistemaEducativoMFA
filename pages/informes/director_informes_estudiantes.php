<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['role_id'] != 1) {
    die("Solo el Director puede acceder.");
}

require_once '../../config.php';

// Obtener todos los cursos
$stmt = $pdo->query("
    SELECT c.id, c.nombre, m.nombre AS materia
    FROM cursos c
    INNER JOIN materias m ON c.materia_id = m.id
");
$cursos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Informes Psicopedagógicos - Director</title>
    <script src="/js/jquery.min.js"></script>
    <script src="/js/informes.js"></script>
</head>
<body>

<h2>Informe Psicopedagógico de Estudiantes</h2>

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
