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

    // Validaciones simples pero robustas
    if (empty($nombre) || empty($id_jugador) || empty($fecha_nacimiento) || empty($genero) || !$equipo_id) {
        $mensaje = 'Todos los campos son obligatorios.';
        $tipo_mensaje = 'error';
    } elseif (!in_array($genero, ['masculino', 'femenino'])) {
        $mensaje = 'Género no válido.';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Insertar en la BD. Usamos $id_jugador como un campo de texto de "Cédula" o "ID Oficial"
            // Ya que en tu BD el campo 'id' es auto-incremental, asumiré que 'id del jugador' es un identificador externo.
            // Si quieres guardarlo, debemos agregar una columna 'identificador_oficial' a la tabla jugadores, o usarlo como ID si cambias la BD.
            // Por simplicidad, lo guardaremos en el nombre concatenado o mejor, agregar una columna.
            // ¡IMPORTANTE! Como en tu SQL no tienes columna 'id_oficial', la omitiré del INSERT.
            // Si quieres que ese ID sea el campo 'id', debes quitar AUTO_INCREMENT. Lo dejaremos como está tu SQL.
            
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
        <!-- Pegas el mismo menú lateral que en dashboard.php -->
        <aside class="sidebar">
            <!-- ... menú igual ... -->
        </aside>
        <main class="main-content">
            <h2>Registrar Jugador</h2>
            <p>Complete los detalles a continuación para añadir un nuevo deportista.</p>

            <?php if ($mensaje): ?>
                <div class="mensaje <?php echo $tipo_mensaje; ?>"><?php echo $mensaje; ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="form-principal">
                <div class="foto-perfil">
                    <p>Foto de Perfil</p>
                    <input type="file" name="foto" accept="image/png, image/jpeg"> (Formatos: JPG, PNG. Máx 2MB)
                </div>
                
                <div class="form-grid">
                    <label>Nombre Completo</label>
                    <input type="text" name="nombre" placeholder="Ej: Juan Pérez García" required>

                    <label>ID del Jugador (Documento)</label>
                    <input type="text" name="id_jugador" placeholder="Ej: 12549" required>
                    
                    <label>Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" required>
                    
                    <label>Género</label>
                    <select name="genero" required>
                        <option value="">Seleccione una opción</option>
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                    </select>

                    <label>Asignar Equipo</label>
                    <select name="equipo_id" required>
                        <option value="">Seleccione un equipo</option>
                        <?php foreach ($equipos as $equipo): ?>
                            <option value="<?php echo $equipo['id']; ?>">
                                <?php echo htmlspecialchars($equipo['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-acciones">
                    <a href="dashboard.php" class="btn-cancelar">Cancelar</a>
                    <button type="submit" name="registrar_jugador" class="btn-guardar">Registrar Jugador</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>