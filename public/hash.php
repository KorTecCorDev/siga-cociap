<?php
// Mostrar el password hasheado en pantalla para "admin1234"
$password = "admin1234";
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
echo "Password hasheado: " . $hashedPassword;