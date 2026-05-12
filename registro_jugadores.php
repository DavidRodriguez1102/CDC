<?php
require_once 'includes/conexion.php';
verificarAutenticacion();

$mensaje = '';
$tipo_mensaje = ''; // 'success' o 'error'

// Obtener todos los equipos para el selector <select>
$stmtEquipos = $pdo->query("SELECT id, nombre FROM equipos WHERE activo = 1 ORDER BY nombre");
$equipos = $stmtEquipos->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['registrar_jugador'])) {
    $nombre = limpiarInput($_POST['nombre']);
    $id_jugador = limpiarInput($_POST['id_jugador']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $genero = $_POST['genero'];
    $equipo_id = filter_var($_POST['equipo_id'], FILTER_VALIDATE_INT);

    // Validaciones 
    if (empty($nombre) || empty($id_jugador) || empty($fecha_nacimiento) || empty($genero) || !$equipo_id) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo_mensaje = 'error';
    } elseif (!in_array($genero, ['masculino', 'femenino'])) {
        $mensaje = 'Género no válido.';
        $tipo_mensaje = 'error';
    } else {
        // Verificar si ya existe un jugador con exactamente la misma información
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM jugadores WHERE nombre = :nombre AND fecha_nacimiento = :fecha_nacimiento AND genero = :genero AND equipo_id = :equipo_id AND activo = 1");
        $stmtCheck->execute([
            ':nombre' => $nombre,
            ':fecha_nacimiento' => $fecha_nacimiento,
            ':genero' => $genero,
            ':equipo_id' => $equipo_id
        ]);
        $existe = $stmtCheck->fetchColumn();
        
        if ($existe > 0) {
            $mensaje = 'Ya existe un jugador con exactamente la misma información (nombre, fecha de nacimiento, género y equipo).';
            $tipo_mensaje = 'error';
        } else {
            try {

                $stmt = $pdo->prepare("INSERT INTO jugadores (nombre, fecha_nacimiento, genero, equipo_id) VALUES (:nombre, :fecha_nacimiento, :genero, :equipo_id)");
                $stmt->execute([
                    ':nombre' => $nombre,
                    ':fecha_nacimiento' => $fecha_nacimiento,
                    ':genero' => $genero,
                    ':equipo_id' => $equipo_id
                ]);
                
                $mensaje = "¡Jugador '$nombre' registrado exitosamente!";
                $tipo_mensaje = 'success';
            } catch (PDOException $e) {
                $mensaje = "Error al guardar: " . $e->getMessage();
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
    <title>Registrar Jugador</title>
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
                <a href="federaciones.php"> Federaciones</a>
                <a href="equipos.php"> Equipos</a>
                <a href="jugadores.php"> Jugadores</a>
                <hr>
                <a href="configuracion_usuarios.php"> Configuración</a>
                <a href="log_out.php" style="color: #e53e3e;"> Cerrar Sesión</a>
            </nav>
        </aside>
        <main class="main-content">
            <header>
                <h2>Registrar Jugador</h2>
                <p>Complete los detalles a continuación para añadir un nuevo deportista.</p>
            </header>

            <?php if ($mensaje): ?>
                <div class="mensaje mensaje-<?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <div class="form-container">
                <h3>Información del Jugador</h3>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Nombre Completo *</label>
                        <input type="text" name="nombre" placeholder="Ej: Juan Pérez García" required>
                    </div>
                                        
                    <div class="form-group">
                        <label>Fecha de Nacimiento *</label>
                        <input type="date" name="fecha_nacimiento" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Género *</label>
                        <select name="genero" required>
                            <option value="">Seleccione una opción</option>
                            <option value="masculino">Masculino</option>
                            <option value="femenino">Femenino</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Asignar Equipo *</label>
                        <select name="equipo_id" required>
                            <option value="">Seleccione un equipo</option>
                            <?php foreach ($equipos as $equipo): ?>
                                <option value="<?php echo $equipo['id']; ?>">
                                    <?php echo htmlspecialchars($equipo['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" name="registrar_jugador" class="btn btn-primary">Registrar Jugador</button>
                    <a href="jugadores.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </main>
    </div>
</body>
</html>