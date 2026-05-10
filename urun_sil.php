<?php
session_start();
require_once "db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["rol"] !== "admin") {
    header("Location: login.php");
    exit;
}

$id = intval($_GET["id"] ?? 0);

if ($id > 0) {
    $conn->query("DELETE FROM urunler WHERE id = $id");
}

header("Location: urun_yonetimi.php");
exit;
?>