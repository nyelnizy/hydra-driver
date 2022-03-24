<?php

return [
    "attachments_folder"=>"attachments",
    "hydra_server"=>"ws://hydra.luntano.net:6001",
    "manage_chats"=>false,
    'hydra' => [
        'driver' => 'mysql',
        'url' => null,
        'host' => "hydra.luntano.net",
        'port' =>"3306",
        'database' => "hydra",
        'username' => "hydra",
        'password' => "hydra-sms-2022",
        'unix_socket' => "",
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
];