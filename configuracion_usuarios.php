<?php
require_once 'includes/conexion.php';
verificarAutenticacion();

$mensaje = '';
$tipo_mensaje = '';
$rol = $_SESSION['usuario_rol'] ?? '';
$federacion_id = $_SESSION['usuario_federacion_id'] ?? null;

// Actualizar información personal
if (isset($_POST['actualizar_info'])) {
    $nombre_completo = limpiarInput($_POST['nombre_completo']);
    
    try {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre_completo = :nombre WHERE id = :id");
        $stmt->execute([':nombre' => $nombre_completo, ':id' => $_SESSION['usuario_id']]);
        $_SESSION['usuario_nombre'] = $nombre_completo;
        $mensaje = 'Información actualizada correctamente';
        $tipo_mensaje = 'success';
    } catch (PDOException $e) {
        $mensaje = 'Error al actualizar: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Cambiar contraseña
if (isset($_POST['cambiar_password'])) {
    $password_actual = $_POST['password_actual'];
    $nuevo_password = $_POST['nuevo_password'];
    $confirmar_password = $_POST['confirmar_password'];
    
    if ($nuevo_password !== $confirmar_password) {
        $mensaje = 'Las contraseñas no coinciden';
        $tipo_mensaje = 'error';
    } elseif (strlen($nuevo_password) < 6) {
        $mensaje = 'La contraseña debe tener al menos 6 caracteres';
        $tipo_mensaje = 'error';
    } else {
        // Verificar contraseña actual
        $stmt = $pdo->prepare("SELECT password_hash FROM usuarios WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['usuario_id']]);
        $usuario = $stmt->fetch();
        
        if (password_verify($password_actual, $usuario['password_hash'])) {
            $nuevo_hash = password_hash($nuevo_password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = :hash WHERE id = :id");
            $stmt->execute([':hash' => $nuevo_hash, ':id' => $_SESSION['usuario_id']]);
            $mensaje = 'Contraseña actualizada correctamente';
            $tipo_mensaje = 'success';
        } else {
            $mensaje = 'La contraseña actual es incorrecta';
            $tipo_mensaje = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Configuración de Usuario</title>
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
                <?php if ($rol === 'super_admin'): ?>
                    <a href="federaciones.php"> Federaciones</a>
                <?php endif; ?>
                <a href="equipos.php"> Equipos</a>
                <a href="jugadores.php"> Jugadores</a>
                <hr>
                <a href="configuracion_usuarios.php" class="active"> Configuración</a>
                <a href="log_out.php" style="color: #e53e3e;"> Cerrar Sesión</a>
            </nav>
        </aside>
        
        <main class="main-content">
            <header>
                <h2>Configuración</h2>
                <span class="badge"><?php echo strtoupper($_SESSION['usuario_rol']); ?></span>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container" style="margin-bottom: 2rem;">
                <h3>AJUSTES DE CUENTA</h3>
                
                <form method="POST" action="">
                    <h4>Información Personal</h4>
                    <div class="form-group">
                        <label>Rol</label>
                        <input type="text" name="nombre_completo" 
                               value="<?php echo htmlspecialchars($_SESSION['usuario_nombre']); ?>" required>
                    </div>
                    <button type="submit" name="actualizar_info" class="btn btn-primary">Actualizar Información</button>
                </form>
            </div>
            
            <div class="form-container">
                <h4>Cambiar Contraseña</h4>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Contraseña Actual</label>
                        <input type="password" name="password_actual" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Nueva Contraseña</label>
                        <input type="password" name="nuevo_password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirmar Nueva Contraseña</label>
                        <input type="password" name="confirmar_password" required minlength="6">
                    </div>
                    
                    <button type="submit" name="cambiar_password" class="btn btn-primary">Cambiar Contraseña</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>