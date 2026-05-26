<?php
// Configuración de la base de datos
$dbConfig = [
    'servername' => 'localhost',
    'username' => 'u490318305_webuser_xhin',
    'password' => 'Mrw852456.',
    'dbname' => 'u490318305_fichajes_xhin'
];



if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['nombre'])) {
        $nombre = $_POST['nombre'];
        $email = $_POST['email'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $rol = $_POST['rol'];
        $departamento = $_POST['departamento'];

        // Hashear la contraseña
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Conectar a la base de datos
        $dsn = "mysql:host={$dbConfig['servername']};dbname={$dbConfig['dbname']};charset=utf8";
        $db_username = $dbConfig['username'];
        $db_password = $dbConfig['password'];

        try {
            $pdo = new PDO($dsn, $db_username, $db_password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Insertar el nuevo usuario en la base de datos
            $stmt = $pdo->prepare("INSERT INTO Usuarios (Nombre, Email, usuario, Contraseña, Rol_ID, Departamento_ID) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $username, $hashedPassword, $rol, $departamento]);

            echo "Usuario añadido correctamente.";
        } catch (PDOException $e) {
            die("Error de conexión o consulta: " . $e->getMessage());
        }
    }
} else {
    $dsn = "mysql:host={$dbConfig['servername']};dbname={$dbConfig['dbname']};charset=utf8";
    $db_username = $dbConfig['username'];
    $db_password = $dbConfig['password'];

    try {
        $pdo = new PDO($dsn, $db_username, $db_password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fetch departments
        $stmt = $pdo->prepare("SELECT Departamento_ID, Nombre FROM Departamentos");
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch roles
        $stmt = $pdo->prepare("SELECT Rol_ID, Nombre FROM Roles");
        $stmt->execute();
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        die("Error de conexión o consulta: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Añadir usuario</title>
</head>
<body>
    <form method="POST" action="">
        <label for="nombre">Nombre:</label><br>
        <input type="text" id="nombre" name="nombre"><br>
        <label for="email">Email:</label><br>
        <input type="email" id="email" name="email"><br>
        <label for="username">Nombre de usuario:</label><br>
        <input type="text" id="username" name="username"><br>
        <label for="password">Contraseña:</label><br>
        <input type="password" id="password" name="password"><br>
        <label for="departamento">Departamento:</label><br>
        <select id="departamento" name="departamento">
            <?php foreach ($departments as $department): ?>
                <option value="<?= htmlspecialchars($department['Departamento_ID'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($department['Nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select><br>
        <label for="rol">Rol:</label><br>
        <select id="rol" name="rol">
            <?php foreach ($roles as $role): ?>
                <option value="<?= htmlspecialchars($role['Rol_ID'], ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($role['Nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select><br>
        <input type="submit" value="Añadir usuario">
    </form>
</body>
</html>