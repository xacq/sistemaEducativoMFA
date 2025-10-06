<?php
session_start();
$authError = $_SESSION['auth_error'] ?? null;
$authSuccess = $_SESSION['auth_success'] ?? null;
$lastEmail = $_SESSION['auth_email'] ?? '';
unset($_SESSION['auth_error'], $_SESSION['auth_success'], $_SESSION['auth_email']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema Académico - U.E. Eduardo Avaroa</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/academic.css" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container-fluid p-0">
        <div class="row g-0">
            <div class="col-md-8 offset-md-2">
                <div class="login-container">
                    <div class="row g-0">
                        <div class="col-md-6 login-image">
                            <div class="overlay">
                                <div class="text-center p-4">
                                    <img src="img/logo_escuela.png" alt="Logo U.E. Eduardo Avaroa" class="img-fluid mb-3" style="max-height: 120px;">
                                    <h2 class="text-white">Unidad Educativa Eduardo Abaroa</h2>
                                    <p class="text-white-50">El Alto, La Paz - Bolivia</p>

                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 login-form-container">
                            <div class="login-form p-4">
                                <div class="text-center mb-4">
                                    <h3>Sistema Académico</h3>
                                    <p class="text-muted">Ingrese sus credenciales para acceder</p>
                                </div>
                                <form class="login-form" id="loginForm" action="login.php" method="POST" novalidate>
                                    <?php if ($authError): ?>
                                        <div class="alert alert-danger" role="alert">
                                            <?php echo htmlspecialchars($authError); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($authSuccess): ?>
                                        <div class="alert alert-success" role="alert">
                                            <?php echo htmlspecialchars($authSuccess); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Usuario</label>
                                        <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="username"
                                            name="email"
                                            placeholder="Ingrese su email"
                                            value="<?php echo htmlspecialchars($lastEmail); ?>"
                                            required
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Contraseña</label>
                                        <div class="input-group">
                                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                        <input
                                            type="password"
                                            class="form-control"
                                            id="password"
                                            name="password"
                                            placeholder="Ingrese su contraseña"
                                            required
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="userType" class="form-label">Tipo de Usuario</label>
                                        <select class="form-select" id="userType" hidden>
                                        <option selected disabled>Seleccione su rol</option>
                                        <option value="estudiante">Estudiante</option>
                                        <option value="profesor">Profesor</option>
                                        <option value="director">Director</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="rememberMe">
                                        <label class="form-check-label" for="rememberMe">Recordar mis datos</label>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-academic">Iniciar Sesión</button>
                                    </div>
                                    <div class="text-center mt-3">
                                        <a href="#" class="text-decoration-none">¿Olvidó su contraseña?</a>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center p-3 text-muted">
                    <small>SisSistema Académico Unidad Educativa Eduardo Abaroa © 2025. Todos los derechos reservados.s.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/jquery-3.3.1.min.js"></script>
    <script src="js/bootstrap.bundle.min.js"></script>
    <!-- === Registro dinámico === -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
        // 1) Localizamos el contenedor del botón de login
        const loginGroup = document.querySelector('.login-form .d-grid.gap-2');
        // 2) Creamos el botón de registro
        const btnReg = document.createElement('button');
        btnReg.type = 'button';
        btnReg.className = 'btn btn-secondary mt-2';
        btnReg.textContent = 'Registrarse';
        btnReg.addEventListener('click', showRegisterForm);
        loginGroup.appendChild(btnReg);
        });

        function showRegisterForm() {
        // Si ya existe, no volvemos a crear
        if (document.getElementById('registerForm')) return;

        const container = document.querySelector('.login-form');
        const form = document.createElement('form');
        form.id = 'registerForm';
        form.action = 'register.php';
        form.method = 'POST';
        form.className = 'mt-4';

        form.innerHTML = `
            <h5 class="text-center mb-3">Registro de Usuario</h5>

            <div class="mb-2">
            <label for="regNombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="regNombre" name="nombre" required>
            </div>

            <div class="mb-2">
            <label for="regApellido" class="form-label">Apellido</label>
            <input type="text" class="form-control" id="regApellido" name="apellido" required>
            </div>

            <div class="mb-2">
            <label for="regEmail" class="form-label">Email</label>
            <input type="email" class="form-control" id="regEmail" name="email" required>
            </div>

            <div class="mb-2">
            <label for="regPassword" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="regPassword" name="password" required>
            </div>

            <div class="mb-3">
            <label for="regRole" class="form-label">Rol</label>
            <select class="form-select" id="regRole" name="role_id" required>
                <option value="" selected disabled>Seleccione rol</option>
                <option value="1">Director</option>
                <option value="2">Profesor</option>
                <option value="3">Estudiante</option>
            </select>
            </div>

            <div class="d-grid">
            <button type="submit" class="btn btn-primary">Crear cuenta</button>
            </div>
        `;

        container.appendChild(form);
        // Opcional: ocultar el form de login mientras se registra
        // document.querySelector('form').style.display = 'none';
        }
    </script>
    <!-- === Fin Registro dinámico === -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('loginForm');
            const emailField = document.getElementById('username');
            const passwordField = document.getElementById('password');

            if (loginForm) {
                loginForm.addEventListener('submit', (event) => {
                    const email = emailField.value.trim();
                    const password = passwordField.value.trim();

                    if (!email || !password) {
                        event.preventDefault();
                        showInlineAlert('Por favor ingrese su correo electrónico y contraseña.');
                    }
                });
            }
        });

        function showInlineAlert(message) {
            let alert = document.querySelector('#loginForm .alert-inline');
            if (!alert) {
                alert = document.createElement('div');
                alert.className = 'alert alert-warning alert-inline';
                const form = document.getElementById('loginForm');
                form.insertBefore(alert, form.firstChild);
            }
            alert.textContent = message;
        }
    </script>
</div>
    </body>
</html>
