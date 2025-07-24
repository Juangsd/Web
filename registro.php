<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $datos = [
        'nombres' => trim($_POST['nombres']),
        'apellidos' => trim($_POST['apellidos']),
        'programa' => trim($_POST['programa']),
        'nivel' => trim($_POST['nivel']),
        'email' => trim($_POST['email']),
        'password' => trim($_POST['password']),
        'confirm_password' => trim($_POST['confirm_password'])
    ];
    
    // Validaciones
    if (empty($datos['nombres']) || empty($datos['apellidos']) || empty($datos['email']) || 
        empty($datos['password']) || empty($datos['confirm_password'])) {
        $error = 'Todos los campos son obligatorios';
    } elseif ($datos['password'] != $datos['confirm_password']) {
        $error = 'Las contraseñas no coinciden';
    } elseif (strlen($datos['password']) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres';
    } else {
        $resultado = registrarEstudiante($datos);
        
        if ($resultado['success']) {
            $success = 'Registro exitoso. Ahora puedes iniciar sesión.';
            $_POST = array(); // Limpiar el formulario
        } else {
            $error = $resultado['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Estudiante - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style2.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Registro de Estudiante - CENTRO DE IDIOMAS</h1>
            <nav>
                <a href="index.php">Inicio</a>
                <a href="login.php">Iniciar Sesión</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <section class="register-section">
            <h2>Crear Cuenta de Estudiante</h2>
            
            <?php if ($error): ?>
                <div class="alert error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert success">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" class="register-form">
                <div class="form-group">
                    <label for="nombres">Nombres:</label>
                    <input type="text" id="nombres" name="nombres" required 
                           value="<?php echo htmlspecialchars($_POST['nombres'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="apellidos">Apellidos:</label>
                    <input type="text" id="apellidos" name="apellidos" required 
                           value="<?php echo htmlspecialchars($_POST['apellidos'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="programa">Programa de Idiomas:</label>
                    <select id="programa" name="programa" required>
                        <option value="">Seleccione un programa</option>
                        <option value="Inglés" <?php echo (($_POST['programa'] ?? '') == 'Inglés' ? 'selected' : ''); ?>>Inglés</option>
                        <option value="Quechua" <?php echo (($_POST['programa'] ?? '') == 'Quechua' ? 'selected' : ''); ?>>Quechua</option>
                        <option value="Aimara" <?php echo (($_POST['programa'] ?? '') == 'Aimara' ? 'selected' : ''); ?>>Aimara</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="nivel">Nivel:</label>
                    <select id="nivel" name="nivel" required>
                        <option value="">Seleccione un nivel</option>
                        <option value="Básico" <?php echo (($_POST['nivel'] ?? '') == 'Básico' ? 'selected' : ''); ?>>Básico</option>
                        <option value="Intermedio" <?php echo (($_POST['nivel'] ?? '') == 'Intermedio' ? 'selected' : ''); ?>>Intermedio</option>
                        <option value="Avanzado" <?php echo (($_POST['nivel'] ?? '') == 'Avanzado' ? 'selected' : ''); ?>>Avanzado</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="email">Correo Electrónico:</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" required>
                    <small>Mínimo 8 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmar Contraseña:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Registrarse</button>
                </div>
                
                <div class="form-links">
                    <a href="login.php">¿Ya tienes cuenta? Inicia sesión</a>
                </div>
            </form>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Centro de Idiomas CELEN - Universidad Nacional del Altiplano</p>
        </div>
    </footer>
</body>
</html>