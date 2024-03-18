<?php
include 'conexion.php'; 
session_start();


header('Access-Control-Allow-Origin: http://localhost:3000'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
   
    exit;
}
try {
    $data = json_decode(file_get_contents('php://input'), true);
    $nombre = strtolower($data['nombre']);
    $apellidoPaterno = strtolower($data['apellidoPaterno']);
    $apellidoMaterno = strtolower($data['apellidoMaterno']);
    $nTelefono = $data['nTelefono'];
    $email = strtolower($data['email']);
    $password = $data['password'];
    $rol =strtolower($data['rol']);
    $empresa = strtolower($data['empresa']);
    $rfc = strtolower($data['rfc']);

    $pdo->beginTransaction();

    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO usuarios (nombre, apellido_paterno, apellido_materno, telefono, email, contraseña, rol) VALUES (?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$nombre, $apellidoPaterno, $apellidoMaterno, $nTelefono, $email, $hashedPassword, $rol]);
    $idUsuario = $pdo->lastInsertId();

    if ($rol === 'contratante') {
        $stmt = $pdo->prepare("INSERT INTO contratante (id_usuario, empresa, rfc) VALUES (?, ?, ?)");
        $stmt->execute([$idUsuario, $empresa, $rfc]);
    }

    $pdo->commit();
    echo json_encode(['message' => 'Usuario registrado exitosamente']);
} catch (Exception $e) {
    $pdo->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Error al registrar el usuario: ' . $e->getMessage()]);
} finally {
}                                                                                                   


?>
