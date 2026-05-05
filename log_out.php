<?php
require_once 'includes/conexion.php';

// Destruir la sesión
session_destroy();

// Redirigir al login
header('Location: log_in.php');
exit;
?>