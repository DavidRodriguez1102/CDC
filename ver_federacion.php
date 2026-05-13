<?php
// ver_federacion.php

require_once __DIR__ . '/includes/conexion.php';
verificarAutenticacion();

$rol = $_SESSION['usuario_rol'] ?? '';
$federacion_id = $_SESSION['usuario_federacion_id'] ?? null;
$error_mensaje = '';

// Manejar eliminación directa desde este mismo archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['federacion_id'])) {
    $federacion_id = filter_var($_POST['federacion_id'], FILTER_VALIDATE_INT);
    if ($federacion_id) {
        try {
            // Iniciar transacción para garantizar consistencia
            $pdo->beginTransaction();
            
            // 1. Eliminar todos los jugadores de los equipos de esta federación
            $stmtDesactivarJugadores = $pdo->prepare(
                "DELETE FROM jugadores 
                 WHERE equipo_id IN (SELECT id FROM equipos WHERE federacion_id = :id)"
            );
            $stmtDesactivarJugadores->execute([':id' => $federacion_id]);
            
            // 2. Eliminar todos los equipos de la federación
            $stmtDesactivarEquipos = $pdo->prepare("DELETE FROM equipos WHERE federacion_id = :id");
            $stmtDesactivarEquipos->execute([':id' => $federacion_id]);
            
            // 3. Eliminar el usuario admin de la federación (si existe)
            $stmtDesactivarUsuario = $pdo->prepare("DELETE FROM usuarios WHERE federacion_id = :id AND rol = 'admin'");
            $stmtDesactivarUsuario->execute([':id' => $federacion_id]);
            
            // 4. Eliminar la federación
            $stmtDesactivarFederacion = $pdo->prepare("DELETE FROM federaciones WHERE id = :id");
            $stmtDesactivarFederacion->execute([':id' => $federacion_id]);
            
            // Confirmar la transacción
            $pdo->commit();
            
            header('Location: federaciones.php');
            exit;
        } catch (PDOException $e) {
            // Revertir la transacción en caso de error
            $pdo->rollBack();
            $error_mensaje = "Error al eliminar la federación: " . $e->getMessage();
        }
    }
}

// Obtener ID de la federación
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$id) {
    header('Location: federaciones.php');
    exit;
}

// Obtener información de la federación
$stmt = $pdo->prepare("SELECT * FROM federaciones WHERE id = :id AND activo = 1");
$stmt->execute([':id' => $id]);
$federacion = $stmt->fetch();

if (!$federacion) {
    header('Location: federaciones.php');
    exit;
}

// Obtener equipos de esta federación
$stmtEquipos = $pdo->prepare("
    SELECT e.*, 
           (SELECT COUNT(*) FROM jugadores WHERE equipo_id = e.id AND activo = 1) as total_jugadores
    FROM equipos e
    WHERE e.federacion_id = :federacion_id AND e.activo = 1
    ORDER BY e.nombre ASC
");
$stmtEquipos->execute([':federacion_id' => $id]);
$equipos = $stmtEquipos->fetchAll();

$total_equipos = count($equipos);
$total_jugadores = 0;
foreach ($equipos as $equipo) {
    $total_jugadores += $equipo['total_jugadores'];
}

// Calcular años de la federación
$fecha_fundacion = new DateTime($federacion['fecha_fundacion']);
$hoy = new DateTime();
$antiguedad = $hoy->diff($fecha_fundacion)->y;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Federación - <?php echo htmlspecialchars($federacion['nombre']); ?></title>
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
        
        .detalle-header .badge {
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
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .info-item {
            background: #050a08;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #00ff88;
        }
        
        .info-item label {
            color: #94a3b8;
            font-size: 0.85rem;
            text-transform: uppercase;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .info-item p {
            color: #ffffff;
            font-size: 1.1rem;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: #050a08;
            border: 1px solid #1a2e25;
            color: #00ff88;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
        }
        
        .stat-card .label {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-top: 0.25rem;
            color: #cbd5e1;
        }
        
        .equipos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .equipo-mini-card {
            background: #050a08;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #1a2e25;
            transition: all 0.3s;
        }
        
        .equipo-mini-card:hover {
            border-color: #00ff88;
            background: rgba(0, 255, 136, 0.05);
            transform: translateY(-2px);
        }
        
        .equipo-mini-card h4 {
            color: #00ff88;
            margin-bottom: 0.5rem;
        }
        
        .equipo-mini-card p {
            color: #cbd5e1;
            font-size: 0.9rem;
        }
        
        .ver-mas {
            display: inline-block;
            margin-top: 0.5rem;
            color: #00ff88;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            border: 1px solid #00ff88;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .ver-mas:hover {
            background: rgba(0, 255, 136, 0.1);
        }
        
        .sin-datos {
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
            flex-wrap: wrap;
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
                <?php if ($rol === 'super_admin'): ?>
                    <a href="federaciones.php" class="active"> Federaciones</a>
                <?php endif; ?>                <a href="equipos.php"> Equipos</a>
                <a href="jugadores.php"> Jugadores</a>
                <hr>
                <a href="configuracion_usuarios.php"> Configuración</a>
                <a href="log_out.php" style="color: #e53e3e;"> Cerrar Sesión</a>
            </nav>
        </aside>
        
        <!-- Contenido Principal -->
        <main class="main-content">
            <header>
                <h2>Detalles de la Federación</h2>
                <a href="federaciones.php" class="btn btn-secondary"> Volver a Federaciones</a>
            </header>
            
            <?php if (!empty($error_mensaje)): ?>
                <div style="margin: 1rem 0; padding: 1rem; border-radius: 8px; background: #fff5f5; color: #742a2a; border: 1px solid #feb2b2;">
                    <?php echo htmlspecialchars($error_mensaje); ?>
                </div>
            <?php endif; ?>

            <div class="detalle-container">
                <div class="detalle-header">
                    <h2><?php echo htmlspecialchars($federacion['nombre']); ?></h2>
                    <span class="badge">ID: <?php echo $federacion['id']; ?></span>
                </div>
                
                <div class="detalle-body">
                    <!-- Estadísticas -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="number"><?php echo $total_equipos; ?></div>
                            <div class="label">Equipos</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?php echo $total_jugadores; ?></div>
                            <div class="label">Jugadores Totales</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?php echo $antiguedad; ?></div>
                            <div class="label">Años de Historia</div>
                        </div>
                    </div>
                    
                    <!-- Información General -->
                    <div class="info-section">
                        <h3> Información General</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label> Fecha de Fundación</label>
                                <p><?php echo date('d/m/Y', strtotime($federacion['fecha_fundacion'])); ?></p>
                            </div>
                            
                            <div class="info-item">
                                <label> Antigüedad</label>
                                <p><?php echo $antiguedad; ?> años</p>
                            </div>
                            
                            <div class="info-item">
                                <label> Departamento</label>
                                <p><?php echo htmlspecialchars($federacion['departamento']); ?></p>
                            </div>
                            
                            <div class="info-item">
                                <label> Municipio</label>
                                <p><?php echo htmlspecialchars($federacion['municipio']); ?></p>
                            </div>
                            
                            <?php if ($federacion['complemento']): ?>
                            <div class="info-item">
                                <label> Dirección Completa</label>
                                <p><?php echo htmlspecialchars($federacion['complemento']); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <label> Fecha de Registro</label>
                                <p><?php echo date('d/m/Y', strtotime($federacion['fecha_creacion'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Equipos -->
                    <div class="info-section">
                        <h3> Equipos de la Federación</h3>
                        
                        <?php if ($total_equipos > 0): ?>
                            <div class="equipos-grid">
                                <?php foreach ($equipos as $equipo): ?>
                                    <div class="equipo-mini-card">
                                        <h4><?php echo htmlspecialchars($equipo['nombre']); ?></h4>
                                        <p> <?php echo $equipo['total_jugadores']; ?> jugadores</p>
                                        <p> Desde <?php echo date('d/m/Y', strtotime($equipo['fecha_creacion'])); ?></p>
                                        <a href="ver_equipo.php?id=<?php echo $equipo['id']; ?>" class="ver-mas">
                                            Ver equipo →
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="sin-datos">
                                <p> No hay equipos registrados en esta federación.</p>
                                <a href="registro_equipos.php" class="btn btn-primary" style="margin-top: 1rem;">
                                     Agregar Equipo
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Acciones -->
                    <div class="acciones">
                        <a href="registro_equipos.php" class="btn btn-primary">
                             Agregar Equipo
                        </a>
                        <a href="editar_federacion.php?id=<?php echo $federacion['id']; ?>" class="btn btn-primary">
                             Editar Federación
                        </a>
                        <a href="federaciones.php" class="btn btn-secondary">
                             Volver a la lista
                        </a>
                        <form method="POST" action="ver_federacion.php?id=<?php echo $federacion['id']; ?>" style="display: inline;" 
                              onsubmit="return confirm('¿Estás seguro de eliminar la federación <?php echo htmlspecialchars(addslashes($federacion['nombre'])); ?>?\n\nSe eliminarán también todos sus equipos y jugadores.')">
                            <input type="hidden" name="federacion_id" value="<?php echo $federacion['id']; ?>">
                            <button type="submit" class="btn btn-danger">
                                 Eliminar Federación
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>