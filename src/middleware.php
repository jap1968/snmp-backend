<?php
// Application middleware

// composer require tuupola/cors-middleware
$app->add(new \Tuupola\Middleware\Cors([
    "origin" => ["*"],
    "methods" => ["GET", "POST"],
    "headers.allow" => ["Accept", "Content-Type", "Authorization", "Origin"],
    "headers.expose" => [],
    "credentials" => true,
    "cache" => 0,
    "logger" => $container['logger']
]));
