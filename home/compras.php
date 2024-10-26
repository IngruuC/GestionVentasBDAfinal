<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 3) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

function getCompras($pdo, $cliente_id) {
    $sql = "SELECT v.id, p.nombre as producto, v.cantidad, 
            (v.cantidad * p.precio) as total, v.fecha_venta,
            s.nombre as sucursal
            FROM ventas v
            JOIN productos p ON v.producto_id = p.id
            JOIN sucursales s ON v.sucursal_id = s.id
            WHERE v.cliente_id = ?
            ORDER BY v.fecha_venta DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$cliente_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$compras = getCompras($pdo, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Compras</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .compra-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }
        
        .compra-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .compra-fecha {
            color: #666;
        }
        
        .compra-total {
            font-size: 1.2em;
            font-weight: bold;
            color: #2ecc71;
        }
        
        .compra-detalle {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .sucursal-badge {
            background: #3498db;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }
        
        .sin-compras {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Mi Cuenta</h2>
        <a href="dashboardUser.php">Inicio</a>
        <a href="perfil.php">Mi Perfil</a>
        <a href="compras.php">Mis Compras</a>
        <a href="favoritos.php">Favoritos</a>
        <a href="../InicioSesion/CerrarSesion.php">Cerrar sesión</a>
    </div>

    <div class="main">
        <h1>Mis Compras</h1>

        <?php if (empty($compras)): ?>
            <div class="sin-compras">
                <h2>🛍️ Aún no tienes compras</h2>
                <p>¡Empieza a comprar y verás tu historial aquí!</p>
            </div>
        <?php else: ?>
            <?php foreach ($compras as $compra): ?>
                <div class="compra-card">
                    <div class="compra-header">
                        <div class="compra-fecha">
                            <?= date('d/m/Y', strtotime($compra['fecha_venta'])) ?>
                        </div>
                        <div class="compra-total">
                            $<?= number_format($compra['total'], 2) ?>
                        </div>
                    </div>
                    <div class="compra-detalle">
                        <div>
                            <h3><?= htmlspecialchars($compra['producto']) ?></h3>
                            <p>Cantidad: <?= $compra['cantidad'] ?></p>
                        </div>
                        <span class="sucursal-badge">
                            <?= htmlspecialchars($compra['sucursal']) ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>