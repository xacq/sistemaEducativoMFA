<?php
// config.php
$host = 'localhost';
$db   = 'sistema_academico_final';
$user = 'root';
$pass = '';
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    die('Error de conexión (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}
$mysqli->set_charset('utf8mb4');
define('BASE_URL', 'http://localhost/sistema_academico_final');
?>