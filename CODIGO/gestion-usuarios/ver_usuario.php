<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado y tiene el rol adecuado
if (!isset($_SESSION['username']) || !isset($_SESSION['Rol_ID']) || !in_array($_SESSION['Rol_ID'], [3, 2])) {
    header('Location: ../login.php');
    exit;
}

// Verificar si se ha pasado un ID de usuario
if (!isset($_GET['id'])) {
    die("ID de usuario no especificado.");
}

$userId = $_GET['id'];

// Configurar PDO para la conexión a la base de datos
$db_host = $_SESSION['db_host'];
$db_username = $_SESSION['db_user'];
$db_password = $_SESSION['db_pass'];
$database = $_SESSION['db_name'];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$database;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Obtener la información del usuario
$stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE ID = ?");
$stmt->execute([$userId]);
$userInfo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$userInfo) {
    die("Usuario no encontrado.");
}

// Obtener el nombre del rol
$roleStmt = $pdo->prepare("SELECT Nombre FROM Roles WHERE Rol_ID = ?");
$roleStmt->execute([$userInfo['Rol_ID']]);
$roleName = $roleStmt->fetchColumn();

// Obtener el nombre del departamento
$deptName = 'No asignado';
if (!empty($userInfo['Departamento_ID'])) {
    $deptStmt = $pdo->prepare("SELECT Nombre FROM Departamentos WHERE Departamento_ID = ?");
    $deptStmt->execute([$userInfo['Departamento_ID']]);
    $deptName = $deptStmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalles del Usuario</title>
    <style>
        /* Estilos básicos del cuerpo */
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
        /* Estilos del contenido */
        .content {
            margin-left: 240px;
            padding: 20px;
            width: calc(100% - 240px);
        }
        /* Estilos del encabezado */
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
        /* Estilos del contenido principal */
        .main-content {
            padding: 15px;
            background: #fff;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        .user-info {
            margin-bottom: 15px;
        }
        .user-info label {
            font-weight: bold;
        }
        .user-info p {
            margin: 5px 0;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        /* Estilos del enlace para volver */
        .back-link {
            display: block;
            margin-top: 15px;
            color: #0056b3;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
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
        <a href="../control-fichajes/control_fichajes.php">Control fichajes</a>
        <a href="gestion_usuarios.php">Gestión usuarios</a>
        <a href="../dashboard.php?logout=true">SALIR</a>
    </div>
    <div class="content">
        <header>
            <h1>Detalles del Usuario</h1>
        </header>
        <div class="main-content">
            <div class="user-info">
                <label for="usuario">Usuario:</label>
                <p><?= htmlspecialchars($userInfo['usuario']) ?></p>
            </div>
            <div class="user-info">
                <label for="nombre">Nombre:</label>
                <p><?= htmlspecialchars($userInfo['Nombre']) ?></p>
            </div>
            <div class="user-info">
                <label for="email">Email:</label>
                <p><?= htmlspecialchars($userInfo['Email']) ?></p>
            </div>
            <div class="user-info">
                <label>Rol:</label>
                <p><?= htmlspecialchars($roleName) ?></p>
            </div>
            <div class="user-info">
                <label>Departamento:</label>
                <p><?= htmlspecialchars($deptName) ?></p>
            </div>
            <a href="gestion_usuarios.php" class="back-link">Volver a Gestión de Usuarios</a>
        </div>
    </div>
</body>
</html>