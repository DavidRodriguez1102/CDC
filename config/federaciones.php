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
        // Leer todas las federaciones o una específica
        if(isset($_GET['id'])) {
            $id = $_GET['id'];
            $stmt = $db->prepare("SELECT * FROM federaciones WHERE id = ? AND activo = 1");
            $stmt->execute([$id]);
            $federacion = $stmt->fetch();
            
            if($federacion) {
                // Obtener conteo de equipos
                $stmtEquipos = $db->prepare("SELECT COUNT(*) as total FROM equipos WHERE federacion_id = ? AND activo = 1");
                $stmtEquipos->execute([$id]);
                $federacion['total_equipos'] = $stmtEquipos->fetch()['total'];
                
                http_response_code(200);
                echo json_encode($federacion);
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Federación no encontrada."]);
            }
        } else {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            
            $stmt = $db->prepare("
                SELECT f.*, 
                       (SELECT COUNT(*) FROM equipos WHERE federacion_id = f.id AND activo = 1) as total_equipos 
                FROM federaciones f 
                WHERE f.activo = 1 
                ORDER BY f.fecha_fundacion DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            $federaciones = $stmt->fetchAll();
            
            http_response_code(200);
            echo json_encode($federaciones);
        }
        break;

    case 'POST':
        // Crear nueva federación
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->nombre) && !empty($data->fecha_fundacion) && 
           !empty($data->departamento) && !empty($data->municipio)) {
            
            $query = "INSERT INTO federaciones (nombre, fecha_fundacion, departamento, municipio, complemento) 
                     VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            
            $complemento = $data->complemento ?? '';
            
            if($stmt->execute([
                $data->nombre,
                $data->fecha_fundacion,
                $data->departamento,
                $data->municipio,
                $complemento
            ])) {
                http_response_code(201);
                echo json_encode([
                    "message" => "Federación creada exitosamente.",
                    "id" => $db->lastInsertId()
                ]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "No se pudo crear la federación."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Datos incompletos. Todos los campos son requeridos."]);
        }
        break;

    case 'PUT':
        // Actualizar federación
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            $query = "UPDATE federaciones 
                     SET nombre = ?, fecha_fundacion = ?, departamento = ?, 
                         municipio = ?, complemento = ? 
                     WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([
                $data->nombre,
                $data->fecha_fundacion,
                $data->departamento,
                $data->municipio,
                $data->complemento ?? '',
                $data->id
            ])) {
                http_response_code(200);
                echo json_encode(["message" => "Federación actualizada."]);
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
        // Eliminar federación (soft delete)
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->id)) {
            // Verificar si tiene equipos activos
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM equipos WHERE federacion_id = ? AND activo = 1");
            $stmt->execute([$data->id]);
            $count = $stmt->fetch()['total'];
            
            if($count > 0) {
                http_response_code(400);
                echo json_encode(["message" => "No se puede eliminar la federación porque tiene equipos activos."]);
                exit;
            }
            
            $query = "UPDATE federaciones SET activo = 0 WHERE id = ?";
            $stmt = $db->prepare($query);
            
            if($stmt->execute([$data->id])) {
                http_response_code(200);
                echo json_encode(["message" => "Federación eliminada."]);
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