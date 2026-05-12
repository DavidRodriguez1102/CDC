<?php
// ver_equipo.php
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

$error_mensaje = '';

// Manejar eliminación directa desde este mismo archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipo_id'])) {
    $equipo_id = filter_var($_POST['equipo_id'], FILTER_VALIDATE_INT);
    if ($equipo_id) {
        try {
            $stmtJugadoresCount = $pdo->prepare("SELECT COUNT(*) FROM jugadores WHERE equipo_id = :id AND activo = 1");
            $stmtJugadoresCount->execute([':id' => $equipo_id]);
            $total_jugadores = $stmtJugadoresCount->fetchColumn();

            if ($total_jugadores > 0) {
                $error_mensaje = "No se puede eliminar el equipo porque tiene $total_jugadores jugadores activos asignados.";
            } else {
                $stmtDeleteEquipo = $pdo->prepare("UPDATE equipos SET activo = 0 WHERE id = :id");
                $stmtDeleteEquipo->execute([':id' => $equipo_id]);
                header('Location: equipos.php');
                exit;
            }
        } catch (PDOException $e) {
            $error_mensaje = "Error al eliminar el equipo: " . $e->getMessage();
        }
    }
}

// Obtener ID del equipo
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$id) {
    header('Location: equipos.php');
    exit;
}

// Obtener información del equipo
$stmt = $pdo->prepare("
    SELECT e.*, f.nombre as nombre_federacion, f.id as federacion_id
    FROM equipos e
    LEFT JOIN federaciones f ON e.federacion_id = f.id
    WHERE e.id = :id AND e.activo = 1
");
$stmt->execute([':id' => $id]);
$equipo = $stmt->fetch();

if (!$equipo) {
    header('Location: equipos.php');
    exit;
}

// Obtener jugadores del equipo
$stmtJugadores = $pdo->prepare("
    SELECT id, nombre, fecha_nacimiento, genero, fecha_creacion
    FROM jugadores
    WHERE equipo_id = :equipo_id AND activo = 1
    ORDER BY nombre ASC
");
$stmtJugadores->execute([':equipo_id' => $id]);
$jugadores = $stmtJugadores->fetchAll();

$total_jugadores = count($jugadores);
$total_masculino = 0;
$total_femenino = 0;

foreach ($jugadores as $jugador) {
    if ($jugador['genero'] === 'masculino') {
        $total_masculino++;
    } else {
        $total_femenino++;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Equipo - <?php echo htmlspecialchars($equipo['nombre']); ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .detalle-container {
            background: #0d1a15;
            border-radius: 12px;
            border: 1px solid #1a2e25;
            overflow: hidden;
        }
        
        .detalle-header {
            background: linear-gradient(135deg, #0a110e 0%, #0d1a15 100%);
            border-bottom: 2px solid #00ff88;
            color: white;
            padding: 2rem;
        }
        
        .detalle-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #00ff88;
        }
        
        .detalle-header .id-badge {
            display: inline-block;
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            color: #00ff88;
            padding: 0.25rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        
        .detalle-body {
            padding: 2rem;
        }
        
        .info-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #1a2e25;
        }
        
        .info-section:last-child {
            border-bottom: none;
        }
        
        .info-section h3 {
            color: #ffffff;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: #050a08;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #1a2e25;
        }
        
        .stat-box .number {
            font-size: 2rem;
            font-weight: bold;
            color: #00ff88;
        }
        
        .stat-box .label {
            color: #cbd5e1;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .jugadores-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .jugadores-table th {
            background: #12241d;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #00ff88;
            border-bottom: 2px solid #1a2e25;
        }
        
        .jugadores-table td {
            padding: 1rem;
            border-bottom: 1px solid #1a2e25;
            color: #cbd5e1;
        }
        
        .jugadores-table tr:hover {
            background: rgba(0, 255, 136, 0.05);
        }
        
        .badge-genero {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-masculino {
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid #00ff88;
        }
        
        .badge-femenino {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid #ff4444;
        }
        
        .sin-jugadores {
            text-align: center;
            padding: 2rem;
            color: #cbd5e1;
        }
        
        .acciones {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #1a2e25;
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
        
        .btn-danger {
            background: #ff4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dd3333;
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
                <h2>Detalles del Equipo</h2>
                <a href="equipos.php" class="btn btn-secondary"> Volver a Equipos</a>
            </header>

            <?php if (!empty($error_mensaje)): ?>
                <div style="margin: 1rem 0; padding: 1rem; border-radius: 8px; background: #fff5f5; color: #742a2a; border: 1px solid #feb2b2;">
                    <?php echo htmlspecialchars($error_mensaje); ?>
                </div>
            <?php endif; ?>
            
            <div class="detalle-container">
                <div class="detalle-header">
                    <h2><?php echo htmlspecialchars($equipo['nombre']); ?></h2>
                    <span class="id-badge">ID: <?php echo $equipo['id']; ?></span>
                </div>
                
                <div class="detalle-body">
                    <!-- Información General -->
                    <div class="info-section">
                        <h3> Información General</h3>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div>
                                <strong style="color: #4a5568;">Federación:</strong><br>
                                <?php if ($equipo['nombre_federacion']): ?>
                                    <a href="ver_federacion.php?id=<?php echo $equipo['federacion_id']; ?>" style="color: #2b6cb0; text-decoration: none;">
                                         <?php echo htmlspecialchars($equipo['nombre_federacion']); ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color: #a0aec0;">Sin federación asignada</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <strong style="color: #4a5568;">Fecha de Creación:</strong><br>
                                 <?php echo date('d/m/Y', strtotime($equipo['fecha_creacion'])); ?>
                            </div>
                            <div>
                                <strong style="color: #4a5568;">Última Actualización:</strong><br>
                                 <?php echo date('d/m/Y H:i', strtotime($equipo['fecha_actualizacion'])); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Estadísticas -->
                    <div class="info-section">
                        <h3> Estadísticas de Jugadores</h3>
                        <div class="stats-mini">
                            <div class="stat-box">
                                <div class="number"><?php echo $total_jugadores; ?></div>
                                <div class="label">Total Jugadores</div>
                            </div>
                            <div class="stat-box">
                                <div class="number"><?php echo $total_masculino; ?></div>
                                <div class="label">Masculinos</div>
                            </div>
                            <div class="stat-box">
                                <div class="number"><?php echo $total_femenino; ?></div>
                                <div class="label">Femeninos</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lista de Jugadores -->
                    <div class="info-section">
                        <h3> Jugadores del Equipo</h3>
                        
                        <?php if ($total_jugadores > 0): ?>
                            <div style="overflow-x: auto;">
                                <table class="jugadores-table">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Género</th>
                                            <th>Fecha Nacimiento</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jugadores as $jugador): ?>
                                        <tr>
                                            <td><span <?php echo $jugador['nombre']; ?>>
                                                <?php echo ucfirst($jugador['nombre']); ?>
                                                </span>
                                            <td>
                                                <span class="badge-genero badge-<?php echo $jugador['genero']; ?>">
                                                    <?php echo ucfirst($jugador['genero']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($jugador['fecha_nacimiento'])); ?></td>
                                            <td>
                                                <a href="ver_jugador.php?id=<?php echo $jugador['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.75rem; font-size: 0.85rem;">
                                                    Ver
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="sin-jugadores">
                                <p> No hay jugadores registrados en este equipo.</p>
                                <a href="registro_jugadores.php" class="btn btn-primary" style="margin-top: 1rem;">
                                     Agregar Jugador
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="acciones">
                        <a href="registro_jugadores.php" class="btn btn-primary">
                             Agregar Jugador
                        </a>
                        <a href="editar_equipo.php?id=<?php echo $equipo['id']; ?>" class="btn btn-primary">
                             Editar Equipo
                        </a>
                        <a href="equipos.php" class="btn btn-secondary">
                             Volver a la lista
                        </a>
                        <form method="POST" action="ver_equipo.php?id=<?php echo $equipo['id']; ?>" style="display: inline;" 
                              onsubmit="return confirm('¿Estás seguro de eliminar el equipo <?php echo htmlspecialchars(addslashes($equipo['nombre'])); ?>?')">
                            <input type="hidden" name="equipo_id" value="<?php echo $equipo['id']; ?>">
                            <button type="submit" class="btn btn-danger">
                                 Eliminar Equipo
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>