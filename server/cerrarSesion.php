<?php
session_start();
require 'conexion.php'; 
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $sql = "UPDATE usuarios SET token = NULL, token_tiempo = NULL WHERE id_usuario = ?";
    $stmt = $pdo->prepare($sql);
    if($stmt->execute([$user_id])) {
    }
}

$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

echo json_encode(['success' => true]);
