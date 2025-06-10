<?php
session_start();

// Si no hay sesión activa, volvemos al login
if (empty($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Conexión
require_once '../config.php';

// Obtener nombre y apellido
$stmt = $mysqli->prepare("
    SELECT nombre, apellido
      FROM usuarios
     WHERE id = ?
");
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($nombre, $apellido);
$stmt->fetch();
$stmt->close();
include __DIR__ . '/side_bar_director.php';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Profesores</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            

            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Gestión de Profesores</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-3">
                            <i class="bi bi-bell fs-4"></i>
                            <span class="notification-badge">8</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="director_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="director_configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../index.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons - Only Director can add/edit professors -->
                <div class="row mb-4 editable-by-director">
                    <div class="col-12 text-end">
                        <button class="btn btn-success me-2 edit-permission-director" data-bs-toggle="modal" data-bs-target="#newProfessorModal">
                            <i class="bi bi-person-plus"></i> Nuevo Profesor
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="departmentFilter" class="form-label">Departamento</label>
                                <select class="form-select" id="departmentFilter">
                                    <option value="" selected>Todos</option>
                                    <option value="ciencias">Ciencias Exactas</option>
                                    <option value="letras">Humanidades y Letras</option>
                                    <option value="sociales">Ciencias Sociales</option>
                                    <option value="artes">Artes y Deportes</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="statusFilter" class="form-label">Estado</label>
                                <select class="form-select" id="statusFilter">
                                    <option value="" selected>Todos</option>
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                    <option value="leave">De Licencia</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="experienceFilter" class="form-label">Años de Experiencia</label>
                                <select class="form-select" id="experienceFilter">
                                    <option value="" selected>Todos</option>
                                    <option value="0-5">0-5 años</option>
                                    <option value="6-10">6-10 años</option>
                                    <option value="11-15">11-15 años</option>
                                    <option value="16+">16+ años</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="searchInput" class="form-label">Buscar</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="searchInput" placeholder="Nombre, ID, materia...">
                                    <button class="btn btn-academic" type="button">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Professors List -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Listado de Profesores (41)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre</th>
                                        <th>Departamento</th>
                                        <th>Materias</th>
                                        <th>Años de Servicio</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>PROF-2013-042</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded-circle me-2" alt="Foto de perfil">
                                                <div>
                                                    <h6 class="mb-0">María López</h6>
                                                    <small class="text-muted">maria.lopez@eduardoavaroa.edu.bo</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Ciencias Exactas</td>
                                        <td>Matemáticas, Física, Química</td>
                                        <td>12</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-director"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger edit-permission-director"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>PROF-2010-018</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded-circle me-2" alt="Foto de perfil">
                                                <div>
                                                    <h6 class="mb-0">Roberto Flores</h6>
                                                    <small class="text-muted">roberto.flores@eduardoavaroa.edu.bo</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Humanidades y Letras</td>
                                        <td>Literatura, Lenguaje</td>
                                        <td>15</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-director"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger edit-permission-director"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>PROF-2015-063</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded-circle me-2" alt="Foto de perfil">
                                                <div>
                                                    <h6 class="mb-0">Javier Mendoza</h6>
                                                    <small class="text-muted">javier.mendoza@eduardoavaroa.edu.bo</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Ciencias Sociales</td>
                                        <td>Historia, Geografía, Cívica</td>
                                        <td>10</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-director"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger edit-permission-director"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>PROF-2018-087</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded-circle me-2" alt="Foto de perfil">
                                                <div>
                                                    <h6 class="mb-0">Laura Sánchez</h6>
                                                    <small class="text-muted">laura.sanchez@eduardoavaroa.edu.bo</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Ciencias Exactas</td>
                                        <td>Química, Biología</td>
                                        <td>7</td>
                                        <td><span class="badge bg-warning">De Licencia</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-director"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger edit-permission-director"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>PROF-2012-035</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded-circle me-2" alt="Foto de perfil">
                                                <div>
                                                    <h6 class="mb-0">Luis Ramírez</h6>
                                                    <small class="text-muted">luis.ramirez@eduardoavaroa.edu.bo</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Artes y Deportes</td>
                                        <td>Educación Física, Deportes</td>
                                        <td>13</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-director"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger edit-permission-director"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>PROF-2014-051</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded-circle me-2" alt="Foto de perfil">
                                                <div>
                                                    <h6 class="mb-0">Ana Guzmán</h6>
                                                    <small class="text-muted">ana.guzman@eduardoavaroa.edu.bo</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Humanidades y Letras</td>
                                        <td>Inglés, Francés</td>
                                        <td>11</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-director"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger edit-permission-director"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>PROF-2019-092</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded-circle me-2" alt="Foto de perfil">
                                                <div>
                                                    <h6 class="mb-0">Carlos Vargas</h6>
                                                    <small class="text-muted">carlos.vargas@eduardoavaroa.edu.bo</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Ciencias Exactas</td>
                                        <td>Informática, Tecnología</td>
                                        <td>6</td>
                                        <td><span class="badge bg-success">Activo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-director"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger edit-permission-director"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>PROF-2017-076</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="https://via.placeholder.com/40" class="rounded-circle me-2" alt="Foto de perfil">
                                                <div>
                                                    <h6 class="mb-0">Patricia Morales</h6>
                                                    <small class="text-muted">patricia.morales@eduardoavaroa.edu.bo</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>Artes y Deportes</td>
                                        <td>Música, Artes Plásticas</td>
                                        <td>8</td>
                                        <td><span class="badge bg-secondary">Inactivo</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary edit-permission-director"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger edit-permission-director"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center">
                                <li class="page-item disabled">
                                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Anterior</a>
                                </li>
                                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                <li class="page-item"><a class="page-link" href="#">2</a></li>
                                <li class="page-item"><a class="page-link" href="#">3</a></li>
                                <li class="page-item"><a class="page-link" href="#">4</a></li>
                                <li class="page-item"><a class="page-link" href="#">5</a></li>
                                <li class="page-item">
                                    <a class="page-link" href="#">Siguiente</a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>

                <!-- Department Statistics -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Estadísticas por Departamento</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Distribución de Profesores</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Departamento</th>
                                                <th>Profesores</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Ciencias Exactas</td>
                                                <td>15</td>
                                                <td>36.6%</td>
                                            </tr>
                                            <tr>
                                                <td>Humanidades y Letras</td>
                                                <td>10</td>
                                                <td>24.4%</td>
                                            </tr>
                                            <tr>
                                                <td>Ciencias Sociales</td>
                                                <td>8</td>
                                                <td>19.5%</td>
                                            </tr>
                                            <tr>
                                                <td>Artes y Deportes</td>
                                                <td>8</td>
                                                <td>19.5%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Experiencia Docente</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Años de Experiencia</th>
                                                <th>Profesores</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>0-5 años</td>
                                                <td>8</td>
                                                <td>19.5%</td>
                                            </tr>
                                            <tr>
                                                <td>6-10 años</td>
                                                <td>12</td>
                                                <td>29.3%</td>
                                            </tr>
                                            <tr>
                                                <td>11-15 años</td>
                                                <td>15</td>
                                                <td>36.6%</td>
                                            </tr>
                                            <tr>
                                                <td>16+ años</td>
                                                <td>6</td>
                                                <td>14.6%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Professor Modal - Only Director can access -->
    <div class="modal fade" id="newProfessorModal" tabindex="-1" aria-labelledby="newProfessorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="newProfessorModalLabel">Nuevo Profesor</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="crear_profesor.php" method="POST" id="profesorForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                            <label for="firstName" class="form-label">Nombre</label>
                            <input
                                type="text"
                                class="form-control"
                                id="firstName"
                                name="nombre"
                                required
                            >
                            </div>
                            <div class="col-md-6">
                            <label for="lastName" class="form-label">Apellido</label>
                            <input
                                type="text"
                                class="form-control"
                                id="lastName"
                                name="apellido"
                                required
                            >
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input
                                type="email"
                                class="form-control"
                                id="email"
                                name="email"
                                required
                            >
                            </div>
                            <div class="col-md-6">
                            <label for="phone" class="form-label">Teléfono</label>
                            <input
                                type="tel"
                                class="form-control"
                                id="phone"
                                name="telefono"
                                required
                            >
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                            <label for="idNumber" class="form-label">Número de Identidad</label>
                            <input
                                type="text"
                                class="form-control"
                                id="idNumber"
                                name="cedula"
                                required
                            >
                            </div>
                            <div class="col-md-6">
                            <label for="birthDate" class="form-label">Fecha de Nacimiento</label>
                            <input
                                type="date"
                                class="form-control"
                                id="birthDate"
                                name="fecha_nacimiento"
                                required
                            >
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                            <label for="department" class="form-label">Departamento</label>
                            <input
                                type="text"
                                class="form-control"
                                id="department"
                                name="departamento"
                                placeholder="Ingrese departamento"
                                required
                            >
                            </div>
                            <div class="col-md-6">
                            <label for="position" class="form-label">Cargo</label>
                            <input
                                type="text"
                                class="form-control"
                                id="position"
                                name="cargo"
                                required
                            >
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="subjects" class="form-label">Materias</label>
                            <select
                            class="form-select"
                            id="subjects"
                            name="materias[]"
                            multiple
                            required
                            >
                            <!-- Asigna el value al ID de cada materia según tu tabla materias -->
                            <option value="1">Matemáticas</option>
                            <option value="2">Física</option>
                            <option value="3">Química</option>
                            <option value="4">Biología</option>
                            <!-- … y así hasta Tecnología (id=16) :contentReference[oaicite:2]{index=2} -->
                            </select>
                            <div class="form-text">
                            Mantenga presionada Ctrl (o Cmd en Mac) para múltiples.
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                            <label for="startDate" class="form-label">Fecha de Inicio</label>
                            <input
                                type="date"
                                class="form-control"
                                id="startDate"
                                name="fecha_inicio"
                                required
                            >
                            </div>
                            <div class="col-md-6">
                            <label for="contractType" class="form-label">Tipo de Contrato</label>
                            <select
                                class="form-select"
                                id="contractType"
                                name="tipo_contrato"
                                required
                            >
                                <option selected disabled value="">
                                Seleccionar tipo…
                                </option>
                                <option value="Tiempo Completo">Tiempo Completo</option>
                                <option value="Medio Tiempo">Medio Tiempo</option>
                                <option value="Por Horas">Por Horas</option>
                                <option value="Temporal">Temporal</option>
                            </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Dirección</label>
                            <input
                            type="text"
                            class="form-control"
                            id="address"
                            name="direccion"
                            required
                            >
                        </div>
                        <div class="mb-3">
                            <label for="academicBackground" class="form-label">
                            Formación Académica
                            </label>
                            <textarea
                            class="form-control"
                            id="academicBackground"
                            name="formacion_academica"
                            rows="3"
                            required
                            ></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="profilePhoto" class="form-label">
                            Foto de Perfil
                            </label>
                            <input
                            class="form-control"
                            type="file"
                            id="profilePhoto"
                            name="foto_perfil"
                            >
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                id="sendCredentials"
                                name="enviar_credenciales"
                                value="1"
                                checked
                            >
                            <label class="form-check-label" for="sendCredentials">
                                Enviar credenciales por correo
                            </label>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                            Guardar Profesor
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic">Guardar Profesor</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('profesorForm');
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const fd = new FormData(form);
        try {
        const res = await fetch('crear_profesor.php', {
            method: 'POST',
            body: fd
        });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            form.reset();
        } else {
            alert('Error: ' + data.message);
        }
        } catch (err) {
        console.error(err);
        alert('Error de conexión');
        }
    });
    });
    </script>

</body>
</html>
