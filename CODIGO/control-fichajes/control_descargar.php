<?php
require_once '../session_config.php';

// Verificar si el usuario está logueado y tiene el rol adecuado
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id']) || !isset($_SESSION['db_name']) || !in_array($_SESSION['Rol_ID'], [2, 3])) {
    header('Location: ../login.php');
    exit;
}

// Configuración de la base de datos
$db_host = $_SESSION['db_host'];
$db_username = $_SESSION['db_user'];
$db_password = $_SESSION['db_pass'];
$database = $_SESSION['db_name'];

require '../../vendor/autoload.php'; // Cargar el autoload de Composer
use Dompdf\Dompdf;

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$db_host;dbname=$database;charset=utf8", $db_username, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener el user_id del usuario seleccionado
    if (!isset($_GET['user_id'])) {
        throw new Exception("No se ha proporcionado un ID de usuario.");
    }
    $selected_user_id = intval($_GET['user_id']);

    // Obtener el nombre del usuario seleccionado
    $stmt_user = $pdo->prepare("SELECT Nombre FROM Usuarios WHERE ID = :user_id");
    $stmt_user->execute(['user_id' => $selected_user_id]);
    $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    $user_name = $user['Nombre'];

    // Construir la consulta con los filtros
    $sql = "SELECT Fecha_Hora_Entrada, Fecha_Hora_Salida, Tipo, Nota FROM RegistrosDeTiempo WHERE Usuario_ID = :user_id";
    $params = ['user_id' => $selected_user_id];

    if (isset($_GET['tipo']) && $_GET['tipo'] != '') {
        $sql .= " AND Tipo = :tipo";
        $params['tipo'] = $_GET['tipo'];
    }

    if (isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] != '' && isset($_GET['fecha_fin']) && $_GET['fecha_fin'] != '') {
        $sql .= " AND (DATE(Fecha_Hora_Entrada) BETWEEN :fecha_inicio AND :fecha_fin OR DATE(Fecha_Hora_Salida) BETWEEN :fecha_inicio AND :fecha_fin)";
        $params['fecha_inicio'] = $_GET['fecha_inicio'];
        $params['fecha_fin'] = $_GET['fecha_fin'];
    } elseif (isset($_GET['fecha_inicio']) && $_GET['fecha_inicio'] != '') {
        $sql .= " AND (DATE(Fecha_Hora_Entrada) >= :fecha_inicio OR DATE(Fecha_Hora_Salida) >= :fecha_inicio)";
        $params['fecha_inicio'] = $_GET['fecha_inicio'];
    } elseif (isset($_GET['fecha_fin']) && $_GET['fecha_fin'] != '') {
        $sql .= " AND (DATE(Fecha_Hora_Entrada) <= :fecha_fin OR DATE(Fecha_Hora_Salida) <= :fecha_fin)";
        $params['fecha_fin'] = $_GET['fecha_fin'];
    }

    $sql .= " ORDER BY Fecha_Hora_Entrada DESC";
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => &$value) {
        $stmt->bindParam(":$key", $value);
    }
    $stmt->execute();
    $fichajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $format = isset($_GET['format']) ? $_GET['format'] : 'csv';

    switch ($format) {
        case 'csv':
            // Generar CSV
            $filename = "fichajes_" . $user_name . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=' . $filename);

            $output = fopen('php://output', 'w');
            fputcsv($output, array('Fecha y Hora de Entrada', 'Fecha y Hora de Salida', 'Tipo', 'Nota'));

            foreach ($fichajes as $fichaje) {
                fputcsv($output, $fichaje);
            }

            fclose($output);
            break;

        case 'excel':
            // Generar Excel
            $filename = "fichajes_" . $user_name . ".xls";
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename=' . $filename);
            header('Cache-Control: max-age=0');

            echo '<table border="1">';
            echo '<tr><th>Fecha y Hora de Entrada</th><th>Fecha y Hora de Salida</th><th>Tipo</th><th>Nota</th></tr>';
            foreach ($fichajes as $fichaje) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($fichaje['Fecha_Hora_Entrada']) . '</td>';
                echo '<td>' . htmlspecialchars($fichaje['Fecha_Hora_Salida'] ?: 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($fichaje['Tipo']) . '</td>';
                echo '<td>' . htmlspecialchars($fichaje['Nota']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            break;

        case 'pdf':
            // Generar PDF
            $filename = "fichajes_" . $user_name . ".pdf";
            $dompdf = new Dompdf();

            $html = '
            <style>
                body { font-family: Arial, sans-serif; }
                h1 { text-align: center; color: #333; }
                table { width: 100%; border-collapse: collapse; }
                th, td { padding: 8px 12px; border: 1px solid #ddd; text-align: left; }
                th { background-color: #f4f4f4; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                tr:hover { background-color: #f1f1f1; }
            </style>
            <h1>Fichajes de ' . htmlspecialchars($user_name) . '</h1>
            <table>
                <thead>
                    <tr>
                        <th>Fecha y Hora de Entrada</th>
                        <th>Fecha y Hora de Salida</th>
                        <th>Tipo</th>
                        <th>Nota</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($fichajes as $fichaje) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($fichaje['Fecha_Hora_Entrada']) . '</td>';
                $html .= '<td>' . htmlspecialchars($fichaje['Fecha_Hora_Salida'] ?: 'N/A') . '</td>';
                $html .= '<td>' . htmlspecialchars($fichaje['Tipo']) . '</td>';
                $html .= '<td>' . htmlspecialchars($fichaje['Nota']) . '</td>';
                $html .= '</tr>';
            }

            $html .= '
                </tbody>
            </table>';

            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();
            $dompdf->stream($filename, array("Attachment" => 1));

            break;
    }
    exit;
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
