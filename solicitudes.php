<?php
// admin/solicitudes.php

// Corrected includes using __DIR__ for robustness
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php'; // Ensure DB connection is available
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Access the global database connection variable
global $db;

// Verificar autenticación
requiereAutenticacion('admin');

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['accion'])) {
    $solicitud_id = $_POST['solicitud_id'];
    $accion = $_POST['accion'];
    $observaciones = trim($_POST['observaciones'] ?? '');
    
    // Validar que la solicitud exista y esté pendiente
    $query = "SELECT id FROM solicitudes WHERE id = :id AND estado = 'pendiente'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $solicitud_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        // Actualizar estado
        $nuevo_estado = ($accion == 'aprobar') ? 'aprobada' : 'rechazada'; // Ensure 'aprobada' matches ENUM
        
        $query = "UPDATE solicitudes SET estado = :estado, fecha_revision = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':estado', $nuevo_estado);
        $stmt->bindParam(':id', $solicitud_id);
        $stmt->execute();
        
        // Registrar validación
        // It seems your 'validaciones' table has `validado_por` as INT (user_id)
        $query = "INSERT INTO validaciones 
                  (solicitud_id, validado_por, observaciones, fecha_validacion) 
                  VALUES 
                  (:solicitud_id, :validado_por, :observaciones, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':solicitud_id', $solicitud_id);
        $stmt->bindParam(':validado_por', $_SESSION['user_id']);
        $stmt->bindParam(':observaciones', $observaciones);
        $stmt->execute();
        
        // Si se aprueba, generar certificado automáticamente
        if ($accion == 'aprobar') {
            // Llamar a la API de generación (ensure this path is also correct)
            $url = SITE_URL . '/api/generar.php';
            $data = ['solicitud_id' => $solicitud_id];
            
            $options = [
                'http' => [
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($data),
                    'ignore_errors' => true, // Important for debugging API calls
                ],
            ];
            
            $context  = stream_context_create($options);
            $result = @file_get_contents($url, false, $context); // Use @ to suppress warnings
            
            // Optional: Check API response for debugging
            // if ($result === FALSE) { /* Handle error */ }
            // $response_data = json_decode($result, true);
            // if (isset($response_data['error'])) { /* Log API error */ }
        }
        
        $_SESSION['success_message'] = 'Solicitud ' . $nuevo_estado . ' correctamente';
        header('Location: solicitudes.php?estado=' . $nuevo_estado); // Redirect to show relevant state
        exit();
    } else {
        $_SESSION['error_message'] = 'La solicitud no pudo ser procesada o ya no está pendiente.';
        header('Location: solicitudes.php?estado=pendiente');
        exit();
    }
}

// Mostrar mensajes de éxito
$success = '';
if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
// Mostrar mensajes de error
$error = '';
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}


// Filtrar por estado
$estado = isset($_GET['estado']) ? htmlspecialchars($_GET['estado']) : 'pendiente'; // Sanitize input

// Obtener solicitudes
// Corrected: s.tipo should be used, not s.tipo_certificado
$query = "SELECT s.*, e.nombres, e.apellidos, e.programa, e.nivel 
          FROM solicitudes s
          JOIN estudiantes e ON s.estudiante_id = e.id
          WHERE s.estado = :estado
          ORDER BY s.fecha_solicitud DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':estado', $estado);
$stmt->execute();
$solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Gestión de Solicitudes</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="certificados.php">Certificados</a>
                <a href="../logout.php">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <?php if ($success): ?>
            <div class="alert success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <section>
            <div class="filters">
                <h2>Filtrar por estado:</h2>
                <div class="filter-buttons">
                    <a href="?estado=pendiente" class="btn <?php echo $estado == 'pendiente' ? 'active' : ''; ?>">Pendientes</a>
                    <a href="?estado=aprobada" class="btn <?php echo $estado == 'aprobada' ? 'active' : ''; ?>">Aprobadas</a> <a href="?estado=rechazada" class="btn <?php echo $estado == 'rechazada' ? 'active' : ''; ?>">Rechazadas</a>
                </div>
            </div>
            
            <?php if (empty($solicitudes)): ?>
                <div class="alert info">
                    No hay solicitudes en este estado.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Programa</th>
                            <th>Nivel</th>
                            <th>Tipo</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $solicitud): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($solicitud['nombres'] . ' ' . $solicitud['apellidos']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud['programa']); ?></td>
                            <td><?php echo htmlspecialchars($solicitud['nivel']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($solicitud['tipo'])); ?></td>
                            <td>
                                <?php 
                                    if (!empty($solicitud['fecha_solicitud'])) {
                                        echo date('d/m/Y', strtotime($solicitud['fecha_solicitud']));
                                    } else {
                                        echo 'N/A';
                                    }
                                ?>
                            </td>
                            <td>
                                <span class="status <?php echo htmlspecialchars($solicitud['estado'] ?? 'desconocido'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($solicitud['estado'] ?? 'Desconocido')); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($solicitud['estado'] == 'pendiente'): ?>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="solicitud_id" value="<?php echo htmlspecialchars($solicitud['id']); ?>">
                                        <input type="hidden" name="accion" value="aprobar">
                                        <textarea name="observaciones" placeholder="Observaciones (opcional)" rows="1"></textarea>
                                        <button type="submit" class="btn small">Aprobar</button>
                                    </form>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="solicitud_id" value="<?php echo htmlspecialchars($solicitud['id']); ?>">
                                        <input type="hidden" name="accion" value="rechazar">
                                        <textarea name="observaciones" placeholder="Motivo de rechazo" rows="1" required></textarea>
                                        <button type="submit" class="btn small secondary">Rechazar</button>
                                    </form>
                                <?php else: ?>
                                    <a href="detalle.php?id=<?php echo htmlspecialchars($solicitud['id']); ?>" class="btn small">Ver Detalle</a>
                                <?php endif; ?>
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