<?php
$config = require __DIR__ . '/config.php';
mysqli_report(MYSQLI_REPORT_OFF);

$conn = new mysqli($config['host'], $config['user'], $config['password'], $config['dbname'], $config['port']);

if ($conn->connect_error) {
    error_log('Error de conexión: ' . $conn->connect_error);
    http_response_code(500);
    die('No se pudo conectar con la base de datos.');
}
$conn->set_charset('utf8mb4');
?>