<?php

// Copia este archivo como db.local.php en el hosting.
// En cPanel de Imagina Colombia: MySQL Databases, crea la base y el usuario,
// asígnalo con todos los privilegios, e importa docs/schema.sql en phpMyAdmin.
// db.local.php no se sube al repositorio.

return [
    'host' => 'localhost',
    'db_name' => 'usuario_nuncajamas',
    'username' => 'usuario_nuncajamas',
    'password' => 'la-clave-de-cpanel',
];
