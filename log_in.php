<?php
require_once __DIR__ . '/includes/conexion.php';

// Redirigir si ya está logueado
if (isset($_SESSION['usuario_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = limpiarInput($_POST['username']);
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || $password === '') {
        $error = 'Por favor, complete todos los campos.';
    } else {
        $stmt = $pdo->prepare("SELECT id, password_hash, nombre_completo, rol, federacion_id FROM usuarios WHERE username = :username AND activo = 1");
        $stmt->execute([':username' => $username]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            // Guardar datos en sesión
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre_completo'];
            $_SESSION['usuario_rol'] = $usuario['rol'];
            $_SESSION['usuario_federacion_id'] = $usuario['federacion_id'] ?? null; 
            
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
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: linear-gradient(135deg, #0a110e 0%, #0d1a15 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background: #050a08;
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 20px 25px rgba(0, 255, 136, 0.1);
            border: 1px solid #1a2e25;
            width: 100%;
            max-width: 400px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            color: #00ff88;
        }

        h2 {
            text-align: center;
            color: #94a3b8;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #1a2e25;
            border-radius: 8px;
            box-sizing: border-box;
            background: #0d1a15;
            color: #ffffff;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #00ff88;
            color: #050a08;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            margin-top: 1rem;
            transition: background 0.2s;
        }

        button:hover {
            background: rgba(0, 255, 136, 0.8);
        }

        .error {
            background: rgba(255, 68, 68, 0.1);
            color: #ff4444;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
            border: 1px solid #ff4444;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo"> Federación de Fútbol</div>
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
    </div>
</body>
</html>