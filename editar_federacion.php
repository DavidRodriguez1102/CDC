<?php
// editar_federacion.php


require_once 'includes/conexion.php';
verificarAutenticacion();

$rol = $_SESSION['usuario_rol'] ?? '';
$federacion_id = $_SESSION['usuario_federacion_id'] ?? null;

if ($rol !== 'super_admin') {
    header('Location: dashboard.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

// Obtener ID de la federación
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$id) {
    header('Location: federaciones.php');
    exit;
}

// Obtener datos actuales de la federación
$stmt = $pdo->prepare("SELECT * FROM federaciones WHERE id = :id AND activo = 1");
$stmt->execute([':id' => $id]);
$federacion = $stmt->fetch();

if (!$federacion) {
    header('Location: federaciones.php');
    exit;
}

// Procesar formulario de actualización
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['actualizar_federacion'])) {
    $nombre = limpiarInput($_POST['nombre']);
    $fecha_fundacion = $_POST['fecha_fundacion'];
    $departamento = limpiarInput($_POST['departamento']);
    $municipio = limpiarInput($_POST['municipio']);
    $complemento = limpiarInput($_POST['complemento']);
    
    if (empty($nombre) || empty($fecha_fundacion) || empty($departamento) || empty($municipio)) {
        $mensaje = 'Todos los campos obligatorios (*) deben ser completados.';
        $tipo_mensaje = 'error';
    } else {
        // Verificar si ya existe otra federación con el mismo nombre
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM federaciones WHERE nombre = :nombre AND activo = 1 AND id != :id");
        $stmtCheck->execute([':nombre' => $nombre, ':id' => $id]);
        $existe = $stmtCheck->fetchColumn();
        
        if ($existe > 0) {
            $mensaje = 'Ya existe otra federación con este nombre.';
            $tipo_mensaje = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE federaciones 
                    SET nombre = :nombre, 
                        fecha_fundacion = :fecha_fundacion, 
                        departamento = :departamento, 
                        municipio = :municipio, 
                        complemento = :complemento 
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':fecha_fundacion' => $fecha_fundacion,
                    ':departamento' => $departamento,
                    ':municipio' => $municipio,
                    ':complemento' => $complemento,
                    ':id' => $id
                ]);
                
                $mensaje = 'Federación actualizada exitosamente.';
                $tipo_mensaje = 'success';
                
                // Actualizar datos en la variable
                $federacion['nombre'] = $nombre;
                $federacion['fecha_fundacion'] = $fecha_fundacion;
                $federacion['departamento'] = $departamento;
                $federacion['municipio'] = $municipio;
                $federacion['complemento'] = $complemento;
                
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
    <title>Editar Federación - <?php echo htmlspecialchars($federacion['nombre']); ?></title>
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
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #1a2e25;
        }
        
        .form-section:last-of-type {
            border-bottom: none;
        }
        
        .form-section h3 {
            color: #ffffff;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
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
        
        .form-group small {
            display: block;
            color: #cbd5e1;
            font-size: 0.85rem;
            margin-top: 0.25rem;
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
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
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
                <?php endif; ?>
                <a href="equipos.php"> Equipos</a>
                <a href="jugadores.php"> Jugadores</a>
                <hr>
                <a href="configuracion_usuarios.php"> Configuración</a>
                <a href="log_out.php" style="color: #e53e3e;"> Cerrar Sesión</a>
            </nav>
        </aside>
        
        <!-- Contenido Principal -->
        <main class="main-content">
            <header>
                <h2>Editar Federación</h2>
                <a href="ver_federacion.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                    ← Volver a la federación
                </a>
            </header>
            
            <?php if ($mensaje): ?>
                <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>">
                    <?php echo $mensaje; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <div class="form-header">
                    <h2> Editando: <?php echo htmlspecialchars($federacion['nombre']); ?></h2>
                    <p>ID de Federación: <?php echo $federacion['id']; ?></p>
                </div>
                
                <form method="POST" action="">
                    <!-- Datos Generales -->
                    <div class="form-section">
                        <h3> Datos Generales</h3>
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label for="nombre">Nombre Oficial *</label>
                                <input type="text" 
                                       id="nombre" 
                                       name="nombre" 
                                       value="<?php echo htmlspecialchars($federacion['nombre']); ?>" 
                                       placeholder="Federación Deportiva de..."
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="fecha_fundacion">Fecha de Fundación *</label>
                                <input type="date" 
                                       id="fecha_fundacion" 
                                       name="fecha_fundacion" 
                                       value="<?php echo $federacion['fecha_fundacion']; ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dirección -->
                    <div class="form-section">
                        <h3> Dirección</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="departamento">Departamento *</label>
                                    <input type="text" 
                                       id="departamento" 
                                       name="departamento" 
                                       value="<?php echo htmlspecialchars($federacion['departamento']); ?>" 
                                       placeholder="Ej:Barcelona, Madrid, etc."
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label for="municipio">Municipio *</label>
                                <input type="text" 
                                       id="municipio" 
                                       name="municipio" 
                                       value="<?php echo htmlspecialchars($federacion['municipio']); ?>" 
                                       placeholder="Ej: Madrid, Barcelona Centro, etc."
                                       required>
                            </div>
                            
                            <div class="form-group full-width">
                                <label for="complemento">Complemento (Calle, Edificio, Oficina)</label>
                                <input type="text" 
                                       id="complemento" 
                                       name="complemento" 
                                       value="<?php echo htmlspecialchars($federacion['complemento']); ?>" 
                                       placeholder="Ej: Calle 50 # 12-34, Edificio FIFA, Piso 5">
                                <small>Información adicional de la dirección</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="actualizar_federacion" class="btn btn-primary">
                             Guardar Cambios
                        </button>
                        <a href="ver_federacion.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                             Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>