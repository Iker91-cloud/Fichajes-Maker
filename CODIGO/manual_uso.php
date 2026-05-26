<?php
require_once 'session_config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username']) || !isset($_SESSION['db_name']) || !isset($_SESSION['db_user']) || !isset($_SESSION['db_pass']) || !isset($_SESSION['db_host'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual de Uso</title>
    <style>
        /* Estilos del cuerpo */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        /* Estilos de la barra lateral */
        .sidebar {
            height: 100%;
            width: 230px;
            position: fixed;
            top: 0;
            left: 0;
            background-color: #333;
            padding-top: 20px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
        }
        .sidebar a {
            padding: 10px 20px;
            text-align: center;
            font-size: 16px;
            color: white;
            display: block;
            text-decoration: none;
            margin: 5px 0;
            transition: background-color 0.3s;
        }
        .sidebar a:hover {
            background-color: #575757;
        }
        .sidebar img {
            width: 60%;
            margin: 0 auto 20px auto;
            display: block;
            border-radius: 50%;
        }
        .sidebar .links {
            flex-grow: 1;
        }
        /* Estilos del contenido principal */
        .content {
            margin-left: 240px;
            padding: 20px;
            width: calc(100% - 240px);
            overflow-y: auto;
        }
        header {
            background: #333;
            color: #fff;
            padding: 10px 0;
            text-align: center;
            position: relative;
        }
        header h1 {
            margin: 0;
            font-size: 32px;
        }
        .main-content {
            padding: 15px;
            background: #fff;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        iframe {
            width: 100%;
            height: calc(100vh - 100px); /* Ajusta esto según la altura de tu encabezado y otros elementos */
            border: none;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="/CODIGO/dashboard.php"><img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon"></a>
        <a href="/CODIGO/user-info/user-info.php"><?= htmlspecialchars($_SESSION['nombre']); ?></a>
        <a href="#">Manual de uso</a>
        <a href="/CODIGO/fichajes-anteriores/fichajes_anteriores.php">Ver fichajes anteriores</a>
        <?php if (in_array($_SESSION['Rol_ID'], [3, 2])): ?>
            <a href="/CODIGO/control-fichajes/control_fichajes.php">Control fichajes</a>
            <a href="/CODIGO/gestion-usuarios/gestion_usuarios.php">Gestión usuarios</a>
        <?php endif; ?>
        <?php if ($_SESSION['Rol_ID'] == 4): ?>
            <a href="/CODIGO/herramientas-admin/herramientas_admin.php">Herramientas Admin</a>
        <?php endif; ?>
        <a href="/CODIGO/dashboard.php?logout=true">SALIR</a>
    </div>
    <div class="content">
        <header>
            <h1>Manual de Uso</h1>
        </header>
        <div class="main-content">
            <iframe src="manual_de_uso.pdf"></iframe>
        </div>
    </div>
</body>
</html>
