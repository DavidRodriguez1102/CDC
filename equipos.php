<?php
require_once __DIR__ . '/includes/conexion.php';
verificarAutenticacion();

// Manejar eliminación de equipo
if (isset($_POST['eliminar_equipo']) && isset($_POST['equipo_id'])) {
    $equipo_id = filter_var($_POST['equipo_id'], FILTER_VALIDATE_INT);
    
    if ($equipo_id) {
        try {
            // Verificar si tiene jugadores asociados
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM jugadores WHERE equipo_id = :equipo_id AND activo = 1");
            $stmt->execute([':equipo_id' => $equipo_id]);
            $total_jugadores = $stmt->fetchColumn();
            
            if ($total_jugadores > 0) {
                $error_mensaje = "No se puede eliminar el equipo porque tiene $total_jugadores jugadores activos.";
            } else {
                // Soft delete (desactivar)
                $stmt = $pdo->prepare("UPDATE equipos SET activo = 0 WHERE id = :equipo_id");
                $stmt->execute([':equipo_id' => $equipo_id]);
                $success_mensaje = "Equipo eliminado correctamente.";
            }
        } catch (PDOException $e) {
            $error_mensaje = "Error al eliminar: " . $e->getMessage();
        }
    }
}

// Parámetros de búsqueda y filtro
$busqueda = isset($_GET['buscar']) ? limpiarInput($_GET['buscar']) : '';
$federacion_filtro = isset($_GET['federacion_id']) ? filter_var($_GET['federacion_id'], FILTER_VALIDATE_INT) : 0;

// Construir consulta SQL base
$sql = "SELECT e.*, f.nombre as nombre_federacion,
        (SELECT COUNT(*) FROM jugadores WHERE equipo_id = e.id AND activo = 1) as total_jugadores
        FROM equipos e
        LEFT JOIN federaciones f ON e.federacion_id = f.id
        WHERE e.activo = 1";

$params = [];

// Agregar condiciones de búsqueda
if ($busqueda) {
    $sql .= " AND (e.nombre LIKE :busqueda OR e.id LIKE :busqueda2)";
    $params[':busqueda'] = "%$busqueda%";
    $params[':busqueda2'] = "%$busqueda%";
}

if ($federacion_filtro) {
    $sql .= " AND e.federacion_id = :federacion_id";
    $params[':federacion_id'] = $federacion_filtro;
}

$sql .= " ORDER BY e.nombre ASC";

// Ejecutar consulta
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipos = $stmt->fetchAll();

// Obtener total de equipos para estadísticas
$total_equipos = count($equipos);
$total_jugadores_global = $pdo->query("SELECT COUNT(*) FROM jugadores WHERE activo = 1")->fetchColumn();

// Obtener federaciones para el filtro
$federaciones = $pdo->query("SELECT id, nombre FROM federaciones WHERE activo = 1 ORDER BY nombre")->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipos - Gestión Deportiva</title>
    <link rel="stylesheet" href="/css/admin.css">
    <style>
        /* Estilos específicos para la página de equipos */
        .equipos-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .equipos-stats {
            display: flex;
            gap: 1.5rem;
        }
        
        .stat-mini {
            text-align: center;
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-mini .number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2b6cb0;
        }
        
        .stat-mini .label {
            font-size: 0.8rem;
            color: #718096;
        }
        
        .filtros-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
        }
        
        .search-box input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #2b6cb0;
            box-shadow: 0 0 0 3px rgba(43, 108, 176, 0.1);
        }
        
        .filtro-select {
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            background: white;
            cursor: pointer;
            min-width: 200px;
        }
        
        .filtro-select:focus {
            outline: none;
            border-color: #2b6cb0;
        }
        
        .btn-limpiar {
            padding: 0.75rem 1.5rem;
            background: #edf2f7;
            color: #4a5568;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.95rem;
            transition: background 0.3s;
        }
        
        .btn-limpiar:hover {
            background: #e2e8f0;
        }
        
        .equipos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .equipo-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s;
            border: 1px solid #e2e8f0;
        }
        
        .equipo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        
        .equipo-card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        
        .equipo-card-header h3 {
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
        }
        
        .equipo-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .equipo-card-body {
            padding: 1.5rem;
        }
        
        .equipo-info {
            margin-bottom: 0.75rem;
        }
        
        .equipo-info strong {
            color: #4a5568;
            display: block;
            font-size: 0.85rem;
            margin-bottom: 0.25rem;
        }
        
        .equipo-info span {
            color: #2d3748;
            font-size: 1rem;
        }
        
        .jugadores-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
            padding: 0.75rem;
            background: #f7fafc;
            border-radius: 8px;
        }
        
        .jugadores-count .icon {
            font-size: 1.5rem;
        }
        
        .jugadores-count .count {
            font-size: 1.8rem;
            font-weight: bold;
            color: #2b6cb0;
        }
        
        .jugadores-count .text {
            color: #718096;
            font-size: 0.9rem;
        }
        
        .equipo-card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .btn-ver {
            background: #ebf4ff;
            color: #2b6cb0;
        }
        
        .btn-ver:hover {
            background: #bee3f8;
        }
        
        .btn-editar {
            background: #fefcbf;
            color: #975a16;
        }
        
        .btn-editar:hover {
            background: #faf089;
        }
        
        .btn-eliminar {
            background: #fed7d7;
            color: #c53030;
        }
        
        .btn-eliminar:hover {
            background: #feb2b2;
        }
        
        .mensaje-flotante {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            animation: slideIn 0.3s ease;
            max-width: 400px;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
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
        
        .sin-resultados {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .sin-resultados .icono {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .sin-resultados h3 {
            color: #4a5568;
            margin-bottom: 0.5rem;
        }
        
        .sin-resultados p {
            color: #a0aec0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .equipos-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .equipos-grid {
                grid-template-columns: 1fr;
            }
            
            .filtros-bar {
                flex-direction: column;
            }
            
            .search-box {
                min-width: 100%;
            }
            
            .filtro-select {
                width: 100%;
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
                <a href="federaciones.php"> Federaciones</a>
                <a href="equipos.php" class="active"> Equipos</a>
                <a href="jugadores.php"> Jugadores</a>
                <hr>
                <a href="configuracion_usuarios.php"> Configuración</a>
                <a href="log_out.php" style="color: #e53e3e;"> Cerrar Sesión</a>
            </nav>
        </aside>
        
        <!-- Contenido Principal -->
        <main class="main-content">
            <!-- Mensajes de feedback -->
            <?php if (isset($success_mensaje)): ?>
                <div class="mensaje-flotante mensaje-success" id="mensajeFlotante">
                     <?php echo $success_mensaje; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_mensaje)): ?>
                <div class="mensaje-flotante mensaje-error" id="mensajeFlotante">
                     <?php echo $error_mensaje; ?>
                </div>
            <?php endif; ?>
            
            <!-- Cabecera -->
            <div class="equipos-header">
                <div>
                    <h2>Lista de Equipos</h2>
                    <p style="color: #718096; margin-top: 0.25rem;">
                        Gestiona todos los equipos registrados en el sistema
                    </p>
                </div>
                
                <div class="equipos-stats">
                    <div class="stat-mini">
                        <div class="number"><?php echo $total_equipos; ?></div>
                        <div class="label">Equipos</div>
                    </div>
                    <div class="stat-mini">
                        <div class="number"><?php echo $total_jugadores_global; ?></div>
                        <div class="label">Jugadores Totales</div>
                    </div>
                </div>
                
                <a href="registro_equipos.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <span></span> Nuevo Equipo
                </a>
            </div>
            
            <!-- Filtros -->
            <div class="filtros-bar">
                <div class="search-box">
<form method="GET" action="equipos.php" style="display: flex; gap: 0.5rem;">
                        <input type="text" 
                               name="buscar" 
                               placeholder=" Buscar equipos por nombre o ID de federacion..." 
                               value="<?php echo htmlspecialchars($busqueda); ?>">
                        
                        <select name="federacion_id" class="filtro-select" onchange="this.form.submit()">
                            <option value="">Todas las Federaciones</option>
                            <?php foreach ($federaciones as $federacion): ?>
                                <option value="<?php echo $federacion['id']; ?>" 
                                    <?php echo $federacion_filtro == $federacion['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($federacion['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem;">
                            Buscar
                        </button>
                        
                        <?php if ($busqueda || $federacion_filtro): ?>
                            <a href="equipos.php" class="btn-limpiar">✕ Limpiar filtros</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Lista de Equipos -->
            <?php if (count($equipos) > 0): ?>
                <div class="equipos-grid">
                    <?php foreach ($equipos as $equipo): ?>
                        <div class="equipo-card">
                            <div class="equipo-card-header">
                                <h3>
                                    <?php echo htmlspecialchars($equipo['nombre']); ?>
                                </h3>
                                <span class="equipo-badge">
                                    ID: <?php echo $equipo['federacion_id']; ?>
                                </span>
                            </div>
                            
                            <div class="equipo-card-body">
                                <div class="equipo-info">
                                    <strong>Federación</strong>
                                    <span> <?php echo htmlspecialchars($equipo['nombre_federacion'] ?? 'Sin federación'); ?></span>
                                </div>
                                
                                <div class="equipo-info">
                                    <strong>Fecha de Creación</strong>
                                    <span> <?php echo date('d/m/Y', strtotime($equipo['fecha_creacion'])); ?></span>
                                </div>
                                
                                <div class="jugadores-count">
                                    <span class="icon"></span>
                                    <span class="count"><?php echo $equipo['total_jugadores']; ?></span>
                                    <span class="text">Jugadores activos</span>
                                </div>
                                
                                <div class="equipo-card-actions">
                                    <a href="ver_equipo.php?id=<?php echo $equipo['id']; ?>" class="btn-sm btn-ver">
                                         Ver Detalles
                                    </a>
                                    
                                    <a href="editar_equipo.php?id=<?php echo $equipo['id']; ?>" class="btn-sm btn-editar">
                                         Editar
                                    </a>
                                    
                                        <form method="POST" action="equipos.php" style="display: inline;" 
                                          onsubmit="return confirmarEliminacion('<?php echo htmlspecialchars(addslashes($equipo['nombre'])); ?>', <?php echo $equipo['total_jugadores']; ?>)">
                                        <input type="hidden" name="equipo_id" value="<?php echo $equipo['id']; ?>">
                                        <button type="submit" name="eliminar_equipo" class="btn-sm btn-eliminar">
                                             Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Paginación simple -->
                <div style="text-align: center; margin-top: 1rem; color: #718096;">
                    Mostrando <?php echo $total_equipos; ?> equipo(s)
                </div>
                
            <?php else: ?>
                <div class="sin-resultados">
                    <div class="icono"></div>
                    <h3>No se encontraron equipos</h3>
                    <?php if ($busqueda || $federacion_filtro): ?>
                        <p>No hay resultados para los filtros aplicados.</p>
                        <a href="equipos.php" class="btn btn-primary" style="margin-top: 1rem;">
                            Limpiar filtros
                        </a>
                    <?php else: ?>
                        <p>Aún no hay equipos registrados en el sistema.</p>
                        <a href="registro_equipos.php" class="btn btn-primary" style="margin-top: 1rem;">
                             Registrar Primer Equipo
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
    
    <!-- JavaScript para funcionalidades -->
    <script>
        // Ocultar mensajes flotantes después de 5 segundos
        document.addEventListener('DOMContentLoaded', function() {
            const mensajeFlotante = document.getElementById('mensajeFlotante');
            if (mensajeFlotante) {
                setTimeout(function() {
                    mensajeFlotante.style.animation = 'slideOut 0.3s ease forwards';
                    setTimeout(function() {
                        mensajeFlotante.remove();
                    }, 300);
                }, 5000);
            }
        });
        
        // Función para confirmar eliminación
        function confirmarEliminacion(nombreEquipo, totalJugadores) {
            if (totalJugadores > 0) {
                alert('No se puede eliminar "' + nombreEquipo + '" porque tiene ' + totalJugadores + ' jugadores activos.\n\nDebe reasignar o eliminar los jugadores primero.');
                return false;
            }
            
            return confirm('¿Estás seguro de que deseas eliminar el equipo "' + nombreEquipo + '"?\n\nEsta acción no se puede deshacer.');
        }
        
        // Agregar animación para ocultar mensajes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>