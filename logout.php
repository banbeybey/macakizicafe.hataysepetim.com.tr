<?php
session_start();
$rol = $_SESSION["rol"] ?? "garson";
session_destroy();
header("Location: " . ($rol === "admin" ? "admin_login.php" : "login.php"));
exit;
?>
