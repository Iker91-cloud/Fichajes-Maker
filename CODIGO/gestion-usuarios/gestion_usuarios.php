<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado y tiene el rol adecuado
if (!isset($_SESSION['username']) || !isset($_SESSION['Rol_ID']) || !in_array($_SESSION['Rol_ID'], [3, 2])) {
    header('Location: ../login.php');
    exit;
}

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

$mensaje = '';
$resultados = [];

// Manejar el formulario de búsqueda
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_query'])) {
    $searchQuery = $_POST['search_query'];
    
    // Preparar y ejecutar la consulta de búsqueda
    $stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE Nombre LIKE ? OR Email LIKE ? OR usuario LIKE ?");
    $stmt->execute(["%$searchQuery%", "%$searchQuery%", "%$searchQuery%"]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <style>
        /* Estilos básicos del cuerpo */
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
        /* Estilos del contenido */
        .content {
            margin-left: 240px;
            padding: 20px;
            width: calc(100% - 240px);
            overflow-y: auto;
        }
        /* Estilos del encabezado */
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
        /* Estilos del contenido principal */
        .main-content {
            padding: 15px;
            background: #fff;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            text-align: center;
        }
        /* Estilos del contenedor de botones */
        .button-container {
            margin-top: 20px;
        }
        .button-container a {
            padding: 10px 20px;
            background-color: #0056b3;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
            display: inline-block;
            margin: 0 10px;
        }
        .button-container a:hover {
            background-color: #004494;
        }
        /* Estilos del formulario de búsqueda */
        .search-form {
            margin-top: 20px;
            text-align: center;
        }
        .search-form input[type="text"] {
            padding: 10px;
            width: 60%;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .search-form input[type="submit"] {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .search-form input[type="submit"]:hover {
            background-color: #0056b3;
        }
        /* Estilos de la tabla de resultados */
        .results-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .results-table th,
        .results-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .results-table th {
            background-color: #f4f4f4;
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
            <h1>Gestión de Usuarios</h1>
        </header>
        <div class="main-content">
            <h2>Seleccione una acción</h2>
            <div class="button-container">
                <a href="anadir_usuario.php">Añadir Usuario</a>
                <a href="eliminar_usuario.php">Eliminar Usuario</a>
                <a href="modificar_usuario.php">Modificar Usuario</a>
            </div>
            <div class="search-form">
                <form method="POST" action="">
                    <input type="text" name="search_query" placeholder="Buscar por nombre, email o usuario" required>
                    <input type="submit" value="Buscar">
                </form>
            </div>
            <?php if (!empty($resultados)): ?>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultados as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                                <td><?= htmlspecialchars($usuario['Nombre']) ?></td>
                                <td><?= htmlspecialchars($usuario['Email']) ?></td>
                                <td><a href="ver_usuario.php?id=<?= $usuario['ID'] ?>">Ver Detalles</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($_SERVER['REQUEST_METHOD'] == 'POST'): ?>
                <p>No se encontraron resultados.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>