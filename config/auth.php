<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'POST':
        $data = json_decode(file_get_contents("php://input"));
        
        if(!empty($data->username) && !empty($data->password)) {
            $stmt = $db->prepare("SELECT id, username, password_hash, nombre_completo, rol 
                                 FROM usuarios 
                                 WHERE username = ? AND activo = 1");
            $stmt->execute([$data->username]);
            $user = $stmt->fetch();
            
            if($user && password_verify($data->password, $user['password_hash'])) {
                // Actualizar último acceso
                $updateStmt = $db->prepare("UPDATE usuarios SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->execute([$user['id']]);
                
                // Generar token simple (en producción usar JWT)
                $token = bin2hex(random_bytes(32));
                
                http_response_code(200);
                echo json_encode([
                    "message" => "Login exitoso.",
                    "user" => [
                        "id" => $user['id'],
                        "username" => $user['username'],
                        "nombre_completo" => $user['nombre_completo'],
                        "rol" => $user['rol']
                    ],
                    "token" => $token
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Usuario o contraseña incorrectos."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Usuario y contraseña son requeridos."]);
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