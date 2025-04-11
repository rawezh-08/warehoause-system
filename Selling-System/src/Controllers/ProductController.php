<?php

namespace Controllers;

use Core\Controllers\BaseController;
use Models\Product;
use Exception;

class ProductController extends BaseController {
    private $productModel;

    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
    }

    public function index() {
        try {
            $products = $this->productModel->getAll();
            return $this->view('products/index', [
                'products' => $products,
                'title' => 'Product List'
            ]);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/products');
        }
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $data = [
                    'name' => $_POST['name'],
                    'code' => $_POST['code'],
                    'barcode' => $_POST['barcode'],
                    'category_id' => $_POST['category_id'],
                    'unit_id' => $_POST['unit_id'],
                    'purchase_price' => $_POST['purchase_price'],
                    'selling_price' => $_POST['selling_price'],
                    'stock' => $_POST['stock'],
                    'min_stock' => $_POST['min_stock'],
                    'description' => $_POST['description'] ?? null
                ];

                // Check for duplicate code/barcode
                $duplicate = $this->productModel->checkDuplicate($data['code'], $data['barcode']);
                if ($duplicate && $duplicate['count'] > 0) {
                    throw new Exception('Product code or barcode already exists');
                }

                $this->productModel->create($data);
                $_SESSION['success'] = 'Product created successfully';
                $this->redirect('/products');
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
                $this->redirect('/products/create');
            }
        }

        return $this->view('products/create', [
            'title' => 'Add New Product'
        ]);
    }

    public function edit($id) {
        try {
            $product = $this->productModel->getById($id);
            if (!$product) {
                throw new Exception('Product not found');
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = [
                    'name' => $_POST['name'],
                    'code' => $_POST['code'],
                    'barcode' => $_POST['barcode'],
                    'category_id' => $_POST['category_id'],
                    'unit_id' => $_POST['unit_id'],
                    'purchase_price' => $_POST['purchase_price'],
                    'selling_price' => $_POST['selling_price'],
                    'stock' => $_POST['stock'],
                    'min_stock' => $_POST['min_stock'],
                    'description' => $_POST['description'] ?? null
                ];

                // Check for duplicate code/barcode excluding current product
                $duplicate = $this->productModel->checkDuplicate($data['code'], $data['barcode']);
                if ($duplicate && $duplicate['count'] > 0 && $product['code'] !== $data['code'] && $product['barcode'] !== $data['barcode']) {
                    throw new Exception('Product code or barcode already exists');
                }

                $this->productModel->update($id, $data);
                $_SESSION['success'] = 'Product updated successfully';
                $this->redirect('/products');
            }

            return $this->view('products/edit', [
                'product' => $product,
                'title' => 'Edit Product'
            ]);
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/products');
        }
    }

    public function delete($id) {
        try {
            $product = $this->productModel->getById($id);
            if (!$product) {
                throw new Exception('Product not found');
            }

            $this->productModel->delete($id);
            $_SESSION['success'] = 'Product deleted successfully';
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }

        $this->redirect('/products');
    }

    public function search() {
        try {
            $keyword = $_GET['q'] ?? '';
            $products = $this->productModel->search($keyword);
            
            if ($this->isAjaxRequest()) {
                return $this->json($products);
            }

            return $this->view('products/search', [
                'products' => $products,
                'keyword' => $keyword,
                'title' => 'Search Results'
            ]);
        } catch (Exception $e) {
            if ($this->isAjaxRequest()) {
                return $this->json(['error' => $e->getMessage()]);
            }
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('/products');
        }
    }

    private function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
} 