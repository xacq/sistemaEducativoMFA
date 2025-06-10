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
include __DIR__ . '/side_bar_profesor.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - Profesor Materiales</title>
    <link href="../css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            
            <!-- Main content -->
            <div class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Materiales Educativos</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="position-relative me-3">
                            <i class="bi bi-bell fs-4"></i>
                            <span class="notification-badge">5</span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle me-1"></i>
                                <?php echo htmlspecialchars($nombre . ' ' . $apellido, ENT_QUOTES, 'UTF-8'); ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                <li><a class="dropdown-item" href="profesor_perfil.php">Mi Perfil</a></li>
                                <li><a class="dropdown-item" href="profesor_configuracion.php">Configuración</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../index.php">Cerrar Sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Filter and Search -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text">Curso</span>
                            <select class="form-select" id="courseSelect">
                                <option selected>Matemáticas - 6° Secundaria</option>
                                <option>Matemáticas - 5° Secundaria</option>
                                <option>Física - 6° Secundaria</option>
                                <option>Física - 5° Secundaria</option>
                                <option>Química - 6° Secundaria</option>
                                <option>Química - 5° Secundaria</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text">Tipo</span>
                            <select class="form-select">
                                <option selected>Todos</option>
                                <option>Presentaciones</option>
                                <option>Documentos</option>
                                <option>Videos</option>
                                <option>Enlaces</option>
                                <option>Otros</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <span class="input-group-text">Unidad</span>
                            <select class="form-select">
                                <option selected>Todas</option>
                                <option>Unidad 1</option>
                                <option>Unidad 2</option>
                                <option>Unidad 3</option>
                                <option>Unidad 4</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Buscar material...">
                            <button class="btn btn-academic" type="button">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mb-4">
                    <div class="col-12 text-end">
                        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#newMaterialModal">
                            <i class="bi bi-plus-circle"></i> Nuevo Material
                        </button>
                        <button class="btn btn-primary me-2">
                            <i class="bi bi-folder-plus"></i> Nueva Carpeta
                        </button>
                        
                    </div>
                </div>

                <!-- Current Path -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="#">Mis Materiales</a></li>
                        <li class="breadcrumb-item"><a href="#">Matemáticas</a></li>
                        <li class="breadcrumb-item"><a href="#">6° Secundaria</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Cálculo Diferencial</li>
                    </ol>
                </nav>

                <!-- Materials List -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Materiales - Cálculo Diferencial</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Tamaño</th>
                                        <th>Fecha de Creación</th>
                                        <th>Última Modificación</th>
                                        <th>Compartido</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <i class="bi bi-folder-fill text-warning me-2"></i>
                                            <a href="#">Límites y Continuidad</a>
                                        </td>
                                        <td>Carpeta</td>
                                        <td>5 elementos</td>
                                        <td>10/05/2025</td>
                                        <td>15/05/2025</td>
                                        <td><span class="badge bg-success">Sí</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-folder-fill text-warning me-2"></i>
                                            <a href="#">Derivadas</a>
                                        </td>
                                        <td>Carpeta</td>
                                        <td>8 elementos</td>
                                        <td>20/05/2025</td>
                                        <td>28/05/2025</td>
                                        <td><span class="badge bg-success">Sí</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>
                                            <a href="#">Introducción al Cálculo Diferencial.pdf</a>
                                        </td>
                                        <td>PDF</td>
                                        <td>2.5 MB</td>
                                        <td>05/05/2025</td>
                                        <td>05/05/2025</td>
                                        <td><span class="badge bg-success">Sí</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-slides-fill text-success me-2"></i>
                                            <a href="#">Presentación Cálculo Diferencial.pptx</a>
                                        </td>
                                        <td>Presentación</td>
                                        <td>5.8 MB</td>
                                        <td>08/05/2025</td>
                                        <td>08/05/2025</td>
                                        <td><span class="badge bg-success">Sí</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-text-fill text-primary me-2"></i>
                                            <a href="#">Guía de Estudio - Cálculo Diferencial.docx</a>
                                        </td>
                                        <td>Documento</td>
                                        <td>1.2 MB</td>
                                        <td>12/05/2025</td>
                                        <td>12/05/2025</td>
                                        <td><span class="badge bg-success">Sí</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-excel-fill text-success me-2"></i>
                                            <a href="#">Ejercicios Resueltos.xlsx</a>
                                        </td>
                                        <td>Hoja de Cálculo</td>
                                        <td>0.8 MB</td>
                                        <td>15/05/2025</td>
                                        <td>15/05/2025</td>
                                        <td><span class="badge bg-success">Sí</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-link-45deg text-info me-2"></i>
                                            <a href="#">Khan Academy - Cálculo Diferencial</a>
                                        </td>
                                        <td>Enlace</td>
                                        <td>-</td>
                                        <td>18/05/2025</td>
                                        <td>18/05/2025</td>
                                        <td><span class="badge bg-success">Sí</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-play-fill text-danger me-2"></i>
                                            <a href="#">Video - Introducción a las Derivadas.mp4</a>
                                        </td>
                                        <td>Video</td>
                                        <td>45.2 MB</td>
                                        <td>22/05/2025</td>
                                        <td>22/05/2025</td>
                                        <td><span class="badge bg-success">Sí</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recently Shared Materials -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Materiales Compartidos Recientemente</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-academic">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Curso</th>
                                        <th>Compartido con</th>
                                        <th>Fecha de Compartición</th>
                                        <th>Vistas</th>
                                        <th>Descargas</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-pdf-fill text-danger me-2"></i>
                                            <a href="#">Guía de Ejercicios - Derivadas.pdf</a>
                                        </td>
                                        <td>Matemáticas - 6° Secundaria</td>
                                        <td>Estudiantes</td>
                                        <td>28/05/2025</td>
                                        <td>25</td>
                                        <td>18</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-slides-fill text-success me-2"></i>
                                            <a href="#">Presentación - Aplicaciones de Derivadas.pptx</a>
                                        </td>
                                        <td>Matemáticas - 6° Secundaria</td>
                                        <td>Estudiantes</td>
                                        <td>25/05/2025</td>
                                        <td>23</td>
                                        <td>15</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-play-fill text-danger me-2"></i>
                                            <a href="#">Video - Regla de la Cadena.mp4</a>
                                        </td>
                                        <td>Matemáticas - 6° Secundaria</td>
                                        <td>Estudiantes</td>
                                        <td>22/05/2025</td>
                                        <td>20</td>
                                        <td>12</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <i class="bi bi-file-earmark-text-fill text-primary me-2"></i>
                                            <a href="#">Formulario de Derivadas.docx</a>
                                        </td>
                                        <td>Matemáticas - 6° Secundaria</td>
                                        <td>Estudiantes</td>
                                        <td>20/05/2025</td>
                                        <td>25</td>
                                        <td>22</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></button>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Storage Usage -->
                <div class="card mb-4">
                    <div class="card-header card-header-academic">
                        <h5 class="mb-0 text-white">Uso de Almacenamiento</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Espacio Utilizado</h6>
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">25%</div>
                                </div>
                                <p>Has utilizado 250 MB de 1 GB disponible.</p>
                                <button class="btn btn-sm btn-outline-primary">Solicitar Más Espacio</button>
                            </div>
                            <div class="col-md-6">
                                <h6>Distribución por Tipo de Archivo</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="table-academic">
                                            <tr>
                                                <th>Tipo</th>
                                                <th>Cantidad</th>
                                                <th>Espacio</th>
                                                <th>Porcentaje</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Documentos</td>
                                                <td>25</td>
                                                <td>50 MB</td>
                                                <td>20%</td>
                                            </tr>
                                            <tr>
                                                <td>Presentaciones</td>
                                                <td>15</td>
                                                <td>75 MB</td>
                                                <td>30%</td>
                                            </tr>
                                            <tr>
                                                <td>Videos</td>
                                                <td>5</td>
                                                <td>100 MB</td>
                                                <td>40%</td>
                                            </tr>
                                            <tr>
                                                <td>Otros</td>
                                                <td>10</td>
                                                <td>25 MB</td>
                                                <td>10%</td>
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

    <!-- New Material Modal -->
    <div class="modal fade" id="newMaterialModal" tabindex="-1" aria-labelledby="newMaterialModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header card-header-academic text-white">
                    <h5 class="modal-title" id="newMaterialModalLabel">Nuevo Material</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="materialTitle" class="form-label">Título</label>
                                <input type="text" class="form-control" id="materialTitle" placeholder="Ej: Guía de Ejercicios - Derivadas" required>
                            </div>
                            <div class="col-md-6">
                                <label for="materialType" class="form-label">Tipo</label>
                                <select class="form-select" id="materialType" required>
                                    <option selected disabled value="">Seleccionar tipo...</option>
                                    <option>Documento</option>
                                    <option>Presentación</option>
                                    <option>Video</option>
                                    <option>Enlace</option>
                                    <option>Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="materialCourse" class="form-label">Curso</label>
                                <select class="form-select" id="materialCourse" required>
                                    <option selected disabled value="">Seleccionar curso...</option>
                                    <option>Matemáticas - 6° Secundaria</option>
                                    <option>Matemáticas - 5° Secundaria</option>
                                    <option>Física - 6° Secundaria</option>
                                    <option>Física - 5° Secundaria</option>
                                    <option>Química - 6° Secundaria</option>
                                    <option>Química - 5° Secundaria</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="materialUnit" class="form-label">Unidad</label>
                                <select class="form-select" id="materialUnit" required>
                                    <option selected disabled value="">Seleccionar unidad...</option>
                                    <option>Unidad 1 - Introducción al Cálculo</option>
                                    <option>Unidad 2 - Límites y Continuidad</option>
                                    <option>Unidad 3 - Derivadas</option>
                                    <option>Unidad 4 - Aplicaciones de Derivadas</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="materialDescription" class="form-label">Descripción</label>
                            <textarea class="form-control" id="materialDescription" rows="3" placeholder="Descripción del material..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="materialFile" class="form-label">Archivo</label>
                            <input class="form-control" type="file" id="materialFile">
                        </div>
                        <div class="mb-3">
                            <label for="materialLink" class="form-label">Enlace (opcional)</label>
                            <input type="url" class="form-control" id="materialLink" placeholder="https://...">
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="shareWithStudents" checked>
                                <label class="form-check-label" for="shareWithStudents">
                                    Compartir con estudiantes
                                </label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notifyStudents" checked>
                                <label class="form-check-label" for="notifyStudents">
                                    Notificar a los estudiantes
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-academic">Guardar Material</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>
