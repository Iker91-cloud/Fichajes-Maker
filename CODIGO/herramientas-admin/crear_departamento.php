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

// Obtener los departamentos existentes
$stmt = $pdo->prepare("SELECT * FROM Departamentos");
$stmt->execute();
$departamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Manejar creación de departamentos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("INSERT INTO Departamentos (Nombre, Descripcion) VALUES (?, ?)");
        $stmt->execute([$nombre, $descripcion]);
        $mensaje = "Departamento creado con éxito.";
        header('Location: crear_departamento.php');
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al crear el departamento: " . $e->getMessage();
    }
}

// Manejar modificación de departamentos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    $departamento_id = $_POST['departamento_id'];
    $nuevo_nombre = $_POST['nuevo_nombre'];
    $nueva_descripcion = $_POST['nueva_descripcion'];
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $pdo->prepare("UPDATE Departamentos SET Nombre = ?, Descripcion = ? WHERE Departamento_ID = ?");
        $stmt->execute([$nuevo_nombre, $nueva_descripcion, $departamento_id]);
        $mensaje = "Departamento modificado con éxito.";
        header('Location: crear_departamento.php');
        exit;
    } catch (PDOException $e) {
        $mensaje = "Error al modificar el departamento: " . $e->getMessage();
    }
}

// Manejar eliminación de departamentos
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $departamento_id = $_POST['departamento_id'];
    try {
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Verificar si hay usuarios en el departamento
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Usuarios WHERE Departamento_ID = ?");
        $stmt->execute([$departamento_id]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $mensaje = "No tiene que existir ningún usuario en el departamento para ser borrado.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM Departamentos WHERE Departamento_ID = ?");
            $stmt->execute([$departamento_id]);
            $mensaje = "Departamento eliminado con éxito.";
            header('Location: crear_departamento.php');
            exit;
        }
    } catch (PDOException $e) {
        $mensaje = "Error al eliminar el departamento: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Departamentos</title>
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
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ddd;
        }
        .section h2 {
            margin-bottom: 15px;
        }
        .section form {
            display: flex;
            flex-direction: column;
        }
        .section label {
            margin-bottom: 5px;
        }
        .section input[type="text"],
        .section input[type="submit"],
        .section textarea,
        .section select {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }
        .section input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .section input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .section .error {
            color: red;
            margin-top: -10px;
            margin-bottom: 10px;
        }
        .view-users-btn {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        .view-users-btn:hover {
            background-color: #0056b3;
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
            <h1>Gestión de Departamentos</h1>
        </header>
        <div class="main-content">
            <?php if (isset($mensaje)): ?>
                <p><?= htmlspecialchars($mensaje) ?></p>
            <?php endif; ?>

            <a class="view-users-btn" href="usuarios_departamentos.php">Ver Usuarios por Departamento</a>

            <div class="section">
                <h2>Crear Departamento</h2>
                <form method="POST" action="">
                    <label for="nombre">Nombre:</label>
                    <input type="text" name="nombre" id="nombre" required>
                    <label for="descripcion">Descripción:</label>
                    <textarea name="descripcion" id="descripcion" rows="4" required></textarea>
                    <input type="submit" name="create" value="Crear">
                </form>
            </div>

            <div class="section">
                <h2>Modificar Departamento</h2>
                <form method="POST" action="">
                    <label for="departamento_id">Seleccionar Departamento:</label>
                    <select name="departamento_id" id="departamento_id" required>
                        <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?= $departamento['Departamento_ID'] ?>"><?= htmlspecialchars($departamento['Nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label for="nuevo_nombre">Nuevo Nombre:</label>
                    <input type="text" name="nuevo_nombre" id="nuevo_nombre" required>
                    <label for="nueva_descripcion">Nueva Descripción:</label>
                    <textarea name="nueva_descripcion" id="nueva_descripcion" rows="4" required></textarea>
                    <input type="submit" name="update" value="Modificar">
                </form>
            </div>

            <div class="section">
                <h2>Eliminar Departamento</h2>
                <form method="POST" action="">
                    <label for="departamento_id">Seleccionar Departamento:</label>
                    <select name="departamento_id" id="departamento_id" required>
                        <?php foreach ($departamentos as $departamento): ?>
                            <option value="<?= $departamento['Departamento_ID'] ?>"><?= htmlspecialchars($departamento['Nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="submit" name="delete" value="Eliminar">
                </form>
            </div>
        </div>
    </div>
</body>
</html>