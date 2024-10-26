<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username']) || $_SESSION['role_id'] != 3) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

// Manejar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['agregar_carrito'])) {
        $producto_id = $_POST['producto_id'];
        $cantidad = $_POST['cantidad'] ?? 1;
        
        // Verificar si ya existe en el carrito
        $sql = "SELECT id, cantidad FROM carrito WHERE usuario_id = ? AND producto_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['user_id'], $producto_id]);
        $carrito_existente = $stmt->fetch();
        
        if ($carrito_existente) {
            // Actualizar cantidad
            $sql = "UPDATE carrito SET cantidad = cantidad + ? WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$cantidad, $carrito_existente['id']]);
        } else {
            // Insertar nuevo item
            $sql = "INSERT INTO carrito (usuario_id, producto_id, cantidad) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$_SESSION['user_id'], $producto_id, $cantidad]);
        }
        
        $mensaje = "Producto agregado al carrito exitosamente.";
    }
}

function getOfertasActivas($pdo) {
    $sql = "SELECT p.id, p.nombre, p.precio, p.stock, 
            CASE 
                WHEN p.stock < 10 THEN p.precio * 0.9
                WHEN p.stock < 20 THEN p.precio * 0.95
                ELSE p.precio
            END as precio_oferta,
            CASE
                WHEN p.stock < 10 THEN '10%'
                WHEN p.stock < 20 THEN '5%'
                ELSE '0%'
            END as descuento
            FROM productos p 
            WHERE p.stock > 0
            AND p.stock < 20  -- Solo productos con algún descuento
            ORDER BY p.stock ASC";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getProductosRecomendados($pdo) {
    $sql = "SELECT p.id, p.nombre, COUNT(v.id) as veces_vendido, p.precio, p.stock
            FROM productos p
            LEFT JOIN ventas v ON p.id = v.producto_id
            GROUP BY p.id
            ORDER BY veces_vendido DESC
            LIMIT 4";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getItemsCarrito($pdo, $usuario_id) {
    $sql = "SELECT c.id, p.nombre, c.cantidad, p.precio, (c.cantidad * p.precio) as subtotal
            FROM carrito c
            JOIN productos p ON c.producto_id = p.id
            WHERE c.usuario_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener datos
$ofertas = getOfertasActivas($pdo);
$recomendados = getProductosRecomendados($pdo);
$items_carrito = getItemsCarrito($pdo, $_SESSION['user_id']);
$total_carrito = array_sum(array_column($items_carrito, 'subtotal'));
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Usuario</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .ofertas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .oferta-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .descuento-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: bold;
        }
        
        .precio-original {
            text-decoration: line-through;
            color: #999;
            font-size: 0.9em;
        }
        
        .precio-oferta {
            color: #e74c3c;
            font-size: 1.4em;
            font-weight: bold;
            margin: 10px 0;
        }
        
        .stock-info {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 15px;
        }
        
        .btn-agregar {
            background: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-agregar:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }
        
        .carrito-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            max-width: 300px;
        }
        
        .carrito-total {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .mensaje {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .cantidad-input {
            width: 60px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
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
        <h1>Bienvenido, <?= htmlspecialchars($_SESSION['username']) ?></h1>

        <?php if (isset($mensaje)): ?>
            <div class="mensaje">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <!-- Ofertas Especiales -->
        <h2>🏷️ Ofertas Especiales</h2>
        <div class="ofertas-grid">
            <?php foreach ($ofertas as $oferta): ?>
                <div class="oferta-card">
                    <?php if ($oferta['descuento'] !== '0%'): ?>
                        <div class="descuento-badge">-<?= $oferta['descuento'] ?></div>
                    <?php endif; ?>
                    
                    <h3><?= htmlspecialchars($oferta['nombre']) ?></h3>
                    <div class="precio-original">$<?= number_format($oferta['precio'], 2) ?></div>
                    <div class="precio-oferta">$<?= number_format($oferta['precio_oferta'], 2) ?></div>
                    <div class="stock-info">Stock disponible: <?= $oferta['stock'] ?> unidades</div>
                    
                    <form method="POST" style="display: inline-flex; align-items: center; justify-content: center;">
                        <input type="hidden" name="producto_id" value="<?= $oferta['id'] ?>">
                        <input type="number" name="cantidad" value="1" min="1" max="<?= $oferta['stock'] ?>" class="cantidad-input">
                        <button type="submit" name="agregar_carrito" class="btn-agregar">
                            Agregar al carrito 🛒
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Productos Recomendados -->
        <h2>🎯 Recomendados para ti</h2>
        <div class="ofertas-grid">
            <?php foreach ($recomendados as $producto): ?>
                <div class="oferta-card">
                    <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                    <div class="precio-oferta">$<?= number_format($producto['precio'], 2) ?></div>
                    <div class="stock-info">Stock disponible: <?= $producto['stock'] ?> unidades</div>
                    
                    <form method="POST" style="display: inline-flex; align-items: center; justify-content: center;">
                        <input type="hidden" name="producto_id" value="<?= $producto['id'] ?>">
                        <input type="number" name="cantidad" value="1" min="1" max="<?= $producto['stock'] ?>" class="cantidad-input">
                        <button type="submit" name="agregar_carrito" class="btn-agregar">
                            Agregar al carrito 🛒
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Widget del Carrito -->
<?php if (!empty($items_carrito)): ?>
    <div class="carrito-widget">
        <h3>🛒 Tu Carrito</h3>
        <?php foreach ($items_carrito as $item): ?>
            <div style="margin: 10px 0;">
                <strong><?= htmlspecialchars($item['nombre']) ?></strong><br>
                <?= $item['cantidad'] ?> x $<?= number_format($item['precio'], 2) ?>
            </div>
        <?php endforeach; ?>
        <div class="carrito-total">
            Total: $<?= number_format($total_carrito, 2) ?>
        </div>
        <a href="finalizar_compra.php" class="btn-finalizar">
            Finalizar Compra 💳
        </a>
    </div>
<?php endif; ?>
    </div>
</body>
</html>