<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirigir si ya está logueado
if (esUsuarioLogueado()) {
    if ($_SESSION['user_type'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $tipo = $_POST['tipo'] ?? '';
    
    // Validación básica
    if (empty($email) || empty($password) || empty($tipo)) {
        $error = 'Por favor complete todos los campos';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor ingrese un email válido';
    } else {
        // Intentar login según el tipo
        if ($tipo == 'student') {
            if (loginEstudiante($email, $password)) {
                header('Location: student/dashboard.php');
                exit();
            } else {
                $error = 'Email o contraseña incorrectos para estudiante';
            }
        } elseif ($tipo == 'admin') {
            if (loginAdmin($email, $password)) {
                header('Location: admin/dashboard.php');
                exit();
            } else {
                $error = 'Email o contraseña incorrectos para administrador';
            }
        } else {
            $error = 'Tipo de usuario inválido';
        }
        
        // Pequeña pausa para prevenir ataques de fuerza bruta
        sleep(1);
    }
}

// Obtener mensaje de éxito si viene de registro
if (isset($_GET['registered']) && $_GET['registered'] == '1') {
    $success = '¡Registro exitoso! Ya puedes iniciar sesión con tu cuenta.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style1.css">
    <meta name="description" content="Iniciar sesión en el sistema de certificados CERTI-CELEN">
</head>
<body class="login-page">
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="assets/img/logo.png" alt="Logo CELEN" class="logo-img">
                    <h1><a href="index.php">CENTRO DE IDIOMAS</a></h1>
                </div>
                <nav>
                    <a href="index.php" class="nav-link">Inicio</a>
                    <a href="registro.php" class="nav-link">Registrarse</a>
                    <a href="verificar.php" class="nav-link">Verificar Certificado</a>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="container">
        <section class="login-section">
            <div class="login-container">
                <div class="login-header">
                    <h2>Acceso al Sistema</h2>
                    <p>Ingresa tus credenciales para acceder a tu cuenta</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">⚠️</span>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <span class="alert-icon">✅</span>
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="login-form" novalidate>
                    <div class="form-group">
                        <label for="email">Correo Electrónico:</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>"
                               required 
                               placeholder="ejemplo@correo.com"
                               class="form-input">
                        <span class="form-error" id="email-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <div class="password-input">
                            <input type="password" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   placeholder="Ingresa tu contraseña"
                                   class="form-input">
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <span id="password-icon">👁️</span>
                            </button>
                        </div>
                        <span class="form-error" id="password-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo">Tipo de Usuario:</label>
                        <select id="tipo" name="tipo" required class="form-select">
                            <option value="">Selecciona una opción</option>
                            <option value="student" <?php echo (isset($tipo) && $tipo == 'student') ? 'selected' : ''; ?>>
                                Estudiante
                            </option>
                            <option value="admin" <?php echo (isset($tipo) && $tipo == 'admin') ? 'selected' : ''; ?>>
                                Administrador
                            </option>
                        </select>
                        <span class="form-error" id="tipo-error"></span>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-full">
                            <span class="btn-text">Iniciar Sesión</span>
                            <span class="btn-loading" style="display: none;">Cargando...</span>
                        </button>
                    </div>
                    
                    <div class="form-links">
                        <p>¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a></p>
                        <p><a href="index.php">← Volver al inicio</a></p>
                    </div>
                </form>
                
                <div class="login-help">
                    <h3>¿Necesitas ayuda?</h3>
                    <ul>
                        <li>Los estudiantes deben registrarse primero en <a href="registro.php">esta página</a></li>
                        <li>Si olvidas tu contraseña, contacta a soporte: <strong>soporte@celen.unap.edu.pe</strong></li>
                        <li>Los administradores reciben sus credenciales directamente del sistema</li>
                    </ul>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> Centro de Idiomas CELEN - Universidad Nacional del Altiplano</p>
                <p>Sistema seguro protegido con encriptación SSL</p>
            </div>
        </div>
    </footer>

    <script>
        // Validación del formulario en tiempo real
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.login-form');
            const submitBtn = form.querySelector('button[type="submit"]');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            // Validación en tiempo real
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.addEventListener('blur', validateInput);
                input.addEventListener('input', clearError);
            });
            
            function validateInput(e) {
                const input = e.target;
                const errorElement = document.getElementById(input.name + '-error');
                let isValid = true;
                let errorMessage = '';
                
                if (input.hasAttribute('required') && !input.value.trim()) {
                    errorMessage = 'Este campo es obligatorio';
                    isValid = false;
                } else if (input.type === 'email' && input.value && !isValidEmail(input.value)) {
                    errorMessage = 'Por favor ingresa un email válido';
                    isValid = false;
                } else if (input.name === 'password' && input.value && input.value.length < 6) {
                    errorMessage = 'La contraseña debe tener al menos 6 caracteres';
                    isValid = false;
                }
                
                if (errorElement) {
                    errorElement.textContent = errorMessage;
                    input.classList.toggle('error', !isValid);
                }
                
                return isValid;
            }
            
            function clearError(e) {
                const input = e.target;
                const errorElement = document.getElementById(input.name + '-error');
                if (errorElement) {
                    errorElement.textContent = '';
                    input.classList.remove('error');
                }
            }
            
            function isValidEmail(email) {
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
            }
            
            // Mostrar loading al enviar
            form.addEventListener('submit', function(e) {
                const isFormValid = Array.from(inputs).every(input => validateInput({target: input}));
                
                if (isFormValid) {
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoading.style.display = 'inline';
                }
            });
        });
        
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                passwordIcon.textContent = '👁️';
            }
        }
    </script>
</body>
</html>