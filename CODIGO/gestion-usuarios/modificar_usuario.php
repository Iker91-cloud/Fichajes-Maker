<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado y tiene el rol adecuado
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id']) || !isset($_SESSION['db_name']) || !in_array($_SESSION['Rol_ID'], [2, 3])) {
    header('Location: ../login.php');
    exit;
}

$db_host = $_SESSION['db_host'];
$db_username = $_SESSION['db_user'];
$db_password = $_SESSION['db_pass'];
$database = $_SESSION['db_name'];
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['Rol_ID'];
$department_id = isset($_SESSION['Department_ID']) ? $_SESSION['Department_ID'] : null;
$mensaje_confirmacion = "";

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$database;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener los usuarios según el rol del usuario logueado
    if ($role_id == 3) { // CEO_RRHH
        $sql = "SELECT Usuarios.ID, Usuarios.Nombre, Usuarios.usuario, Usuarios.Email, Departamentos.Nombre as Departamento FROM Usuarios LEFT JOIN Departamentos ON Usuarios.Departamento_ID = Departamentos.Departamento_ID ORDER BY Departamentos.Nombre, Usuarios.Nombre";
        $stmt = $pdo->prepare($sql);
    } elseif ($role_id == 2) { // JEFE_DEPT
        $sql = "SELECT Usuarios.ID, Usuarios.Nombre, Usuarios.usuario, Usuarios.Email, Departamentos.Nombre as Departamento FROM Usuarios LEFT JOIN Departamentos ON Usuarios.Departamento_ID = Departamentos.Departamento_ID WHERE Usuarios.Departamento_ID = :department_id ORDER BY Departamentos.Nombre, Usuarios.Nombre";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':department_id', $department_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener los departamentos
    $stmt = $pdo->prepare("SELECT Departamento_ID, Nombre FROM Departamentos");
    $stmt->execute();
    $departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Manejar la actualización de los datos del usuario
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
        $user_id_to_modify = $_POST['user_id'];
        $nuevo_nombre = $_POST['nombre'];
        $nuevo_usuario = $_POST['usuario'];
        $nuevo_email = $_POST['email'];
        $nuevo_departamento_id = $_POST['departamento_id'];

        // Validar datos
        if (empty($nuevo_nombre) || empty($nuevo_usuario) || empty($nuevo_email) || empty($nuevo_departamento_id)) {
            $mensaje_confirmacion = "Todos los campos son obligatorios.";
        } else {
            // Actualizar el usuario
            $stmt = $pdo->prepare("UPDATE Usuarios SET Nombre = :nombre, usuario = :usuario, Email = :email, Departamento_ID = :department_id WHERE ID = :user_id");
            $stmt->bindParam(':nombre', $nuevo_nombre, PDO::PARAM_STR);
            $stmt->bindParam(':usuario', $nuevo_usuario, PDO::PARAM_STR);
            $stmt->bindParam(':email', $nuevo_email, PDO::PARAM_STR);
            $stmt->bindParam(':department_id', $nuevo_departamento_id, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $user_id_to_modify, PDO::PARAM_INT);
            $stmt->execute();

            $mensaje_confirmacion = "Usuario actualizado con éxito.";
        }
    }

    // Obtener información del usuario seleccionado para la edición
    $usuario_seleccionado = null;
    if (isset($_GET['user_id'])) {
        $stmt = $pdo->prepare("SELECT ID, Nombre, usuario, Email, Departamento_ID FROM Usuarios WHERE ID = :user_id");
        $stmt->bindParam(':user_id', $_GET['user_id'], PDO::PARAM_INT);
        $stmt->execute();
        $usuario_seleccionado = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Usuario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
        }
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
            margin: 10px 0;
        }
        form label {
            display: block;
            margin: 5px 0;
        }
        form select, form input[type="text"], form input[type="email"], form input[type="submit"] {
            padding: 8px;
            margin: 5px 0;
            width: calc(100% - 20px);
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        form input[type="submit"] {
            background-color: #5cb85c;
            color: white;
            border: none;
            width: 100%;
        }
        form input[type="submit"]:hover {
            background-color: #4cae4c;
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
        img.icon {
            height: 60px;
        }
        .title {
            margin-bottom: 20px;
            font-size: 22px;
            color: #5cb85c;
            text-align: center;
        }
        .confirmation-message {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        .departamento-option {
            font-weight: bold;
            background-color: #e9ecef;
        }
    </style>
    <script>
        function loadUserData() {
            const userId = document.getElementById('user_id').value;
            window.location.href = `?user_id=${userId}`;
        }
    </script>
</head>
<body>
    <div class="sidebar">
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
            <h1>Modificar Usuario</h1>
        </header>
        <div class="main-content">
            <?php if ($mensaje_confirmacion): ?>
                <div class="confirmation-message">
                    <?= htmlspecialchars($mensaje_confirmacion); ?>
                </div>
            <?php endif; ?>
            <h2 class="title">Seleccione un usuario para modificar:</h2>
            <form method="GET" action="">
                <label for="user_id">Usuario:</label>
                <select name="user_id" id="user_id" required onchange="loadUserData()">
                    <option value="">Seleccione un usuario</option>
                    <?php 
                    $currentDept = '';
                    foreach ($usuarios as $usuario): 
                        if ($usuario['Departamento'] !== $currentDept):
                            if ($currentDept !== ''): ?>
                                </optgroup>
                            <?php endif; ?>
                            <optgroup label="<?= htmlspecialchars($usuario['Departamento']); ?>" class="departamento-option">
                            <?php $currentDept = $usuario['Departamento']; 
                        endif; ?>
                        <option value="<?= htmlspecialchars($usuario['ID']); ?>" <?= (isset($usuario_seleccionado) && $usuario_seleccionado['ID'] == $usuario['ID']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($usuario['Nombre']); ?> (<?= htmlspecialchars($usuario['usuario']); ?>)
                        </option>
                    <?php endforeach; ?>
                    </optgroup>
                </select>
            </form>
            <?php if ($usuario_seleccionado): ?>
                <form method="POST" action="">
                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($usuario_seleccionado['ID']); ?>">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario_seleccionado['Nombre']); ?>" required>
                    <br>
                    <label for="usuario">Nombre de Usuario:</label>
                    <input type="text" id="usuario" name="usuario" value="<?= htmlspecialchars($usuario_seleccionado['usuario']); ?>" required>
                    <br>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($usuario_seleccionado['Email']); ?>" required>
                    <br>
                    <label for="departamento_id">Nuevo Departamento:</label>
                    <select name="departamento_id" id="departamento_id" required>
                        <option value="">Seleccione un departamento</option>
                        <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?= htmlspecialchars($departamento['Departamento_ID']); ?>" <?= ($usuario_seleccionado['Departamento_ID'] == $departamento['Departamento_ID']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($departamento['Nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <br>
                    <input type="submit" value="Modificar Usuario">
                </form>
            <?php endif; ?>
            <a class="back-link" href="gestion_usuarios.php">Volver a Gestión de Usuarios</a>
        </div>
    </div>
</body>
</html>