<?php
// C:\xampp\htdocs\certi-celen\student\solicitar.php

// Corrected includes using __DIR__ for robustness
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php'; // Also ensure db.php is included
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Access the global database connection variable ($db from db.php)
global $db; 

// Verificar autenticación
requiereAutenticacion('student');

// Procesar solicitud
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // IMPORTANT: In your SQL schema, the column is 'tipo', not 'tipo_certificado'.
    // Adjust the variable name here if you want it to align with the DB.
    // However, the form field name is 'tipo_certificado', so let's keep it consistent with the form for now.
    // We'll map it to 'tipo' for the DB query.
    $form_tipo_certificado = $_POST['tipo_certificado'];
    
    // Verificar que el estudiante no tenga una solicitud pendiente del mismo tipo
    $query = "SELECT id FROM solicitudes 
              WHERE estudiante_id = :estudiante_id 
              AND tipo = :tipo_certificado_db_column 
              AND estado = 'pendiente'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':estudiante_id', $_SESSION['user_id']);
    $stmt->bindParam(':tipo_certificado_db_column', $form_tipo_certificado); // Use the form value
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $error = 'Ya tienes una solicitud pendiente para este tipo de certificado';
    } else {
        // Crear nueva solicitud
        $query = "INSERT INTO solicitudes 
                  (estudiante_id, tipo, fecha_solicitud, estado) 
                  VALUES 
                  (:estudiante_id, :tipo_db_column, NOW(), 'pendiente')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':estudiante_id', $_SESSION['user_id']);
        $stmt->bindParam(':tipo_db_column', $form_tipo_certificado); // Use the form value
        
        if ($stmt->execute()) {
            $success = 'Solicitud enviada correctamente. Será revisada por el personal administrativo.';
        } else {
            $error = 'Error al enviar la solicitud. Por favor, inténtalo nuevamente.';
        }
    }
}

// Obtener información del estudiante
$query = "SELECT * FROM estudiantes WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitar Certificado - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css"> 
</head>
<body>
    <header>
        <div class="container">
            <h1>Solicitar Certificado - <?php echo htmlspecialchars($estudiante['nombres']); ?></h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="mis-certificados.php">Mis Certificados</a>
                <a href="../logout.php">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <section>
            <h2>Nueva Solicitud de Certificado</h2>
            
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
            
            <div class="student-info">
                <h3>Información del Estudiante</h3>
                <p><strong>Nombre:</strong> <?php echo htmlspecialchars($estudiante['nombres'] . ' ' . $estudiante['apellidos']); ?></p>
                <p><strong>Programa:</strong> <?php echo htmlspecialchars($estudiante['programa']); ?></p>
                <p><strong>Nivel:</strong> <?php echo htmlspecialchars($estudiante['nivel']); ?></p>
            </div>
            
            <form method="post" class="request-form">
                <div class="form-group">
                    <label for="tipo_certificado">Tipo de Certificado:</label>
                    <select id="tipo_certificado" name="tipo_certificado" required>
                        <option value="">Seleccione un tipo</option>
                        <option value="certificado">Certificado de Estudios</option> <option value="constancia">Constancia</option>            <option value="duplicado">Duplicado</option>              </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Enviar Solicitud</button>
                </div>
            </form>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CELEN - UNAP</p>
        </div>
    </footer>
</body>
</html>