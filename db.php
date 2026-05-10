<?php
$host = "localhost";
$dbname = "hataysep_macakizicafe";
$username = "hataysep_macakizicafe";
$password = "117X=bmJo_u4?3Vb";

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>