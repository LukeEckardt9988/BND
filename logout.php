<?php
session_start();

// Zerstört alle Session-Daten
session_unset();
session_destroy();

// Leitet zum Login weiter
header("Location: login.php");
exit;
?>