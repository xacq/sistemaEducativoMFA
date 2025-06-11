<?php

// Conexión a la base de datos
require_once 'config.php'; // ¡Asegúrate que esta ruta es correcta!

echo "<h1>Generador de Profesores</h1>";

// --- DATOS DE PRUEBA ---
$nombres = ["Alejandro", "Beatriz", "Carlos", "Diana", "Eduardo", "Fernanda", "Gabriel", "Hilda", "Ignacio", "Julieta", "Leonardo", "Marcela", "Nicolás", "Olivia", "Patricio", "Renata", "Santiago", "Teresa", "Ulises", "Valeria"];
$apellidos = ["Quispe", "Mamani", "Flores", "Vargas", "Rojas", "Gutiérrez", "Sánchez", "Chávez", "Castillo", "Paredes", "Mendoza", "Cruz", "Ramos", "Torres", "Díaz", "Vega", "Morales", "Ortiz"];
$departamentos = ["Ciencias Exactas", "Humanidades y Letras", "Ciencias Sociales", "Artes y Deportes", "Idiomas"];
$cargos = ["Profesor Titular", "Profesor Auxiliar", "Jefe de Departamento", "Coordinador de Nivel"];
$formaciones = ["Licenciado en Educación", "Magíster en Pedagogía", "Doctor en Ciencias", "Ingeniero de Sistemas", "Licenciado en Artes"];
$contratos = ["Tiempo Completo", "Medio Tiempo", "Por Horas", "Temporal"];

// --- OBTENER DATOS DE LA BD ---

// Obtener el ID del rol de Profesor (asumiendo que es 2 según tu DUMP)
$role_id_profesor = 2; 

// Obtener todas las materias disponibles
$materias_disponibles = [];
if ($result_materias = $mysqli->query("SELECT id, nombre FROM materias")) {
    while ($row = $result_materias->fetch_assoc()) {
        $materias_disponibles[] = $row;
    }
    $result_materias->free();
} else {
    die("Error: No se pudo obtener la lista de materias. Asegúrate de que la tabla 'materias' no esté vacía.");
}
if (empty($materias_disponibles)) {
    die("Error: La tabla 'materias' está vacía. Por favor, inserta algunas materias primero.");
}


$total_profesores_a_crear = 50;

// Iniciar transacción para seguridad
$mysqli->begin_transaction();

try {
    for ($i = 1; $i <= $total_profesores_a_crear; $i++) {
        
        // --- 1. CREAR EL USUARIO ---
        $nombre_aleatorio = $nombres[array_rand($nombres)];
        $apellido_aleatorio = $apellidos[array_rand($apellidos)];
        $email_aleatorio = strtolower(substr($nombre_aleatorio, 0, 1) . $apellido_aleatorio . $i . '@colegio.edu.bo');
        $password_default = 'profesor123';
        $password_hashed = password_hash($password_default, PASSWORD_DEFAULT);

        $stmt_user = $mysqli->prepare("INSERT INTO usuarios (nombre, apellido, email, password, role_id) VALUES (?, ?, ?, ?, ?)");
        $stmt_user->bind_param('ssssi', $nombre_aleatorio, $apellido_aleatorio, $email_aleatorio, $password_hashed, $role_id_profesor);
        $stmt_user->execute();

        $nuevo_usuario_id = $mysqli->insert_id;
        if (!$nuevo_usuario_id) throw new Exception("Error creando usuario para el profesor #$i.");
        $stmt_user->close();

        // --- 2. CREAR EL PROFESOR ---
        $cedula = rand(1000000, 9999999) . " LP";
        $anio_nacimiento = rand(1960, 1995);
        $fecha_nacimiento = "$anio_nacimiento-" . rand(1, 12) . "-" . rand(1, 28);
        $departamento = $departamentos[array_rand($departamentos)];
        $cargo = $cargos[array_rand($cargos)];
        $fecha_inicio = (rand(2000, 2022)) . "-" . rand(1, 12) . "-" . rand(1, 28);
        $tipo_contrato = $contratos[array_rand($contratos)];
        $direccion = "Avenida Principal #" . rand(100, 999) . ", Zona " . ['Central', 'Sur', 'Norte'][rand(0, 2)];
        $formacion_academica = $formaciones[array_rand($formaciones)];
        $telefono = "6" . rand(1000000, 9999999);
        
        $stmt_profesor = $mysqli->prepare(
            "INSERT INTO profesores (usuario_id, cedula, fecha_nacimiento, departamento, cargo, fecha_inicio, tipo_contrato, direccion, formacion_academica, telefono) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt_profesor->bind_param(
            'isssssssss',
            $nuevo_usuario_id, $cedula, $fecha_nacimiento, $departamento, $cargo, 
            $fecha_inicio, $tipo_contrato, $direccion, $formacion_academica, $telefono
        );
        $stmt_profesor->execute();
        
        $nuevo_profesor_id = $mysqli->insert_id; // Este es el ID de la tabla 'profesores', no 'usuarios'.
        if (!$nuevo_profesor_id) throw new Exception("Error creando el perfil del profesor #$i.");
        $stmt_profesor->close();

        // --- 3. ASIGNAR MATERIAS ALEATORIAS ---
        $numero_de_materias = rand(1, 3); // Cada profesor impartirá entre 1 y 3 materias
        $materias_asignadas_keys = array_rand($materias_disponibles, $numero_de_materias);
        
        $stmt_materias = $mysqli->prepare("INSERT INTO profesor_materias (profesor_id, materia_id) VALUES (?, ?)");
        
        if (is_array($materias_asignadas_keys)) {
            foreach ($materias_asignadas_keys as $key) {
                $materia_id = $materias_disponibles[$key]['id'];
                $stmt_materias->bind_param('ii', $nuevo_profesor_id, $materia_id);
                $stmt_materias->execute();
            }
        } else { // Si solo se asigna 1 materia, array_rand devuelve solo la clave, no un array de claves.
            $materia_id = $materias_disponibles[$materias_asignadas_keys]['id'];
            $stmt_materias->bind_param('ii', $nuevo_profesor_id, $materia_id);
            $stmt_materias->execute();
        }
        $stmt_materias->close();
        
        echo "($i/$total_profesores_a_crear) Profesor Creado: $nombre_aleatorio $apellido_aleatorio<br>";
    }

    // Si todo fue bien, confirmar los cambios.
    $mysqli->commit();
    echo "<h2>¡Proceso completado! Se crearon $total_profesores_a_crear profesores.</h2>";

} catch (Exception $e) {
    // Si algo falla, revertir todo.
    $mysqli->rollback();
    echo "<h2>Ocurrió un error. Se revirtieron todos los cambios.</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

$mysqli->close();
?>