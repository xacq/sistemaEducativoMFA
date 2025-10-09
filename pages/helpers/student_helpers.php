<?php
/**
 * Conjunto de funciones reutilizables para obtener información del contexto del estudiante
 * y simplificar las consultas utilizadas en los módulos de autoservicio.
 */

/**
 * Obtiene la información base de un estudiante a partir del identificador del usuario.
 *
 * @param mysqli $mysqli Conexión activa a la base de datos.
 * @param int    $userId Identificador del usuario autenticado.
 *
 * @return array{user: array{nombre: string, apellido: string, email: string}, student: (array{id: int, codigo_estudiante: string|null, fecha_nacimiento: string|null, genero: string|null, telefono: string|null, direccion: string|null, tutor_nombre: string|null, tutor_telefono: string|null}|null), matriculas: list<array{id: int, curso_id: int, curso_nombre: string, curso_codigo: string, profesor: string|null}>}
 */
function student_fetch_context(mysqli $mysqli, int $userId): array
{
    $context = [
        'user' => [
            'nombre' => '',
            'apellido' => '',
            'email' => '',
        ],
        'student' => null,
        'matriculas' => [],
    ];

    if ($stmt = $mysqli->prepare('SELECT nombre, apellido, email FROM usuarios WHERE id = ?')) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($nombre, $apellido, $email);
        if ($stmt->fetch()) {
            $context['user']['nombre'] = $nombre ?? '';
            $context['user']['apellido'] = $apellido ?? '';
            $context['user']['email'] = $email ?? '';
        }
        $stmt->close();
    }

    if ($stmt = $mysqli->prepare('SELECT id, codigo_estudiante, fecha_nacimiento, genero, telefono, direccion, tutor_nombre, tutor_telefono FROM estudiantes WHERE usuario_id = ? LIMIT 1')) {
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($id, $codigo, $fechaNacimiento, $genero, $telefono, $direccion, $tutorNombre, $tutorTelefono);
        if ($stmt->fetch()) {
            $context['student'] = [
                'id' => (int) $id,
                'codigo_estudiante' => $codigo,
                'fecha_nacimiento' => $fechaNacimiento,
                'genero' => $genero,
                'telefono' => $telefono,
                'direccion' => $direccion,
                'tutor_nombre' => $tutorNombre,
                'tutor_telefono' => $tutorTelefono,
            ];
        }
        $stmt->close();
    }

    if (!empty($context['student']['id'])) {
        $sqlMatriculas = "SELECT m.id, m.curso_id, c.nombre AS curso_nombre, c.codigo AS curso_codigo, CONCAT(u_prof.nombre, ' ', u_prof.apellido) AS profesor
                          FROM matriculas m
                          INNER JOIN cursos c ON c.id = m.curso_id
                          LEFT JOIN profesores p ON p.id = c.profesor_id
                          LEFT JOIN usuarios u_prof ON u_prof.id = p.usuario_id
                          WHERE m.estudiante_id = ?
                          ORDER BY c.nombre";
        if ($stmt = $mysqli->prepare($sqlMatriculas)) {
            $stmt->bind_param('i', $context['student']['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $context['matriculas'][] = [
                    'id' => (int) $row['id'],
                    'curso_id' => (int) $row['curso_id'],
                    'curso_nombre' => $row['curso_nombre'] ?? '',
                    'curso_codigo' => $row['curso_codigo'] ?? '',
                    'profesor' => $row['profesor'] ?: null,
                ];
            }
            $stmt->close();
        }
    }

    return $context;
}

/**
 * Calcula el porcentaje de asistencia ponderando las llegadas tarde como medio punto.
 *
 * @param mysqli   $mysqli       Conexión activa.
 * @param int|null $estudianteId Identificador del estudiante.
 */
function student_calculate_attendance(mysqli $mysqli, ?int $estudianteId): ?float
{
    if (!$estudianteId) {
        return null;
    }

    $sql = "SELECT COUNT(*) AS total_clases,
                   SUM(CASE WHEN estado = 'Presente' THEN 1
                            WHEN estado = 'Tarde' THEN 0.5
                            ELSE 0 END) AS asistencias
            FROM asistencia
            WHERE matricula_id IN (SELECT id FROM matriculas WHERE estudiante_id = ?)";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $estudianteId);
        $stmt->execute();
        $stmt->bind_result($totalClases, $asistencias);
        if ($stmt->fetch() && (int) $totalClases > 0) {
            $stmt->close();
            return round(((float) $asistencias / (float) $totalClases) * 100, 1);
        }
        $stmt->close();
    }

    return null;
}

/**
 * Obtiene el promedio general de calificaciones del estudiante.
 */
function student_calculate_average_grade(mysqli $mysqli, ?int $estudianteId): ?float
{
    if (!$estudianteId) {
        return null;
    }

    $sql = 'SELECT AVG(calificacion) FROM calificaciones WHERE matricula_id IN (SELECT id FROM matriculas WHERE estudiante_id = ?)';
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $estudianteId);
        $stmt->execute();
        $stmt->bind_result($averageGrade);
        if ($stmt->fetch() && $averageGrade !== null) {
            $stmt->close();
            return round((float) $averageGrade, 1);
        }
        $stmt->close();
    }

    return null;
}

/**
 * Retorna el listado de tareas clasificadas por estado (pendiente, entregada, calificada).
 *
 * @return array{all: list<array<string,mixed>>, pending: list<array<string,mixed>>, submitted: list<array<string,mixed>>, graded: list<array<string,mixed>>}
 */
function student_fetch_tasks_with_status(mysqli $mysqli, ?int $estudianteId): array
{
    $buckets = [
        'all' => [],
        'pending' => [],
        'submitted' => [],
        'graded' => [],
    ];

    if (!$estudianteId) {
        return $buckets;
    }

    $sql = "SELECT t.id, t.titulo, t.descripcion, t.fecha_asignacion, t.fecha_entrega,
                   c.nombre AS curso_nombre,
                   m.id AS matricula_id,
                   te.id AS entrega_id, te.fecha_envio, te.file_path, te.comentario,
                   (SELECT cal.calificacion
                      FROM calificaciones cal
                      INNER JOIN evaluaciones ev ON ev.id = cal.evaluacion_id
                     WHERE cal.matricula_id = m.id
                       AND ev.curso_id = t.curso_id
                       AND ev.titulo = t.titulo
                     ORDER BY cal.fecha DESC, cal.id DESC
                     LIMIT 1) AS calificacion,
                   (SELECT cal.fecha
                      FROM calificaciones cal
                      INNER JOIN evaluaciones ev ON ev.id = cal.evaluacion_id
                     WHERE cal.matricula_id = m.id
                       AND ev.curso_id = t.curso_id
                       AND ev.titulo = t.titulo
                     ORDER BY cal.fecha DESC, cal.id DESC
                     LIMIT 1) AS fecha_calificacion,
                   (SELECT cal.comentario
                      FROM calificaciones cal
                      INNER JOIN evaluaciones ev ON ev.id = cal.evaluacion_id
                     WHERE cal.matricula_id = m.id
                       AND ev.curso_id = t.curso_id
                       AND ev.titulo = t.titulo
                     ORDER BY cal.fecha DESC, cal.id DESC
                     LIMIT 1) AS retroalimentacion
            FROM tareas t
            INNER JOIN matriculas m ON m.curso_id = t.curso_id AND m.estudiante_id = ?
            INNER JOIN cursos c ON c.id = t.curso_id
            LEFT JOIN tarea_entregas te ON te.tarea_id = t.id AND te.matricula_id = m.id
            ORDER BY t.fecha_entrega ASC, t.id ASC";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $estudianteId);
        $stmt->execute();
        $result = $stmt->get_result();
        $today = new DateTimeImmutable('today');

        while ($row = $result->fetch_assoc()) {
            $task = [
                'id' => (int) $row['id'],
                'titulo' => $row['titulo'] ?? '',
                'descripcion' => $row['descripcion'] ?? '',
                'fecha_asignacion' => $row['fecha_asignacion'] ?? null,
                'fecha_entrega' => $row['fecha_entrega'] ?? null,
                'curso_nombre' => $row['curso_nombre'] ?? '',
                'matricula_id' => (int) $row['matricula_id'],
                'entrega_id' => $row['entrega_id'] !== null ? (int) $row['entrega_id'] : null,
                'fecha_envio' => $row['fecha_envio'],
                'file_path' => $row['file_path'] ?? '',
                'comentario' => $row['comentario'] ?? '',
                'calificacion' => $row['calificacion'] !== null ? (float) $row['calificacion'] : null,
                'fecha_calificacion' => $row['fecha_calificacion'] ?? null,
                'retroalimentacion' => $row['retroalimentacion'] ?? null,
                'status' => 'pending',
                'status_label' => 'Pendiente',
                'status_badge' => 'bg-info',
            ];

            $dueDate = !empty($task['fecha_entrega']) ? DateTimeImmutable::createFromFormat('Y-m-d', $task['fecha_entrega']) : null;

            if ($task['entrega_id']) {
                $task['status'] = 'submitted';
                $task['status_label'] = 'Entregada';
                $task['status_badge'] = 'bg-primary';
                if ($task['calificacion'] !== null) {
                    $task['status'] = 'graded';
                    $task['status_label'] = 'Calificada';
                    $task['status_badge'] = 'bg-success';
                }
            } elseif ($dueDate && $dueDate < $today) {
                $task['status_badge'] = 'bg-danger';
                $task['status_label'] = 'Atrasada';
            }

            $buckets['all'][] = $task;
            $buckets[$task['status']][] = $task;
        }
        $stmt->close();
    }

    return $buckets;
}

/**
 * Obtiene el identificador de la matrícula asociada a una tarea para el estudiante dado.
 */
function student_find_matricula_for_task(mysqli $mysqli, int $estudianteId, int $taskId): ?int
{
    $sql = 'SELECT m.id FROM matriculas m INNER JOIN tareas t ON t.curso_id = m.curso_id WHERE t.id = ? AND m.estudiante_id = ? LIMIT 1';
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('ii', $taskId, $estudianteId);
        $stmt->execute();
        $stmt->bind_result($matriculaId);
        if ($stmt->fetch()) {
            $stmt->close();
            return (int) $matriculaId;
        }
        $stmt->close();
    }

    return null;
}

/**
 * Recupera el horario del día y determina el estado de cada clase con base en la hora actual.
 *
 * @param mysqli                $mysqli Conexión.
 * @param int|null              $estudianteId Identificador del estudiante.
 * @param DateTimeImmutable|null $now Momento de referencia para calcular estados.
 *
 * @return list<array<string,mixed>>
 */
function student_fetch_today_schedule(mysqli $mysqli, ?int $estudianteId, ?DateTimeImmutable $now = null): array
{
    if (!$estudianteId) {
        return [];
    }

    $now = $now ?: new DateTimeImmutable();
    $today = strtolower($now->format('l'));
    $schedule = [];

    $sql = "SELECT h.hora_inicio, h.hora_fin, h.aula, c.nombre AS curso_nombre,
                   CONCAT(u_prof.nombre, ' ', u_prof.apellido) AS profesor
            FROM horarios h
            INNER JOIN cursos c ON c.id = h.curso_id
            INNER JOIN matriculas m ON m.curso_id = h.curso_id AND m.estudiante_id = ?
            LEFT JOIN profesores p ON p.id = c.profesor_id
            LEFT JOIN usuarios u_prof ON u_prof.id = p.usuario_id
            WHERE h.dia = ?
            ORDER BY h.hora_inicio";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('is', $estudianteId, $today);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $start = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d') . ' ' . $row['hora_inicio']);
            $end = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d') . ' ' . $row['hora_fin']);
            $status = 'Pendiente';
            $badgeClass = 'bg-warning';

            if ($start && $end) {
                if ($now > $end) {
                    $status = 'Completada';
                    $badgeClass = 'bg-success';
                } elseif ($now >= $start && $now <= $end) {
                    $status = 'En curso';
                    $badgeClass = 'bg-primary';
                }
            }

            $schedule[] = [
                'curso_nombre' => $row['curso_nombre'] ?? '',
                'aula' => $row['aula'] ?? '',
                'profesor' => $row['profesor'] ?: 'Sin asignar',
                'hora_inicio' => $row['hora_inicio'] ?? '',
                'hora_fin' => $row['hora_fin'] ?? '',
                'status' => $status,
                'badge_class' => $badgeClass,
            ];
        }
        $stmt->close();
    }

    return $schedule;
}

/**
 * Devuelve las calificaciones más recientes asociadas al estudiante.
 *
 * @return list<array{calificacion: float, fecha: string, titulo: string|null, curso_nombre: string|null}>
 */
function student_fetch_recent_grades(mysqli $mysqli, ?int $estudianteId, int $limit = 5): array
{
    if (!$estudianteId) {
        return [];
    }

    $grades = [];
    $sql = "SELECT cal.calificacion, cal.fecha, ev.titulo, c.nombre AS curso_nombre
            FROM calificaciones cal
            INNER JOIN matriculas m ON m.id = cal.matricula_id
            INNER JOIN evaluaciones ev ON ev.id = cal.evaluacion_id
            LEFT JOIN cursos c ON c.id = ev.curso_id
            WHERE m.estudiante_id = ?
            ORDER BY cal.fecha DESC, cal.id DESC
            LIMIT $limit";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $estudianteId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $grades[] = [
                'calificacion' => isset($row['calificacion']) ? (float) $row['calificacion'] : 0.0,
                'fecha' => $row['fecha'] ?? '',
                'titulo' => $row['titulo'] ?? null,
                'curso_nombre' => $row['curso_nombre'] ?? null,
            ];
        }
        $stmt->close();
    }

    return $grades;
}
