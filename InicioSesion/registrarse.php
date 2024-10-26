<?php
session_start();
require_once '../config/Connection.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role_id = $_POST['role_id'];

    try {
        $connection = new Connection();
        $pdo = $connection->connect();

        $sql = "INSERT INTO usuarios (username, password, role_id) VALUES (:username, :password, :role_id)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'username' => $username,
            'password' => $password,
            'role_id' => $role_id
        ]);

        $_SESSION['success_message'] = "¡Usuario registrado exitosamente!";
        header('Location: ../index.php');
        exit();

    } catch (\Throwable $th) {
        $_SESSION['error_message'] = "Error al registrar el usuario: " . $th->getMessage();
        header('Location: ../Registrarse.php');
        exit();
    }
}

// Si alguien accede directamente a este archivo
header('Location: ../index.php');
exit();