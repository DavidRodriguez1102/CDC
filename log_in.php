<?php
require_once 'includes/conexion.php';

function limpiarInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Redirigir si ya está logueado
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = limpiarInput($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Por favor, complete todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash, nombre_completo, rol FROM usuarios WHERE username = :username AND activo = 1");
        $stmt->execute([':username' => $username]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            // Guardar datos en sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Portal - Federación de Fútbol</title>
    <style>
        /* Tus estilos de la página de login */
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: linear-gradient(135deg, #1a365d 0%, #2c5282 100%); display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-container { background: white; padding: 2.5rem; border-radius: 16px; box-shadow: 0 20px 25px rgba(0,0,0,0.2); width: 100%; max-width: 400px; }
        .logo { text-align: center; margin-bottom: 2rem; font-size: 2rem; color: #1a365d; }
        h2 { text-align: center; color: #4a5568; font-weight: 500; margin-bottom: 1.5rem; }
        input[type="text"], input[type="password"] { width: 100%; padding: 12px; margin: 8px 0; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 12px; background: #2b6cb0; color: white; border: none; border-radius: 8px; font-size: 1rem; font-weight: bold; cursor: pointer; margin-top: 1rem; transition: background 0.2s; }
        button:hover { background: #2c5282; }
        .error { background: #fed7d7; color: #c53030; padding: 10px; border-radius: 8px; margin-bottom: 1rem; text-align: center; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">⚽ Federación de Fútbol</div>
        <h2>Sistema de Gestión Profesional</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="username">ID de Usuario</label>
            <input type="text" id="username" name="username" placeholder="Entidad del usuario" required>
            
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            
            <button type="submit">Iniciar Sesión</button>
        </form>
        <p style="text-align:center; margin-top: 1rem; font-size:0.9rem; color:#a0aec0;">¿Olvidó su contraseña?</p>
    </div>
</body>
</html>