<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 3) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

function getItemsCarrito($pdo, $usuario_id) {
    $sql = "SELECT c.id, c.producto_id, p.nombre, c.cantidad, p.precio, 
            (c.cantidad * p.precio) as subtotal, p.stock
            FROM carrito c
            JOIN productos p ON c.producto_id = p.id
            WHERE c.usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$items_carrito = getItemsCarrito($pdo, $_SESSION['user_id']);
$total_carrito = array_sum(array_column($items_carrito, 'subtotal'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_compra'])) {
    try {
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Verificar stock antes de procesar
        $error = false;
        foreach ($items_carrito as $item) {
            if ($item['cantidad'] > $item['stock']) {
                $error = true;
                $mensaje_error = "No hay suficiente stock de " . $item['nombre'];
                break;
            }
        }
        
        if (!$error) {
            // Crear la orden
            $sql = "INSERT INTO ordenes (usuario_id, total, estado) VALUES (?, ?, 'completada')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $total_carrito]);
            $orden_id = $pdo->lastInsertId();
            
            // Insertar detalles de la orden y actualizar stock
            foreach ($items_carrito as $item) {
                // Insertar detalle
                $sql = "INSERT INTO orden_detalle (orden_id, producto_id, cantidad, precio_unitario, subtotal) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    $orden_id, 
                    $item['producto_id'], 
                    $item['cantidad'], 
                    $item['precio'], 
                    $item['subtotal']
                ]);
                
                // Actualizar stock
                $sql = "UPDATE productos SET stock = stock - ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$item['cantidad'], $item['producto_id']]);
            }
            
            // Limpiar carrito
            $sql = "DELETE FROM carrito WHERE usuario_id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id']]);
            
            // Confirmar transacción
            $pdo->commit();
            
            $mensaje_exito = "¡Compra realizada con éxito! Tu número de orden es: " . $orden_id;
            header("refresh:3;url=dashboardUser.php");
        }
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        $pdo->rollBack();
        $mensaje_error = "Error al procesar la compra: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .checkout-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .checkout-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .item-list {
            margin: 20px 0;
        }
        
        .item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .total {
            font-size: 1.2em;
            font-weight: bold;
            text-align: right;
            padding: 20px 0;
        }
        
        .btn-confirmar {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 1.1em;
            cursor: pointer;
            width: 100%;
        }
        
        .btn-confirmar:hover {
            background: #27ae60;
        }
        
        .mensaje-exito {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
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
        <div class="checkout-container">
            <h1>Finalizar Compra</h1>

            <?php if (isset($mensaje_exito)): ?>
                <div class="mensaje-exito">
                    <?= $mensaje_exito ?>
                </div>
            <?php endif; ?>

            <?php if (isset($mensaje_error)): ?>
                <div class="mensaje-error">
                    <?= $mensaje_error ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($items_carrito) && !isset($mensaje_exito)): ?>
                <div class="checkout-card">
                    <h2>Resumen de tu compra</h2>
                    <div class="item-list">
                        <?php foreach ($items_carrito as $item): ?>
                            <div class="item">
                                <div>
                                    <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                    <br>
                                    <?= $item['cantidad'] ?> x $<?= number_format($item['precio'], 2) ?>
                                </div>
                                <div>
                                    $<?= number_format($item['subtotal'], 2) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="total">
                        Total a pagar: $<?= number_format($total_carrito, 2) ?>
                    </div>
                </div>

                <form method="POST">
                    <button type="submit" name="confirmar_compra" class="btn-confirmar">
                        Confirmar y Pagar 💳
                    </button>
                </form>
            <?php elseif (empty($items_carrito) && !isset($mensaje_exito)): ?>
                <div class="checkout-card">
                    <h2>Tu carrito está vacío</h2>
                    <p>No hay productos en tu carrito de compras.</p>
                    <a href="dashboardUser.php" class="btn-confirmar" style="display: inline-block; text-align: center; text-decoration: none; margin-top: 20px;">
                        Volver a la tienda
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>