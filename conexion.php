<?php
$host     = "localhost";
$user     = "root";
$password = ""; 
$dbname   = "sistema_duncan"; 
$port     = 3309; 

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>