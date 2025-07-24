<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirigir según el tipo de usuario si ya está logueado
if (esUsuarioLogueado()) {
    if ($_SESSION['user_type'] == 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit();
}

// Página de inicio pública
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Sistema de Certificados Digitales</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="description" content="Sistema de emisión y verificación de certificados digitales del Centro de Idiomas CELEN - UNAP">
    <meta name="keywords" content="CELEN, UNAP, certificados, idiomas, inglés, francés">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="assets/img/logo.png" alt="Logo CELEN" class="logo-img">
                    <h1>CENTRO DE IDIOMAS</h1>
                </div>
                <nav>
                    <a href="login.php" class="nav-link">Iniciar Sesión</a>
                    <a href="registro.php" class="nav-link">Registrarse</a>
                    <a href="verificar.php" class="nav-link">Verificar Certificado</a>
                </nav>
            </div>
        </div>
    </header>
    
    <main class="container">
        <section class="hero">
            <div class="hero-content">
                <h2>Sistema de Certificados Digitales</h2>
                <p class="hero-subtitle">Centro de Idiomas CELEN - Universidad Nacional del Altiplano</p>
                <p class="hero-description">
                    Solicita, recibe y verifica tus certificados de idiomas de manera rápida, 
                    segura y completamente digital. Reduce el tiempo de espera y accede a 
                    tus documentos desde cualquier lugar.
                </p>
                <div class="cta-buttons">
                    <a href="registro.php" class="btn btn-primary">Regístrate como Estudiante</a>
                    <a href="verificar.php" class="btn btn-secondary">Verificar Certificado</a>
                </div>
            </div>
        </section>
        
        <section class="features">
            <h2 class="section-title">¿Por qué elegir nuestro sistema?</h2>
            <div class="features-grid">
                <div class="feature">
                    <div class="feature-icon">📄</div>
                    <h3>Certificados Digitales</h3>
                    <p>
                        Recibe tus certificados en formato PDF de alta calidad con 
                        código QR de verificación y firma digital.
                    </p>
                </div>
                <div class="feature">
                    <div class="feature-icon">⚡</div>
                    <h3>Proceso Rápido</h3>
                    <p>
                        Reduce el tiempo de espera de semanas a solo unos días. 
                        Solicita online y recibe notificaciones automáticas.
                    </p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🔍</div>
                    <h3>Verificación Instantánea</h3>
                    <p>
                        Cualquier persona puede verificar la autenticidad de tu 
                        certificado en línea las 24 horas del día.
                    </p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🔒</div>
                    <h3>Totalmente Seguro</h3>
                    <p>
                        Sistema con encriptación avanzada y códigos únicos 
                        que garantizan la autenticidad de cada documento.
                    </p>
                </div>
                <div class="feature">
                    <div class="feature-icon">📱</div>
                    <h3>Acceso Móvil</h3>
                    <p>
                        Accede a tu cuenta y descarga tus certificados desde 
                        cualquier dispositivo móvil o computadora.
                    </p>
                </div>
                <div class="feature">
                    <div class="feature-icon">🌍</div>
                    <h3>Reconocimiento Internacional</h3>
                    <p>
                        Certificados respaldados por la Universidad Nacional 
                        del Altiplano con validez internacional.
                    </p>
                </div>
            </div>
        </section>

        <section class="process">
            <h2 class="section-title">¿Cómo funciona?</h2>
            <div class="process-steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h3>Regístrate</h3>
                    <p>Crea tu cuenta con tus datos personales y académicos</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h3>Solicita</h3>
                    <p>Envía tu solicitud de certificado con la documentación requerida</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h3>Aprobación</h3>
                    <p>Nuestro equipo verifica y procesa tu solicitud</p>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <h3>Recibe</h3>
                    <p>Descarga tu certificado digital verificado</p>
                </div>
            </div>
        </section>

        <section class="contact-info">
            <h2 class="section-title">Información de Contacto</h2>
            <div class="contact-grid">
                <div class="contact-item">
                    <h3>🏢 Dirección</h3>
                    <p>Centro de Idiomas CELEN<br>
                    Universidad Nacional del Altiplano<br>
                    Ciudad Universitaria - Puno</p>
                </div>
                <div class="contact-item">
                    <h3>📞 Teléfono</h3>
                    <p>(051) 365719<br>
                    Anexo: 249</p>
                </div>
                <div class="contact-item">
                    <h3>✉️ Email</h3>
                    <p>celen@unap.edu.pe<br>
                    certificados@celen.unap.edu.pe</p>
                </div>
                <div class="contact-item">
                    <h3>🕒 Horarios</h3>
                    <p>Lunes a Viernes: 8:00 - 17:00<br>
                    Sábados: 8:00 - 12:00</p>
                </div>
            </div>
        </section>
    </main>
    
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>CERTI-CELEN</h3>
                    <p>Sistema oficial de certificados digitales del Centro de Idiomas CELEN - UNAP</p>
                </div>
                <div class="footer-section">
                    <h3>Enlaces Útiles</h3>
                    <ul>
                        <li><a href="verificar.php">Verificar Certificado</a></li>
                        <li><a href="registro.php">Registro de Estudiantes</a></li>
                        <li><a href="login.php">Iniciar Sesión</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Soporte</h3>
                    <p>¿Necesitas ayuda? Contáctanos:</p>
                    <p>📧 soporte@celen.unap.edu.pe</p>
                    <p>📞 (051) 365719 - Ext. 249</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Centro de Idiomas CELEN - Universidad Nacional del Altiplano. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="assets/js/script.js"></script>
</body>
</html>