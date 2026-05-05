<?php
// editar_equipo.php
session_start();

// Conexión a la base de datos
$host = 'localhost';
$dbname = 'soccer_federation';
$usuario = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para limpiar datos
function limpiarInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: log_in.php');
    exit;
}

// Obtener ID del equipo
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$id) {
    header('Location: equipos.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Obtener datos actuales del equipo
$stmt = $pdo->prepare("SELECT e.*, f.nombre as nombre_federacion FROM equipos e LEFT JOIN federaciones f ON e.federacion_id = f.id WHERE e.id = :id AND e.activo = 1");
$stmt->execute([':id' => $id]);
$equipo = $stmt->fetch();

if (!$equipo) {
    header('Location: equipos.php');
    exit;
}

// Obtener todas las federaciones para el selector
$federaciones = $pdo->query("SELECT id, nombre FROM federaciones WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_equipo'])) {
    $nombre = limpiarInput($_POST['nombre']);
    $federacion_id = filter_var($_POST['federacion_id'], FILTER_VALIDATE_INT);
    
    if (empty($nombre) || !$federacion_id) {
        $mensaje = 'El nombre y la federación son obligatorios.';
        $tipo_mensaje = 'error';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE equipos 
                SET nombre = :nombre, 
                    federacion_id = :federacion_id 
                WHERE id = :id
            ");
            $stmt->execute([
                ':nombre' => $nombre,
                ':federacion_id' => $federacion_id,
                ':id' => $id
            ]);
            
            $mensaje = 'Equipo actualizado exitosamente.';
            $tipo_mensaje = 'success';
            
            // Actualizar datos en la variable
            $equipo['nombre'] = $nombre;
            $equipo['federacion_id'] = $federacion_id;
            
        } catch (PDOException $e) {
            $mensaje = 'Error al actualizar: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo - <?php echo htmlspecialchars($equipo['nombre']); ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .form-header h2 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #718096;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4a5568;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2b6cb0;
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
        }
        
        .info-box {
            background: #ebf8ff;
            border: 1px solid #bee3f8;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-box p {
            color: #2b6cb0;
            margin: 0;
        }
        
        .mensaje {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .mensaje-success {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .mensaje-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #2b6cb0;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2c5282;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1> Gestión Deportiva</h1>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"> Dashboard</a>
                <a href="federaciones.php"> Federaciones</a>
                <a href="equipos.php" class="active"> Equipos</a>
                <a href="jugadores.php"> Jugadores</a>
                <hr>
                <a href="configuracion_usuarios.php"> Configuración</a>
                <a href="log_out.php" style="color: #e53e3e;"> Cerrar Sesión</a>
            </nav>
        </aside>
        
        <!-- Contenido Principal -->
        <main class="main-content">
            <header>
                <h2>Editar Equipo</h2>
                <a href="ver_equipo.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    ← Volver al equipo
                </a>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <div class="form-header">
                    <h2> Editando: <?php echo htmlspecialchars($equipo['nombre']); ?></h2>
                    <p>ID del equipo: <?php echo $equipo['id']; ?></p>
                </div>
                
                <div class="info-box">
                    <p> Federación actual: <strong><?php echo htmlspecialchars($equipo['nombre_federacion']); ?></strong></p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nombre">Nombre del Equipo *</label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               value="<?php echo htmlspecialchars($equipo['nombre']); ?>" 
                               placeholder="Ej: Real Madrid CF"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="federacion_id">Federación *</label>
                        <select id="federacion_id" name="federacion_id" required>
                            <option value="">Seleccione Federación</option>
                            <?php foreach ($federaciones as $federacion): ?>
                                <option value="<?php echo $federacion['id']; ?>" 
                                    <?php echo $equipo['federacion_id'] == $federacion['id'] ? 'selected' : ''; ?>>
                                     <?php echo htmlspecialchars($federacion['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="actualizar_equipo" class="btn btn-primary">
                             Guardar Cambios
                        </button>
                        <a href="ver_equipo.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                             Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>