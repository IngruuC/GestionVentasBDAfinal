<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

function getVentasPorPeriodo($pdo) {
    $sql = "SELECT DATE(fecha_venta) as fecha, SUM(v.cantidad * p.precio) as total
            FROM ventas v
            JOIN productos p ON v.producto_id = p.id
            GROUP BY DATE(fecha_venta)
            ORDER BY fecha_venta
            LIMIT 30";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getTopProductos($pdo) {
    $sql = "SELECT p.nombre, SUM(v.cantidad) as cantidad
            FROM ventas v
            JOIN productos p ON v.producto_id = p.id
            GROUP BY p.id
            ORDER BY cantidad DESC
            LIMIT 5";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getComparacionSucursales($pdo) {
    $sql = "SELECT s.nombre, SUM(v.cantidad * p.precio) as total
            FROM sucursales s
            JOIN ventas v ON s.id = v.sucursal_id
            JOIN productos p ON v.producto_id = p.id
            GROUP BY s.id
            ORDER BY total DESC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getProductoMasVendido($pdo) {
    $sql = "SELECT p.nombre, SUM(v.cantidad) as total_vendido
            FROM productos p
            JOIN ventas v ON p.id = v.producto_id
            GROUP BY p.id
            ORDER BY total_vendido DESC
            LIMIT 1";
    return $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);
}

$ventasPorPeriodo = getVentasPorPeriodo($pdo);
$topProductos = getTopProductos($pdo);
$comparacionSucursales = getComparacionSucursales($pdo);
$productoMasVendido = getProductoMasVendido($pdo);

$totalVentas = array_sum(array_column($ventasPorPeriodo, 'total'));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas</title>
    <link rel="stylesheet" href="../css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .stat-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        .stat-icon {
            font-size: 48px;
            margin-right: 20px;
        }
        .stat-info h3 {
            margin: 0;
            font-size: 24px;
        }
        .stat-info p {
            margin: 5px 0 0;
            font-size: 16px;
            color: #666;
        }
        .product-spotlight {
            background-color: #f0f0f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .product-spotlight img {
            max-width: 200px;
            margin-bottom: 15px;
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
        <h1>Estadísticas del Supermercado</h1>
        
        <div class="grid-container">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3>Total de Ventas</h3>
                    <p>$<?= number_format($totalVentas, 2) ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <h3>Promedio Diario</h3>
                    <p>$<?= number_format($totalVentas / count($ventasPorPeriodo), 2) ?></p>
                </div>
            </div>
        </div>

        <div class="card">
            <h2>Ventas por Período</h2>
            <canvas id="ventasPorPeriodoChart"></canvas>
        </div>
        
        <div class="grid-container">
            <div class="card">
                <h2>Top 5 Productos</h2>
                <canvas id="topProductosChart"></canvas>
            </div>
            
            <div class="product-spotlight">
                <h2>Producto Más Vendido</h2>
                <img src="../img/product-best-seller.png" alt="Producto más vendido">
                <h3><?= htmlspecialchars($productoMasVendido['nombre']) ?></h3>
                <p>Unidades vendidas: <?= $productoMasVendido['total_vendido'] ?></p>
            </div>
        </div>
        
        <div class="card">
            <h2>Comparación de Sucursales</h2>
            <canvas id="comparacionSucursalesChart"></canvas>
        </div>

        <div class="card">
            <h2>Consejos para Aumentar las Ventas</h2>
            <ul>
                <li>Implementa promociones cruzadas con los productos más vendidos.</li>
                <li>Optimiza la disposición de los productos en las sucursales con menor rendimiento.</li>
                <li>Considera ampliar el inventario de los productos top en todas las sucursales.</li>
                <li>Analiza los patrones de venta para ajustar los horarios de personal.</li>
            </ul>
        </div>
    </div>

    <script>
        // Gráfico de Ventas por Período
        new Chart(document.getElementById('ventasPorPeriodoChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($ventasPorPeriodo, 'fecha')) ?>,
                datasets: [{
                    label: 'Ventas Diarias',
                    data: <?= json_encode(array_column($ventasPorPeriodo, 'total')) ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Top 5 Productos
        new Chart(document.getElementById('topProductosChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($topProductos, 'nombre')) ?>,
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: <?= json_encode(array_column($topProductos, 'cantidad')) ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de Comparación de Sucursales
        new Chart(document.getElementById('comparacionSucursalesChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($comparacionSucursales, 'nombre')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($comparacionSucursales, 'total')) ?>,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>