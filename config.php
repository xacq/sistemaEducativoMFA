<?php

$host = 'localhost';
$db   = 'sistema_academico_final';
$user = 'root';
$pass = '';


$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_error) {
    die('Error de conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

$mysqli->set_charset('utf8mb4');

// 2) NUEVA CONEXIÓN PDO PARA LOS REPORTES PSICOPEDAGÓGICOS
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (Exception $e) {
    die("Error de conexión PDO: " . $e->getMessage());
}


define('BASE_URL', 'http://localhost/sistemaEducativoMFA');


// Configuración de OpenAI
//Ingresar la API key de OpenAI aquí
define('OPENAI_API_KEY', ''); // ← cámbiala cuando la tengas

?>
