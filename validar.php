<?php
// admin/validar.php

// Ensure all necessary files are included using __DIR__ for robust paths
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Access the global database connection variable
global $db;

// Verify authentication for admin users
requiereAutenticacion('admin');

$solicitud_id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // Get the request ID from the URL

// Redirect if no valid ID is provided
if ($solicitud_id === 0) {
    header('Location: solicitudes.php');
    exit();
}

$error = '';
$success = '';

// Process approval/rejection action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $observaciones = trim($_POST['observaciones'] ?? '');

    // Fetch the request to ensure it exists and is pending
    $query_check = "SELECT id, estado FROM solicitudes WHERE id = :solicitud_id";
    $stmt_check = $db->prepare($query_check);
    $stmt_check->bindParam(':solicitud_id', $solicitud_id);
    $stmt_check->execute();
    $solicitud_data = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if (!$solicitud_data || $solicitud_data['estado'] !== 'pendiente') {
        $error = 'La solicitud no existe o ya ha sido procesada.';
    } else {
        $new_status = ($action === 'aprobar') ? 'aprobada' : 'rechazada'; // Use 'aprobada' to match ENUM
        
        // Update the request status and set the revision date
        $query_update = "UPDATE solicitudes SET estado = :new_status, fecha_revision = NOW() WHERE id = :solicitud_id";
        $stmt_update = $db->prepare($query_update);
        $stmt_update->bindParam(':new_status', $new_status);
        $stmt_update->bindParam(':solicitud_id', $solicitud_id);
        
        if ($stmt_update->execute()) {
            // Record the validation in the 'validaciones' table
            $query_validation = "INSERT INTO validaciones 
                                 (solicitud_id, validado_por, observaciones, fecha_validacion) 
                                 VALUES 
                                 (:solicitud_id, :validado_por, :observaciones, NOW())";
            $stmt_validation = $db->prepare($query_validation);
            $stmt_validation->bindParam(':solicitud_id', $solicitud_id);
            $stmt_validation->bindParam(':validado_por', $_SESSION['user_id']); // Assuming 'user_id' stores admin ID
            $stmt_validation->bindParam(':observaciones', $observaciones);
            $stmt_validation->execute();

            // If approved, trigger certificate generation via API
            if ($action === 'aprobar') {
                $api_url = SITE_URL . '/api/generar.php';
                $api_data = ['solicitud_id' => $solicitud_id];
                
                $options = [
                    'http' => [
                        'header'  => "Content-type: application/json\r\n",
                        'method'  => 'POST',
                        'content' => json_encode($api_data),
                        'ignore_errors' => true, // Important for debugging API calls
                    ],
                ];
                
                $context  = stream_context_create($options);
                $api_result = @file_get_contents($api_url, false, $context); // Use @ to suppress warnings
                
                // You might want to log or display API response for debugging
                $api_response = json_decode($api_result, true);
                if (isset($api_response['error'])) {
                    error_log("API Error generating certificate for solicitud ID $solicitud_id: " . $api_response['error']);
                    // Optionally set an error message for the user here as well
                } else {
                    $success = 'Solicitud aprobada y certificado generado correctamente.';
                }
            } else {
                $success = 'Solicitud rechazada correctamente.';
            }

            // Redirect to the list of requests after processing
            $_SESSION['success_message'] = $success;
            header('Location: solicitudes.php?estado=' . $new_status);
            exit();

        } else {
            $error = 'Error al procesar la solicitud. Por favor, inténtalo nuevamente.';
        }
    }
}

// Fetch request details along with student information
// Corrected: s.tipo should be used, not s.tipo_certificado
$query_solicitud = "SELECT s.*, e.nombres, e.apellidos, e.programa, e.nivel
                    FROM solicitudes s
                    JOIN estudiantes e ON s.estudiante_id = e.id
                    WHERE s.id = :solicitud_id";
$stmt_solicitud = $db->prepare($query_solicitud);
$stmt_solicitud->bindParam(':solicitud_id', $solicitud_id);
$stmt_solicitud->execute();
$solicitud = $stmt_solicitud->fetch(PDO::FETCH_ASSOC);

// If the request is not found, redirect back
if (!$solicitud) {
    header('Location: solicitudes.php');
    exit();
}

// Check for session messages
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Solicitud - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Detalle de Solicitud</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="solicitudes.php">Volver a Solicitudes</a>
                <a href="../logout.php">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <?php if ($success): ?>
            <div class="alert success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="request-detail">
            <h2>Solicitud #<?php echo htmlspecialchars($solicitud['id']); ?></h2>
            <div class="info-grid">
                <div><strong>Estudiante:</strong> <?php echo htmlspecialchars($solicitud['nombres'] . ' ' . $solicitud['apellidos']); ?></div>
                <div><strong>Programa:</strong> <?php echo htmlspecialchars($solicitud['programa']); ?></div>
                <div><strong>Nivel:</strong> <?php echo htmlspecialchars($solicitud['nivel']); ?></div>
                <div><strong>Tipo de Certificado:</strong> <?php echo htmlspecialchars(ucfirst($solicitud['tipo'])); ?></div> <div><strong>Fecha de Solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?></div>
                <div><strong>Estado Actual:</strong> <span class="status <?php echo htmlspecialchars($solicitud['estado']); ?>"><?php echo htmlspecialchars(ucfirst($solicitud['estado'])); ?></span></div>
                <?php if (!empty($solicitud['fecha_revision'])): ?>
                    <div><strong>Fecha de Revisión:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_revision'])); ?></div>
                <?php endif; ?>
            </div>

            <?php if ($solicitud['estado'] == 'pendiente'): ?>
                <h3>Acciones de Validación</h3>
                <form method="post" class="validation-form">
                    <input type="hidden" name="solicitud_id" value="<?php echo htmlspecialchars($solicitud['id']); ?>">
                    
                    <div class="form-group">
                        <label for="observaciones">Observaciones:</label>
                        <textarea id="observaciones" name="observaciones" rows="3" placeholder="Añadir comentarios o motivo de rechazo"></textarea>
                    </div>
                    
                    <div class="form-group action-buttons">
                        <button type="submit" name="action" value="aprobar" class="btn success">Aprobar Solicitud</button>
                        <button type="submit" name="action" value="rechazar" class="btn danger" onclick="return confirm('¿Está seguro de que desea rechazar esta solicitud? Asegúrese de añadir un motivo.');">Rechazar Solicitud</button>
                    </div>
                </form>
            <?php elseif ($solicitud['estado'] == 'rechazada' || $solicitud['estado'] == 'aprobada'): ?>
                <h3>Detalle de Validación</h3>
                <?php
                // Fetch validation details if available
                // CORRECTED: Changed 'administradores' to 'usuarios_admin' and 'a.username' to 'a.nombre'
                $query_validation_details = "SELECT v.*, a.nombre 
                                             FROM validaciones v
                                             JOIN usuarios_admin a ON v.validado_por = a.id
                                             WHERE v.solicitud_id = :solicitud_id
                                             ORDER BY v.fecha_validacion DESC LIMIT 1";
                $stmt_validation_details = $db->prepare($query_validation_details);
                $stmt_validation_details->bindParam(':solicitud_id', $solicitud_id);
                $stmt_validation_details->execute();
                $validation_detail = $stmt_validation_details->fetch(PDO::FETCH_ASSOC);

                if ($validation_detail):
                ?>
                    <div class="validation-info">
                        <p><strong>Validado por:</strong> <?php echo htmlspecialchars($validation_detail['nombre']); ?></p>
                        <p><strong>Fecha de Validación:</strong> <?php echo date('d/m/Y H:i', strtotime($validation_detail['fecha_validacion'])); ?></p>
                        <?php if (!empty($validation_detail['observaciones'])): ?>
                            <p><strong>Observaciones:</strong> <?php echo htmlspecialchars($validation_detail['observaciones']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert info">No se encontraron detalles de validación para esta solicitud.</div>
                <?php endif; ?>

                <?php if ($solicitud['estado'] == 'aprobada'): ?>
                    <?php
                    // Fetch certificate details if approved
                    $query_certificate = "SELECT c.codigo_verificacion, c.archivo_pdf AS url_pdf_from_db
                                          FROM certificados c
                                          WHERE c.solicitud_id = :solicitud_id";
                    $stmt_certificate = $db->prepare($query_certificate);
                    $stmt_certificate->bindParam(':solicitud_id', $solicitud_id);
                    $stmt_certificate->execute();
                    $certificate_detail = $stmt_certificate->fetch(PDO::FETCH_ASSOC);
                    
                    if ($certificate_detail):
                    ?>
                        <h3>Detalles del Certificado Generado</h3>
                        <div class="certificate-info">
                            <p><strong>Código de Verificación:</strong> 
                                <a href="../../verificar.php?codigo=<?php echo urlencode($certificate_detail['codigo_verificacion']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($certificate_detail['codigo_verificacion']); ?>
                                </a>
                            </p>
                            <?php if (!empty($certificate_detail['url_pdf_from_db'])): ?>
                                <p>
                                    <a href="<?php echo htmlspecialchars($certificate_detail['url_pdf_from_db']); ?>" class="btn" download>
                                        Descargar Certificado
                                    </a>
                                </p>
                            <?php else: ?>
                                <div class="alert warning">El archivo PDF del certificado no está disponible.</div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert warning">El certificado no ha sido generado o encontrado para esta solicitud.</div>
                    <?php endif; ?>
                <?php endif; ?>

            <?php endif; ?>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
        </div>
    </footer>
</body>
</html>