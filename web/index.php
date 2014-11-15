<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nice\Application;
use Nice\Router\RouteCollector;
use Nice\Extension\DoctrineDbalExtension;

require __DIR__ . '/../vendor/autoload.php';

// Enable Symfony debug error handlers
Symfony\Component\Debug\Debug::enable();

$app = new Application();
$app->appendExtension(new DoctrineDbalExtension(array(
    'database' => array(
        'driver' => 'pdo_sqlite',
        'path' => '%app.root_dir%/sqlite.db'
    )
)));

// Configure your routes
$app->set('routes', function (RouteCollector $r) {
    $r->addRoute('GET', '/', function (Application $app, Request $request) {
            return new Response('Hello, world');
        });

    $r->addRoute('GET', '/hello/{name}', function (Application $app, Request $request, $name) {
            return new Response('Hello, ' . $name . '!');
        });

    $r->addRoute('GET', '/messages', function (Application $app, Request $request) {
            $conn = $app->get('doctrine.dbal.database_connection');
            $results = $conn->executeQuery("SELECT * FROM messages")->fetchAll();
            return new \Symfony\Component\HttpFoundation\JsonResponse($results);
        });
});

// Run the application
$app->run();