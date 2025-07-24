<?php
require_once 'config.php';
require_once 'db.php';

function loginEstudiante($email, $password) {
    global $db;
    
    try {
        $query = "SELECT * FROM estudiantes WHERE email = :email AND estado = 'activo'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $estudiante['password_hash'])) {
                // Regenerar ID de sesión para seguridad
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $estudiante['id'];
                $_SESSION['user_type'] = 'student';
                $_SESSION['user_email'] = $estudiante['email'];
                $_SESSION['user_name'] = $estudiante['nombres'] . ' ' . $estudiante['apellidos'];
                $_SESSION['login_time'] = time();
                
                // Actualizar último acceso
                $updateQuery = "UPDATE estudiantes SET ultimo_acceso = NOW() WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':id', $estudiante['id'], PDO::PARAM_INT);
                $updateStmt->execute();
                
                return true;
            }
        }
    } catch (PDOException $e) {
        error_log("Error en loginEstudiante: " . $e->getMessage());
    }
    
    return false;
}

function loginAdmin($email, $password) {
    global $db;
    
    try {
        $query = "SELECT * FROM usuarios_admin WHERE email = :email AND estado = 'activo'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $admin['password_hash'])) {
                // Regenerar ID de sesión para seguridad
                session_regenerate_id(true);
                
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['user_email'] = $admin['email'];
                $_SESSION['user_name'] = $admin['nombre'];
                $_SESSION['user_role'] = $admin['rol'];
                $_SESSION['login_time'] = time();
                
                // Actualizar último acceso
                $updateQuery = "UPDATE usuarios_admin SET ultimo_acceso = NOW() WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindParam(':id', $admin['id'], PDO::PARAM_INT);
                $updateStmt->execute();
                
                return true;
            }
        }
    } catch (PDOException $e) {
        error_log("Error en loginAdmin: " . $e->getMessage());
    }
    
    return false;
}

function registrarEstudiante($datos) {
    global $db;
    
    try {
        // Validar datos de entrada
        if (empty($datos['email']) || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }
        
        if (empty($datos['password']) || strlen($datos['password']) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }
        
        // Verificar si el email ya existe
        $query = "SELECT id FROM estudiantes WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $datos['email'], PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'El email ya está registrado'];
        }
        
        // Hash de la contraseña
        $password_hash = password_hash($datos['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        
        // Insertar nuevo estudiante
        $query = "INSERT INTO estudiantes 
                  (nombres, apellidos, programa, nivel, email, password_hash, fecha_registro, estado) 
                  VALUES 
                  (:nombres, :apellidos, :programa, :nivel, :email, :password_hash, NOW(), 'activo')";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nombres', $datos['nombres'], PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $datos['apellidos'], PDO::PARAM_STR);
        $stmt->bindParam(':programa', $datos['programa'], PDO::PARAM_STR);
        $stmt->bindParam(':nivel', $datos['nivel'], PDO::PARAM_STR);
        $stmt->bindParam(':email', $datos['email'], PDO::PARAM_STR);
        $stmt->bindParam(':password_hash', $password_hash, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            return ['success' => true, 'id' => $db->lastInsertId()];
        } else {
            return ['success' => false, 'message' => 'Error al registrar el estudiante'];
        }
    } catch (PDOException $e) {
        error_log("Error en registrarEstudiante: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error interno del servidor'];
    }
}

function logout() {
    // Destruir todas las variables de sesión
    $_SESSION = array();
    
    // Si se desea destruir la sesión completamente, borre también la cookie de sesión
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finalmente, destruir la sesión
    session_destroy();
    
    // Redirigir al login
    header('Location: ' . (dirname($_SERVER['PHP_SELF']) === '/' ? '' : dirname($_SERVER['PHP_SELF'])) . '/login.php');
    exit();
}

function requiereAutenticacion($tipo = null) {
    // Verificar si hay sesión activa
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['login_time'])) {
        header('Location: ' . (dirname($_SERVER['PHP_SELF']) === '/' ? '' : '../') . 'login.php');
        exit();
    }
    
    // Verificar expiración de sesión (2 horas)
    if (time() - $_SESSION['login_time'] > 7200) {
        logout();
    }
    
    // Verificar tipo de usuario si se especifica
    if ($tipo && $_SESSION['user_type'] != $tipo) {
        header('Location: ' . (dirname($_SERVER['PHP_SELF']) === '/' ? '' : '../') . 'index.php');
        exit();
    }
    
    // Actualizar tiempo de última actividad
    $_SESSION['last_activity'] = time();
}

function esUsuarioLogueado() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function obtenerUsuarioActual() {
    if (esUsuarioLogueado()) {
        return [
            'id' => $_SESSION['user_id'],
            'type' => $_SESSION['user_type'],
            'email' => $_SESSION['user_email'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'] ?? null
        ];
    }
    return null;
}
?>