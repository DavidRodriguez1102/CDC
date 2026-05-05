<?php
// ...Conexión y autenticación...
$equipos = $pdo->query("SELECT e.id, e.nombre, f.nombre as federacion, 
                                (SELECT COUNT(*) FROM jugadores WHERE equipo_id = e.id AND activo = 1) as total_jugadores 
                         FROM equipos e 
                         JOIN federaciones f ON e.federacion_id = f.id 
                         WHERE e.activo = 1")->fetchAll();
?>
<!-- HTML: Cabecera, sidebar... -->
<h2>Lista de Equipos</h2>
<input type="text" placeholder="Buscar equipos..."> <!-- Esto usaría JS o un filtro PHP -->
<div class="lista-equipos">
    <?php foreach ($equipos as $equipo): ?>
    <div class="card-equipo">
        <h3><?php echo htmlspecialchars($equipo['nombre']); ?></h3>
        <p>ID: <?php echo $equipo['id']; ?></p>
        <p><?php echo htmlspecialchars($equipo['federacion']); ?></p>
        <p><?php echo $equipo['total_jugadores']; ?> Jugadores</p>
        <a href="ver_equipo.php?id=<?php echo $equipo['id']; ?>" class="btn-detalles">Ver Detalles</a>
    </div>
    <?php endforeach; ?>
</div>
<a href="registro_equipos.php" class="btn-nuevo">Nuevo Equipo</a>