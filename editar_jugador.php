<?php
// editar_jugador.php
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

// Obtener ID del jugador
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$id) {
    header('Location: jugadores.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Obtener datos actuales del jugador
$stmt = $pdo->prepare("SELECT * FROM jugadores WHERE id = :id AND activo = 1");
$stmt->execute([':id' => $id]);
$jugador = $stmt->fetch();

if (!$jugador) {
    header('Location: jugadores.php');
    exit;
}

// Obtener todos los equipos para el selector
$stmtEquipos = $pdo->query("SELECT id, nombre FROM equipos WHERE activo = 1 ORDER BY nombre");
$equipos = $stmtEquipos->fetchAll();

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_jugador'])) {
    $nombre = limpiarInput($_POST['nombre']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $genero = $_POST['genero'];
    $equipo_id = filter_var($_POST['equipo_id'], FILTER_VALIDATE_INT);
    
    // Validaciones
    if (empty($nombre) || empty($fecha_nacimiento) || empty($genero) || !$equipo_id) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo_mensaje = 'error';
    } elseif (!in_array($genero, ['masculino', 'femenino'])) {
        $mensaje = 'Género no válido.';
        $tipo_mensaje = 'error';
    } else {
        // Verificar si ya existe otro jugador con exactamente la misma información
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM jugadores WHERE nombre = :nombre AND fecha_nacimiento = :fecha_nacimiento AND genero = :genero AND activo = 1 AND id != :id");
        $stmtCheck->execute([
            ':nombre' => $nombre,
            ':fecha_nacimiento' => $fecha_nacimiento,
            ':genero' => $genero,
            ':id' => $id
        ]);
        $existe = $stmtCheck->fetchColumn();
        
        if ($existe > 0) {
            $mensaje = 'Ya existe otro jugador con exactamente la misma información (nombre, fecha de nacimiento y género). No se puede registrar el mismo jugador en equipos diferentes.';
            $tipo_mensaje = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE jugadores 
                    SET nombre = :nombre, 
                        fecha_nacimiento = :fecha_nacimiento, 
                        genero = :genero, 
                        equipo_id = :equipo_id 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':fecha_nacimiento' => $fecha_nacimiento,
                    ':genero' => $genero,
                    ':equipo_id' => $equipo_id,
                    ':id' => $id
                ]);
                
                $mensaje = 'Jugador actualizado exitosamente.';
                $tipo_mensaje = 'success';
                
                // Actualizar datos del jugador en la variable
                $jugador['nombre'] = $nombre;
                $jugador['fecha_nacimiento'] = $fecha_nacimiento;
                $jugador['genero'] = $genero;
                $jugador['equipo_id'] = $equipo_id;
                
            } catch (PDOException $e) {
                $mensaje = 'Error al actualizar: ' . $e->getMessage();
                $tipo_mensaje = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Jugador - <?php echo htmlspecialchars($jugador['nombre']); ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .form-container {
            background: #0d1a15;
            border-radius: 12px;
            border: 1px solid #1a2e25;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #1a2e25;
        }
        
        .form-header h2 {
            color: #ffffff;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #94a3b8;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #94a3b8;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #1a2e25;
            border-radius: 8px;
            font-size: 1rem;
            background: #050a08;
            color: #ffffff;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00ff88;
            box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.1);
        }
        
        .mensaje {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .mensaje-success {
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid #00ff88;
        }
        
        .mensaje-error {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid #ff4444;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #1a2e25;
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
            background: #00ff88;
            color: #050a08;
        }
        
        .btn-primary:hover {
            background: rgba(0, 255, 136, 0.8);
            transform: translateY(-2px);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.2);
        }
        
        .btn-secondary {
            background: #1a2e25;
            color: #00ff88;
            border: 1px solid #1a2e25;
        }
        
        .btn-secondary:hover {
            background: #0d1a15;
            border-color: #00ff88;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
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
                <a href="equipos.php"> Equipos</a>
                <a href="jugadores.php" class="active"> Jugadores</a>
                <hr>
                <a href="configuracion_usuarios.php"> Configuración</a>
                <a href="log_out.php" style="color: #e53e3e;"> Cerrar Sesión</a>
            </nav>
        </aside>
        
        <!-- Contenido Principal -->
        <main class="main-content">
            <header>
                <h2>Editar Jugador</h2>
                <a href="ver_jugador.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    ← Volver al jugador
                </a>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <div class="form-header">
                    <h2> Editando: <?php echo htmlspecialchars($jugador['nombre']); ?></h2>
                    <p>ID del jugador: <?php echo $jugador['id']; ?></p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="nombre">Nombre Completo *</label>
                            <input type="text" 
                                   id="nombre" 
                                   name="nombre" 
                                   value="<?php echo htmlspecialchars($jugador['nombre']); ?>" 
                                   placeholder="Ej: Juan Pérez García"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_nacimiento">Fecha de Nacimiento *</label>
                            <input type="date" 
                                   id="fecha_nacimiento" 
                                   name="fecha_nacimiento" 
                                   value="<?php echo $jugador['fecha_nacimiento']; ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="genero">Género *</label>
                            <select id="genero" name="genero" required>
                                <option value="">Seleccione una opción</option>
                                <option value="masculino" <?php echo $jugador['genero'] == 'masculino' ? 'selected' : ''; ?>>
                                     Masculino
                                </option>
                                <option value="femenino" <?php echo $jugador['genero'] == 'femenino' ? 'selected' : ''; ?>>
                                     Femenino
                                </option>
                            </select>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="equipo_id">Asignar Equipo *</label>
                            <select id="equipo_id" name="equipo_id" required>
                                <option value="">Seleccione un equipo</option>
                                <?php foreach ($equipos as $equipo): ?>
                                    <option value="<?php echo $equipo['id']; ?>" 
                                        <?php echo $jugador['equipo_id'] == $equipo['id'] ? 'selected' : ''; ?>>
                                         <?php echo htmlspecialchars($equipo['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="actualizar_jugador" class="btn btn-primary">
                             Guardar Cambios
                        </button>
                        <a href="ver_jugador.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                             Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>