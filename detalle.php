<?php
// admin/detalle.php

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

// Fetch request details along with student information
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

// Check for session messages (though less common for a read-only page, good practice)
$success = '';
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
$error = '';
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
    <title>Detalle de Solicitud #<?php echo htmlspecialchars($solicitud['id']); ?> - <?php echo SITE_NAME; ?></title>
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
            <h2>Detalle de Solicitud #<?php echo htmlspecialchars($solicitud['id']); ?></h2>
            <div class="info-grid">
                <div><strong>Estudiante:</strong> <?php echo htmlspecialchars($solicitud['nombres'] . ' ' . $solicitud['apellidos']); ?></div>
                <div><strong>Programa:</strong> <?php echo htmlspecialchars($solicitud['programa']); ?></div>
                <div><strong>Nivel:</strong> <?php echo htmlspecialchars($solicitud['nivel']); ?></div>
                <div><strong>Tipo de Certificado:</strong> <?php echo htmlspecialchars(ucfirst($solicitud['tipo'])); ?></div>
                <div><strong>Fecha de Solicitud:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?></div>
                <div><strong>Estado Actual:</strong> <span class="status <?php echo htmlspecialchars($solicitud['estado']); ?>"><?php echo htmlspecialchars(ucfirst($solicitud['estado'])); ?></span></div>
                <?php if (!empty($solicitud['fecha_revision'])): ?>
                    <div><strong>Fecha de Revisión:</strong> <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_revision'])); ?></div>
                <?php endif; ?>
                 <?php if (!empty($solicitud['fecha_inicio'])): ?>
                    <div><strong>Fecha de Inicio Curso:</strong> <?php echo date('d/m/Y', strtotime($solicitud['fecha_inicio'])); ?></div>
                <?php endif; ?>
                <?php if (!empty($solicitud['fecha_fin'])): ?>
                    <div><strong>Fecha de Fin Curso:</strong> <?php echo date('d/m/Y', strtotime($solicitud['fecha_fin'])); ?></div>
                <?php endif; ?>
                <?php if (!empty($solicitud['horas_academicas'])): ?>
                    <div><strong>Horas Académicas:</strong> <?php echo htmlspecialchars($solicitud['horas_academicas']); ?></div>
                <?php endif; ?>
                 <?php if (!empty($solicitud['archivo_adjunto'])): ?>
                    <div>
                        <strong>Archivo Adjunto:</strong> 
                        <a href="<?php echo htmlspecialchars($solicitud['archivo_adjunto']); ?>" target="_blank" class="btn small">Ver Archivo</a>
                    </div>
                <?php endif; ?>
            </div>

            <h3>Detalles de Validación</h3>
            <?php
            // Fetch validation details if available
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
        </section>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
        </div>
    </footer>
</body>
</html>