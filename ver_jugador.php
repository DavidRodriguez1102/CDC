<?php
// ver_jugador.php
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

// Obtener información del jugador con su equipo y federación
$stmt = $pdo->prepare("
    SELECT j.*, 
           e.nombre as nombre_equipo, 
           e.id as equipo_id,
           f.nombre as nombre_federacion,
           f.id as federacion_id
    FROM jugadores j
    LEFT JOIN equipos e ON j.equipo_id = e.id
    LEFT JOIN federaciones f ON e.federacion_id = f.id
    WHERE j.id = :id AND j.activo = 1
");
$stmt->execute([':id' => $id]);
$jugador = $stmt->fetch();

if (!$jugador) {
    header('Location: jugadores.php');
    exit;
}

// Calcular edad
$fecha_nacimiento = new DateTime($jugador['fecha_nacimiento']);
$hoy = new DateTime();
$edad = $hoy->diff($fecha_nacimiento)->y;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Jugador - <?php echo htmlspecialchars($jugador['nombre']); ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .perfil-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .perfil-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            margin: 0 auto 1rem;
            border: 4px solid white;
        }
        
        .perfil-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .perfil-header .rol {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .perfil-body {
            padding: 2rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: #f7fafc;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .info-card h4 {
            color: #4a5568;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .info-card p {
            color: #2d3748;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .badge-genero {
            display: inline-block;
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .badge-masculino {
            background: #bee3f8;
            color: #2b6cb0;
        }
        
        .badge-femenino {
            background: #fed7e2;
            color: #c53030;
        }
        
        .acciones {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
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
        
        .btn-danger {
            background: #e53e3e;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c53030;
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
                <h2>Perfil del Jugador</h2>
                <a href="jugadores.php" class="btn btn-secondary"> Volver a Jugadores</a>
            </header>
            
            <div class="perfil-container">
                <div class="perfil-header">
                    <h2><?php echo htmlspecialchars($jugador['nombre']); ?></h2>
                    <div class="rol">
                        <span>ID: <?php echo $jugador['id']; ?></span>
                    </div>
                </div>
                
                <div class="perfil-body">
                    <div class="info-grid">
                        <div class="info-card">
                            <h4> Fecha de Nacimiento</h4>
                            <p><?php echo date('d/m/Y', strtotime($jugador['fecha_nacimiento'])); ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h4> Edad</h4>
                            <p><?php echo $edad; ?> años</p>
                        </div>
                        
                        <div class="info-card">
                            <h4> Género</h4>
                            <p>
                                <span class="badge-genero badge-<?php echo $jugador['genero']; ?>">
                                    <?php echo ucfirst($jugador['genero']); ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="info-card">
                            <h4> Equipo</h4>
                            <p>
                                <?php if ($jugador['nombre_equipo']): ?>
                                    <a href="ver_equipo.php?id=<?php echo $jugador['equipo_id']; ?>" style="color: #2b6cb0; text-decoration: none;">
                                        <?php echo htmlspecialchars($jugador['nombre_equipo']); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #a0aec0;">Sin equipo asignado</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="info-card">
                            <h4> Federación</h4>
                            <p>
                                <?php if ($jugador['nombre_federacion']): ?>
                                    <a href="ver_federacion.php?id=<?php echo $jugador['federacion_id']; ?>" style="color: #2b6cb0; text-decoration: none;">
                                        <?php echo htmlspecialchars($jugador['nombre_federacion']); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #a0aec0;">Sin federación</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div class="info-card">
                            <h4> Fecha de Registro</h4>
                            <p><?php echo date('d/m/Y H:i', strtotime($jugador['fecha_creacion'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="acciones">
                        <a href="editar_jugador.php?id=<?php echo $jugador['id']; ?>" class="btn btn-primary">
                             Editar Jugador
                        </a>
                        <a href="jugadores.php" class="btn btn-secondary">
                             Volver a la lista
                        </a>
                        <form method="POST" action="eliminar_jugador.php" style="display: inline;" 
                              onsubmit="return confirm('¿Estás seguro de eliminar a <?php echo htmlspecialchars(addslashes($jugador['nombre'])); ?>?')">
                            <input type="hidden" name="jugador_id" value="<?php echo $jugador['id']; ?>">
                            <button type="submit" class="btn btn-danger">
                                 Eliminar Jugador
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>