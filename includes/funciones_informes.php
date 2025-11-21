<?php
// ===============================================================
//  ARCHIVO: includes/funciones_informes.php
//  PROPOSITO: Extraer los datos numéricos del sistema académico
//             para construir el "perfil" que se envía a la IA.
//  SISTEMA: sistemaEducativoMFA
// ===============================================================


/**
 * Construye el perfil numérico de un ESTUDIANTE para el informe IA.
 *
 * @param PDO $pdo
 * @param int $estudiante_id
 * @return array
 */
function construirPerfilEstudiante(PDO $pdo, int $estudiante_id): array
{
    // ===========================================================
    // 1. DATOS PERSONALES DEL ESTUDIANTE
    // ===========================================================
    $stmt = $pdo->prepare("
        SELECT 
            e.id,
            u.nombre AS nombre_estudiante,
            e.fecha_nacimiento,
            e.genero,
            g.nombre AS grado_nombre,
            e.seccion
        FROM estudiantes e
        INNER JOIN usuarios u ON e.usuario_id = u.id
        INNER JOIN grados g ON e.grado_id = g.id
        WHERE e.id = :estudiante_id
        LIMIT 1
    ");
    $stmt->execute([':estudiante_id' => $estudiante_id]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    // Si el estudiante no existe
    if (!$estudiante) {
        return ['error' => 'Estudiante no encontrado'];
    }


    // ===========================================================
    // 2. RENDIMIENTO: PROMEDIOS POR MATERIA
    // ===========================================================
    $stmt = $pdo->prepare("
        SELECT 
            m.id AS materia_id,
            m.nombre AS materia_nombre,
            ROUND(AVG(c.calificacion), 2) AS promedio_materia
        FROM calificaciones c
        INNER JOIN evaluaciones e ON c.evaluacion_id = e.id
        INNER JOIN cursos cu ON e.curso_id = cu.id
        INNER JOIN materias m ON cu.materia_id = m.id
        INNER JOIN matriculas ma ON c.matricula_id = ma.id
        WHERE ma.estudiante_id = :estudiante_id
        GROUP BY m.id, m.nombre
        ORDER BY m.nombre ASC
    ");
    $stmt->execute([':estudiante_id' => $estudiante_id]);
    $promedios_por_materia = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ===========================================================
    // 3. PROMEDIO GENERAL DEL AÑO
    // ===========================================================
    $stmt = $pdo->prepare("
        SELECT ROUND(AVG(c.calificacion), 2) AS promedio_general
        FROM calificaciones c
        INNER JOIN matriculas ma ON c.matricula_id = ma.id
        WHERE ma.estudiante_id = :estudiante_id
    ");
    $stmt->execute([':estudiante_id' => $estudiante_id]);
    $promedio_general = floatval($stmt->fetchColumn() ?? 0);


    // ===========================================================
    // 4. TAREAS: ASIGNADAS, ENTREGADAS, PROMEDIOS
    // ===========================================================
    // TOTAL ASIGNADAS
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM tareas t
        INNER JOIN cursos cu ON t.curso_id = cu.id
        INNER JOIN matriculas ma ON ma.curso_id = cu.id
        WHERE ma.estudiante_id = :estudiante_id
    ");
    $stmt->execute([':estudiante_id' => $estudiante_id]);
    $tareas_asignadas = intval($stmt->fetchColumn());

    // ENTREGADAS POR EL ESTUDIANTE
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT te.tarea_id)
        FROM tarea_entregas te
        INNER JOIN matriculas ma ON te.matricula_id = ma.id
        WHERE ma.estudiante_id = :estudiante_id
    ");
    $stmt->execute([':estudiante_id' => $estudiante_id]);
    $tareas_entregadas = intval($stmt->fetchColumn());

    // PORCENTAJE
    $porcentaje_entrega = ($tareas_asignadas > 0)
        ? round(($tareas_entregadas / $tareas_asignadas) * 100, 2)
        : 0;

    // PROMEDIO DE CALIFICACIONES DE TAREAS
    $stmt = $pdo->prepare("
        SELECT ROUND(AVG(ct.calificacion), 2)
        FROM calificaciones_tareas ct
        INNER JOIN tarea_entregas te ON ct.tarea_entrega_id = te.id
        INNER JOIN matriculas ma ON te.matricula_id = ma.id
        WHERE ma.estudiante_id = :estudiante_id
    ");
    $stmt->execute([':estudiante_id' => $estudiante_id]);
    $promedio_tareas = floatval($stmt->fetchColumn() ?? 0);


    // ===========================================================
    // 5. ASISTENCIA
    // ===========================================================
    $stmt = $pdo->prepare("
        SELECT 
            SUM(a.estado = 'Presente') AS presentes,
            SUM(a.estado = 'Ausente') AS ausentes,
            SUM(a.estado = 'Tarde') AS tardanzas,
            COUNT(*) AS total
        FROM asistencia a
        INNER JOIN matriculas ma ON a.matricula_id = ma.id
        WHERE ma.estudiante_id = :estudiante_id
    ");
    $stmt->execute([':estudiante_id' => $estudiante_id]);
    $asistencia = $stmt->fetch(PDO::FETCH_ASSOC);

    $porcentaje_asistencia = ($asistencia['total'] > 0)
        ? round(($asistencia['presentes'] / $asistencia['total']) * 100, 2)
        : 0;


    // ===========================================================
    // 6. PARTICIPACIÓN INFERIDA (ENTREGAS + ASISTENCIA)
    // ===========================================================
    $participacion = round(
        ($porcentaje_entrega * 0.6) + ($porcentaje_asistencia * 0.4),
        2
    );


    // ===========================================================
    // RETORNO FINAL
    // ===========================================================
    return [
        'estudiante' => [
            'id' => $estudiante['id'],
            'nombre' => $estudiante['nombre_estudiante'],
            'fecha_nacimiento' => $estudiante['fecha_nacimiento'],
            'genero' => $estudiante['genero'],
            'grado' => $estudiante['grado_nombre'],
            'seccion' => $estudiante['seccion']
        ],

        'promedios_por_materia' => $promedios_por_materia,
        'promedio_general' => $promedio_general,

        'tareas' => [
            'asignadas' => $tareas_asignadas,
            'entregadas' => $tareas_entregadas,
            'porcentaje_entrega' => $porcentaje_entrega,
            'promedio_tareas' => $promedio_tareas
        ],

        'asistencia' => [
            'presentes' => intval($asistencia['presentes']),
            'ausentes' => intval($asistencia['ausentes']),
            'tardanzas' => intval($asistencia['tardanzas']),
            'total_registros' => intval($asistencia['total']),
            'porcentaje_asistencia' => $porcentaje_asistencia
        ],

        'participacion_inferida' => $participacion
    ];
}





// ===============================================================
//  PERFIL DEL DOCENTE (PARA DIRECTORES)
// ===============================================================

/**
 * Construye el perfil numérico de un DOCENTE para el informe IA.
 *
 * @param PDO $pdo
 * @param int $profesor_id
 * @return array
 */
function construirPerfilDocente(PDO $pdo, int $profesor_id): array
{
    // ===========================================================
    // 1. INFORMACIÓN DEL PROFESOR
    // ===========================================================
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            u.nombre AS nombre_profesor,
            u.email
        FROM profesores p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        WHERE p.id = :profesor_id
        LIMIT 1
    ");
    $stmt->execute([':profesor_id' => $profesor_id]);
    $profesor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profesor) {
        return ['error' => 'Profesor no encontrado'];
    }


    // ===========================================================
    // 2. PROMEDIO GENERAL POR MATERIA
    // ===========================================================
    $stmt = $pdo->prepare("
        SELECT 
            m.id AS materia_id,
            m.nombre AS materia_nombre,
            ROUND(AVG(c.calificacion), 2) AS promedio_materia
        FROM calificaciones c
        INNER JOIN evaluaciones e ON c.evaluacion_id = e.id
        INNER JOIN cursos cu ON e.curso_id = cu.id
        INNER JOIN materias m ON cu.materia_id = m.id
        WHERE cu.profesor_id = :profesor_id
        GROUP BY m.id, m.nombre
        ORDER BY m.nombre ASC
    ");
    $stmt->execute([':profesor_id' => $profesor_id]);
    $promedios_materia = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // ===========================================================
    // 3. TOTAL DE EVALUACIONES CREADAS
    // ===========================================================
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM evaluaciones e
        INNER JOIN cursos cu ON e.curso_id = cu.id
        WHERE cu.profesor_id = :profesor_id
    ");
    $stmt->execute([':profesor_id' => $profesor_id]);
    $total_evaluaciones = intval($stmt->fetchColumn());


    // ===========================================================
    // 4. TOTAL DE CALIFICACIONES CARGADAS
    // ===========================================================
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM calificaciones c
        INNER JOIN evaluaciones e ON c.evaluacion_id = e.id
        INNER JOIN cursos cu ON e.curso_id = cu.id
        WHERE cu.profesor_id = :profesor_id
    ");
    $stmt->execute([':profesor_id' => $profesor_id]);
    $total_calificaciones = intval($stmt->fetchColumn());


    // ===========================================================
    // 5. TAREAS ASIGNADAS Y ENTREGADAS
    // ===========================================================
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM tareas t
        INNER JOIN cursos cu ON t.curso_id = cu.id
        WHERE cu.profesor_id = :profesor_id
    ");
    $stmt->execute([':profesor_id' => $profesor_id]);
    $tareas_asignadas = intval($stmt->fetchColumn());

    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT te.tarea_id)
        FROM tarea_entregas te
        INNER JOIN tareas t ON te.tarea_id = t.id
        INNER JOIN cursos cu ON t.curso_id = cu.id
        WHERE cu.profesor_id = :profesor_id
    ");
    $stmt->execute([':profesor_id' => $profesor_id]);
    $tareas_entregadas = intval($stmt->fetchColumn());

    $porcentaje_entrega = ($tareas_asignadas > 0)
        ? round(($tareas_entregadas / $tareas_asignadas) * 100, 2)
        : 0;


    // ===========================================================
    // RETORNO FINAL
    // ===========================================================
    return [
        'profesor' => [
            'id' => $profesor['id'],
            'nombre' => $profesor['nombre_profesor'],
            'email' => $profesor['email']
        ],

        'promedios_por_materia' => $promedios_materia,
        'total_evaluaciones' => $total_evaluaciones,
        'total_calificaciones' => $total_calificaciones,

        'tareas' => [
            'asignadas' => $tareas_asignadas,
            'entregadas' => $tareas_entregadas,
            'porcentaje_entrega' => $porcentaje_entrega
        ]
    ];
}

?>
