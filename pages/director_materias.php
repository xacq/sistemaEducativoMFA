<?php
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once '../config.php';
require_once __DIR__ . '/helpers/director_helpers.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    if ($action === 'create') {
        if ($nombre === '') {
            flash_push('error', 'El nombre de la materia es obligatorio.');
        } else {
            $stmt = $mysqli->prepare('SELECT id FROM materias WHERE nombre = ? LIMIT 1');
            $stmt->bind_param('s', $nombre);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                flash_push('error', 'Ya existe una materia con el mismo nombre.');
            } else {
                $insert = $mysqli->prepare('INSERT INTO materias (nombre) VALUES (?)');
                if ($insert) {
                    $insert->bind_param('s', $nombre);
                    if ($insert->execute()) {
                        flash_push('success', 'Materia registrada correctamente.');
                    } else {
                        flash_push('error', 'No se pudo registrar la materia.');
                    }
                    $insert->close();
                } else {
                    flash_push('error', 'No se pudo preparar la inserción de la materia.');
                }
            }
            $stmt->close();
        }
        header('Location: director_materias.php');
        exit;
    }

    if ($action === 'update') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            flash_push('error', 'La materia a actualizar no es válida.');
        } elseif ($nombre === '') {
            flash_push('error', 'El nombre de la materia es obligatorio.');
        } else {
            $stmt = $mysqli->prepare('SELECT id FROM materias WHERE nombre = ? AND id <> ? LIMIT 1');
            $stmt->bind_param('si', $nombre, $id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                flash_push('error', 'Ya existe otra materia con ese nombre.');
            } else {
                $update = $mysqli->prepare('UPDATE materias SET nombre = ? WHERE id = ?');
                if ($update) {
                    $update->bind_param('si', $nombre, $id);
                    if ($update->execute()) {
                        flash_push('success', 'Materia actualizada correctamente.');
                    } else {
                        flash_push('error', 'No se pudo actualizar la materia.');
                    }
                    $update->close();
                } else {
                    flash_push('error', 'No se pudo preparar la actualización.');
                }
            }
            $stmt->close();
        }
        header('Location: director_materias.php');
        exit;
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            flash_push('error', 'La materia a eliminar no es válida.');
        } else {
            $stmtCursos = $mysqli->prepare('SELECT COUNT(*) FROM cursos WHERE materia_id = ?');
            $stmtCursos->bind_param('i', $id);
            $stmtCursos->execute();
            $stmtCursos->bind_result($totalCursos);
            $stmtCursos->fetch();
            $stmtCursos->close();
            if ($totalCursos > 0) {
                flash_push('error', 'No se puede eliminar la materia porque tiene cursos asociados.');
            } else {
                $delete = $mysqli->prepare('DELETE FROM materias WHERE id = ?');
                if ($delete) {
                    $delete->bind_param('i', $id);
                    if ($delete->execute()) {
                        flash_push('success', 'Materia eliminada correctamente.');
                    } else {
                        flash_push('error', 'No se pudo eliminar la materia.');
                    }
                    $delete->close();
                } else {
                    flash_push('error', 'No se pudo preparar la eliminación.');
                }
            }
        }
        header('Location: director_materias.php');
        exit;
    }
}

$identity = director_get_identity($mysqli, (int)$_SESSION['user_id']);
$materias = director_get_materias($mysqli);
$messages = flash_consume();
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
include __DIR__ . '/side_bar_director.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestión de Materias</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Materias</h1>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars(trim(($identity['nombre'] ?? '') . ' ' . ($identity['apellido'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                        <li><a class="dropdown-item" href="director_configuracion.php">Configuración</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Cerrar Sesión</a></li>
                    </ul>
                </div>
            </div>

            <?php foreach (['success' => 'success', 'error' => 'danger', 'warning' => 'warning'] as $type => $bootstrap): ?>
                <?php foreach ($messages[$type] as $message): ?>
                    <div class="alert alert-<?php echo $bootstrap; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <div class="card mb-4">
                <div class="card-header card-header-academic text-white">
                    <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Nueva materia</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="row g-3">
                        <input type="hidden" name="action" value="create">
                        <div class="col-md-8">
                            <label for="materiaNombre" class="form-label">Nombre de la materia</label>
                            <input type="text" class="form-control" name="nombre" id="materiaNombre" maxlength="100" required>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="bi bi-save me-1"></i>Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header card-header-academic text-white">
                    <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Materias registradas</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-academic">
                            <tr>
                                <th>Nombre</th>
                                <th>Cursos asociados</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($materias)): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No hay materias registradas.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($materias as $materia): ?>
                                <tr>
                                    <td>
                                        <?php if ($editId === (int)$materia['id']): ?>
                                            <form method="post" class="row g-2">
                                                <input type="hidden" name="action" value="update">
                                                <input type="hidden" name="id" value="<?php echo (int)$materia['id']; ?>">
                                                <div class="col-12 col-md-8">
                                                    <input type="text" class="form-control" name="nombre" maxlength="100" value="<?php echo htmlspecialchars($materia['nombre'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                                </div>
                                                <div class="col-12 col-md-4 d-flex gap-2">
                                                    <button type="submit" class="btn btn-primary w-50">Guardar</button>
                                                    <a href="director_materias.php" class="btn btn-outline-secondary w-50">Cancelar</a>
                                                </div>
                                            </form>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($materia['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo (int)$materia['cursos_activos']; ?></td>
                                    <td class="text-end">
                                        <?php if ($editId !== (int)$materia['id']): ?>
                                            <a href="?edit=<?php echo (int)$materia['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                                <i class="bi bi-pencil"></i> Editar
                                            </a>
                                            <form method="post" class="d-inline" onsubmit="return confirm('¿Desea eliminar esta materia?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int)$materia['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i> Eliminar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
