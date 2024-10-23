<?php
session_start();
require_once '../Config/Connection.php';

if (!isset($_SESSION['username'])) {
    header('Location: ../index.php');
    exit;
}

$connection = new Connection();
$pdo = $connection->connect();

function getUsuarios($pdo) {
    $sql = "SELECT u.id, u.username, r.nombre as rol, u.role_id FROM usuarios u JOIN roles r ON u.role_id = r.id";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getRoles($pdo) {
    $sql = "SELECT id, nombre FROM roles";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$usuarios = getUsuarios($pdo);
$roles = getRoles($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accion'])) {
        switch ($_POST['accion']) {
            case 'agregar_usuario':
                $username = $_POST['username'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role_id = $_POST['role_id'];
                
                $sql = "INSERT INTO usuarios (username, password, role_id) VALUES (?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $password, $role_id]);
                
                $mensaje = "Usuario agregado correctamente.";
                break;
            case 'editar_usuario':
                $id = $_POST['id'];
                $username = $_POST['username'];
                $role_id = $_POST['role_id'];
                
                $sql = "UPDATE usuarios SET username = ?, role_id = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$username, $role_id, $id]);
                
                $mensaje = "Usuario actualizado correctamente.";
                break;
            case 'eliminar_usuario':
                $id = $_POST['id'];
                
                $sql = "DELETE FROM usuarios WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$id]);
                
                $mensaje = "Usuario eliminado correctamente.";
                break;
        }
        
        $usuarios = getUsuarios($pdo);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .user-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            background-color: #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        .user-actions {
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
        <h1>Configuración del Sistema</h1>
        
        <div class="grid-container">
            <div class="card">
                <h2>Gestión de Usuarios</h2>
                <?php if (isset($mensaje)): ?>
                    <p style="color: green;"><?= $mensaje ?></p>
                <?php endif; ?>
                <?php foreach ($usuarios as $usuario): ?>
                    <div class="user-card">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?= strtoupper(substr($usuario['username'], 0, 1)) ?>
                            </div>
                            <div>
                                <h3><?= htmlspecialchars($usuario['username']) ?></h3>
                                <p>Rol: <?= htmlspecialchars($usuario['rol']) ?></p>
                            </div>
                        </div>
                        <div class="user-actions">
                            <button onclick="editarUsuario(<?= $usuario['id'] ?>, '<?= htmlspecialchars($usuario['username']) ?>', <?= $usuario['role_id'] ?>)">Editar</button>
                            <button onclick="eliminarUsuario(<?= $usuario['id'] ?>)">Eliminar</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <button onclick="agregarUsuario()">Agregar Nuevo Usuario</button>
            </div>
            
            <div class="card" id="formularioUsuario" style="display: none;">
                <h2 id="tituloFormulario"></h2>
                <form action="" method="POST">
                    <input type="hidden" name="accion" id="accionUsuario">
                    <input type="hidden" name="id" id="idUsuario">
                    <input type="text" name="username" id="usernameUsuario" placeholder="Nombre de usuario" required>
                    <input type="password" name="password" id="passwordUsuario" placeholder="Contraseña">
                    <select name="role_id" id="roleUsuario" required>
                        <?php foreach ($roles as $rol): ?>
                            <option value="<?= $rol['id'] ?>"><?= htmlspecialchars($rol['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">Guardar</button>
                    <button type="button" onclick="cancelarFormulario()">Cancelar</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function editarUsuario(id, username, roleId) {
            document.getElementById('tituloFormulario').innerText = 'Editar Usuario';
            document.getElementById('accionUsuario').value = 'editar_usuario';
            document.getElementById('idUsuario').value = id;
            document.getElementById('usernameUsuario').value = username;
            document.getElementById('passwordUsuario').required = false;
            document.getElementById('roleUsuario').value = roleId;
            document.getElementById('formularioUsuario').style.display = 'block';
        }

        function eliminarUsuario(id) {
            if (confirm('¿Estás seguro de eliminar este usuario?')) {
                document.getElementById('accionUsuario').value = 'eliminar_usuario';
                document.getElementById('idUsuario').value = id;
                document.querySelector('form').submit();
            }
        }

        function agregarUsuario() {
            document.getElementById('tituloFormulario').innerText = 'Agregar Nuevo Usuario';
            document.getElementById('accionUsuario').value = 'agregar_usuario';
            document.getElementById('idUsuario').value = '';
            document.getElementById('usernameUsuario').value = '';
            document.getElementById('passwordUsuario').value = '';
            document.getElementById('passwordUsuario').required = true;
            document.getElementById('roleUsuario').value = '';
            document.getElementById('formularioUsuario').style.display = 'block';
        }

        function cancelarFormulario() {
            document.getElementById('formularioUsuario').style.display = 'none';
        }
    </script>
</body>
</html>