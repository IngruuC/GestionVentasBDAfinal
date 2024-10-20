<?php
require_once '../config/Connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Corregido el nombre de la variable username (estaba como usernme)
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    // Agregada la variable role_id que faltaba
    $role_id = $_POST['role_id'];

    try {
        $connection = new Connection();
        $pdo = $connection->connect();

        // Corregido el nombre del parámetro role_id (estaba como role)
        $sql = "INSERT INTO usuarios (username, password, role_id) VALUES (:username, :password, :role_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'username' => $username,
            'password' => $password,
            'role_id' => $role_id
        ]);

        echo "<script>
        alert('Usuario registrado correctamente.');
        window.location.href = '../index.php';
        </script>";

    } catch (\Throwable $th) {
        echo "<script>
        alert('Error al registrar el usuario: " . addslashes($th->getMessage()) . "');
        window.location.href = '../Registrarse.php';
        </script>";
    }
}
?>