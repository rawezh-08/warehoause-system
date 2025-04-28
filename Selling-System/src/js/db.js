/**
 * Database Manager for the Warehouse Management System
 * Handles all data storage operations using IndexedDB
 */

class DatabaseManager {
    constructor() {
        this.dbName = 'warehouseDB';
        this.dbVersion = 1;
        this.db = null;
        
        // Initialize the database connection
        this.initDB();
    }

    /**
     * Initialize the IndexedDB database
     */
    initDB() {
        return new Promise((resolve, reject) => {
            if (this.db) {
                resolve(this.db);
                return;
            }

            const request = indexedDB.open(this.dbName, this.dbVersion);

            request.onerror = (event) => {
                console.error('Error opening database:', event.target.error);
                reject(event.target.error);
            };

            request.onsuccess = (event) => {
                this.db = event.target.result;
                console.log('Database opened successfully');
                resolve(this.db);
            };

            request.onupgradeneeded = (event) => {
                const db = event.target.result;

                // Create object stores if they don't exist
                if (!db.objectStoreNames.contains('products')) {
                    const productStore = db.createObjectStore('products', { keyPath: 'id' });
                    productStore.createIndex('name', 'name', { unique: false });
                    productStore.createIndex('categoryId', 'categoryId', { unique: false });
                }

                if (!db.objectStoreNames.contains('categories')) {
                    const categoryStore = db.createObjectStore('categories', { keyPath: 'id' });
                    categoryStore.createIndex('name', 'name', { unique: true });
                }

                if (!db.objectStoreNames.contains('suppliers')) {
                    const supplierStore = db.createObjectStore('suppliers', { keyPath: 'id' });
                    supplierStore.createIndex('name', 'name', { unique: false });
                }

                if (!db.objectStoreNames.contains('customers')) {
                    const customerStore = db.createObjectStore('customers', { keyPath: 'id' });
                    customerStore.createIndex('name', 'name', { unique: false });
                }

                if (!db.objectStoreNames.contains('purchases')) {
                    const purchaseStore = db.createObjectStore('purchases', { keyPath: 'id' });
                    purchaseStore.createIndex('date', 'date', { unique: false });
                    purchaseStore.createIndex('supplierId', 'supplierId', { unique: false });
                }

                if (!db.objectStoreNames.contains('sales')) {
                    const saleStore = db.createObjectStore('sales', { keyPath: 'id' });
                    saleStore.createIndex('date', 'date', { unique: false });
                    saleStore.createIndex('customerId', 'customerId', { unique: false });
                }

                if (!db.objectStoreNames.contains('expenses')) {
                    const expenseStore = db.createObjectStore('expenses', { keyPath: 'id' });
                    expenseStore.createIndex('date', 'date', { unique: false });
                    expenseStore.createIndex('category', 'category', { unique: false });
                }

                // Add default data
                this.addDefaultData(db);
            };
        });
    }

    /**
     * Add default data to the database during initialization
     */
    addDefaultData(db) {
        // Add default categories
        const categoryStore = db.transaction('categories', 'readwrite').objectStore('categories');
        const defaultCategories = [
            { id: 1, name: 'خواردن' },
            { id: 2, name: 'خواردنەوە' },
            { id: 3, name: 'جل و بەرگ' },
            { id: 4, name: 'کەلوپەلی ناوماڵ' },
            { id: 5, name: 'ئەلیکترۆنیات' }
        ];

        defaultCategories.forEach(category => {
            categoryStore.add(category);
        });

        // Add default suppliers
        const supplierStore = db.transaction('suppliers', 'readwrite').objectStore('suppliers');
        const defaultSuppliers = [
            { id: 1, name: 'شەریکەی هەولێر', phone: '0750 123 4567', address: 'هەولێر، شەقامی 60 مەتری', debt: 0 },
            { id: 2, name: 'کۆمپانیای سلێمانی', phone: '0770 987 6543', address: 'سلێمانی، شەقامی سالم', debt: 0 }
        ];

        defaultSuppliers.forEach(supplier => {
            supplierStore.add(supplier);
        });

        // Add default products
        const productStore = db.transaction('products', 'readwrite').objectStore('products');
        const defaultProducts = [
            { id: 1, name: 'برنج', categoryId: '1', buyPrice: 1.5, sellPrice: 2.0, stock: 100 },
            { id: 2, name: 'شەکر', categoryId: '1', buyPrice: 0.8, sellPrice: 1.2, stock: 150 },
            { id: 3, name: 'ڕۆن', categoryId: '1', buyPrice: 2.0, sellPrice: 2.5, stock: 80 },
            { id: 4, name: 'چا', categoryId: '2', buyPrice: 3.0, sellPrice: 4.0, stock: 50 },
            { id: 5, name: 'کۆلا', categoryId: '2', buyPrice: 0.5, sellPrice: 1.0, stock: 200 }
        ];

        defaultProducts.forEach(product => {
            productStore.add(product);
        });
    }

    /**
     * Get all products from the database
     */
    getProducts() {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('products', 'readonly');
                    const store = transaction.objectStore('products');
                    const request = store.getAll();

                    request.onsuccess = () => {
                        resolve(request.result);
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Get a specific product by ID
     */
    getProduct(id) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('products', 'readonly');
                    const store = transaction.objectStore('products');
                    const request = store.get(id);

                    request.onsuccess = () => {
                        resolve(request.result);
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Add a new product to the database
     */
    addProduct(product) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('products', 'readwrite');
                    const store = transaction.objectStore('products');
                    const request = store.add(product);

                    request.onsuccess = () => {
                        resolve();
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Update an existing product
     */
    updateProduct(product) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('products', 'readwrite');
                    const store = transaction.objectStore('products');
                    const request = store.put(product);

                    request.onsuccess = () => {
                        resolve();
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Delete a product by ID
     */
    deleteProduct(id) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('products', 'readwrite');
                    const store = transaction.objectStore('products');
                    const request = store.delete(id);

                    request.onsuccess = () => {
                        resolve();
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Get all categories from the database
     */
    getCategories() {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('categories', 'readonly');
                    const store = transaction.objectStore('categories');
                    const request = store.getAll();

                    request.onsuccess = () => {
                        resolve(request.result);
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Add a new category to the database
     */
    addCategory(category) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('categories', 'readwrite');
                    const store = transaction.objectStore('categories');
                    const request = store.add(category);

                    request.onsuccess = () => {
                        resolve();
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Get all suppliers from the database
     */
    getSuppliers() {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('suppliers', 'readonly');
                    const store = transaction.objectStore('suppliers');
                    const request = store.getAll();

                    request.onsuccess = () => {
                        resolve(request.result);
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Get a specific supplier by ID
     */
    getSupplier(id) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('suppliers', 'readonly');
                    const store = transaction.objectStore('suppliers');
                    const request = store.get(id);

                    request.onsuccess = () => {
                        resolve(request.result);
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Add a new supplier to the database
     */
    addSupplier(supplier) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('suppliers', 'readwrite');
                    const store = transaction.objectStore('suppliers');
                    const request = store.add(supplier);

                    request.onsuccess = () => {
                        resolve();
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Update an existing supplier
     */
    updateSupplier(supplier) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('suppliers', 'readwrite');
                    const store = transaction.objectStore('suppliers');
                    const request = store.put(supplier);

                    request.onsuccess = () => {
                        resolve();
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Add a new purchase record to the database
     */
    addPurchase(purchase) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('purchases', 'readwrite');
                    const store = transaction.objectStore('purchases');
                    const request = store.add(purchase);

                    request.onsuccess = () => {
                        resolve();
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Get all purchase records from the database
     */
    getPurchases() {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('purchases', 'readonly');
                    const store = transaction.objectStore('purchases');
                    const request = store.getAll();

                    request.onsuccess = () => {
                        resolve(request.result);
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Get purchases by supplier ID
     */
    getPurchasesBySupplier(supplierId) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('purchases', 'readonly');
                    const store = transaction.objectStore('purchases');
                    const index = store.index('supplierId');
                    const request = index.getAll(supplierId);

                    request.onsuccess = () => {
                        resolve(request.result);
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Add a new sale record to the database
     */
    addSale(sale) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('sales', 'readwrite');
                    const store = transaction.objectStore('sales');
                    const request = store.add(sale);

                    request.onsuccess = () => {
                        resolve();
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Get all sales records from the database
     */
    getSales() {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('sales', 'readonly');
                    const store = transaction.objectStore('sales');
                    const request = store.getAll();

                    request.onsuccess = () => {
                        resolve(request.result);
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Add a new expense record to the database
     */
    addExpense(expense) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('expenses', 'readwrite');
                    const store = transaction.objectStore('expenses');
                    const request = store.add(expense);

                    request.onsuccess = () => {
                        resolve();
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Get all expense records from the database
     */
    getExpenses() {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction('expenses', 'readonly');
                    const store = transaction.objectStore('expenses');
                    const request = store.getAll();

                    request.onsuccess = () => {
                        resolve(request.result);
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }

    /**
     * Clear a specific object store (for testing/debug purposes)
     */
    clearStore(storeName) {
        return new Promise((resolve, reject) => {
            this.initDB()
                .then(db => {
                    const transaction = db.transaction(storeName, 'readwrite');
                    const store = transaction.objectStore(storeName);
                    const request = store.clear();

                    request.onsuccess = () => {
                        resolve();
                    };

                    request.onerror = (event) => {
                        reject(event.target.error);
                    };
                })
                .catch(error => {
                    reject(error);
                });
        });
    }
}

// Initialize database
const dbManager = new DatabaseManager(); 