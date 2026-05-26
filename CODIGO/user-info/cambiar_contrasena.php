<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Configurar PDO
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

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $contrasena_actual = $_POST['contrasena_actual'];
    $nueva_contrasena = $_POST['nueva_contrasena'];
    $user_id = $_SESSION['user_id'];

    // Validar nueva contraseña
    if (strlen($nueva_contrasena) < 8 || !preg_match('/[A-Z]/', $nueva_contrasena)) {
        $mensaje = 'La nueva contraseña debe tener al menos 8 caracteres y una letra mayúscula.';
    } else {
        // Obtener la contraseña actual del usuario
        $stmt = $pdo->prepare("SELECT Contraseña FROM Usuarios WHERE ID = ?");
        $stmt->execute([$user_id]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($contrasena_actual, $usuario['Contraseña'])) {
            // Hashear la nueva contraseña
            $hashedPassword = password_hash($nueva_contrasena, PASSWORD_DEFAULT);

            // Actualizar la contraseña en la base de datos
            $stmt = $pdo->prepare("UPDATE Usuarios SET Contraseña = ? WHERE ID = ?");
            $stmt->execute([$hashedPassword, $user_id]);

            $mensaje = 'Contraseña cambiada con éxito.';
        } else {
            $mensaje = 'La contraseña actual es incorrecta.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cambiar Contraseña</title>
    <style>
        /* Estilos generales del cuerpo */
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
            margin-left: 230px;
            padding: 20px;
            width: calc(100% - 230px);
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
        form {
            display: flex;
            flex-direction: column;
        }
        form label {
            margin: 5px 0;
        }
        form input[type="password"] {
            padding: 8px;
            margin: 5px 0;
            width: 100%;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        form input[type="submit"] {
            background-color: #5cb85c;
            color: white;
            border: none;
            cursor: pointer;
            width: 50%;
            align-self: center;
            padding: 10px;
            margin-top: 10px;
        }
        form input[type="submit"]:hover {
            background-color: #4cae4c;
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
        .back-link {
            color: #0056b3;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            display: inline-block;
            background-color: white;
            padding: 10px 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            text-align: center;
            margin-top: 20px;
        }
        .back-link:hover {
            background-color: #e7e7e7;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <!-- Barra lateral con enlaces -->
        <a href="/CODIGO/dashboard.php"><img src="/CODIGO/imagenes/icono-maker.png" alt="Maker Icon"></a>
        <a href="/CODIGO/user-info/user-info.php"><?= htmlspecialchars($_SESSION['nombre']); ?></a>
        <a href="/CODIGO/manual_uso.php">Manual de uso</a>
        <a href="/CODIGO/fichajes-anteriores/fichajes_anteriores.php">Ver fichajes anteriores</a>
        <?php if (in_array($_SESSION['Rol_ID'], [3, 2])): ?>
            <a href="/CODIGO/control-fichajes/control_fichajes.php">Control fichajes</a>
            <a href="/CODIGO/gestion-usuarios/gestion_usuarios.php">Gestión usuarios</a>
        <?php endif; ?>
        <a href="/CODIGO/dashboard.php?logout=true">SALIR</a>
        <?php if ($_SESSION['Rol_ID'] == 4): ?>
            <a href="/CODIGO/herramientas-admin/herramientas-admin.php">Herramientas Admin</a>
        <?php endif; ?>
    </div>
    <div class="content">
        <header>
            <h1>Cambiar Contraseña</h1>
        </header>
        <div class="main-content">
            <!-- Mostrar mensajes de éxito o error -->
            <?php if ($mensaje): ?>
                <div class="<?= strpos($mensaje, 'éxito') !== false ? 'message' : 'error' ?>">
                    <?= htmlspecialchars($mensaje) ?>
                </div>
            <?php endif; ?>
            <!-- Formulario para cambiar la contraseña -->
            <form method="POST" action="">
                <label for="contrasena_actual">Contraseña Actual:</label>
                <input type="password" name="contrasena_actual" id="contrasena_actual" required>

                <label for="nueva_contrasena">Nueva Contraseña:</label>
                <input type="password" name="nueva_contrasena" id="nueva_contrasena" required>

                <input type="submit" value="Cambiar Contraseña">
            </form>
            <a class="back-link" href="../user-info/user-info.php">Volver a Información del Usuario</a>
        </div>
    </div>
</body>
</html>