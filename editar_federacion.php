<?php
// editar_federacion.php
session_start();

// Conexión a la base de datos
$host = 'localhost';
$dbname = 'soccer_federation';
$usuario = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $usuario, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Función para limpiar datos
function limpiarInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    header('Location: log_in.php');
    exit;
}

// Obtener ID de la federación
$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : 0;

if (!$id) {
    header('Location: federaciones.php');
    exit;
}

$mensaje = '';
$tipo_mensaje = '';

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
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .form-header h2 {
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #718096;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-section:last-of-type {
            border-bottom: none;
        }
        
        .form-section h3 {
            color: #2d3748;
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
            color: #4a5568;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #2b6cb0;
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
        }
        
        .form-group small {
            display: block;
            color: #a0aec0;
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
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #9ae6b4;
        }
        
        .mensaje-error {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e2e8f0;
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
            background: #2b6cb0;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2c5282;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #718096;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
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
                <a href="federaciones.php" class="active"> Federaciones</a>
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
                        <h3>📋 Datos Generales</h3>
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
                        <h3>📍 Dirección</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="departamento">Departamento *</label>
                                <select id="departamento" name="departamento" required>
                                    <option value="">Seleccionar ▼</option>
                                    <?php
                                    $departamentos = ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Bilbao', 'Zaragoza', 'Málaga', 'Murcia'];
                                    foreach ($departamentos as $dep): 
                                    ?>
                                        <option value="<?php echo $dep; ?>" <?php echo $federacion['departamento'] == $dep ? 'selected' : ''; ?>>
                                            <?php echo $dep; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="municipio">Municipio *</label>
                                <select id="municipio" name="municipio" required>
                                    <option value="">Seleccionar ▼</option>
                                    <?php
                                    $municipios = ['Madrid Centro', 'Barcelona Centro', 'Valencia Centro', 'Sevilla Centro', 'Bilbao Centro', 'Zaragoza Centro'];
                                    foreach ($municipios as $mun): 
                                    ?>
                                        <option value="<?php echo $mun; ?>" <?php echo $federacion['municipio'] == $mun ? 'selected' : ''; ?>>
                                            <?php echo $mun; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
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