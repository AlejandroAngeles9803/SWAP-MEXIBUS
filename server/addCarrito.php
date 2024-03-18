<?php
session_start();
require_once 'conexion.php';
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


$data = json_decode(file_get_contents('php://input'), true);
$idEspacio = $data['id_espacio'];
$precio = $data['precio'];
$cantidad = $data['cantidad'] ?? 1; 
$idCarrito = $data['id_carrito']; 

try {
    $stmt = $pdo->prepare("SELECT * FROM carrito_detalle WHERE id_carrito = ? AND id_espacio = ?");
    $stmt->execute([$idCarrito, $idEspacio]);
    $existe = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existe) {
        $nuevaCantidad = $existe['cantidad'] + $cantidad;
        $updateStmt = $pdo->prepare("UPDATE carrito_detalle SET cantidad = ? WHERE id_detalle = ?");
        $updateStmt->execute([$nuevaCantidad, $existe['id_detalle']]);
        $respuesta = ['success' => true, 'message' => 'Cantidad actualizada en el carrito.'];
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO carrito_detalle (id_carrito, id_espacio, precio, cantidad) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$idCarrito, $idEspacio, $precio, $cantidad]);
        if ($insertStmt->rowCount() > 0) {
            $respuesta = ['success' => true, 'message' => 'Espacio agregado al carrito correctamente.'];
        } else {
            $respuesta = ['success' => false, 'message' => 'No se pudo agregar el espacio al carrito.'];
        }
    }

    echo json_encode($respuesta);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Ocurrió un error al procesar la solicitud: ' . $e->getMessage()]);
}

