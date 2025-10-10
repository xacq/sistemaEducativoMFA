<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';

// --- INICIALIZACIÓN DE VARIABLES ---
$perfil_profesor = null;
$materias_asignadas = [];
$cursos_asignados = [];
$stats = [
    'total_cursos' => 0,
    'total_estudiantes' => 0,
    'anios_servicio' => 0
];
$usuario_id = $_SESSION['user_id'];

// --- LÓGICA DE DATOS ---

// 1. OBTENER TODOS LOS DATOS DEL PERFIL DEL PROFESOR
$sql_perfil = "
    SELECT 
        u.nombre, u.apellido, u.email,
        p.id AS profesor_id, p.cedula, p.fecha_nacimiento, p.departamento, p.cargo, p.fecha_inicio,
        p.tipo_contrato, p.codigo_empleado, p.titulo_academico, p.universidad, p.especializacion,
        p.anio_graduacion, p.otros_titulos, p.direccion, p.formacion_academica, p.foto_perfil, p.telefono
    FROM usuarios u
    JOIN profesores p ON u.id = p.usuario_id
    WHERE u.id = ?
";
$stmt = $mysqli->prepare($sql_perfil);
$stmt->bind_param('i', $usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$perfil_profesor = $result->fetch_assoc();
$stmt->close();

if (!$perfil_profesor) {
    die("Error: No se pudo encontrar el perfil del profesor.");
}

$profesor_id = $perfil_profesor['profesor_id'];

// 2. OBTENER MATERIAS QUE IMPARTE
$sql_materias = "
    SELECT m.nombre 
    FROM materias m
    JOIN profesor_materias pm ON m.id = pm.materia_id
    WHERE pm.profesor_id = ?
    ORDER BY m.nombre
";
$stmt_materias = $mysqli->prepare($sql_materias);
$stmt_materias->bind_param('i', $profesor_id);
$stmt_materias->execute();
$result_materias = $stmt_materias->get_result();
while($row = $result_materias->fetch_assoc()) {
    $materias_asignadas[] = $row['nombre'];
}
$stmt_materias->close();

// 3. OBTENER CURSOS ASIGNADOS
$sql_cursos = "SELECT nombre FROM cursos WHERE profesor_id = ? ORDER BY nombre";
$stmt_cursos = $mysqli->prepare($sql_cursos);
$stmt_cursos->bind_param('i', $profesor_id);
$stmt_cursos->execute();
$result_cursos = $stmt_cursos->get_result();
while($row = $result_cursos->fetch_assoc()) {
    $cursos_asignados[] = $row['nombre'];
}
$stmt_cursos->close();


// 4. CALCULAR ESTADÍSTICAS
// a) Total de cursos (ya lo tenemos)
$stats['total_cursos'] = count($cursos_asignados);

// b) Total de estudiantes únicos
$sql_estudiantes = "
    SELECT COUNT(DISTINCT m.estudiante_id) as total
    FROM matriculas m
    JOIN cursos c ON m.curso_id = c.id
    WHERE c.profesor_id = ?
";
$stmt_estudiantes = $mysqli->prepare($sql_estudiantes);
$stmt_estudiantes->bind_param('i', $profesor_id);
$stmt_estudiantes->execute();
$result_estudiantes = $stmt_estudiantes->get_result();
if($row = $result_estudiantes->fetch_assoc()){
    $stats['total_estudiantes'] = $row['total'];
}
$stmt_estudiantes->close();

// c) Años de servicio
if (!empty($perfil_profesor['fecha_inicio'])) {
    $fecha_inicio = new DateTime($perfil_profesor['fecha_inicio']);
    $hoy = new DateTime();
    $stats['anios_servicio'] = $hoy->diff($fecha_inicio)->y;
}


// --- FIN LÓGICA ---
include __DIR__ . '/side_bar_profesor.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Mi Perfil</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Mi Perfil</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($perfil_profesor['nombre'] . ' ' . $perfil_profesor['apellido']); ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="profesor_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Profile Content -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <?php $foto_path = !empty($perfil_profesor['foto_perfil']) && strpos($perfil_profesor['foto_perfil'], 'http') === 0 ? $perfil_profesor['foto_perfil'] : 'https://via.placeholder.com/150'; ?>
                                <img src="<?php echo htmlspecialchars($foto_path); ?>" class="rounded-circle mb-3" alt="Foto de perfil" style="width: 150px; height: 150px; object-fit: cover;">
                                <h5 class="card-title"><?php echo htmlspecialchars($perfil_profesor['nombre'] . ' ' . $perfil_profesor['apellido']); ?></h5>
                                <p class="card-text text-muted"><?php echo htmlspecialchars($perfil_profesor['cargo']); ?></p>
                                <p class="card-text">
                                    <?php foreach($materias_asignadas as $materia): ?>
                                        <span class="badge bg-primary me-1"><?php echo htmlspecialchars($materia); ?></span>
                                    <?php endforeach; ?>
                                </p>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">Cursos Asignados<span class="badge bg-academic rounded-pill"><?php echo $stats['total_cursos']; ?></span></li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">Estudiantes Totales<span class="badge bg-academic rounded-pill"><?php echo $stats['total_estudiantes']; ?></span></li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">Años de Servicio<span class="badge bg-academic rounded-pill"><?php echo $stats['anios_servicio']; ?></span></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Información Personal</h5></div>
                            <div class="card-body"><form>
                                <div class="row mb-3">
                                    <div class="col-md-6"><label class="form-label">Nombre</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($perfil_profesor['nombre']); ?>" readonly></div>
                                    <div class="col-md-6"><label class="form-label">Apellido</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($perfil_profesor['apellido']); ?>" readonly></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6"><label class="form-label">Correo Electrónico</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($perfil_profesor['email']); ?>" readonly></div>
                                    <div class="col-md-6"><label class="form-label">Teléfono</label><input type="tel" class="form-control" value="<?php echo htmlspecialchars($perfil_profesor['telefono']); ?>" readonly></div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6"><label class="form-label">Fecha de Nacimiento</label><input type="text" class="form-control" value="<?php echo date("d/m/Y", strtotime($perfil_profesor['fecha_nacimiento'])); ?>" readonly></div>
                                    <div class="col-md-6"><label class="form-label">Número de Identidad</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($perfil_profesor['cedula']); ?>" readonly></div>
                                </div>
                                <div class="mb-3"><label class="form-label">Dirección</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($perfil_profesor['direccion']); ?>" readonly></div>
                            </form></div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Información Académica</h5></div>
                            <div class="card-body"><form>
                                <div class="mb-3"><label class="form-label">Formación Principal</label><textarea class="form-control" rows="2" readonly><?php echo htmlspecialchars($perfil_profesor['formacion_academica']); ?></textarea></div>
                            </form></div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header card-header-academic"><h5 class="mb-0 text-white">Información Laboral</h5></div>
                            <div class="card-body"><form>
                                <div class="row mb-3">
                                    <div class="col-md-6"><label class="form-label">Cargo</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($perfil_profesor['cargo']); ?>" readonly></div>
                                    <div class="col-md-6"><label class="form-label">Departamento</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($perfil_profesor['departamento']); ?>" readonly></div>
                                </div>
                                <div class="mb-3"><label class="form-label">Cursos Asignados</label><textarea class="form-control" rows="3" readonly><?php echo htmlspecialchars(implode("\n", $cursos_asignados)); ?></textarea></div>
                            </form></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>