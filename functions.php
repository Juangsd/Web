<?php
require_once 'db.php';
require_once 'config.php';

function generarCodigoVerificacion() {
    return strtoupper(bin2hex(random_bytes(16))); // Código más corto y legible
}

function generarQR($data, $filename) {
    // Verificar si existe la librería QR
    $qrLibPath = BASE_PATH . '/libs/phpqrcode/qrlib.php';
    if (!file_exists($qrLibPath)) {
        error_log("Librería QR no encontrada en: " . $qrLibPath);
        return false;
    }
    
    require_once $qrLibPath;
    
    try {
        $path = QR_DIR . $filename . '.png';
        QRcode::png($data, $path, QR_ECLEVEL_L, 10, 2);
        return $path;
    } catch (Exception $e) {
        error_log("Error generando QR: " . $e->getMessage());
        return false;
    }
}

function generarPDF($datos) {
    // Verificar si existe la librería TCPDF
    $tcpdfPath = BASE_PATH . '/libs/tcpdf/tcpdf.php';
    if (!file_exists($tcpdfPath)) {
        error_log("Librería TCPDF no encontrada en: " . $tcpdfPath);
        return false;
    }
    
    require_once $tcpdfPath;
    
    try {
        // Crear nuevo documento PDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configurar documento
        $pdf->SetCreator('CERTI-CELEN');
        $pdf->SetAuthor('CELEN - UNAP');
        $pdf->SetTitle('Certificado de ' . ($datos['tipo'] ?? 'Idiomas'));
        $pdf->SetSubject('Certificado CELEN');
        $pdf->SetKeywords('CELEN, UNAP, Certificado, Idiomas');
        
        // Configurar márgenes
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        
        // Agregar página
        $pdf->AddPage();
        $pdf->SetFont('helvetica', '', 12);
        
        // Contenido del certificado
        $html = '
        <div style="text-align: center; font-family: helvetica;">
            <img src="' . BASE_PATH . '/assets/img/logo.png" width="80" style="margin-bottom: 10px;"><br>
            <h1 style="color: #1e3a8a; margin-bottom: 5px;">UNIVERSIDAD NACIONAL DEL ALTIPLANO</h1>
            <h2 style="color: #1e40af; margin-bottom: 20px;">CENTRO DE IDIOMAS CELEN</h2>
            
            <div style="border: 3px solid #1e40af; padding: 20px; margin: 20px 0;">
                <h3 style="color: #1e40af; margin-bottom: 15px;">CERTIFICADO</h3>
                <p style="font-size: 14px; margin-bottom: 10px;">Se certifica que:</p>
                <h2 style="color: #1e3a8a; font-weight: bold; margin: 15px 0;">' . strtoupper($datos['nombre_completo']) . '</h2>
                <p style="font-size: 14px; margin: 10px 0;">Ha completado satisfactoriamente el programa de</p>
                <h3 style="color: #1e40af; margin: 10px 0;">' . strtoupper($datos['programa']) . '</h3>
                <p style="font-size: 14px; margin: 10px 0;">Nivel alcanzado: <strong>' . strtoupper($datos['nivel']) . '</strong></p>
                <p style="font-size: 12px; margin: 15px 0;">Fecha de emisión: ' . date('d/m/Y') . '</p>
            </div>
            
            <div style="margin-top: 30px;">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%; text-align: center;">
                            <div style="border-top: 1px solid #000; width: 200px; margin: 0 auto;">
                                <p style="font-size: 10px; margin-top: 5px;">Director CELEN</p>
                            </div>
                        </td>
                        <td style="width: 50%; text-align: center;">
                            <img src="' . $datos['qr_path'] . '" width="80"><br>
                            <p style="font-size: 8px; margin-top: 5px;">Código: ' . $datos['codigo_verificacion'] . '</p>
                            <p style="font-size: 8px;">Verifique en: ' . SITE_URL . '/verificar.php</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Generar nombre único para el archivo
        $pdf_filename = 'certificado_' . $datos['codigo_verificacion'] . '_' . date('Ymd') . '.pdf';
        $pdf_path = UPLOAD_DIR . $pdf_filename;
        
        // Guardar PDF
        $pdf->Output($pdf_path, 'F');
        
        return [
            'success' => true,
            'filename' => $pdf_filename,
            'path' => $pdf_path,
            'url' => SITE_URL . '/uploads/' . $pdf_filename
        ];
    } catch (Exception $e) {
        error_log("Error generando PDF: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error al generar el PDF'];
    }
}

function verificarCertificado($codigo) {
    global $db;
    
    try {
        $codigo = strtoupper(trim($codigo));
        
        $query = "SELECT c.*, s.tipo as solicitud_tipo, s.fecha_solicitud,
                         e.nombres, e.apellidos, e.programa, e.nivel, e.email
                  FROM certificados c
                  JOIN solicitudes s ON c.solicitud_id = s.id
                  JOIN estudiantes e ON s.estudiante_id = e.id
                  WHERE c.codigo_verificacion = :codigo AND c.estado = 'emitido'";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':codigo', $codigo, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $certificado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Registrar la verificación
            registrarVerificacion($certificado['id'], 'valido');
            
            return [
                'valido' => true,
                'datos' => $certificado
            ];
        } else {
            // Registrar intento de verificación fallido
            registrarVerificacion(null, 'invalido', $codigo);
            return ['valido' => false];
        }
    } catch (PDOException $e) {
        error_log("Error en verificarCertificado: " . $e->getMessage());
        return ['valido' => false, 'error' => 'Error interno del sistema'];
    }
}

function registrarVerificacion($certificado_id, $resultado, $codigo = null) {
    global $db;
    
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $query = "INSERT INTO verificaciones (certificado_id, ip, user_agent, resultado, codigo_buscado, fecha) 
                  VALUES (:certificado_id, :ip, :user_agent, :resultado, :codigo_buscado, NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':certificado_id', $certificado_id, PDO::PARAM_INT);
        $stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
        $stmt->bindParam(':user_agent', $user_agent, PDO::PARAM_STR);
        $stmt->bindParam(':resultado', $resultado, PDO::PARAM_STR);
        $stmt->bindParam(':codigo_buscado', $codigo, PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error registrando verificación: " . $e->getMessage());
        return false;
    }
}

function obtenerEstadisticas() {
    global $db;
    
    try {
        $stats = [];
        
        // Total estudiantes registrados
        $query = "SELECT COUNT(*) as total FROM estudiantes WHERE estado = 'activo'";
        $stmt = $db->query($query);
        $stats['estudiantes'] = $stmt->fetch()['total'];
        
        // Total solicitudes
        $query = "SELECT COUNT(*) as total FROM solicitudes";
        $stmt = $db->query($query);
        $stats['solicitudes'] = $stmt->fetch()['total'];
        
        // Total certificados emitidos
        $query = "SELECT COUNT(*) as total FROM certificados WHERE estado = 'emitido'";
        $stmt = $db->query($query);
        $stats['certificados'] = $stmt->fetch()['total'];
        
        // Verificaciones este mes
        $query = "SELECT COUNT(*) as total FROM verificaciones WHERE MONTH(fecha) = MONTH(NOW()) AND YEAR(fecha) = YEAR(NOW())";
        $stmt = $db->query($query);
        $stats['verificaciones_mes'] = $stmt->fetch()['total'];
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Error obteniendo estadísticas: " . $e->getMessage());
        return [];
    }
}

function limpiarInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function formatearFecha($fecha) {
    return date('d/m/Y H:i', strtotime($fecha));
}

function formatearFechaCorta($fecha) {
    return date('d/m/Y', strtotime($fecha));
}
?>