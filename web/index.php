<?php

use Nice\Extension\CacheExtension;
use Nice\Extension\DoctrineKeyValueExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Nice\Application;
use Nice\Router\RouteCollector;
use Nice\Extension\DoctrineDbalExtension;
use Nice\Extension\LogExtension;

require __DIR__ . '/../vendor/autoload.php';

// Enable Symfony debug error handlers
Symfony\Component\Debug\Debug::enable();

$app = new Application();
$app->appendExtension(new CacheExtension(array(
    'connections' => array(
        'default' => array(
            'driver' => 'redis',
            'options' => array(
                'socket' => '/tmp/redis.sock'
            )
        )
    )
)));
$app->appendExtension(new DoctrineKeyValueExtension(array(
    'key_value' => array(
        'mapping' => array(
            'paths' => array('%app.root_dir/src')
        )
    )
)));
$app->appendExtension(new DoctrineDbalExtension(array(
    'database' => array(
        'driver' => 'pdo_sqlite',
        'path' => '%app.root_dir%/sqlite.db'
    )
)));
$app->appendExtension(new LogExtension(array(
    'channels' => array(
        'default' => array(
            'handler' => 'stream',
            'level' => 100, // Debug
            'options' => array(
                'file' => '%app.log_dir%/dev.log'
            )
        )
    )
)));

\Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace('Doctrine\KeyValueStore', __DIR__ . '/../vendor/doctrine/key-value-store/lib');

// Configure your routes
$app->set('routes', function (RouteCollector $r) {
    $r->addRoute('GET', '/', function (Application $app, Request $request) {
        $app->get('logger.default')->debug('This is a test');

        return new Response('Hello, world');
    });

    $r->addRoute('GET', '/hello/{name}', function (Application $app, Request $request, $name) {
        $cache = $app->get('cache.default');
        $cache->save('last-hello', $name);

        return new Response('Hello, ' . $name . '!');
    });

    $r->addRoute('GET', '/last-hello', function (Application $app) {
        $cache = $app->get('cache.default');
        $name = $cache->fetch('last-hello');

        if (!$name) {
            return new Response('I have not said "Hello" to anyone :(');
        }

        return new Response('Last said hello to: ' . $name);
    });

    $r->addRoute('GET', '/messages', function (Application $app, Request $request) {
        $conn = $app->get('doctrine.dbal.database_connection');
        $results = $conn->executeQuery("SELECT * FROM messages")->fetchAll();
        return new \Symfony\Component\HttpFoundation\JsonResponse($results);
    });

    $r->addRoute('GET', '/make-person/{name}/{age}', function (Application $app, $name, $age) {
        $em = $app->get('doctrine.key_value.entity_manager');
        $person = new \Example\Person($name, $age);
        $em->persist($person);
        $em->flush();
        return new Response('Person added!');
    });

    $r->addRoute('GET', '/person/{name}', function (Application $app, $name) {
        $em = $app->get('doctrine.key_value.entity_manager');

        $person = $em->find('Example\Person', $name);
        if (!$person) {
            return new Response('Unable to find ' . $name);
        }

        return new Response($name . ' is ' . $person->getAge() . ' years old!');
    });
});

// Run the application
$app->run();