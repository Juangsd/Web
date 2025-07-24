<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Solo accesible por administradores
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso no autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['solicitud_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de solicitud requerido']);
        exit();
    }
    
    try {
        // Obtener información de la solicitud y estudiante
        $query = "SELECT s.*, e.nombres, e.apellidos, e.programa, e.nivel 
                  FROM solicitudes s
                  JOIN estudiantes e ON s.estudiante_id = e.id
                  WHERE s.id = :id AND s.estado = 'aprobado'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['solicitud_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Solicitud no encontrada o no aprobada']);
            exit();
        }
        
        $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar si ya existe un certificado para esta solicitud
        $query = "SELECT id FROM certificados WHERE solicitud_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $data['solicitud_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Ya existe un certificado para esta solicitud']);
            exit();
        }
        
        // Generar código de verificación único
        $codigo_verificacion = generarCodigoVerificacion();
        $qr_data = SITE_URL . '/verificar.php?codigo=' . $codigo_verificacion;
        
        // Generar QR
        $qr_filename = 'qr_' . $codigo_verificacion;
        $qr_path = generarQR($qr_data, $qr_filename);
        
        // Datos para el PDF
        $pdf_data = [
            'tipo' => $solicitud['tipo_certificado'],
            'nombre_completo' => $solicitud['nombres'] . ' ' . $solicitud['apellidos'],
            'programa' => $solicitud['programa'],
            'nivel' => $solicitud['nivel'],
            'qr_path' => $qr_path,
            'codigo_verificacion' => $codigo_verificacion
        ];
        
        // Generar PDF
        $pdf_result = generarPDF($pdf_data);
        
        // Guardar en base de datos
        $query = "INSERT INTO certificados 
                  (solicitud_id, url_pdf, codigo_qr, codigo_verificacion) 
                  VALUES (:solicitud_id, :url_pdf, :codigo_qr, :codigo_verificacion)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':solicitud_id', $data['solicitud_id']);
        $stmt->bindParam(':url_pdf', $pdf_result['url']);
        $stmt->bindParam(':codigo_qr', $qr_path);
        $stmt->bindParam(':codigo_verificacion', $codigo_verificacion);
        $stmt->execute();
        
        // Registrar validación
        $query = "INSERT INTO validaciones 
                  (solicitud_id, validado_por, observaciones) 
                  VALUES (:solicitud_id, :validado_por, 'Certificado generado automáticamente')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':solicitud_id', $data['solicitud_id']);
        $stmt->bindParam(':validado_por', $_SESSION['user_id']);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'pdf_url' => $pdf_result['url'],
            'certificado_id' => $db->lastInsertId()
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>