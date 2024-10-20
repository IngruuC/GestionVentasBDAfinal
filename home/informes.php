<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

function getInformesRecientes($pdo) {
    // Simulación de informes recientes
    return [
        ['id' => 1, 'nombre' => 'Ventas Diarias - 2023-10-20', 'fecha' => '2023-10-21', 'tipo' => 'ventas'],
        ['id' => 2, 'nombre' => 'Estado del Inventario - Octubre 2023', 'fecha' => '2023-10-15', 'tipo' => 'inventario'],
        ['id' => 3, 'nombre' => 'Actividad de Clientes - Q3 2023', 'fecha' => '2023-10-01', 'tipo' => 'clientes'],
    ];
}

$informesRecientes = getInformesRecientes($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipoInforme = $_POST['tipo_informe'];
    $fechaInicio = $_POST['fecha_inicio'];
    $fechaFin = $_POST['fecha_fin'];
    
    // Aquí iría la lógica para generar el informe
    $mensajeExito = "Informe de $tipoInforme generado para el período del $fechaInicio al $fechaFin.";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informes</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .informe-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .informe-icon {
            font-size: 24px;
            margin-right: 15px;
        }
        .informe-info {
            flex-grow: 1;
        }
        .informe-actions {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
    <h2>Dashboard</h2>
    <a href="dashboard.php">Inicio</a>
    <a href="estadisticas.php">Estadísticas</a>
    <a href="informes.php">Informes</a>
    <a href="inventario.php">Inventario</a>
    <a href="configuracion.php">Configuración</a>
    <a href="../InicioSesion/CerrarSesion.php">Cerrar sesión</a>
    <a href="javascript:history.back()">Volver</a>
    </div>
    <div class="main">
        <h1>Generación de Informes</h1>
        
        <div class="grid-container">
            <div class="card">
                <h2>Generar Nuevo Informe</h2>
                <form action="" method="POST">
                    <select name="tipo_informe" required>
                        <option value="ventas_diarias">Ventas Diarias</option>
                        <option value="inventario">Estado del Inventario</option>
                        <option value="clientes">Actividad de Clientes</option>
                        <option value="productos">Rendimiento de Productos</option>
                    </select>
                    <input type="date" name="fecha_inicio" required>
                    <input type="date" name="fecha_fin" required>
                    <button type="submit">Generar Informe</button>
                </form>
                <?php if (isset($mensajeExito)): ?>
                    <p style="color: green;"><?= $mensajeExito ?></p>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <h2>Informes Recientes</h2>
                <?php foreach ($informesRecientes as $informe): ?>
                    <div class="informe-card">
                        <div class="informe-icon">
                            <?php
                            switch($informe['tipo']) {
                                case 'ventas':
                                    echo '📊';
                                    break;
                                case 'inventario':
                                    echo '📦';
                                    break;
                                case 'clientes':
                                    echo '👥';
                                    break;
                                default:
                                    echo '📄';
                            }
                            ?>
                        </div>
                        <div class="informe-info">
                            <h3><?= htmlspecialchars($informe['nombre']) ?></h3>
                            <p>Generado el: <?= htmlspecialchars($informe['fecha']) ?></p>
                        </div>
                        <div class="informe-actions">
                            <a href="#" class="btn">Descargar</a>
                            <a href="#" class="btn">Ver</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <h2>Programar Informes Automáticos</h2>
            <form action="" method="POST">
                <select name="informe_automatico" required>
                    <option value="ventas_semanales">Ventas Semanales</option>
                    <option value="inventario_mensual">Inventario Mensual</option>
                    <option value="clientes_trimestral">Actividad de Clientes Trimestral</option>
                </select>
                <select name="frecuencia" required>
                    <option value="semanal">Semanal</option>
                    <option value="mensual">Mensual</option>
                    <option value="trimestral">Trimestral</option>
                </select>
                <input type="email" name="email" placeholder="Email para recibir el informe" required>
                <button type="submit">Programar Informe</button>
            </form>
        </div>
    </div>
</body>
</html>