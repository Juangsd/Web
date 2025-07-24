<?php
// admin/certificados.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

global $db;

requiereAutenticacion('admin');

$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$programa_filtro = isset($_GET['programa']) ? trim($_GET['programa']) : '';

$query = "SELECT c.*, s.tipo, s.fecha_solicitud, 
                 e.nombres, e.apellidos, e.programa, e.nivel,
                 c.archivo_pdf AS url_pdf_from_db 
          FROM certificados c
          JOIN solicitudes s ON c.solicitud_id = s.id
          JOIN estudiantes e ON s.estudiante_id = e.id
          WHERE 1=1";

$params = [];

if (!empty($busqueda)) {
    // Use unique placeholders for each LIKE clause
    $query .= " AND (e.nombres LIKE :busqueda1 OR e.apellidos LIKE :busqueda2 OR c.codigo_verificacion LIKE :busqueda3)";
    $params[':busqueda1'] = "%$busqueda%";
    $params[':busqueda2'] = "%$busqueda%";
    $params[':busqueda3'] = "%$busqueda%";
}

if (!empty($programa_filtro)) {
    $query .= " AND e.programa = :programa_filtro";
    $params[':programa_filtro'] = $programa_filtro;
}

$query .= " ORDER BY c.fecha_emision DESC";

$stmt = $db->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute(); // This is line 51
$certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener programas para filtro (assuming 'programa' is the column name in 'estudiantes' table)
$query = "SELECT DISTINCT programa FROM estudiantes ORDER BY programa";
$stmt = $db->query($query);
$programas_list = $stmt->fetchAll(PDO::FETCH_COLUMN); // Renamed to avoid conflict
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificados Emitidos - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Certificados Emitidos</h1>
            <nav>
                <a href="dashboard.php">Inicio</a>
                <a href="solicitudes.php">Solicitudes</a>
                <a href="../logout.php">Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <section>
            <h2>Búsqueda de Certificados</h2>
            
            <form method="get" class="search-form">
                <div class="form-group">
                    <label for="busqueda">Buscar:</label>
                    <input type="text" id="busqueda" name="busqueda" 
                           placeholder="Nombre, apellido o código" 
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                </div>
                
                <div class="form-group">
                    <label for="programa">Programa:</label>
                    <select id="programa" name="programa">
                        <option value="">Todos los programas</option>
                        <?php foreach ($programas_list as $prog): // Use the renamed variable ?>
                            <option value="<?php echo htmlspecialchars($prog); ?>" 
                                <?php echo $programa_filtro == $prog ? 'selected' : ''; ?>> <?php echo htmlspecialchars($prog); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn">Buscar</button>
                    <a href="certificados.php" class="btn secondary">Limpiar</a>
                </div>
            </form>
            
            <?php if (empty($certificados)): ?>
                <div class="alert info">
                    No se encontraron certificados con los criterios de búsqueda.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Programa</th>
                            <th>Nivel</th>
                            <th>Tipo</th>
                            <th>Fecha Emisión</th>
                            <th>Código</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificados as $certificado): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($certificado['nombres'] . ' ' . $certificado['apellidos']); ?></td>
                            <td><?php echo htmlspecialchars($certificado['programa']); ?></td>
                            <td><?php echo htmlspecialchars($certificado['nivel']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($certificado['tipo'])); ?></td>
                            <td>
                                <?php 
                                    if (!empty($certificado['fecha_emision'])) {
                                        echo date('d/m/Y', strtotime($certificado['fecha_emision']));
                                    } else {
                                        echo 'N/A';
                                    }
                                ?>
                            </td>
                            <td>
                                <a href="../../verificar.php?codigo=<?php echo urlencode($certificado['codigo_verificacion']); ?>" 
                                   target="_blank" 
                                   title="Verificar certificado">
                                    <?php echo htmlspecialchars($certificado['codigo_verificacion']); ?>
                                </a>
                            </td>
                            <td class="actions">
                                <?php if (!empty($certificado['url_pdf_from_db'])): ?>
                                <a href="<?php echo htmlspecialchars($certificado['url_pdf_from_db']); ?>" 
                                   class="btn small primary" 
                                   download="certificado_<?php echo htmlspecialchars($certificado['codigo_verificacion']); ?>.pdf">
                                    <i class="icon-download"></i> Descargar
                                </a>
                                <?php else: ?>
                                    <span class="btn small primary disabled">No PDF</span>
                                <?php endif; ?>
                                
                                <a href="ver_certificado.php?id=<?php echo htmlspecialchars($certificado['id']); ?>" 
                                   class="btn small" 
                                   target="_blank">
                                    <i class="icon-eye"></i> Ver
                                </a>
                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): // Explicit check for admin type ?>
                                    <a href="anular_certificado.php?id=<?php echo htmlspecialchars($certificado['id']); ?>" 
                                       class="btn small danger" 
                                       onclick="return confirm('¿Está seguro de anular este certificado?');">
                                        <i class="icon-cancel"></i> Anular
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="pagination">
                    <?php
                    // Make sure countTotalCertificados function is defined in functions.php
                    // Example (add this to functions.php if it's not there):
                    /*
                    function countTotalCertificados($busqueda, $programa_filtro) {
                        global $db;
                        $query = "SELECT COUNT(c.id) 
                                  FROM certificados c
                                  JOIN solicitudes s ON c.solicitud_id = s.id
                                  JOIN estudiantes e ON s.estudiante_id = e.id
                                  WHERE 1=1";
                        $params = [];
                        if (!empty($busqueda)) {
                            $query .= " AND (e.nombres LIKE :busqueda OR e.apellidos LIKE :busqueda OR c.codigo_verificacion LIKE :busqueda)";
                            $params[':busqueda'] = "%$busqueda%";
                        }
                        if (!empty($programa_filtro)) {
                            $query .= " AND e.programa = :programa_filtro";
                            $params[':programa_filtro'] = $programa_filtro;
                        }
                        $stmt = $db->prepare($query);
                        foreach ($params as $key => $value) {
                            $stmt->bindValue($key, $value);
                        }
                        $stmt->execute();
                        return $stmt->fetchColumn();
                    }
                    */

                    // Pass $programa_filtro to the function
                    $totalCertificados = countTotalCertificados($busqueda, $programa_filtro); 
                    $porPagina = 20;
                    $paginas = ceil($totalCertificados / $porPagina);
                    $paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
                    
                    if ($paginas > 1) {
                        // Ensure page number is within bounds
                        $paginaActual = max(1, min($paginaActual, $paginas));

                        $queryParams = http_build_query([
                            'busqueda' => $busqueda,
                            'programa' => $programa_filtro
                        ]);

                        if ($paginaActual > 1) {
                            echo '<a href="?pagina='.($paginaActual-1).'&'.$queryParams.'">&laquo; Anterior</a>';
                        }
                        
                        for ($i = 1; $i <= $paginas; $i++) {
                            if ($i == $paginaActual) {
                                echo '<span class="current">'.$i.'</span>';
                            } else {
                                echo '<a href="?pagina='.$i.'&'.$queryParams.'">'.$i.'</a>';
                            }
                        }
                        
                        if ($paginaActual < $paginas) {
                            echo '<a href="?pagina='.($paginaActual+1).'&'.$queryParams.'">Siguiente &raquo;</a>';
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?></p>
        </div>
    </footer>
    
    <script src="../assets/js/script.js"></script>
    <script>
        // Función para confirmar antes de anular un certificado
        document.querySelectorAll('.btn.danger').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (!confirm('¿Está seguro de que desea anular este certificado?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>