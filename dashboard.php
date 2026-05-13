<?php
require_once 'includes/conexion.php';
verificarAutenticacion();

$rol = $_SESSION['usuario_rol'] ?? '';
$federacion_id = $_SESSION['usuario_federacion_id'] ?? null;
$totalFederaciones = $pdo->query("SELECT COUNT(*) FROM federaciones WHERE activo = 1")->fetchColumn();

// Para usuarios admin, filtrar datos de su federación
if ($rol === 'admin' && $federacion_id) {
    $totalEquipos = $pdo->prepare("SELECT COUNT(*) FROM equipos WHERE federacion_id = ? AND activo = 1");
    $totalEquipos->execute([$federacion_id]);
    $totalEquipos = $totalEquipos->fetchColumn();
    
    $totalJugadores = $pdo->prepare("
        SELECT COUNT(*) FROM jugadores j 
        JOIN equipos e ON j.equipo_id = e.id 
        WHERE e.federacion_id = ? AND j.activo = 1
    ");
    $totalJugadores->execute([$federacion_id]);
    $totalJugadores = $totalJugadores->fetchColumn();
}
else {
    // Para super admin, mostrar todos los datos
    $totalEquipos = $pdo->query("SELECT COUNT(*) FROM equipos WHERE activo = 1")->fetchColumn();
    $totalJugadores = $pdo->query("SELECT COUNT(*) FROM jugadores WHERE activo = 1")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Gestión Soccer</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1> Gestión Soccer</h1>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active"> Dashboard</a>
                <?php if ($rol === 'super_admin'): ?>
                    <a href="federaciones.php"> Federaciones</a>
                <?php endif; ?>
                <a href="equipos.php"> Equipos</a>
                <a href="jugadores.php"> Jugadores</a>
                <hr>
                <a href="configuracion_usuarios.php"> Configuración</a>
                <a href="log_out.php" style="color: #e53e3e;"> Cerrar Sesión</a>
            </nav>
        </aside>
        <main class="main-content">
            <header>
                <h2>Dashboard</h2>
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?></span>
            </header>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3><?php echo $totalFederaciones; ?></h3>
                    <p>Federaciones</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $totalEquipos; ?></h3>
                    <p>Equipos</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo $totalJugadores; ?></h3>
                    <p>Jugadores</p>
                </div>
            </div>

            <div class="actions-panel">
                <h3>Acciones Rápidas</h3>
                <?php if ($rol === 'super_admin'): ?>
                    <a href="registro_equipos.php" class="action-btn">Inscribir Equipo</a>
                    <a href="registro_jugadores.php" class="action-btn">Registrar Jugador</a>
                    <a href="registro_federaciones.php" class="action-btn">Registrar Federación</a>
                <?php elseif ($rol === 'admin'): ?>
                    <a href="registro_equipos.php" class="action-btn">Inscribir Equipo</a>
                    <a href="registro_jugadores.php" class="action-btn">Registrar Jugador</a>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>