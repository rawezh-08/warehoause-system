export class Store {
    constructor() {
        this.db = null;
        this.listeners = new Map();
    }

    async initialize() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('SalesManagementSystem', 1);
            
            request.onerror = () => reject(request.error);
            request.onsuccess = () => {
                this.db = request.result;
                resolve();
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Create object stores
                if (!db.objectStoreNames.contains('customers')) {
                    db.createObjectStore('customers', { keyPath: 'id', autoIncrement: true });
                }
                if (!db.objectStoreNames.contains('products')) {
                    db.createObjectStore('products', { keyPath: 'id', autoIncrement: true });
                }
                if (!db.objectStoreNames.contains('orders')) {
                    db.createObjectStore('orders', { keyPath: 'id', autoIncrement: true });
                }
            };
        });
    }
}
