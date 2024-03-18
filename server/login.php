<?php
session_start();
/**
 * This file is responsible for handling the login functionality.
 * It includes the necessary headers for enabling CORS (Cross-Origin Resource Sharing)
 * and sets the content type to JSON.
 *
 * @package SWAP-MEXIBUS
 * @subpackage Server
 */

require_once 'conexion.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');

/**
 * This code block checks if the current request method is OPTIONS.
 * If the request method is OPTIONS, the script exits.
 * This is commonly used to handle preflight requests in CORS (Cross-Origin Resource Sharing) scenarios.
 */
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
            $token = bin2hex(random_bytes(32));
            $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

            $updateStmt = $pdo->prepare('UPDATE usuarios SET token = ?, token_tiempo = ? WHERE id_usuario = ?');
            $updateStmt->execute([$token, $expiry, $user['id_usuario']]);

            $_SESSION['user_id'] = $user['id_usuario'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_role'] = $user['rol'];
            $_SESSION['token'] = $token;

            $stmt = $pdo->prepare("SELECT id_contratante FROM contratante WHERE id_usuario = ?");
            $stmt->execute([$user['id_usuario']]);
            $contratante = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($contratante) {
                $_SESSION['id_contratante'] = $contratante['id_contratante'];
                $user['id_contratante'] = $contratante['id_contratante'];
            }


            $id_contratante = $_SESSION['id_contratante'];
            $query = "SELECT id_carrito FROM carrito WHERE id_contratante = ?";
            $stmt = $pdo->prepare($query); 
            $stmt->bindParam(1, $id_contratante, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);


            if (count($result) > 0) {
                $_SESSION['id_carrito'] = $result[0]['id_carrito'];
            } else {
                $insertQuery = "INSERT INTO carrito (id_contratante, subtotal, iva, estado,total) VALUES (?, 0, 0,'activo',0)";
                $stmt = $pdo->prepare($insertQuery);
                $stmt->bindParam(1, $id_contratante, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $_SESSION['id_carrito'] = $pdo->lastInsertId();
                } else {
                    echo "Error al crear el carrito.";
                }
            }

            unset($user['contraseña']);
            echo json_encode(['success' => true, 'user' => $user, 'token' => $token, 'id_contratante' => $user['id_contratante'], 'id_carrito' => $_SESSION['id_carrito']]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Las credenciales proporcionadas son incorrectas']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
}
?>