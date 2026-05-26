<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado y tiene el rol adecuado (ADMIN)
if (!isset($_SESSION['username']) || $_SESSION['Rol_ID'] != 4) {
    header('Location: ../login.php');
    exit;
}

// Define las credenciales de la base de datos
$dbhost = $_SESSION['db_host'];
$dbuser = $_SESSION['db_user'];
$dbpass = $_SESSION['db_pass'];
$dbname = $_SESSION['db_name'];

// Crea una nueva instancia de PDO
try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("No se pudo conectar a la base de datos $dbname: " . $e->getMessage());
}

// Obtener todos los departamentos con sus usuarios
$stmt = $pdo->prepare("
    SELECT d.Nombre AS Departamento, u.Nombre AS Usuario
    FROM Departamentos d
    LEFT JOIN Usuarios u ON d.Departamento_ID = u.Departamento_ID
    ORDER BY d.Nombre, u.Nombre
");
$stmt->execute();
$departamentosUsuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar datos por departamentos
$departamentos = [];
foreach ($departamentosUsuarios as $row) {
    $departamentos[$row['Departamento']][] = $row['Usuario'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios por Departamento</title>
    <style>
        /* Estilos generales del cuerpo */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
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
        /* Estilos del contenido principal */
        .content {
            margin-left: 240px;
            padding: 20px;
            width: calc(100% - 240px);
        }
        header {
            background: #333;
            color: #fff;
            padding: 10px 0;
            text-align: center;
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
        .departamento {
            margin-bottom: 20px;
        }
        .departamento h2 {
            margin-bottom: 10px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 5px;
        }
        .usuario {
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <!-- Barra lateral con enlaces -->
        <a href="../dashboard.php"><img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon"></a>
        <a href="../user-info/user-info.php"><?= htmlspecialchars($_SESSION['nombre']); ?></a>
        <a href="../manual_uso.php">Manual de uso</a>
        <a href="../fichajes-anteriores/fichajes_anteriores.php">Ver fichajes anteriores</a>
        <?php if (in_array($_SESSION['Rol_ID'], [3, 2])): ?>
            <a href="../control-fichajes/control_fichajes.php">Control fichajes</a>
            <a href="../gestion-usuarios/gestion_usuarios.php">Gestión usuarios</a>
        <?php endif; ?>
        <?php if ($_SESSION['Rol_ID'] == 4): ?>
            <a href="../herramientas-admin/herramientas_admin.php">Herramientas Admin</a>
        <?php endif; ?>
        <a href="../dashboard.php?logout=true">SALIR</a>
    </div>
    <div class="content">
        <header>
            <h1>Usuarios por Departamento</h1>
        </header>
        <div class="main-content">
            <!-- Mostrar los usuarios por cada departamento -->
            <?php foreach ($departamentos as $departamento => $usuarios): ?>
                <div class="departamento">
                    <h2><?= htmlspecialchars($departamento) ?></h2>
                    <?php if (!empty($usuarios)): ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <div class="usuario"><?= htmlspecialchars($usuario) ?></div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="usuario">No hay usuarios en este departamento.</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
