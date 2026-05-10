<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php");
    exit;
}

$id = intval($_GET["id"] ?? 0);

if ($id > 0) {
    $conn->query("UPDATE kullanicilar SET aktif = 0 WHERE id = $id AND rol = 'garson'");
}

header("Location: garson_ekle.php");
exit;
?>