<?php
// genera_hash.php — DA ELIMINARE SUBITO DOPO L'USO
$password = '123456789'; // ← cambia qui
echo password_hash($password, PASSWORD_BCRYPT);
