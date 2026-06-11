<?php
// db.php
$host = 'localhost';       // ou seu servidor
$user = 'root';            // seu usuário MySQL
$password = '';            // sua senha
$database = 'media_kit_rose';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die(json_encode(['erro' => 'Falha na conexão: ' . $conn->connect_error]));
}

$conn->set_charset('utf8mb4');
?>
