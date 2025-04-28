<?php

namespace App\Core\Http;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $content;

    public function __construct($content = '')
    {
        $this->content = $content;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setContent($content): self
    {
        $this->content = $content;
        return $this;
    }

    public function redirect(string $url): self
    {
        $this->setHeader('Location', $url);
        $this->setStatusCode(302);
        return $this;
    }

    public function json($data): self
    {
        $this->setHeader('Content-Type', 'application/json');
        $this->content = json_encode($data);
        return $this;
    }

    public function send(): void
    {
        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        // Send content
        if (is_string($this->content) || is_numeric($this->content)) {
            echo $this->content;
        } elseif (is_array($this->content) || is_object($this->content)) {
            echo json_encode($this->content);
        }
    }
} 