<?php

namespace App\Core;

use App\Core\Http\Router;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Database\Connection;
use App\Core\Container\Container;

class Application
{
    private array $config;
    private static Container $container;
    private Router $router;

    public function __construct(array $config)
    {
        $this->config = $config;
        static::$container = new Container();
        $this->router = new Router();
        
        $this->registerServices();
    }

    private function registerServices(): void
    {
        // Register database connection
        static::$container->singleton(Connection::class, function () {
            return new Connection([
                'host' => $_ENV['DB_HOST'],
                'database' => $_ENV['DB_NAME'],
                'username' => $_ENV['DB_USER'],
                'password' => $_ENV['DB_PASS']
            ]);
        });

        // Register request/response services
        static::$container->singleton(Request::class, function () {
            return new Request();
        });

        static::$container->singleton(Response::class, function () {
            return new Response();
        });
    }

    public function handle(): void
    {
        try {
            $request = static::$container->get(Request::class);
            $response = $this->router->resolve($request);
            $response->send();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    private function handleException(\Throwable $e): void
    {
        if ($this->config['debug']) {
            throw $e;
        }

        $response = static::$container->get(Response::class);
        $response->setStatusCode(500);
        $response->setContent('Internal Server Error');
        $response->send();

        error_log($e->getMessage());
    }

    public static function container(): Container
    {
        return static::$container;
    }

    public function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }
} 