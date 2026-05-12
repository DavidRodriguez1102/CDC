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

// Manejar eliminación directa desde este mismo archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipo_id'])) {
    $equipo_id = filter_var($_POST['equipo_id'], FILTER_VALIDATE_INT);
    if ($equipo_id) {
        $pdo->beginTransaction();
        $stmtDeleteJugadores = $pdo->prepare("UPDATE jugadores SET activo = 0 WHERE equipo_id = :id");
        $stmtDeleteJugadores->execute([':id' => $equipo_id]);

        $stmtDeleteEquipo = $pdo->prepare("UPDATE equipos SET activo = 0 WHERE id = :id");
        $stmtDeleteEquipo->execute([':id' => $equipo_id]);
        $pdo->commit();
    }
    header('Location: equipos.php');
    exit;
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
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .detalle-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
        }
        
        .detalle-header h2 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .detalle-header .id-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
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
            border-bottom: 1px solid #e2e8f0;
        }
        
        .info-section:last-child {
            border-bottom: none;
        }
        
        .info-section h3 {
            color: #2d3748;
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
            background: #f7fafc;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e2e8f0;
        }
        
        .stat-box .number {
            font-size: 2rem;
            font-weight: bold;
            color: #2b6cb0;
        }
        
        .stat-box .label {
            color: #718096;
            font-size: 0.9rem;
            margin-top: 0.25rem;
        }
        
        .jugadores-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .jugadores-table th {
            background: #f7fafc;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #4a5568;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .jugadores-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .jugadores-table tr:hover {
            background: #f7fafc;
        }
        
        .badge-genero {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
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
        
        .sin-jugadores {
            text-align: center;
            padding: 2rem;
            color: #a0aec0;
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