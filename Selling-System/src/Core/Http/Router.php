<?php

namespace App\Core\Http;

use App\Core\Application;
use App\Core\Http\Exceptions\RouteNotFoundException;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    public function middleware(string $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    private function addRoute(string $method, string $path, $handler): self
    {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'middlewares' => $this->middlewares
        ];
        
        // Reset middlewares after adding route
        $this->middlewares = [];
        
        return $this;
    }

    public function resolve(Request $request): Response
    {
        $path = $request->getPath();
        $method = $request->getMethod();
        $route = $this->routes[$method][$path] ?? null;

        if (!$route) {
            throw new RouteNotFoundException();
        }

        // Run middlewares
        foreach ($route['middlewares'] as $middleware) {
            $instance = Application::container()->get($middleware);
            $response = $instance->handle($request);
            
            if ($response instanceof Response) {
                return $response;
            }
        }

        $handler = $route['handler'];
        
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = Application::container()->get($class);
            $handler = [$instance, $method];
        }

        if (is_callable($handler)) {
            $response = call_user_func($handler, $request);
            
            if (!$response instanceof Response) {
                $response = new Response($response);
            }
            
            return $response;
        }

        throw new RouteNotFoundException();
    }
} 