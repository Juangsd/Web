<?php
// admin/dashboard.php

// Use __DIR__ to define paths relative to the current file's directory.
// Go up one level (from admin/ to certi-celen/) and then into 'includes/'.
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php'; // Make sure DB connection is available
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Verify authentication and user type
// Using SITE_URL defined in config.php for redirects
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    // If not logged in as admin, redirect to login page
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Ensure the database connection variable ($db from db.php) is accessible
// db.php defines a global $db, so you can just use it directly after including db.php.
// The 'global $pdo;' line is not needed and causes the confusion.
// REMOVE: global $pdo;

// Obtener información del administrador
// Assuming admin users are stored in the 'usuarios_admin' table
$query = "SELECT id, nombre, email, rol, estado FROM usuarios_admin WHERE id = :id";
// Change $pdo to $db here and in all subsequent queries
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// If for some reason the admin user is not found (e.g., session desync with DB),
// redirect them out or handle the error gracefully.
if (!$admin) {
    session_unset(); // Clear session variables
    session_destroy(); // Destroy the session
    header('Location: ' . SITE_URL . '/login.php?error=admin_not_found');
    exit();
}


// Estadísticas - Ensure you're using $db for queries
$query = "SELECT COUNT(*) as total FROM solicitudes";
$stmt = $db->query($query); // Use $db
$total_solicitudes = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as total FROM solicitudes WHERE estado = 'pendiente'";
$stmt = $db->query($query); // Use $db
$pendientes = $stmt->fetchColumn();

$query = "SELECT COUNT(*) as total FROM certificados";
$stmt = $db->query($query); // Use $db
$certificados = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style3.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Panel de Administración</h1>
            <nav>
                <a href="solicitudes.php">Solicitudes</a>
                <a href="certificados.php">Certificados</a>
                <a href="../logout.php">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <section class="stats">
            <div class="stat-card">
                <h3>Solicitudes Totales</h3>
                <p><?php echo $total_solicitudes; ?></p>
            </div>
            <div class="stat-card">
                <h3>Solicitudes Pendientes</h3>
                <p><?php echo $pendientes; ?></p>
            </div>
            <div class="stat-card">
                <h3>Certificados Emitidos</h3>
                <p><?php echo $certificados; ?></p>
            </div>
        </section>
        
        <section>
            <h2>Solicitudes Recientes</h2>
            <?php
            // Use $db for queries
            // Correct the JOIN: use 'estudiantes' table, not 'users'
            $query = "SELECT s.*, e.nombres, e.apellidos 
                      FROM solicitudes s
                      JOIN estudiantes e ON s.estudiante_id = e.id 
                      ORDER BY s.fecha_solicitud DESC
                      LIMIT 5";
            $stmt = $db->query($query); // Use $db
            $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <table>
                <thead>
                    <tr>
                        <th>Estudiante</th>
                        <th>Tipo</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitudes)): ?>
                        <tr>
                            <td colspan="5">No hay solicitudes recientes.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudes as $solicitud): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($solicitud['nombres'] . ' ' . $solicitud['apellidos']); ?></td>
                            <td><?php echo ucfirst($solicitud['tipo']); ?></td> <td><?php echo date('d/m/Y', strtotime($solicitud['fecha_solicitud'])); ?></td>
                            <td>
                                <span class="status <?php echo $solicitud['estado']; ?>">
                                    <?php echo ucfirst($solicitud['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="validar.php?id=<?php echo $solicitud['id']; ?>" class="btn">Revisar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="actions">
                <a href="solicitudes.php" class="btn">Ver Todas las Solicitudes</a>
            </div>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> CELEN - UNAP</p>
        </div>
    </footer>
</body>
</html>