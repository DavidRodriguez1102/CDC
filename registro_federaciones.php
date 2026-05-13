<?php
require_once 'includes/conexion.php';
verificarAutenticacion();

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = limpiarInput($_POST['nombre']);
    $fecha_fundacion = $_POST['fecha_fundacion'];
    $departamento = limpiarInput($_POST['departamento']);
    $municipio = limpiarInput($_POST['municipio']);
    $complemento = limpiarInput($_POST['complemento']);
    
    // Validaciones
    if (empty($nombre) || empty($fecha_fundacion) || empty($departamento) || empty($municipio)) {
        $mensaje = 'Todos los campos marcados con * son obligatorios';
        $tipo_mensaje = 'error';
    } elseif ($fecha_fundacion > date('Y-m-d')) {
        $mensaje = 'La fecha de fundación no puede ser futura';
        $tipo_mensaje = 'error';
    } else {
        // Verificar si ya existe una federación con el mismo nombre
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM federaciones WHERE nombre = :nombre AND activo = 1");
        $stmtCheck->execute([':nombre' => $nombre]);
        $existe = $stmtCheck->fetchColumn();
        
        if ($existe > 0) {
            $mensaje = 'Ya existe una federación con este nombre';
            $tipo_mensaje = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO federaciones (nombre, fecha_fundacion, departamento, municipio, complemento) 
                                       VALUES (:nombre, :fecha_fundacion, :departamento, :municipio, :complemento)");
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':fecha_fundacion' => $fecha_fundacion,
                    ':departamento' => $departamento,
                    ':municipio' => $municipio,
                    ':complemento' => $complemento
                ]);
                
                // Obtener el ID de la federación recién creada
                $federacion_id = $pdo->lastInsertId();

                // Generar credenciales para el admin de la federación
                $username_admin = 'fed_' . strtolower(str_replace(' ', '_', substr($nombre, 0, 15))) . '_admin';
                $password_admin = generarPasswordSeguro();
                $password_hash = password_hash($password_admin, PASSWORD_DEFAULT);
                $nombre_completo = 'Admin ' . $nombre;

                // Insertar usuario admin para esta federación
                $stmtUser = $pdo->prepare("
                    INSERT INTO usuarios (username, password_hash, nombre_completo, rol, federacion_id) 
                    VALUES (:username, :password_hash, :nombre_completo, 'admin', :federacion_id)
                ");
                $stmtUser->execute([
                    ':username' => $username_admin,
                    ':password_hash' => $password_hash,
                    ':nombre_completo' => $nombre_completo,
                    ':federacion_id' => $federacion_id
                ]);

                // Vincular el usuario admin a la federación
                $usuario_admin_id = $pdo->lastInsertId();
                $stmtUpdateFed = $pdo->prepare("UPDATE federaciones SET usuario_admin_id = :user_id WHERE id = :fed_id");
                $stmtUpdateFed->execute([':user_id' => $usuario_admin_id, ':fed_id' => $federacion_id]);

                // Mostrar credenciales en el mensaje
                $mensaje = "Federación registrada exitosamente. 
                            Usuario Admin: <strong>$username_admin</strong> 
                            Contraseña: <strong>$password_admin</strong>";
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
    <title>Registrar Federación - Gestión Deportiva</title>
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
                <h2>Registro de Federación</h2>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <p>Complete los datos generales para iniciar el proceso de registro oficial.</p>
                
                <form method="POST" action="">
                    <h3>Datos generales</h3>
                    
                    <div class="form-group">
                        <label>Nombre Oficial *</label>
                        <input type="text" name="nombre" placeholder="Federación Deportiva de..." required>
                    </div>
                    
                    <div class="form-group">
                        <label>Fecha de Fundación *</label>
                        <input type="date" name="fecha_fundacion" required>
                    </div>
                    
                    <h3>Dirección</h3>
                    
                    <div class="form-group">
                        <label>Departamento *</label>
                        <input type="text" name="departamento" placeholder="Departamento..." required>
                    </div>
                    
                    <div class="form-group">
                        <label>Municipio *</label>
                        <input type="text" name="municipio" placeholder="Municipio..." required>
                    </div>
                    
                    <div class="form-group">
                        <label>Complemento (Calle, Edificio, Oficina)</label>
                        <input type="text" name="complemento" 
                               placeholder="Ej: Calle 50 # 12-34, Edificio FIFA, Piso 5">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Registrar Datos</button>
                    <a href="federaciones.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </main>
    </div>
</body>
</html>