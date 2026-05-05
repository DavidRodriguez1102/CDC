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
            // Obtener un equipo específico
            $id = $_GET['id'];
            $stmt = $db->prepare("
                SELECT e.*, f.nombre as federacion_nombre 
                FROM equipos e 
                INNER JOIN federaciones f ON e.federacion_id = f.id 
                WHERE e.id = ? AND e.activo = 1
            ");
            $stmt->execute([$id]);
            $equipo = $stmt->fetch();
            
            if($equipo) {
                // Obtener conteo de jugadores
                $stmtJugadores = $db->prepare("SELECT COUNT(*) as total FROM jugadores WHERE equipo_id = ? AND activo = 1");
                $stmtJugadores->execute([$id]);
                $equipo['total_jugadores'] = $stmtJugadores->fetch()['total'];
                
                http_response_code(200);
                echo json_encode($equipo);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Equipo no encontrado."]);
            }
        } else if(isset($_GET['federacion_id'])) {
            // Obtener equipos por federación
            $federacion_id = $_GET['federacion_id'];
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $stmt = $db->prepare("
                SELECT e.*, 
                       (SELECT COUNT(*) FROM jugadores WHERE equipo_id = e.id AND activo = 1) as total_jugadores 
                FROM equipos e 
                WHERE e.federacion_id = ? AND e.activo = 1 
                ORDER BY e.nombre
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$federacion_id, $limit, $offset]);
            $equipos = $stmt->fetchAll();
            
            http_response_code(200);
            echo json_encode($equipos);
        } else {
            // Obtener todos los equipos
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $stmt = $db->prepare("
                SELECT e.*, f.nombre as federacion_nombre,
                       (SELECT COUNT(*) FROM jugadores WHERE equipo_id = e.id AND activo = 1) as total_jugadores 
                FROM equipos e 
                INNER JOIN federaciones f ON e.federacion_id = f.id 
                WHERE e.activo = 1 
                ORDER BY e.nombre
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $equipos = $stmt->fetchAll();
            
            http_response_code(200);
            echo json_encode($equipos);
        }
        break;

    case 'POST':
        // Crear nuevo equipo
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->nombre) && !empty($data->federacion_id)) {
            // Verificar que la federación existe
            $stmt = $db->prepare("SELECT id FROM federaciones WHERE id = ? AND activo = 1");
            $stmt->execute([$data->federacion_id]);
            
            if(!$stmt->fetch()) {
                http_response_code(400);
                echo json_encode(["message" => "La federación no existe."]);
                exit;
            }
            
            $query = "INSERT INTO equipos (nombre, federacion_id) VALUES (?, ?)";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([$data->nombre, $data->federacion_id])) {
                http_response_code(201);
                echo json_encode([
                    "message" => "Equipo creado exitosamente.",
                    "id" => $db->lastInsertId()
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "No se pudo crear el equipo."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos."]);
        }
        break;

    case 'PUT':
        // Actualizar equipo
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            $query = "UPDATE equipos SET nombre = ?, federacion_id = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([$data->nombre, $data->federacion_id, $data->id])) {
                http_response_code(200);
                echo json_encode(["message" => "Equipo actualizado."]);
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
        // Eliminar equipo (soft delete)
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            // Verificar si tiene jugadores activos
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM jugadores WHERE equipo_id = ? AND activo = 1");
            $stmt->execute([$data->id]);
            $count = $stmt->fetch()['total'];
            
            if($count > 0) {
                http_response_code(400);
                echo json_encode(["message" => "No se puede eliminar el equipo porque tiene jugadores activos."]);
                exit;
            }
            
            $query = "UPDATE equipos SET activo = 0 WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([$data->id])) {
                http_response_code(200);
                echo json_encode(["message" => "Equipo eliminado."]);
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