<?php
// editar_equipo.php
require_once 'includes/conexion.php';
verificarAutenticacion();

$rol = $_SESSION['usuario_rol'] ?? '';
$federacion_id = $_SESSION['usuario_federacion_id'] ?? null;

// Obtener ID del equipo
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$id) {
    header('Location: equipos.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Obtener datos actuales del equipo
$stmt = $pdo->prepare("SELECT e.id, e.nombre, e.federacion_id, f.nombre as nombre_federacion FROM equipos e LEFT JOIN federaciones f ON e.federacion_id = f.id WHERE e.id = :id");
$stmt->execute([':id' => $id]);
$equipo = $stmt->fetch();

if (!$equipo) {
    header('Location: equipos.php');
    exit;
}

// Verificar permisos: si es admin, solo puede editar equipos de su federación
if ($rol === 'admin' && $federacion_id && isset($equipo['federacion_id']) && $equipo['federacion_id'] != $federacion_id) {
    header('Location: equipos.php');
    exit;
}

// Obtener todas las federaciones para el selector
$federaciones = $pdo->query("SELECT id, nombre FROM federaciones WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_equipo'])) {
    $nombre = limpiarInput($_POST['nombre']);
    $federacion_id_post = filter_var($_POST['federacion_id'], FILTER_VALIDATE_INT);
    
    // Para admin, forzar la federación a la suya propia
    if ($rol === 'admin') {
        $federacion_id = $_SESSION['usuario_federacion_id'];
    } else {
        $federacion_id = $federacion_id_post;
    }
    
    if (empty($nombre) || !$federacion_id) {
        $mensaje = 'El nombre y la federación son obligatorios.';
        $tipo_mensaje = 'error';
    } else {
        // Verificar si ya existe otro equipo con el mismo nombre (en cualquier federación)
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM equipos WHERE nombre = :nombre AND activo = 1 AND id != :id");
        $stmtCheck->execute([':nombre' => $nombre, ':id' => $id]);
        $existe = $stmtCheck->fetchColumn();
        
        if ($existe > 0) {
            $mensaje = 'Ya existe otro equipo con este nombre. No se puede registrar el mismo equipo en diferentes federaciones';
            $tipo_mensaje = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE equipos 
                    SET nombre = :nombre, 
                        federacion_id = :federacion_id 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':federacion_id' => $federacion_id,
                    ':id' => $id
                ]);
                
                $mensaje = 'Equipo actualizado exitosamente.';
                $tipo_mensaje = 'success';
                
                // Actualizar datos en la variable
                $equipo['nombre'] = $nombre;
                $equipo['federacion_id'] = $federacion_id;
                
            } catch (PDOException $e) {
                $mensaje = 'Error al actualizar: ' . $e->getMessage();
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo - <?php echo htmlspecialchars($equipo['nombre']); ?></title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .form-container {
            background: #0d1a15;
            border-radius: 12px;
            border: 1px solid #1a2e25;
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #1a2e25;
        }
        
        .form-header h2 {
            color: #ffffff;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #94a3b8;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #94a3b8;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #1a2e25;
            border-radius: 8px;
            font-size: 1rem;
            background: #050a08;
            color: #ffffff;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00ff88;
            box-shadow: 0 0 0 3px rgba(0, 255, 136, 0.1);
        }
        
        .info-box {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-box p {
            color: #00ff88;
            margin: 0;
        }
        
        .mensaje {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .mensaje-success {
            background: rgba(0, 255, 136, 0.1);
            color: #00ff88;
            border: 1px solid #00ff88;
        }
        
        .mensaje-error {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            border: 1px solid #ff4444;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #1a2e25;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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
                <h2>Editar Equipo</h2>
                <a href="ver_equipo.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    ← Volver al equipo
                </a>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <div class="form-header">
                    <h2> Editando: <?php echo htmlspecialchars($equipo['nombre']); ?></h2>
                    <p>ID del equipo: <?php echo $equipo['id']; ?></p>
                </div>
                
                <div class="info-box">
                    <p> Federación actual: <strong><?php echo htmlspecialchars($equipo['nombre_federacion']); ?></strong></p>
                </div>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nombre">Nombre del Equipo *</label>
                        <input type="text" 
                               id="nombre" 
                               name="nombre" 
                               value="<?php echo htmlspecialchars($equipo['nombre']); ?>" 
                               placeholder="Ej: Real Madrid CF"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="federacion_id">Federación *</label>
                        <select id="federacion_id" name="federacion_id" required <?php echo $rol === 'admin' ? 'disabled' : ''; ?>>
                            <option value="">Seleccione Federación</option>
                            <?php foreach ($federaciones as $federacion): ?>
                                <option value="<?php echo $federacion['id']; ?>" 
                                    <?php echo $equipo['federacion_id'] == $federacion['id'] ? 'selected' : ''; ?>>
                                     <?php echo htmlspecialchars($federacion['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="actualizar_equipo" class="btn btn-primary">
                             Guardar Cambios
                        </button>
                        <a href="ver_equipo.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                             Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>