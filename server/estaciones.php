<?php
session_start();
include 'conexion.php';
header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit;
}


header('Content-Type: application/json');

try {

    $estacionesStmt = $pdo->query('SELECT 
    espacio_publicitario.id_espacio, 
    espacio_publicitario.id_tamano, 
    espacio_publicitario.id_estacion, 
    espacio_publicitario.ubicacion, 
    espacio_publicitario.lado, 
    espacio_publicitario.estado, 
    espacio_publicitario.imagen_espacio,
    tamano_publicidad.id_tamano, 
    tamano_publicidad.id_formato, 
    tamano_publicidad.descripcion, 
    tamano_publicidad.altura, 
    tamano_publicidad.ancho, 
    tamano_publicidad.precio,
    formato_publicidad.id_formato, 
    formato_publicidad.nombre_formato,
    estaciones_lineas.id_estacion_linea, 
    estaciones_lineas.id_estacion, 
    estaciones_lineas.id_linea,
    estaciones.nombre_estacion,
    lineas.nombre_linea
    FROM 
    lineas 
JOIN 
    estaciones_lineas  ON lineas.id_linea = estaciones_lineas.id_linea
JOIN 
    estaciones  ON estaciones_lineas.id_estacion = estaciones.id_estacion
LEFT JOIN 
    espacio_publicitario  ON estaciones_lineas.id_estacion_linea = espacio_publicitario.id_estacion
LEFT JOIN 
    tamano_publicidad  ON espacio_publicitario.id_tamano = tamano_publicidad.id_tamano
LEFT JOIN 
    formato_publicidad  ON tamano_publicidad.id_formato = formato_publicidad.id_formato
');
    $estaciones = $estacionesStmt->fetchAll(PDO::FETCH_ASSOC);

    $lineas = [];
    foreach ($estaciones as $estacion) {
        if (!isset($lineas[$estacion['nombre_linea']][$estacion['nombre_estacion']])) {
            $lineas[$estacion['nombre_linea']][$estacion['nombre_estacion']] = [
                'id_estacion' => $estacion['id_estacion'],
                'nombre_estacion' => $estacion['nombre_estacion'],
                'espacios' => []
            ];
        }
    
        if ($estacion['id_espacio']) {
            $lineas[$estacion['nombre_linea']][$estacion['nombre_estacion']]['espacios'][] = [
                'id_espacio' => $estacion['id_espacio'],
                'nombre_estacion' => $estacion['nombre_estacion'],
                'nombre_linea' => $estacion['nombre_linea'],
                'ubicacion' => $estacion['ubicacion'],
                'lado' => $estacion['lado'],
                'estado' => $estacion['estado'],
                'tamano_descripcion' => $estacion['descripcion'],
                'altura' => $estacion['altura'],
                'ancho' => $estacion['ancho'],
                'precio' => $estacion['precio'],
                'nombre_formato' => $estacion['nombre_formato'],
                'imagen_espacio' => $estacion['imagen_espacio'],
            ];
        }
    }
    
    $resultado = [];
    foreach ($lineas as $linea => $estacionesNombre) {
        foreach ($estacionesNombre as $nombre => $infoEstacion) {
            $resultado[] = [
                'linea' => $linea,
                'nombre_estacion' => $nombre,
                'espacios' => $infoEstacion['espacios'],
            ];
        }
    }
    
    echo json_encode($resultado);
    



} catch (\PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}




