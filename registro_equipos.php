<?php
require_once 'includes/conexion.php';
verificarAutenticacion();

// Obtener federaciones para el select
$federaciones = $pdo->query("SELECT id, nombre FROM federaciones WHERE activo = 1 ORDER BY nombre")->fetchAll();

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiarInput($_POST['nombre']);
    $federacion_id = filter_var($_POST['federacion_id'], FILTER_VALIDATE_INT);
    $id_equipo = limpiarInput($_POST['id_equipo']);
    
    if (empty($nombre) || !$federacion_id) {
        $mensaje = 'El nombre y la federación son obligatorios';
        $tipo_mensaje = 'error';
    } else {
        // Verificar si ya existe un equipo con el mismo nombre (en cualquier federación)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM equipos WHERE nombre = :nombre AND activo = 1");
        $stmtCheck->execute([':nombre' => $nombre]);
        $existe = $stmtCheck->fetchColumn();
        
        if ($existe > 0) {
            $mensaje = 'Ya existe un equipo con este nombre. No se puede registrar el mismo equipo en diferentes federaciones';
            $tipo_mensaje = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO equipos (nombre, federacion_id) VALUES (:nombre, :federacion_id)");
                $stmt->execute([':nombre' => $nombre, ':federacion_id' => $federacion_id]);
                
                $mensaje = 'Equipo registrado exitosamente';
                $tipo_mensaje = 'success';
            } catch (PDOException $e) {
                $mensaje = 'Error al registrar: ' . $e->getMessage();
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
    <title>Registrar Equipo - Gestión Deportiva</title>
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
                <a href="federaciones.php"> Federaciones</a>
                <a href="equipos.php" class="active"> Equipos</a>
                <a href="jugadores.php"> Jugadores</a>
                <hr>
                <a href="configuracion_usuarios.php"> Configuración</a>
                <a href="log_out.php" style="color: #e53e3e;"> Cerrar Sesión</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header>
                <h2>Registrar Equipo</h2>
                <p>Complete el formulario para integrar una nueva organización deportiva al sistema.</p>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <h3>Información General</h3>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nombre del Equipo *</label>
                        <input type="text" name="nombre" placeholder="Ej. Real Madrid CF" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Federación *</label>
                        <select name="federacion_id" required>
                            <option value="">Seleccione Federación</option>
                            <?php foreach ($federaciones as $federacion): ?>
                                <option value="<?php echo $federacion['id']; ?>">
                                    <?php echo htmlspecialchars($federacion['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Registrar Equipo</button>
                    <a href="equipos.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </main>
    </div>
</body>
</html>