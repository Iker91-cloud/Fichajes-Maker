<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado y tiene el rol adecuado (ADMIN)
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id']) || $_SESSION['Rol_ID'] != 4) {
    header('Location: ../login.php');
    exit;
}

// Configurar la conexión PDO
try {
    $pdo = new PDO("mysql:host={$_SESSION['db_host']};dbname={$_SESSION['db_name']}", $_SESSION['db_user'], $_SESSION['db_pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

$mensaje = '';

// Obtener todos los usuarios y sus departamentos
$stmt = $pdo->prepare("SELECT Usuarios.ID, Usuarios.usuario, Usuarios.Nombre, Departamentos.Nombre as Departamento FROM Usuarios LEFT JOIN Departamentos ON Usuarios.Departamento_ID = Departamentos.Departamento_ID ORDER BY Departamentos.Nombre, Usuarios.Nombre");
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los roles
$rolesStmt = $pdo->prepare("SELECT * FROM Roles");
$rolesStmt->execute();
$roles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);

// Manejar el formulario de reasignación de roles
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['usuario_id'], $_POST['rol_id'], $_POST['confirmar'])) {
    $usuario_id = $_POST['usuario_id'];
    $rol_id = $_POST['rol_id'];

    try {
        $stmt = $pdo->prepare("UPDATE Usuarios SET Rol_ID = ? WHERE ID = ?");
        $stmt->execute([$rol_id, $usuario_id]);
        $mensaje = 'Rol reasignado con éxito.';
    } catch (PDOException $e) {
        $mensaje = 'Error al reasignar el rol: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reasignar Roles</title>
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
            text-align: center;
        }
        form {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        form select, form input[type="submit"], form label {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
            width: 80%;
        }
        form input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        form input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .mensaje {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            background-color: #d4edda;
            color: #155724;
        }
        .departamento-option {
            font-weight: bold;
            background-color: #e9ecef;
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
            <h1>Reasignar Roles</h1>
        </header>
        <div class="main-content">
            <!-- Mostrar mensaje de confirmación -->
            <?php if ($mensaje): ?>
                <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <!-- Formulario de reasignación de roles -->
            <form method="POST" action="">
                <label for="usuario_id">Seleccionar Usuario:</label>
                <select name="usuario_id" id="usuario_id" required>
                    <?php
                    $currentDept = null;
                    foreach ($usuarios as $usuario) {
                        if ($currentDept !== $usuario['Departamento']) {
                            if ($currentDept !== null) {
                                echo '</optgroup>';
                            }
                            $currentDept = $usuario['Departamento'];
                            echo '<optgroup label="' . htmlspecialchars($currentDept) . '">';
                        }
                        echo '<option value="' . htmlspecialchars($usuario['ID']) . '">'
                            . htmlspecialchars($usuario['usuario']) . ' - ' . htmlspecialchars($usuario['Nombre'])
                            . '</option>';
                    }
                    if ($currentDept !== null) {
                        echo '</optgroup>';
                    }
                    ?>
                </select>

                <label for="rol_id">Seleccionar Nuevo Rol:</label>
                <select name="rol_id" id="rol_id" required>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?= htmlspecialchars($rol['Rol_ID']) ?>">
                            <?= htmlspecialchars($rol['Nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label>
                    <input type="checkbox" name="confirmar" required>
                    Estoy seguro: Reasignar roles significará el cambio en los permisos y funcionalidades a las que tiene acceso ese usuario.
                </label>

                <input type="submit" value="Reasignar Rol">
            </form>
        </div>
    </div>
</body>
</html>
