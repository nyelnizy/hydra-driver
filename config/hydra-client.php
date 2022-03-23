<?php

return [
    "attachments_folder"=>"attachments",
    "hydra_server"=>"ws://hydra.luntano.net:6001",
    "manage_chats"=>false,
    'hydra_db' => [
        'driver' => 'mysql',
        'url' => null,
        'host' => "themisterfridayproject.com",
        'port' =>"3306",
        'database' => "smsgateway",
        'username' => "smsgateway",
        'password' => "smsgateway",
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