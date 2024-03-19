<?php
namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class App implements MessageComponentInterface
{

    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "Mensaje recibido: " . $msg . "\n";
        $data = json_decode($msg);
        if ($data === null) {
            echo "Error: " . json_last_error_msg() . "\n";
            return;
        }
        if (isset ($data->action)) {
            switch ($data->action) {
                case 'getEstaciones':
                    $estacionesData = $this->getEstacionesData();
                    $from->send(json_encode([
                        'accion' => 'getEstacionesRespuesta',
                        'data' => $estacionesData
                    ]));
                    break;
                case 'agregarAlCarrito':
                    $respuesta = $this->agregarAlCarrito($data);
                    $from->send(json_encode([
                        'accion' => 'agregarAlCarritoRespuesta',
                        'data' => $respuesta
                    ]));
                case 'getCarrito':
                    $idCarrito = $data->id_carrito;
                    $carritoData = $this->getCarritoData($idCarrito);
                    $from->send(json_encode([
                        'accion' => 'getCarritoRespuesta',
                        'data' => $carritoData
                    ]));
                    break;


            }
        } else {
            echo "La propiedad 'action' no está presente en el objeto decodificado.\n";
        }




    }



    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $conn->close();
    }

    private function getEstacionesData()
    {
        $host = 'localhost';
        $db = 'swap';
        $user = 'root';
        $pass = '';


        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            return ['error' => $e->getMessage()];
        }


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
            $estaciones = $estacionesStmt->fetchAll(\PDO::FETCH_ASSOC);

            $lineas = [];
            foreach ($estaciones as $estacion) {
                if (!isset ($lineas[$estacion['nombre_linea']][$estacion['nombre_estacion']])) {
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

            return $resultado;




        } catch (\PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }


    private function agregarAlCarrito($data)
    {
        $host = 'localhost';
        $db = 'swap';
        $user = 'root';
        $pass = '';


        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new \PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            return ['error' => $e->getMessage()];
        }

        $idEspacio = $data->id_espacio;
        $precio = $data->precio;
        $cantidad = $data->cantidad ?? 1;
        $idCarrito = $data->id_carrito;

        try {
            $stmt = $pdo->prepare("SELECT * FROM carrito_detalle WHERE id_carrito = ? AND id_espacio = ?");
            $stmt->execute([$idCarrito, $idEspacio]);
            $existe = $stmt->fetch(\PDO::FETCH_ASSOC);

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

            return $respuesta;
        } catch (\PDOException $e) {
            return ['error' => $e->getMessage()];
        }
    }


    private function getCarritoData($idCarrito)
    {
        $host = 'localhost';
        $db = 'swap';
        $user = 'root';
        $pass = '';


        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $pdo = new \PDO($dsn, $user, $pass, $options);
            $sql = "SELECT DISTINCT
            carrito_detalle.id_detalle,
            carrito_detalle.id_carrito,
            carrito_detalle.id_espacio,
            carrito_detalle.precio,
            carrito_detalle.cantidad,
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
            formato_publicidad.id_formato,
            formato_publicidad.nombre_formato,
            estaciones_lineas.id_estacion_linea, 
            estaciones_lineas.id_estacion, 
            estaciones_lineas.id_linea,
            estaciones.nombre_estacion,
            lineas.nombre_linea
        FROM carrito_detalle
        JOIN espacio_publicitario ON carrito_detalle.id_espacio = espacio_publicitario.id_espacio
        JOIN tamano_publicidad ON espacio_publicitario.id_tamano = tamano_publicidad.id_tamano
        JOIN formato_publicidad ON tamano_publicidad.id_formato = formato_publicidad.id_formato
        LEFT JOIN estaciones_lineas ON espacio_publicitario.id_estacion = estaciones_lineas.id_estacion_linea
        LEFT JOIN estaciones ON estaciones_lineas.id_estacion = estaciones.id_estacion
        LEFT JOIN lineas ON estaciones_lineas.id_linea = lineas.id_linea
        WHERE carrito_detalle.id_carrito = :idCarrito;
        "; 

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':idCarrito', $idCarrito, \PDO::PARAM_INT);
            $stmt->execute();

            $resultados = $stmt->fetchAll();

            if ($resultados) {
                echo json_encode(['success' => true, 'data' => $resultados]);
                return $resultados;
            } else {
                echo json_encode(['success' => false, 'message' => 'No se encontraron datos.']);
            }

        } catch (\PDOException $e) {
            return ['error' => $e->getMessage()];
        }


    }
}
