<?php
// student/mis-certificados.php

// Corrected includes using __DIR__ for robustness
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php'; // Ensure DB connection is available
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify authentication
requiereAutenticacion('student');

// Access the global database connection variable
global $db;

// Obtener información del estudiante
$query = "SELECT * FROM estudiantes WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

// Obtener certificados emitidos
// Corrected: s.tipo should be used, not s.tipo_certificado
// Corrected: c.archivo_pdf should be selected, not c.url_pdf
$query = "SELECT c.*, s.tipo, s.fecha_solicitud, c.archivo_pdf AS url_pdf_from_db
          FROM certificados c
          JOIN solicitudes s ON c.solicitud_id = s.id
          WHERE s.estudiante_id = :id
          ORDER BY c.fecha_emision DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Certificados - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Mis Certificados - <?php echo htmlspecialchars($estudiante['nombres']); ?></h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="solicitar.php">Nueva Solicitud</a>
                <a href="../logout.php">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <section>
            <h2>Certificados Emitidos</h2>
            
            <?php if (empty($certificados)): ?>
                <div class="alert info">
                    No tienes certificados emitidos aún.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Fecha de Solicitud</th>
                            <th>Fecha de Emisión</th>
                            <th>Código de Verificación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificados as $certificado): ?>
                        <tr>
                            <td><?php echo ucfirst(htmlspecialchars($certificado['tipo'])); ?></td>
                            
                            <td>
                                <?php 
                                    if (!empty($certificado['fecha_solicitud'])) {
                                        echo date('d/m/Y', strtotime($certificado['fecha_solicitud']));
                                    } else {
                                        echo 'N/A';
                                    }
                                ?>
                            </td>
                            
                            <td>
                                <?php 
                                    if (!empty($certificado['fecha_emision'])) {
                                        echo date('d/m/Y', strtotime($certificado['fecha_emision']));
                                    } else {
                                        echo 'N/A';
                                    }
                                ?>
                            </td>
                            
                            <td class="monospace"><?php echo htmlspecialchars($certificado['codigo_verificacion']); ?></td>
                            <td>
                                <?php if (!empty($certificado['url_pdf_from_db'])): ?>
                                    <a href="<?php echo htmlspecialchars($certificado['url_pdf_from_db']); ?>" class="btn" download>Descargar</a>
                                <?php else: ?>
                                    No disponible
                                <?php endif; ?>
                                
                                <a href="../../verificar.php?codigo=<?php echo htmlspecialchars($certificado['codigo_verificacion']); ?>" class="btn secondary">Verificar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CELEN - UNAP</p>
        </div>
    </footer>
</body>
</html>