<?php
require_once 'includes/conexion.php';
verificarAutenticacion();

$busqueda = isset($_GET['buscar']) ? limpiarInput($_GET['buscar']) : '';

$sql = "SELECT * FROM federaciones WHERE activo = 1";
if ($busqueda) {
    $sql .= " AND (nombre LIKE :busqueda OR id LIKE :busqueda2)";
}

$sql .= " ORDER BY nombre";

$stmt = $pdo->prepare($sql);
if ($busqueda) {
    $stmt->execute([':busqueda' => "%$busqueda%", ':busqueda2' => "%$busqueda%"]);
} else {
    $stmt->execute();
}
$federaciones = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Federaciones - Gestión Deportiva</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="admin-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h1> Gestión Deportiva</h1>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"> Dashboard</a>
                <a href="federaciones.php" class="active"> Federaciones</a>
                <a href="equipos.php"> Equipos</a>
                <a href="jugadores.php"> Jugadores</a>
                <hr>
                <a href="configuracion_usuarios.php"> Configuración</a>
                <a href="log_out.php" style="color: #e53e3e;"> Cerrar Sesión</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header>
                <h2>Listado de Federaciones</h2>
                <a href="registro_federaciones.php" class="btn btn-primary">+ Nueva Federación</a>
            </header>
            
            <div class="search-box">
                <form method="GET" action="">
                    <input type="text" name="buscar" placeholder="Buscar federación por nombre o ID..." 
                           value="<?php echo htmlspecialchars($busqueda); ?>">
                </form>
            </div>
            
            <div class="tabla-container">
                <table>
                    <thead>
                        <tr>
                            <th>NOMBRE DE LA FEDERACIÓN</th>
                            <th>ID</th>
                            <th>FUNDACIÓN</th>
                            <th>DEPARTAMENTO</th>
                            <th>MUNICIPIO</th>
                            <th>ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($federaciones) > 0): ?>
                            <?php foreach ($federaciones as $federacion): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($federacion['nombre']); ?></td>
                                <td>FED-<?php echo $federacion['id']; ?></td>
                                <td><?php echo date('d/m/Y', strtotime($federacion['fecha_fundacion'])); ?></td>
                                <td><?php echo htmlspecialchars($federacion['departamento']); ?></td>
                                <td><?php echo htmlspecialchars($federacion['municipio']); ?></td>
                                <td>
                                    <a href="ver_federacion.php?id=<?php echo $federacion['id']; ?>" 
                                       class="btn btn-primary" style="padding: 0.5rem;">👁 Ver</a>
                                    <a href="editar_federacion.php?id=<?php echo $federacion['id']; ?>" 
                                       class="btn btn-secondary" style="padding: 0.5rem;">✏️ Editar</a>
                                    <a href="eliminar_federacion.php?id=<?php echo $federacion['id']; ?>" 
                                       class="btn btn-danger" style="padding: 0.5rem;" 
                                       onclick="return confirm('¿Estás seguro de eliminar esta federación?')">🗑 Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">No se encontraron federaciones</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p>Mostrando <?php echo count($federaciones); ?> federaciones</p>
        </main>
    </div>
</body>
</html>