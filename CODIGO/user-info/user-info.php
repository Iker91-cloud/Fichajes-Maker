<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username']) || !isset($_SESSION['db_name']) || !isset($_SESSION['db_user']) || !isset($_SESSION['db_pass']) || !isset($_SESSION['db_host'])) {
    header('Location: ../login.php');
    exit;
}

// Define las credenciales de la base de datos
$dbhost = $_SESSION['db_host'];
$dbuser = $_SESSION['db_user'];
$dbpass = $_SESSION['db_pass'];
$dbname = $_SESSION['db_name'];

// Crear una nueva instancia de PDO
try {
    $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
} catch (PDOException $e) {
    die("No se pudo conectar a la base de datos $dbname: " . $e->getMessage());
}

// Obtener la información del usuario
$stmt = $pdo->prepare("SELECT * FROM Usuarios WHERE ID = ?");
$stmt->execute([$_SESSION['user_id']]);
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

// Manejar la actualización de los datos del usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $nombre = $_POST['nombre'];
    $email = $_POST['email'];
    $usuario = $_POST['usuario'];
    
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $updateStmt = $pdo->prepare("UPDATE Usuarios SET Nombre = ?, Email = ?, usuario = ? WHERE ID = ?");
        $updateStmt->execute([$nombre, $email, $usuario, $_SESSION['user_id']]);
        $mensaje = "Datos actualizados con éxito.";
        
        // Actualizar la información en la sesión
        $_SESSION['username'] = $usuario;
        $_SESSION['nombre'] = $nombre;
        
        // Refrescar la página para mostrar los cambios
        header('Location: user-info.php?mensaje=' . urlencode($mensaje));
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al actualizar los datos: " . $e->getMessage();
        header('Location: user-info.php?mensaje=' . urlencode($mensaje));
        exit;
    }
}

$mensaje = isset($_GET['mensaje']) ? htmlspecialchars($_GET['mensaje']) : '';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tu información</title>
    <style>
        /* Estilos del cuerpo */
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
        }
        header {
            background: #333;
            color: #fff;
            padding: 10px 0;
            text-align: center;
        }
        header h1 {
            margin: 0;
            font-size: 32px; /* Aumenta el tamaño de la fuente */
        }
        .main-content {
            padding: 15px;
            background: #fff;
            margin-top: 15px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
        }
        .message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
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
        .user-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .user-actions a {
            padding: 10px;
            background-color: #0056b3;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 4px;
            width: 45%;
        }
        .user-actions a:hover {
            background-color: #004494;
        }
        .back-link {
            display: block;
            margin-top: 15px;
            color: #0056b3;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        form input[type="text"],
        form input[type="email"] {
            padding: 10px;
            margin: 5px 0;
            width: calc(100% - 22px);
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #f9f9f9;
        }
        form input[type="submit"] {
            padding: 10px;
            background-color: #0056b3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        form input[type="submit"]:hover {
            background-color: #004494;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <a href="/CODIGO/dashboard.php"><img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon"></a>
        <a href="/CODIGO/user-info/user-info.php"><?= htmlspecialchars($_SESSION['nombre']); ?></a>
        <a href="/CODIGO/manual_uso.php">Manual de uso</a>
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
            <h1>Información del Usuario</h1>
        </header>
        <div class="main-content">
            <!-- Mostrar mensajes de éxito o error -->
            <?php if ($mensaje): ?>
                <div class="<?= strpos($mensaje, 'éxito') !== false ? 'message' : 'error' ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>
            <!-- Formulario para actualizar los datos del usuario -->
            <form method="POST" action="">
                <div class="user-info">
                    <label for="usuario">Usuario:</label>
                    <input type="text" name="usuario" id="usuario" value="<?= htmlspecialchars($userInfo['usuario']) ?>" required>
                </div>
                <div class="user-info">
                    <label for="nombre">Nombre:</label>
                    <input type="text" name="nombre" id="nombre" value="<?= htmlspecialchars($userInfo['Nombre']) ?>" required>
                </div>
                <div class="user-info">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" value="<?= htmlspecialchars($userInfo['Email']) ?>" required>
                </div>
                <div class="user-info">
                    <label>Rol:</label>
                    <p><?= htmlspecialchars($roleName) ?></p>
                </div>
                <div class="user-info">
                    <label>Departamento:</label>
                    <p><?= htmlspecialchars($deptName) ?></p>
                </div>
                <input type="submit" name="update" value="Actualizar Datos">
            </form>
            <!-- Acciones adicionales del usuario -->
            <div class="user-actions">
                <a href="/CODIGO/fichajes-anteriores/fichajes_anteriores.php">Ver Fichajes Anteriores</a>
                <a href="cambiar_contrasena.php">Cambiar Contraseña</a>
            </div>
            <a href="../dashboard.php" class="back-link">Volver al Inicio</a>
        </div>
    </div>
</body>
</html>
