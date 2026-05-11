<?php
$conn = new mysqli('sql307.byethost7.com', 'b7_41844795', 'wissam2005@', 'b7_41844795_wissam');
if ($conn->connect_error) {
    die("Connexion échouée: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>