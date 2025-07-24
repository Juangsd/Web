<?php
// verificar.php

// Ensure all necessary files are included using __DIR__ for robust paths
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php'; // Ensure DB connection is available
require_once __DIR__ . '/includes/functions.php';

global $db; // Access the global database connection

$codigo_verificacion = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

$certificate_found = false;
$certificate_details = null;
$error_message = '';

if (!empty($codigo_verificacion)) {
    // CORRECTED: Changed 's.tipo_certificado' to 's.tipo'
    $query = "SELECT 
                c.*, 
                s.tipo, s.fecha_solicitud, s.comentarios, 
                s.fecha_inicio, s.fecha_fin, s.horas_academicas,
                e.nombres, e.apellidos, e.programa, e.nivel
              FROM certificados c
              JOIN solicitudes s ON c.solicitud_id = s.id
              JOIN estudiantes e ON s.estudiante_id = e.id
              WHERE c.codigo_verificacion = :codigo"; // Line 20 (or near it, depending on exact content)

    $stmt = $db->prepare($query);
    $stmt->bindParam(':codigo', $codigo_verificacion);
    $stmt->execute();
    $certificate_details = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($certificate_details) {
        $certificate_found = true;

        // Record verification attempt
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
        
        // Basic geo-location (optional, requires external service or local database)
        // For simplicity, we'll just log IP for now.
        $country = ''; // You could use a geo-IP API here if needed
        $city = '';    // You could use a geo-IP API here if needed

        $insert_verification_query = "INSERT INTO verificaciones 
                                      (certificado_id, ip, user_agent, resultado, codigo_buscado, pais, ciudad) 
                                      VALUES 
                                      (:certificado_id, :ip, :user_agent, :resultado, :codigo_buscado, :pais, :ciudad)";
        $stmt_insert = $db->prepare($insert_verification_query);
        
        $result_status = 'valido'; // Assume valid if found
        if ($certificate_details['estado'] === 'revocado') {
            $result_status = 'revocado';
        }

        $stmt_insert->bindParam(':certificado_id', $certificate_details['id']);
        $stmt_insert->bindParam(':ip', $ip_address);
        $stmt_insert->bindParam(':user_agent', $user_agent);
        $stmt_insert->bindParam(':resultado', $result_status);
        $stmt_insert->bindParam(':codigo_buscado', $codigo_verificacion);
        $stmt_insert->bindParam(':pais', $country);
        $stmt_insert->bindParam(':ciudad', $city);
        $stmt_insert->execute();

    } else {
        $error_message = 'El código de verificación no es válido o no existe.';
        
        // Record invalid verification attempt
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
        $country = ''; 
        $city = '';    

        $insert_verification_query = "INSERT INTO verificaciones 
                                      (certificado_id, ip, user_agent, resultado, codigo_buscado, pais, ciudad) 
                                      VALUES 
                                      (NULL, :ip, :user_agent, 'invalido', :codigo_buscado, :pais, :ciudad)";
        $stmt_insert = $db->prepare($insert_verification_query);
        
        $stmt_insert->bindParam(':ip', $ip_address);
        $stmt_insert->bindParam(':user_agent', $user_agent);
        $stmt_insert->bindParam(':codigo_buscado', $codigo_verificacion);
        $stmt_insert->bindParam(':pais', $country);
        $stmt_insert->bindParam(':ciudad', $city);
        $stmt_insert->execute();
    }
} else {
    $error_message = 'Por favor, introduce un código de verificación.';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Certificado - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style5.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Verificación de Certificados</h1>
            <nav>
                <a href="index.php">Inicio</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <section class="verification-form">
            <h2>Ingresa el código de verificación</h2>
            <form action="" method="GET">
                <div class="form-group">
                    <label for="codigo">Código de Verificación:</label>
                    <input type="text" id="codigo" name="codigo" value="<?php echo htmlspecialchars($codigo_verificacion); ?>" required>
                </div>
                <button type="submit" class="btn">Verificar</button>
            </form>
        </section>

        <?php if (!empty($error_message)): ?>
            <div class="alert error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if ($certificate_found): ?>
            <section class="certificate-details">
                <h2>Detalles del Certificado</h2>
                <?php if ($certificate_details['estado'] === 'revocado'): ?>
                    <div class="alert warning">
                        Este certificado ha sido **REVOCADO**. No es válido.
                    </div>
                <?php endif; ?>

                <div class="info-grid">
                    <div><strong>Nombre del Estudiante:</strong> <?php echo htmlspecialchars($certificate_details['nombres'] . ' ' . $certificate_details['apellidos']); ?></div>
                    <div><strong>Programa:</strong> <?php echo htmlspecialchars($certificate_details['programa']); ?></div>
                    <div><strong>Nivel:</strong> <?php echo htmlspecialchars($certificate_details['nivel']); ?></div>
                    <div><strong>Tipo de Certificado:</strong> <?php echo htmlspecialchars(ucfirst($certificate_details['tipo'])); ?></div>
                    <div><strong>Código de Verificación:</strong> <?php echo htmlspecialchars($certificate_details['codigo_verificacion']); ?></div>
                    <div><strong>Fecha de Emisión:</strong> <?php echo date('d/m/Y', strtotime($certificate_details['fecha_emision'])); ?></div>
                    <?php if (!empty($certificate_details['fecha_solicitud'])): ?>
                        <div><strong>Fecha de Solicitud:</strong> <?php echo date('d/m/Y', strtotime($certificate_details['fecha_solicitud'])); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($certificate_details['fecha_inicio'])): ?>
                        <div><strong>Fecha de Inicio del Curso:</strong> <?php echo date('d/m/Y', strtotime($certificate_details['fecha_inicio'])); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($certificate_details['fecha_fin'])): ?>
                        <div><strong>Fecha de Fin del Curso:</strong> <?php echo date('d/m/Y', strtotime($certificate_details['fecha_fin'])); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($certificate_details['horas_academicas'])): ?>
                        <div><strong>Horas Académicas:</strong> <?php echo htmlspecialchars($certificate_details['horas_academicas']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($certificate_details['comentarios'])): ?>
                        <div><strong>Observaciones de Solicitud:</strong> <?php echo htmlspecialchars($certificate_details['comentarios']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($certificate_details['qr_code'])): ?>
                        <div class="qr-code">
                            <strong>Código QR:</strong><br>
                            <img src="<?php echo htmlspecialchars($certificate_details['qr_code']); ?>" alt="QR Code">
                        </div>
                    <?php endif; ?>
                </div>
                <p>
                    <?php if (!empty($certificate_details['archivo_pdf'])): ?>
                        <a href="/certi-celen/pdf/Certificado.pdf" class="btn" download>Descargar Certificado Original</a>
                    <?php else: ?>
                        <span class="alert warning">Archivo PDF no disponible.</span>
                    <?php endif; ?>
                </p>
            </section>
        <?php endif; ?>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
        </div>
    </footer>
</body>
</html>