<?php
session_start();

$host = 'localhost';
$dbname = 'soccer_federation';
$usuario = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para limpiar datos de entrada
function limpiarInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Función para verificar autenticación
function verificarAutenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: log_in.php');
        exit;
    }
}

// Función para mostrar mensajes
function mostrarMensaje($mensaje, $tipo = 'success') {
    $icono = $tipo === 'success' ? '✅' : '';
    $clase = $tipo === 'success' ? 'mensaje-success' : 'mensaje-error';
    return "<div class='mensaje $clase'>$icono $mensaje</div>";
}

// Función para subir archivos
function subirArchivo($archivo, $directorio, $tipos_permitidos = ['jpg', 'jpeg', 'png']) {
    $nombre_archivo = $archivo['name'];
    $tamano_archivo = $archivo['size'];
    $tmp_name = $archivo['tmp_name'];
    $error = $archivo['error'];

    if ($error !== 0) {
        return ['success' => false, 'mensaje' => 'Error al subir el archivo'];
    }

    if ($tamano_archivo > 2097152) { // 2MB
        return ['success' => false, 'mensaje' => 'El archivo es demasiado grande. Máximo 2MB'];
    }

    $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
    if (!in_array($extension, $tipos_permitidos)) {
        return ['success' => false, 'mensaje' => 'Formato no permitido. Use: ' . implode(', ', $tipos_permitidos)];
    }

    $nuevo_nombre = uniqid('img_') . '.' . $extension;
    $ruta_destino = $directorio . '/' . $nuevo_nombre;

    if (move_uploaded_file($tmp_name, $ruta_destino)) {
        return ['success' => true, 'ruta' => $ruta_destino];
    } else {
        return ['success' => false, 'mensaje' => 'Error al mover el archivo'];
    }
}
?>