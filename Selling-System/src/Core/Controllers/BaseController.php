<?php

namespace Core\Controllers;

class BaseController {
    protected $db;
    protected $request;
    protected $session;

    public function __construct() {
        $this->initializeDatabase();
        $this->initializeRequest();
        $this->initializeSession();
    }

    protected function initializeDatabase() {
        // Initialize database connection
        require_once __DIR__ . '/../Database/Connection.php';
        
        // Database configuration
        $config = [
            'host' => 'localhost',
            'database' => 'warehouse_db',
            'username' => 'root',
            'password' => ''
        ];
        
        $this->db = new \App\Core\Database\Connection($config);
    }

    protected function initializeRequest() {
        // Initialize request handling
        $this->request = $_REQUEST;
    }

    protected function initializeSession() {
        // Initialize session handling
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->session = &$_SESSION;
    }

    protected function view($view, $data = []) {
        // Extract data to make it available in the view
        extract($data);
        
        // Include the view file
        $viewPath = __DIR__ . '/../../Views/' . $view . '.php';
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new \Exception("View {$view} not found");
        }
    }

    protected function redirect($url) {
        header("Location: {$url}");
        exit();
    }

    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
} 