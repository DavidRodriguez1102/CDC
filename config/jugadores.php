<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        if(isset($_GET['id'])) {
            // Obtener un jugador específico
            $id = $_GET['id'];
            $stmt = $db->prepare("
                SELECT j.*, e.nombre as equipo_nombre, f.nombre as federacion_nombre 
                FROM jugadores j 
                INNER JOIN equipos e ON j.equipo_id = e.id 
                INNER JOIN federaciones f ON e.federacion_id = f.id 
                WHERE j.id = ? AND j.activo = 1
            ");
            $stmt->execute([$id]);
            $jugador = $stmt->fetch();
            
            if($jugador) {
                http_response_code(200);
                echo json_encode($jugador);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Jugador no encontrado."]);
            }
        } else if(isset($_GET['equipo_id'])) {
            // Obtener jugadores por equipo
            $equipo_id = $_GET['equipo_id'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $stmt = $db->prepare("
                SELECT j.*, e.nombre as equipo_nombre 
                FROM jugadores j 
                INNER JOIN equipos e ON j.equipo_id = e.id 
                WHERE j.equipo_id = ? AND j.activo = 1 
                ORDER BY j.nombre
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$equipo_id, $limit, $offset]);
            $jugadores = $stmt->fetchAll();
            
            http_response_code(200);
            echo json_encode($jugadores);
        } else {
            // Obtener todos los jugadores
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $stmt = $db->prepare("
                SELECT j.*, e.nombre as equipo_nombre, f.nombre as federacion_nombre 
                FROM jugadores j 
                INNER JOIN equipos e ON j.equipo_id = e.id 
                INNER JOIN federaciones f ON e.federacion_id = f.id 
                WHERE j.activo = 1 
                ORDER BY j.nombre
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $jugadores = $stmt->fetchAll();
            
            http_response_code(200);
            echo json_encode($jugadores);
        }
        break;

    case 'POST':
        // Crear nuevo jugador
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->nombre) && !empty($data->fecha_nacimiento) && 
           !empty($data->genero) && !empty($data->equipo_id)) {
            
            // Verificar que el equipo existe
            $stmt = $db->prepare("SELECT id FROM equipos WHERE id = ? AND activo = 1");
            $stmt->execute([$data->equipo_id]);
            
            if(!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(["message" => "El equipo no existe."]);
                exit;
            }
            
            // Validar género
            $generos_validos = ['masculino', 'femenino'];
            if(!in_array($data->genero, $generos_validos)) {
                http_response_code(400);
                echo json_encode(["message" => "Género no válido."]);
                exit;
            }
            
            $query = "INSERT INTO jugadores (nombre, fecha_nacimiento, genero, equipo_id) 
                     VALUES (?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([
                $data->nombre,
                $data->fecha_nacimiento,
                $data->genero,
                $data->equipo_id
            ])) {
                http_response_code(201);
                echo json_encode([
                    "message" => "Jugador creado exitosamente.",
                    "id" => $db->lastInsertId()
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "No se pudo crear el jugador."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos."]);
        }
        break;

    case 'PUT':
        // Actualizar jugador
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            $query = "UPDATE jugadores 
                     SET nombre = ?, fecha_nacimiento = ?, genero = ?, equipo_id = ? 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([
                $data->nombre,
                $data->fecha_nacimiento,
                $data->genero,
                $data->equipo_id,
                $data->id
            ])) {
                http_response_code(200);
                echo json_encode(["message" => "Jugador actualizado."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "No se pudo actualizar."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "ID no proporcionado."]);
        }
        break;

    case 'DELETE':
        // Eliminar jugador (soft delete)
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            $query = "UPDATE jugadores SET activo = 0 WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([$data->id])) {
                http_response_code(200);
                echo json_encode(["message" => "Jugador eliminado."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "No se pudo eliminar."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "ID no proporcionado."]);
        }
        break;

    case 'OPTIONS':
        http_response_code(200);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["message" => "Método no permitido."]);
        break;
}
?>