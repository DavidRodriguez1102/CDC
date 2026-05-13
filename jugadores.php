<?php
require_once 'includes/conexion.php';
verificarAutenticacion();

$busqueda = isset($_GET['buscar']) ? limpiarInput($_GET['buscar']) : '';

$rol = $_SESSION['usuario_rol'] ?? '';
$federacion_id = $_SESSION['usuario_federacion_id'] ?? null;


$sql = "SELECT j.*, e.nombre as nombre_equipo, f.nombre as nombre_federacion
        FROM jugadores j
        LEFT JOIN equipos e ON j.equipo_id = e.id
        LEFT JOIN federaciones f ON e.federacion_id = f.id
        WHERE j.activo = 1";

// Si es admin, solo ve jugadores de equipos de su federación
if ($rol === 'admin' && $federacion_id) {
    $sql .= " AND e.federacion_id = :federacion_id";
}

if ($busqueda) {
    $sql .= " AND (j.nombre LIKE :busqueda OR j.id LIKE :busqueda2)";
}

$sql .= " ORDER BY j.nombre";

$stmt = $pdo->prepare($sql);
if ($rol === 'admin' && $federacion_id) {
    if ($busqueda) {
        $stmt->execute([
            ':federacion_id' => $federacion_id,
            ':busqueda' => "%$busqueda%",
            ':busqueda2' => "%$busqueda%"
        ]);
    } else {
        $stmt->execute([':federacion_id' => $federacion_id]);
    }
} else {
    if ($busqueda) {
        $stmt->execute([':busqueda' => "%$busqueda%", ':busqueda2' => "%$busqueda%"]);
    } else {
        $stmt->execute();
    }
}

$jugadores = $stmt->fetchAll();

// Obtener estadísticas
$total_jugadores = count($jugadores);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Jugadores - Gestión Deportiva</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1> Gestión Soccer</h1>
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
        
        <main class="main-content">
            <header>
                <h2>Lista de Jugadores</h2>
                <p style="color: #718096; margin-top: 0.25rem;">
                    <?php echo $total_jugadores; ?> jugador(es)<?php echo $rol === 'admin' ? ' en mi federación' : ' registrados'; ?>
                </p>
                <a href="registro_jugadores.php" class="btn btn-primary">+ Registrar Jugador</a>
            </header>
            
            
            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="buscar" placeholder="Buscar jugador por nombre o ID..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                </form>
            </div>
            
            <div class="card-grid">
                <?php foreach ($jugadores as $jugador): ?>
                <div class="card">
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <div style="font-size: 2.5rem;">
                        </div>
                        <div>
                            <h3><?php echo htmlspecialchars($jugador['nombre']); ?></h3>
                            <p><strong><?php echo strtoupper($jugador['genero']); ?></strong> • 
                               <?php echo htmlspecialchars($jugador['nombre_equipo']); ?></p>
                            <p><strong>Equipo ID:</strong> <?php echo $jugador['equipo_id']; ?></p>
                            <p><strong>F. Nac:</strong> <?php echo date('d/m/Y', strtotime($jugador['fecha_nacimiento'])); ?></p>
                            <p><strong>Federación:</strong> <?php echo htmlspecialchars($jugador['nombre_federacion']); ?></p>
                        </div>
                    </div>
                    <div style="margin-top: 1rem;">
                        <a href="ver_jugador.php?id=<?php echo $jugador['id']; ?>" class="btn btn-primary">Ver Detalles</a>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($jugadores) === 0): ?>
                <div class="card">
                    <p style="text-align: center;">No se encontraron jugadores</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>