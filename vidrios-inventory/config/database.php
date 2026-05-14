<?php
// Credenciales y parámetros de conexión MySQL
return [
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'vidrios_inventory',
    'user'     => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
    ],
];
