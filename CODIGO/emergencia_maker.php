<?php
// emergencia_maker.php
// Datos de conexión directos de tu Hostinger
$db_host = 'bd-fichajesmaker01.mysql.database.azure.com';
$db_name = 'fi_maker';
$db_user = 'adminmaker';
$db_pass = 'Maker12345';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $mensaje = "";

    // PROCESAR FORMULARIO
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $user_login = $_POST['usuario'];
        $pass_plano = $_POST['password'];
        $rol_id = $_POST['rol_id'];
        $depto_id = $_POST['departamento_id'];
        $nuevo_depto = trim($_POST['nuevo_departamento']);

        // 1. Crear departamento si es necesario
        if (!empty($nuevo_depto)) {
            $st = $pdo->prepare("INSERT INTO Departamentos (Nombre) VALUES (?)");
            $st->execute([$nuevo_depto]);
            $depto_id = $pdo->lastInsertId();
        }

        // 2. Hashear contraseña (IGUAL que el login)
        $pass_hash = password_hash($pass_plano, PASSWORD_DEFAULT);

        // 3. Insertar Usuario
        try {
            $sql = "INSERT INTO Usuarios (Nombre, Email, Contrasena, Rol_ID, usuario, Departamento_ID) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $email, $pass_hash, $rol_id, $user_login, $depto_id]);
            $mensaje = "<div style='color:green; font-weight:bold;'>✅ Usuario '$user_login' creado con éxito. Ya puedes ir al login.php</div>";
        } catch (Exception $e) {
            $mensaje = "<div style='color:red;'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }

    // Obtener Roles y Deptos para los select
    $roles = $pdo->query("SELECT * FROM Roles")->fetchAll(PDO::FETCH_ASSOC);
    $deptos = $pdo->query("SELECT * FROM Departamentos")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error crítico de conexión: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Emergencia: Crear Usuario</title>
    <style>
        body { font-family: sans-serif; background: #f0f0f0; display: flex; justify-content: center; padding: 50px; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); width: 100%; max-width: 450px; }
        input, select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box;}
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .aviso { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 13px; border: 1px solid #ffeeba; }
    </style>
</head>
<body>

<div class="card">
    <h2>🚀 Creador de Usuarios (Maker)</h2>
    <div class="aviso">⚠️ <strong>BORRA ESTE ARCHIVO</strong> de tu servidor en cuanto termines de usarlo.</div>
    
    <?= $mensaje ?>

    <form method="POST">
        <label>Nombre Real:</label>
        <input type="text" name="nombre" placeholder="Ej: Administrador" required>

        <label>Email:</label>
        <input type="email" name="email" placeholder="admin@maker.com" required>

        <label>Usuario (para el Login):</label>
        <input type="text" name="usuario" placeholder="admin_maker" required>

        <label>Contraseña:</label>
        <input type="password" name="password" placeholder="Escribe tu contraseña" required>

        <label>Rol:</label>
        <select name="rol_id">
            <?php foreach($roles as $r): ?>
                <option value="<?= $r['Rol_ID'] ?>"><?= $r['Nombre'] ?></option>
            <?php endforeach; ?>
        </select>

        <label>Departamento:</label>
        <select name="departamento_id">
            <option value="">-- Seleccionar existente --</option>
            <?php foreach($deptos as $d): ?>
                <option value="<?= $d['Departamento_ID'] ?>"><?= $d['Nombre'] ?></option>
            <?php endforeach; ?>
        </select>
        
        <input type="text" name="nuevo_departamento" placeholder="O escribe un nuevo departamento">

        <button type="submit">Crear Usuario e ir al Login</button>
    </form>
</div>

</body>
</html>