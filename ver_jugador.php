<?php
// ver_jugador.php

require_once __DIR__ . '/includes/conexion.php';
verificarAutenticacion();

$rol = $_SESSION['usuario_rol'] ?? '';
$federacion_id = $_SESSION['usuario_federacion_id'] ?? null;

// Manejar eliminación directa desde este mismo archivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jugador_id'])) {
    $jugador_id = filter_var($_POST['jugador_id'], FILTER_VALIDATE_INT);
    if ($jugador_id) {
        $stmtDelete = $pdo->prepare("DELETE FROM jugadores WHERE id = :id");
        $stmtDelete->execute([':id' => $jugador_id]);
    }
    header('Location: jugadores.php');
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

// Verificar permisos: si es admin, solo puede ver jugadores de equipos de su federación
if ($rol === 'admin' && $federacion_id && $jugador['federacion_id'] != $federacion_id) {
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
            background: #0d1a15;
            border-radius: 12px;
            border: 1px solid #1a2e25;
            overflow: hidden;
        }
        
        .perfil-header {
            background: linear-gradient(135deg, #0a110e 0%, #0d1a15 100%);
            border-bottom: 2px solid #00ff88;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(0, 255, 136, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            margin: 0 auto 1rem;
            border: 2px solid #00ff88;
            color: #00ff88;
        }
        
        .perfil-header h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            color: #ffffff;
        }
        
        .perfil-header .rol {
            font-size: 1rem;
            color: #cbd5e1;
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
            background: #050a08;
            padding: 1.5rem;
            border-radius: 8px;
            border-left: 4px solid #00ff88;
        }
        
        .info-card h4 {
            color: #94a3b8;
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        
        .info-card p {
            color: #cbd5e1;
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
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid #00ff88;
        }
        
        .badge-femenino {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid #ff4444;
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
                <?php if ($rol === 'super_admin'): ?>
                    <a href="federaciones.php"> Federaciones</a>
                <?php endif; ?>
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
                        <form method="POST" action="ver_jugador.php?id=<?php echo $jugador['id']; ?>" style="display: inline;" 
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