<?php
session_start();
require_once '../Config/Connection.php';

// Verificar si el usuario está autenticado y es proveedor
if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 2) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

// Funciones para obtener datos
function getProductosBajoStock($pdo) {
    $sql = "SELECT p.id, p.nombre, p.stock, p.precio 
            FROM productos p 
            WHERE p.stock < 10 
            ORDER BY p.stock ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getEntregasRecientes($pdo) {
    $sql = "SELECT p.nombre, ep.cantidad, ep.fecha_entrada 
            FROM entradas_productos ep 
            JOIN productos p ON ep.producto_id = p.id 
            ORDER BY ep.fecha_entrada DESC 
            LIMIT 5";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getProductosMasSolicitados($pdo) {
    $sql = "SELECT p.nombre, SUM(v.cantidad) as total_vendido 
            FROM productos p 
            JOIN ventas v ON p.id = v.producto_id 
            GROUP BY p.id 
            ORDER BY total_vendido DESC 
            LIMIT 5";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener datos
$productosBajoStock = getProductosBajoStock($pdo);
$entregasRecientes = getEntregasRecientes($pdo);
$productosMasSolicitados = getProductosMasSolicitados($pdo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Proveedor</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .alert-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stock-alert {
            border-left: 4px solid #e74c3c;
        }
        
        .delivery-card {
            border-left: 4px solid #2ecc71;
        }
        
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .status-critical { background-color: #e74c3c; }
        .status-warning { background-color: #f1c40f; }
        .status-good { background-color: #2ecc71; }
        
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .delivery-timeline {
            list-style: none;
            padding: 0;
        }
        
        .delivery-timeline li {
            padding: 10px;
            border-left: 2px solid #3498db;
            margin-bottom: 10px;
            position: relative;
        }
        
        .delivery-timeline li::before {
            content: '';
            width: 10px;
            height: 10px;
            background: #3498db;
            border-radius: 50%;
            position: absolute;
            left: -6px;
            top: 15px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Dashboard</h2>
        <a href="dashboardProveedor.php">Inicio</a>
        <a href="entregas.php">Gestión de Entregas</a>
        <a href="stock.php">Control de Stock</a>
        <a href="../InicioSesion/CerrarSesion.php">Cerrar sesión</a>
    </div>

    <div class="main">
        <h1>Dashboard de Proveedor</h1>

        <div class="stats-container">
            <!-- Alertas de Stock Bajo -->
            <div class="alert-card stock-alert">
                <h2>🚨 Alertas de Stock Bajo</h2>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($productosBajoStock as $producto): ?>
                        <div class="alert-item">
                            <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                            <p>Stock actual: 
                                <span class="status-indicator <?= 
                                    $producto['stock'] < 5 ? 'status-critical' : 
                                    ($producto['stock'] < 10 ? 'status-warning' : 'status-good') 
                                ?>"></span>
                                <?= $producto['stock'] ?> unidades
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Entregas Recientes -->
            <div class="alert-card delivery-card">
                <h2>📦 Entregas Recientes</h2>
                <ul class="delivery-timeline">
                    <?php foreach ($entregasRecientes as $entrega): ?>
                        <li>
                            <strong><?= htmlspecialchars($entrega['nombre']) ?></strong>
                            <br>
                            Cantidad: <?= $entrega['cantidad'] ?>
                            <br>
                            Fecha: <?= date('d/m/Y', strtotime($entrega['fecha_entrada'])) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Gráfico de Productos Más Solicitados -->
        <div class="card">
            <h2>📈 Productos Más Solicitados</h2>
            <canvas id="productosSolicitadosChart"></canvas>
        </div>
    </div>

    <script>
        // Gráfico de Productos Más Solicitados
        new Chart(document.getElementById('productosSolicitadosChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($productosMasSolicitados, 'nombre')) ?>,
                datasets: [{
                    label: 'Unidades Vendidas',
                    data: <?= json_encode(array_column($productosMasSolicitados, 'total_vendido')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Top 5 Productos Más Vendidos'
                    }
                }
            }
        });
    </script>
</body>
</html>