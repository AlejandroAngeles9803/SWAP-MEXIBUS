<?php
session_start(); 
require_once 'conexion.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'];
        $password = $data['password'];

        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['contraseña'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            
            unset($user['contraseña']); 
            echo json_encode(['success' => true, 'user' => $user]);
            header("Location: index.php");
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Las credenciales proporcionadas son incorrectas']);
        }
    } catch (Exception $e) {
        // Manejo de excepciones
        http_response_code(500);
        echo json_encode(['error' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>
