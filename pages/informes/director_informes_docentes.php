<?php
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['role_id'] != 1) {
    die("Solo el Director puede acceder.");
}

require_once '../../config.php';

// Obtener todos los profesores
$stmt = $pdo->query("
    SELECT p.id, u.nombre 
    FROM profesores p
    INNER JOIN usuarios u ON p.usuario_id = u.id
");
$profesores = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Informe Docente - Director</title>
    <script src="/js/jquery.min.js"></script>
    <script src="/js/informes.js"></script>
</head>
<body>

<h2>Informe Psicopedagógico del Docente</h2>

<select id="profesor_id">
    <option value="">Seleccionar profesor</option>
    <?php foreach ($profesores as $p): ?>
        <option value="<?= $p['id'] ?>">
            <?= $p['nombre'] ?>
        </option>
    <?php endforeach; ?>
</select>

<button id="btn_generar_docente">Generar Informe Docente</button>

<hr>

<h3>Informe generado</h3>
<div id="resultado_informe" style="padding:20px;border:1px solid #ccc;"></div>

</body>
</html>
