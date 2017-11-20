<?php

require_once __DIR__ . '/../../vendor/autoload.php';

try {
    (new Dotenv\Dotenv(__DIR__ . '/queue/'))->load();
} catch (Dotenv\Exception\InvalidPathException $e) {
    //
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    realpath(__DIR__)
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    \Laravel\Lumen\Console\Kernel::class
);

$app->register(\Mpociot\Couchbase\CouchbaseServiceProvider::class);
$app->register(\developermarshak\QueueCouchbase\CouchbaseQueueServiceProvider::class);

return $app;
