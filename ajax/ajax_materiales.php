<?php
require_once __DIR__ . '/../config.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sesión expirada.']);
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Obtener ID del profesor
$stmt = $mysqli->prepare('SELECT id FROM profesores WHERE usuario_id = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->bind_result($profesorId);
$stmt->fetch();
$stmt->close();

if (!$profesorId) {
    echo json_encode(['error' => 'No se encontró el perfil del profesor.']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get':
        $id = (int)$_POST['id'];
        $stmt = $mysqli->prepare('
            SELECT m.*, c.nombre AS curso_nombre 
            FROM materiales m
            JOIN cursos c ON m.curso_id = c.id
            WHERE m.id = ? AND m.profesor_id = ?
        ');
        $stmt->bind_param('ii', $id, $profesorId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        echo json_encode($res);
        break;

    case 'delete':
        $id = (int)$_POST['id'];
        $stmt = $mysqli->prepare('SELECT file_path FROM materiales WHERE id = ? AND profesor_id = ?');
        $stmt->bind_param('ii', $id, $profesorId);
        $stmt->execute();
        $stmt->bind_result($filePath);
        if ($stmt->fetch()) {
            $stmt->close();
            $del = $mysqli->prepare('DELETE FROM materiales WHERE id = ?');
            $del->bind_param('i', $id);
            $del->execute();
            if ($del->affected_rows > 0 && $filePath && file_exists(dirname(__DIR__) . '/' . $filePath)) {
                unlink(dirname(__DIR__) . '/' . $filePath);
            }
            echo json_encode(['success' => true, 'message' => '✅ Material eliminado correctamente.']);
        } else {
            echo json_encode(['error' => 'Material no encontrado o no autorizado.']);
        }
        break;

    case 'toggle':
        $id = (int)$_POST['id'];
        $stmt = $mysqli->prepare('UPDATE materiales SET share_with_students = 1 - share_with_students WHERE id = ? AND profesor_id = ?');
        $stmt->bind_param('ii', $id, $profesorId);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => '👁️ Estado de visibilidad actualizado.']);
        break;

    /* NUEVO: filtro dinámico */
    case 'filter':
        $curso    = $_POST['curso'] ?? '';
        $tipo     = $_POST['tipo'] ?? '';
        $unidad   = $_POST['unidad'] ?? '';
        $busqueda = $_POST['busqueda'] ?? '';

        $sql = "SELECT m.id, m.titulo, m.tipo, m.unidad, m.fecha_subida,
                       c.nombre AS curso_nombre, c.codigo,
                       m.share_with_students, m.file_path, m.url
                FROM materiales m
                JOIN cursos c ON m.curso_id = c.id
                WHERE m.profesor_id = ?";
        $params = [$profesorId];
        $types  = 'i';

        if ($curso !== '') {
            $sql .= " AND m.curso_id = ?";
            $params[] = $curso;
            $types   .= 'i';
        }
        if ($tipo !== '') {
            $sql .= " AND m.tipo = ?";
            $params[] = $tipo;
            $types   .= 's';
        }
        if ($unidad !== '') {
            $sql .= " AND m.unidad = ?";
            $params[] = $unidad;
            $types   .= 's';
        }
        if ($busqueda !== '') {
            $sql .= " AND m.titulo LIKE ?";
            $params[] = "%$busqueda%";
            $types   .= 's';
        }

        $sql .= " ORDER BY m.fecha_subida DESC";
        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta.']);
            exit;
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $html = '';
        while ($row = $result->fetch_assoc()) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($row['titulo']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['curso_nombre'] . ' (' . $row['codigo'] . ')') . '</td>';
            $html .= '<td>' . htmlspecialchars($row['tipo']) . '</td>';
            $html .= '<td>' . htmlspecialchars($row['unidad']) . '</td>';
            $html .= '<td>';
            $html .= ((int)$row['share_with_students'] === 1)
                ? '<span class="badge bg-success">Visible</span>'
                : '<span class="badge bg-secondary">Oculto</span>';
            $html .= '</td>';
            $html .= '<td>' . htmlspecialchars($row['fecha_subida']) . '</td>';
            $html .= '<td class="text-end">';

            if ($row['file_path']) {
                $html .= '<a href="../' . htmlspecialchars($row['file_path']) . '" class="btn btn-sm btn-outline-primary" target="_blank" title="Descargar archivo"><i class="bi bi-download"></i></a>';
            } elseif ($row['url']) {
                $html .= '<a href="' . htmlspecialchars($row['url']) . '" class="btn btn-sm btn-outline-primary" target="_blank" title="Abrir enlace"><i class="bi bi-box-arrow-up-right"></i></a>';
            }

            $html .= '</td></tr>';
        }

        echo json_encode(['success' => true, 'html' => $html]);
        break;

    default:
        echo json_encode(['error' => 'Acción no reconocida.']);
        break;
}
