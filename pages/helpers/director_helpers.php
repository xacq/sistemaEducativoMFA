<?php
require_once __DIR__ . '/flash.php';

function director_get_identity(mysqli $mysqli, int $userId): array
{
    $stmt = $mysqli->prepare('SELECT nombre, apellido FROM usuarios WHERE id = ? LIMIT 1');
    if (!$stmt) {
        return ['nombre' => '', 'apellido' => ''];
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $stmt->bind_result($nombre, $apellido);
    $stmt->fetch();
    $stmt->close();
    return ['nombre' => $nombre ?? '', 'apellido' => $apellido ?? ''];
}

function director_get_grades(mysqli $mysqli): array
{
    $grados = [];
    if ($result = $mysqli->query('SELECT id, nombre FROM grados ORDER BY id')) {
        while ($row = $result->fetch_assoc()) {
            $grados[] = $row;
        }
        $result->free();
    }
    return $grados;
}

function director_get_materias(mysqli $mysqli): array
{
    $materias = [];
    $sql = 'SELECT m.id, m.nombre, COUNT(c.id) AS cursos_activos
            FROM materias m
            LEFT JOIN cursos c ON c.materia_id = m.id
            GROUP BY m.id, m.nombre
            ORDER BY m.nombre';
    if ($result = $mysqli->query($sql)) {
        while ($row = $result->fetch_assoc()) {
            $materias[] = $row;
        }
        $result->free();
    }
    return $materias;
}

function director_get_courses(mysqli $mysqli, ?int $gradoId = null): array
{
    $cursos = [];
    $sql = 'SELECT c.id, c.nombre, c.codigo, c.grado_id, c.seccion, c.capacidad, c.descripcion,
                   CONCAT(u.nombre, " ", u.apellido) AS profesor
            FROM cursos c
            JOIN profesores p ON c.profesor_id = p.id
            JOIN usuarios u ON p.usuario_id = u.id';
    $types = '';
    $params = [];
    if ($gradoId) {
        $sql .= ' WHERE c.grado_id = ?';
        $types .= 'i';
        $params[] = $gradoId;
    }
    $sql .= ' ORDER BY c.grado_id, c.nombre';
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $cursos[] = $row;
        }
        $stmt->close();
    }
    return $cursos;
}

function director_get_students(mysqli $mysqli, ?int $gradoId = null, ?string $busqueda = null): array
{
    $estudiantes = [];
    $sql = 'SELECT e.id, e.codigo_estudiante, e.grado_id,
                   CONCAT(u.nombre, " ", u.apellido) AS nombre_completo
            FROM estudiantes e
            JOIN usuarios u ON e.usuario_id = u.id';
    $where = [];
    $params = [];
    $types = '';
    if ($gradoId) {
        $where[] = 'e.grado_id = ?';
        $params[] = $gradoId;
        $types .= 'i';
    }
    if ($busqueda) {
        $where[] = '(u.nombre LIKE ? OR u.apellido LIKE ? OR e.codigo_estudiante LIKE ?)';
        $like = "%{$busqueda}%";
        $params[] = $like;
        $params[] = $like;
        $params[] = $like;
        $types .= 'sss';
    }
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY u.apellido, u.nombre';
    $stmt = $mysqli->prepare($sql);
    if ($stmt) {
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $estudiantes[] = $row;
        }
        $stmt->close();
    }
    return $estudiantes;
}

function director_validate_horario(mysqli $mysqli, int $cursoId, string $dia, string $horaInicio, string $horaFin, string $aula, ?int $excludeId = null): array
{
    $errores = [];
    if ($horaFin <= $horaInicio) {
        $errores[] = 'La hora de fin debe ser mayor a la hora de inicio.';
    }
    $stmtCurso = $mysqli->prepare('SELECT profesor_id FROM cursos WHERE id = ?');
    if (!$stmtCurso) {
        $errores[] = 'No se pudo validar el curso seleccionado.';
        return $errores;
    }
    $stmtCurso->bind_param('i', $cursoId);
    $stmtCurso->execute();
    $stmtCurso->bind_result($profesorId);
    if (!$stmtCurso->fetch()) {
        $errores[] = 'El curso seleccionado no existe.';
        $stmtCurso->close();
        return $errores;
    }
    $stmtCurso->close();

    $excludeSql = $excludeId ? ' AND id <> ?' : '';

    $sqlCurso = "SELECT COUNT(*) FROM horarios WHERE curso_id = ? AND dia = ?{$excludeSql} AND NOT (? >= hora_fin OR ? <= hora_inicio)";
    $stmt = $mysqli->prepare($sqlCurso);
    if ($excludeId) {
        $stmt->bind_param('isssi', $cursoId, $dia, $horaInicio, $horaFin, $excludeId);
    } else {
        $stmt->bind_param('isss', $cursoId, $dia, $horaInicio, $horaFin);
    }
    $stmt->execute();
    $stmt->bind_result($totalCurso);
    $stmt->fetch();
    $stmt->close();
    if ($totalCurso > 0) {
        $errores[] = 'El curso ya tiene un horario solapado en ese día.';
    }

    $sqlProfesor = "SELECT COUNT(*) FROM horarios h
                    JOIN cursos c ON h.curso_id = c.id
                    WHERE c.profesor_id = ? AND h.dia = ?{$excludeSql}
                      AND NOT (? >= h.hora_fin OR ? <= h.hora_inicio)";
    $stmt = $mysqli->prepare($sqlProfesor);
    if ($excludeId) {
        $stmt->bind_param('isssi', $profesorId, $dia, $horaInicio, $horaFin, $excludeId);
    } else {
        $stmt->bind_param('isss', $profesorId, $dia, $horaInicio, $horaFin);
    }
    $stmt->execute();
    $stmt->bind_result($totalProfesor);
    $stmt->fetch();
    $stmt->close();
    if ($totalProfesor > 0) {
        $errores[] = 'El profesor asignado tiene otro curso en ese horario.';
    }

    $sqlAula = "SELECT COUNT(*) FROM horarios WHERE aula = ? AND dia = ?{$excludeSql} AND NOT (? >= hora_fin OR ? <= hora_inicio)";
    $stmt = $mysqli->prepare($sqlAula);
    if ($excludeId) {
        $stmt->bind_param('isssi', $aula, $dia, $horaInicio, $horaFin, $excludeId);
    } else {
        $stmt->bind_param('isss', $aula, $dia, $horaInicio, $horaFin);
    }
    $stmt->execute();
    $stmt->bind_result($totalAula);
    $stmt->fetch();
    $stmt->close();
    if ($totalAula > 0) {
        $errores[] = 'El aula ya está reservada en ese horario.';
    }

    return $errores;
}

function director_check_prerequisitos(mysqli $mysqli, int $estudianteId, int $cursoId): ?string
{
    $stmtCurso = $mysqli->prepare('SELECT materia_id, grado_id FROM cursos WHERE id = ?');
    if (!$stmtCurso) {
        return 'No se pudo validar el curso seleccionado.';
    }
    $stmtCurso->bind_param('i', $cursoId);
    $stmtCurso->execute();
    $stmtCurso->bind_result($materiaId, $gradoId);
    if (!$stmtCurso->fetch()) {
        $stmtCurso->close();
        return 'El curso seleccionado no existe.';
    }
    $stmtCurso->close();

    if ($gradoId <= 1) {
        return null;
    }
    $gradoPrevio = $gradoId - 1;
    $stmtPrevio = $mysqli->prepare('SELECT id FROM cursos WHERE materia_id = ? AND grado_id = ? LIMIT 1');
    $stmtPrevio->bind_param('ii', $materiaId, $gradoPrevio);
    $stmtPrevio->execute();
    $stmtPrevio->bind_result($cursoPrevioId);
    if (!$stmtPrevio->fetch()) {
        $stmtPrevio->close();
        return null;
    }
    $stmtPrevio->close();

    $sqlPromedio = 'SELECT AVG(calificacion) FROM calificaciones cal
                    JOIN matriculas m ON cal.matricula_id = m.id
                    WHERE m.estudiante_id = ? AND m.curso_id = ?';
    $stmtProm = $mysqli->prepare($sqlPromedio);
    $stmtProm->bind_param('ii', $estudianteId, $cursoPrevioId);
    $stmtProm->execute();
    $stmtProm->bind_result($promedio);
    $stmtProm->fetch();
    $stmtProm->close();

    if ($promedio === null) {
        return 'El estudiante no tiene registrado el curso prerequisito.';
    }
    if ((float)$promedio < 60) {
        return 'El estudiante no aprobó el curso prerequisito requerido.';
    }
    return null;
}
