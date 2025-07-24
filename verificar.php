<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : '';

if (!empty($_POST['codigo'])) {
    $codigo = trim($_POST['codigo']);
}

$certificado = null;
if (!empty($codigo)) {
    $certificado = verificarCertificado($codigo);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Certificado - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>Verificar Certificado CELEN - UNAP</h1>
            <nav>
                <a href="index.php">Inicio</a>
            </nav>
        </div>
    </header>
    
    <main class="container">
        <section>
            <h2>Verificar Autenticidad de Certificado</h2>
            <p>Ingrese el código de verificación que aparece en el certificado para validar su autenticidad.</p>
            
            <form method="post" class="verify-form">
                <div class="form-group">
                    <label for="codigo">Código de Verificación:</label>
                    <input type="text" id="codigo" name="codigo" required 
                           value="<?php echo htmlspecialchars($codigo); ?>">
                </div>
                <button type="submit" class="btn">Verificar</button>
            </form>
            
            <?php if (!empty($codigo)): ?>
                <div class="verification-result">
                    <?php if ($certificado): ?>
                        <div class="result valid">
                            <h3>✅ Certificado Válido</h3>
                            <p>Este certificado ha sido emitido por el Centro de Idiomas CELEN de la Universidad Nacional del Altiplano.</p>
                            
                            <div class="certificate-details">
                                <h4>Detalles del Certificado:</h4>
                                <p><strong>Estudiante:</strong> <?php echo htmlspecialchars($certificado['nombres'] . ' ' . $certificado['apellidos']); ?></p>
                                <p><strong>Programa:</strong> <?php echo htmlspecialchars($certificado['programa']); ?></p>
                                <p><strong>Nivel:</strong> <?php echo htmlspecialchars($certificado['nivel']); ?></p>
                                <p><strong>Fecha de Emisión:</strong> <?php echo date('d/m/Y', strtotime($certificado['fecha_emision'])); ?></p>
                            </div>
                            
                            <div class="actions">
                                <a href="<?php echo $certificado['url_pdf']; ?>" class="btn" download>Descargar Certificado</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="result invalid">
                            <h3>❌ Certificado No Válido</h3>
                            <p>El código proporcionado no corresponde a ningún certificado emitido por nuestro sistema.</p>
                            <p>Por favor, verifique que el código sea correcto o contacte al Centro de Idiomas CELEN para más información.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Centro de Idiomas CELEN - Universidad Nacional del Altiplano</p>
        </div>
    </footer>
</body>
</html>