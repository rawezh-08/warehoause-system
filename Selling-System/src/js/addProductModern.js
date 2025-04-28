/**
 * Modern Add Product Page JavaScript
 * Handles form validation, image uploads, tab navigation and other interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements
    const sidebar = document.getElementById('sidebar');
    const toggleSidebarBtn = document.getElementById('toggleSidebar');
    const addProductForm = document.getElementById('addProductForm');
    const productImageInput = document.getElementById('productImage');
    const tabItems = document.querySelectorAll('.tab-item');
    const purchasePriceInput = document.getElementById('purchasePrice');
    const sellingPriceInput = document.getElementById('sellingPrice');
    
    // Get CSS variables for consistent styling in JS
    const style = getComputedStyle(document.documentElement);
    const primaryColor = style.getPropertyValue('--primary-color').trim();
    const primaryLight = style.getPropertyValue('--primary-light').trim();
    const lightGray = style.getPropertyValue('--light-gray').trim();
    const borderColor = style.getPropertyValue('--border-color').trim();
    
    // Toggle sidebar on mobile
    if (toggleSidebarBtn) {
        toggleSidebarBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
    
    // Handle clicks outside sidebar to close it on mobile
    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggleBtn = toggleSidebarBtn.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnToggleBtn && window.innerWidth < 992 && sidebar.classList.contains('active')) {
            sidebar.classList.remove('active');
        }
    });
    
    // Form validation and submission
    if (addProductForm) {
        addProductForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const productName = document.getElementById('productName').value;
            const productCode = document.getElementById('productCode').value;
            const purchasePrice = purchasePriceInput.value;
            const sellingPrice = sellingPriceInput.value;
            
            // Perform validation
            const errors = [];
            
            if (!productName) errors.push('تکایە ناوی کاڵا بنووسە');
            if (!productCode) errors.push('تکایە کۆدی کاڵا بنووسە');
            if (!purchasePrice) errors.push('تکایە نرخی کڕین بنووسە');
            if (!sellingPrice) errors.push('تکایە نرخی فرۆشتن بنووسە');
            
            if (errors.length > 0) {
                alert(errors.join('\n'));
                return;
            }
            
            // Calculate profit
            const profit = calculateProfit(purchasePrice, sellingPrice);
            
            // In a real application, you would submit the form data to a server here
            // For this demo, we'll just show a success message
            showSuccessMessage();
            
            // Reset form
            addProductForm.reset();
            resetImageUpload();
        });
    }
    
    // Image upload functionality
    if (productImageInput) {
        productImageInput.addEventListener('change', handleImageUpload);
    }
    
    // Tab navigation
    if (tabItems.length > 0) {
        tabItems.forEach(item => {
            item.addEventListener('click', function() {
                tabItems.forEach(tab => tab.classList.remove('active'));
                this.classList.add('active');
                
                // In a real application, you would show/hide different form sections here
                console.log('Tab clicked:', this.textContent.trim());
            });
        });
    }
    
    // Auto-calculate profit when prices change
    if (purchasePriceInput && sellingPriceInput) {
        purchasePriceInput.addEventListener('input', updateProfit);
        sellingPriceInput.addEventListener('input', updateProfit);
        
        // Add visual feedback
        purchasePriceInput.addEventListener('change', function() {
            addHighlightEffect(this);
        });
        
        sellingPriceInput.addEventListener('change', function() {
            addHighlightEffect(this);
        });
    }
    
    // Functions
    function handleImageUpload(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validate file type
        if (!file.type.match('image.*')) {
            alert('تکایە تەنها فایلی وێنە هەڵبژێرە');
            return;
        }
        
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('قەبارەی وێنە دەبێت کەمتر بێت لە 5 مێگابایت');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(event) {
            const imageUpload = document.querySelector('.image-upload');
            
            // Create image preview
            imageUpload.innerHTML = `
                <div style="position: relative; width: 100%; height: 200px; display: flex; align-items: center; justify-content: center;">
                    <img src="${event.target.result}" style="max-width: 100%; max-height: 200px; object-fit: contain;">
                    <button type="button" id="removeImage" style="position: absolute; top: 0; right: 0; background: ${lightGray}; border: none; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; cursor: pointer;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            // Re-add the file input
            const newInput = document.createElement('input');
            newInput.type = 'file';
            newInput.accept = 'image/*';
            newInput.id = 'productImage';
            newInput.style.display = 'none';
            imageUpload.appendChild(newInput);
            
            // Add event listener to new input
            newInput.addEventListener('change', handleImageUpload);
            
            // Add event listener to remove button
            const removeBtn = document.getElementById('removeImage');
            if (removeBtn) {
                removeBtn.addEventListener('click', resetImageUpload);
            }
        };
        
        reader.readAsDataURL(file);
    }
    
    function resetImageUpload() {
        const imageUpload = document.querySelector('.image-upload');
        if (!imageUpload) return;
        
        imageUpload.innerHTML = `
            <i class="fas fa-cloud-upload-alt"></i>
            <p>دەتوانیت وێنەیەک بۆ کاڵاکە هەڵبژێریت یان ڕایبکێشیت بۆ ئێرە</p>
            <input type="file" accept="image/*" id="productImage">
        `;
        
        // Re-add event listener
        const newInput = document.getElementById('productImage');
        if (newInput) {
            newInput.addEventListener('change', handleImageUpload);
        }
    }
    
    function updateProfit() {
        const purchasePrice = parseFloat(purchasePriceInput.value) || 0;
        const sellingPrice = parseFloat(sellingPriceInput.value) || 0;
        const profit = calculateProfit(purchasePrice, sellingPrice);
        
        // In a real application, you would update a profit field here
        console.log('Calculated profit:', profit);
        
        // Show visual feedback on profit calculation
        if (profit > 0) {
            // For a real implementation, you could update a profit display element
            // profitElement.classList.add('text-success');
        } else if (profit < 0) {
            // profitElement.classList.add('text-danger');
        }
    }
    
    function calculateProfit(purchasePrice, sellingPrice) {
        return sellingPrice - purchasePrice;
    }
    
    function addHighlightEffect(element) {
        // Add a CSS class that has the animation
        element.classList.add('highlight-field');
        
        // Remove it after the animation completes
        setTimeout(() => {
            element.classList.remove('highlight-field');
        }, 1000);
    }
    
    function showSuccessMessage() {
        // Create success alert
        const successAlert = document.createElement('div');
        successAlert.className = 'alert alert-success alert-dismissible fade show';
        successAlert.role = 'alert';
        successAlert.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            کاڵاکە بە سەرکەوتوویی زیاد کرا
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Insert before the form
        addProductForm.parentNode.insertBefore(successAlert, addProductForm);
        
        // Auto dismiss after 3 seconds
        setTimeout(() => {
            successAlert.classList.remove('show');
            setTimeout(() => successAlert.remove(), 300);
        }, 3000);
    }
    
    // Handle drag and drop for image upload
    const imageUploadArea = document.querySelector('.image-upload');
    if (imageUploadArea) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            imageUploadArea.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        ['dragenter', 'dragover'].forEach(eventName => {
            imageUploadArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            imageUploadArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight() {
            imageUploadArea.classList.add('highlight');
            imageUploadArea.style.borderColor = primaryColor;
            imageUploadArea.style.backgroundColor = primaryLight;
        }
        
        function unhighlight() {
            imageUploadArea.classList.remove('highlight');
            imageUploadArea.style.borderColor = borderColor;
            imageUploadArea.style.backgroundColor = lightGray;
        }
        
        imageUploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                const fileInput = document.getElementById('productImage');
                fileInput.files = files;
                
                // Trigger change event
                const event = new Event('change', { bubbles: true });
                fileInput.dispatchEvent(event);
            }
        }
    }
}); 