<?php

namespace App\Core\Http;

class Request
{
    private array $queryParams;
    private array $postData;
    private array $cookies;
    private array $files;
    private array $server;

    public function __construct()
    {
        $this->queryParams = $_GET;
        $this->postData = $_POST;
        $this->cookies = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'];
    }

    public function getPath(): string
    {
        $path = $this->server['REQUEST_URI'] ?? '/';
        $position = strpos($path, '?');
        
        if ($position === false) {
            return $path;
        }
        
        return substr($path, 0, $position);
    }

    public function get(string $key, $default = null)
    {
        return $this->queryParams[$key] ?? $default;
    }

    public function post(string $key, $default = null)
    {
        return $this->postData[$key] ?? $default;
    }

    public function cookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    public function all(): array
    {
        return array_merge(
            $this->queryParams,
            $this->postData
        );
    }

    public function has(string $key): bool
    {
        return isset($this->queryParams[$key]) || isset($this->postData[$key]);
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isAjax(): bool
    {
        return isset($this->server['HTTP_X_REQUESTED_WITH']) && 
               strtolower($this->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
} 