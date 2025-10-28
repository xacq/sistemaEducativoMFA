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
function student_fetch_tasks_with_status(mysqli $mysqli, int $estudianteId): array {
    // Toma todas las tareas de las matrículas activas del estudiante
    // y calcula estado: pendiente / entregada / calificada,
    // además de incluir detalles de la calificación si existen.

    // OJO: ajusta los nombres de tus tablas/joins si en tu esquema real difieren:
    //  - tareas t (id, titulo, descripcion, fecha_limite, curso_id, ...)
    //  - cursos c (id, nombre)
    //  - matriculas m (id, estudiante_id, curso_id)
    //  - tarea_entregas te (id, tarea_id, matricula_id, comentario, file_path, fecha_envio)
    //  - calificaciones_tareas ct (id, tarea_entrega_id, profesor_id, calificacion, comentario, fecha_calificacion)
    //  - profesores p JOIN usuarios u (para nombre del profesor)

    $sql = "
        SELECT
            t.id AS tarea_id,
            t.titulo AS tarea_titulo,
            t.descripcion AS tarea_descripcion,
            t.fecha_limite,
            c.nombre AS curso_nombre,

            te.id AS entrega_id,
            te.file_path,
            te.fecha_envio,
            te.comentario AS entrega_comentario,

            ct.id AS calif_id,
            ct.calificacion,
            ct.comentario AS calif_comentario,
            ct.fecha_calificacion,

            CONCAT(u.nombre, ' ', u.apellido) AS profesor_nombre
        FROM tareas t
        INNER JOIN cursos c            ON c.id = t.curso_id
        INNER JOIN matriculas m        ON m.curso_id = c.id
        LEFT JOIN tarea_entregas te    ON te.tarea_id = t.id AND te.matricula_id = m.id
        LEFT JOIN calificaciones_tareas ct ON ct.tarea_entrega_id = te.id
        LEFT JOIN profesores p         ON p.id = ct.profesor_id
        LEFT JOIN usuarios u           ON u.id = p.usuario_id
        WHERE m.estudiante_id = ?
        ORDER BY t.fecha_limite IS NULL, t.fecha_limite ASC, t.id DESC
    ";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $estudianteId);
    $stmt->execute();
    $res = $stmt->get_result();

    $all = [];
    while ($row = $res->fetch_assoc()) {
        // Estado:
        // - calificada: si existe ct.id
        // - entregada (no calificada): si existe te.id y NO ct.id
        // - pendiente: si NO existe te.id
        $estado = 'pendiente';
        if (!empty($row['entrega_id'])) {
            $estado = 'entregada';
        }
        if (!empty($row['calif_id'])) {
            $estado = 'calificada';
        }

        $all[] = [
            'tarea_id'           => (int)$row['tarea_id'],
            'titulo'             => $row['tarea_titulo'],
            'descripcion'        => $row['tarea_descripcion'],
            'fecha_limite'       => $row['fecha_limite'],
            'curso_nombre'       => $row['curso_nombre'],
            'estado'             => $estado,

            'entrega_id'         => $row['entrega_id'] ? (int)$row['entrega_id'] : null,
            'file_path'          => $row['file_path'],
            'fecha_envio'        => $row['fecha_envio'],
            'entrega_comentario' => $row['entrega_comentario'],

            'calif_id'           => $row['calif_id'] ? (int)$row['calif_id'] : null,
            'calificacion'       => $row['calificacion'],
            'calif_comentario'   => $row['calif_comentario'],
            'fecha_calificacion' => $row['fecha_calificacion'],
            'profesor_nombre'    => $row['profesor_nombre'],
        ];
    }
    $stmt->close();

    // Buckets para tus pestañas
    $pending   = array_values(array_filter($all, fn($x) => $x['estado'] === 'pendiente'));
    $submitted = array_values(array_filter($all, fn($x) => $x['estado'] === 'entregada'));
    $graded    = array_values(array_filter($all, fn($x) => $x['estado'] === 'calificada'));

    return [
        'all'       => $all,
        'pending'   => $pending,
        'submitted' => $submitted,
        'graded'    => $graded,
    ];
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
