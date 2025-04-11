/**
 * Add Product Page JavaScript
 * Handles tab navigation, form validation, image uploads, and other interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // DOM Elements - Add null checks
    const sidebar = document.getElementById('sidebar-container');
    const toggleSidebarBtn = document.querySelector('.sidebar-toggle');
    const addProductForm = document.getElementById('addProductForm');
    const productImageInput = document.getElementById('productImage');
    const tabItems = document.querySelectorAll('.tab-item');
    const tabContents = document.querySelectorAll('.tab-content');
    const buyingPriceInput = document.getElementById('buyingPrice');
    const sellingPriceInput = document.getElementById('sellingPrice');
    const nextTabBtn = document.getElementById('nextTabBtn');
    const prevTabBtn = document.getElementById('prevTabBtn');
    const prevTabBtn2 = document.getElementById('prevTabBtn2');
    const submitBtn = document.getElementById('submitBtn');
    const submitBtn2 = document.getElementById('submitBtn2');
    const generateCodeBtn = document.getElementById('generateCode');
    const generateBarcodeBtn = document.getElementById('generateBarcode');
    const productCodeInput = document.getElementById('productCode');
    const barCodeInput = document.getElementById('barCode');
    const unitSelect = document.getElementById('unit_id');
    const unitQuantityContainer = document.getElementById('unitQuantityContainer');
    const piecesPerBoxContainer = document.getElementById('piecesPerBoxContainer');
    const boxesPerSetContainer = document.getElementById('boxesPerSetContainer');
    const imagePreview = document.querySelector('.image-preview');
    const uploadBtn = document.getElementById('uploadBtn');
    
    // Get CSS variables for consistent styling in JS
    const style = getComputedStyle(document.documentElement);
    const primaryColor = style.getPropertyValue('--primary-color').trim();
    const primaryLight = style.getPropertyValue('--primary-light').trim();
    const lightGray = style.getPropertyValue('--light-gray').trim();
    const borderColor = style.getPropertyValue('--border-color').trim();
    
    // Current tab tracking
    let currentTabIndex = 0;
    const tabIds = ['basic-info', 'price-info'];
    
    // Toggle sidebar on mobile
    if (toggleSidebarBtn && sidebar) {
        toggleSidebarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('wrapper').classList.toggle('sidebar-collapsed');
            document.body.classList.toggle('sidebar-active');
            
            let overlay = document.querySelector('.overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'overlay';
                document.body.appendChild(overlay);
                
                overlay.addEventListener('click', function() {
                    document.body.classList.remove('sidebar-active');
                    document.getElementById('wrapper').classList.add('sidebar-collapsed');
                });
            }
        });
    }
    
    // Handle clicks outside sidebar to close it on mobile
    document.addEventListener('click', function(event) {
        // Skip if sidebar or toggle button are not found
        if (!sidebar || !toggleSidebarBtn) return;
        
        try {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggleBtn = toggleSidebarBtn.contains(event.target);
            
            if (!isClickInsideSidebar && !isClickOnToggleBtn && window.innerWidth < 992) {
                // Remove both toggle classes to ensure consistency
                document.body.classList.remove('sidebar-active');
                document.getElementById('wrapper').classList.add('sidebar-collapsed');
            }
        } catch (error) {
            console.error('Error in sidebar click handler:', error);
        }
    });
    
    // Tab navigation - clicking on tab headers
    if (tabItems.length > 0) {
        tabItems.forEach(item => {
            item.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                switchToTab(tabId);
            });
        });
    }
    
    // Navigation buttons event listeners
    const allPrevButtons = ['prevTabBtn', 'prevTabBtn2'];
    const allNextButtons = ['nextTabBtn'];
    const allSubmitButtons = ['submitBtn', 'submitBtn2'];

    // Add event listeners for all previous buttons
    allPrevButtons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.addEventListener('click', function() {
                if (currentTabIndex > 0) {
                    goToPrevTab();
                }
            });
        }
    });

    // Add event listeners for all next buttons
    allNextButtons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.addEventListener('click', function() {
                if (currentTabIndex < tabIds.length - 1) {
                    if (validateCurrentTab()) {
                        goToNextTab();
                    }
                }
            });
        }
    });

    // Add event listeners for all submit buttons
    allSubmitButtons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                if (validateAllTabs()) {
                    document.getElementById('addProductForm').dispatchEvent(new Event('submit'));
                }
            });
        }
    });
    
    // Image upload functionality
    if (productImageInput) {
        productImageInput.addEventListener('change', handleImageUpload);
    }
    
    // Auto-calculate profit when prices change
    if (buyingPriceInput && sellingPriceInput) {
        buyingPriceInput.addEventListener('input', updateProfit);
        sellingPriceInput.addEventListener('input', updateProfit);
        
        // Add visual feedback
        buyingPriceInput.addEventListener('change', function() {
            addHighlightEffect(this);
        });
        
        sellingPriceInput.addEventListener('change', function() {
            addHighlightEffect(this);
        });
    }
    
    // Generate product code
    if (generateCodeBtn) {
        generateCodeBtn.addEventListener('click', function() {
            // Generate a code with 'A' prefix and 3 digits (A001, A002, etc.)
            let randomNum = Math.floor(Math.random() * 999) + 1;
            // Pad with leading zeros to make it 3 digits
            let formattedNum = randomNum.toString().padStart(3, '0');
            // Create the code in format A001
            const code = `A${formattedNum}`;
            
            if (productCodeInput) {
                productCodeInput.value = code;
            }
        });
    }

    // Generate barcode
    if (generateBarcodeBtn && barCodeInput) {
        generateBarcodeBtn.addEventListener('click', function() {
            const timestamp = Date.now().toString();
            barCodeInput.value = timestamp;
        });
    }
    
    // Unit selection handling
    if (unitSelect) {
        unitSelect.addEventListener('change', function() {
            const selectedUnit = this.value;
            const unitQuantityContainer = document.getElementById('unitQuantityContainer');
            const piecesPerBoxContainer = document.getElementById('piecesPerBoxContainer');
            const boxesPerSetContainer = document.getElementById('boxesPerSetContainer');

            if (unitQuantityContainer && piecesPerBoxContainer && boxesPerSetContainer) {
                // First hide all containers
                unitQuantityContainer.style.display = 'none';
                piecesPerBoxContainer.style.display = 'none';
                boxesPerSetContainer.style.display = 'none';

                // Show relevant containers based on unit selection
                switch (selectedUnit) {
                    case '1': // دانە
                        break;
                    case '2': // دانە و کارتۆن
                        unitQuantityContainer.style.display = 'flex';
                        piecesPerBoxContainer.style.display = 'block';
                        break;
                    case '3': // دانە و کارتۆن و سێت
                        unitQuantityContainer.style.display = 'flex';
                        piecesPerBoxContainer.style.display = 'block';
                        boxesPerSetContainer.style.display = 'block';
                        break;
                }

                // Clear values when hiding
                if (selectedUnit === '1') {
                    document.getElementById('piecesPerBox').value = '';
                    document.getElementById('boxesPerSet').value = '';
                } else if (selectedUnit === '2') {
                    document.getElementById('boxesPerSet').value = '';
                }
            }
        });
    }
    
    // زیادکردنی event listener بۆ دوگمەی هەڵبژاردنی وێنە
    uploadBtn.addEventListener('click', function() {
        productImageInput.click();
    });
    
    // Functions
    
    // Switch to a specific tab
    function switchToTab(tabId) {
        // Update tab items
        tabItems.forEach(item => {
            if (item.getAttribute('data-tab') === tabId) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });

        // Update tab contents
        tabContents.forEach(content => {
            if (content.id === `${tabId}-content`) {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        });

        // Update current tab index
        currentTabIndex = tabIds.indexOf(tabId);

        // Update navigation buttons
        updateNavigationButtons();
    }
    
    // Go to next tab
    function goToNextTab() {
        if (currentTabIndex < tabIds.length - 1) {
            switchToTab(tabIds[currentTabIndex + 1]);
        }
    }
    
    // Go to previous tab
    function goToPrevTab() {
        if (currentTabIndex > 0) {
            switchToTab(tabIds[currentTabIndex - 1]);
        }
    }
    
    // Update navigation buttons
    function updateNavigationButtons() {
        const isLastTab = currentTabIndex === tabIds.length - 1;
        const isFirstTab = currentTabIndex === 0;

        // Update previous buttons
        allPrevButtons.forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.style.display = isFirstTab ? 'none' : 'block';
            }
        });

        // Update next buttons
        allNextButtons.forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.style.display = isLastTab ? 'none' : 'block';
            }
        });

        // Update submit buttons
        allSubmitButtons.forEach(btnId => {
            const btn = document.getElementById(btnId);
            if (btn) {
                btn.style.display = isLastTab ? 'block' : 'none';
            }
        });
    }
    
    // Validate current tab
    function validateCurrentTab() {
        let isValid = true;
        const currentTabId = tabIds[currentTabIndex];
        
        // Basic info tab validation
        if (currentTabId === 'basic-info') {
            const productName = document.getElementById('productName').value;
            const productCode = document.getElementById('productCode').value;
            const unit = document.getElementById('unit_id').value;
            
            if (!productName) {
                showValidationError('productName', 'تکایە ناوی کاڵا بنووسە');
                isValid = false;
            } else {
                clearValidationError('productName');
            }
            
            if (!productCode) {
                showValidationError('productCode', 'تکایە کۆدی کاڵا بنووسە');
                isValid = false;
            } else {
                clearValidationError('productCode');
            }
            
            // Validate unit quantities based on selected unit
            if (unit === 'box') {
                const piecesPerBox = document.getElementById('piecesPerBox').value;
                if (!piecesPerBox) {
                    showValidationError('piecesPerBox', 'تکایە ژمارەی دانە لە کارتۆن بنووسە');
                    isValid = false;
                } else {
                    clearValidationError('piecesPerBox');
                }
            } else if (unit === 'set') {
                const piecesPerBox = document.getElementById('piecesPerBox').value;
                const boxesPerSet = document.getElementById('boxesPerSet').value;
                
                if (!piecesPerBox) {
                    showValidationError('piecesPerBox', 'تکایە ژمارەی دانە لە کارتۆن بنووسە');
                    isValid = false;
                } else {
                    clearValidationError('piecesPerBox');
                }
                
                if (!boxesPerSet) {
                    showValidationError('boxesPerSet', 'تکایە ژمارەی کارتۆن لە سێت بنووسە');
                    isValid = false;
                } else {
                    clearValidationError('boxesPerSet');
                }
            }
        }
        
        // Price info tab validation
        else if (currentTabId === 'price-info') {
            const buyingPrice = document.getElementById('buyingPrice').value;
            const sellingPrice = document.getElementById('sellingPrice').value;
            
            if (!buyingPrice) {
                showValidationError('buyingPrice', 'تکایە نرخی کڕین بنووسە');
                isValid = false;
            } else {
                clearValidationError('buyingPrice');
            }
            
            if (!sellingPrice) {
                showValidationError('sellingPrice', 'تکایە نرخی فرۆشتن بنووسە');
                isValid = false;
            } else {
                clearValidationError('sellingPrice');
            }
        }
        
        return isValid;
    }
    
    // Validate all tabs
    function validateAllTabs() {
        // Store current tab
        const originalTabIndex = currentTabIndex;
        
        // Check each tab
        let isValid = true;
        for (let i = 0; i < tabIds.length; i++) {
            currentTabIndex = i;
            if (!validateCurrentTab()) {
                isValid = false;
                switchToTab(tabIds[i]); // Switch to the first invalid tab
                break;
            }
        }
        
        // If all tabs are valid, restore original tab
        if (isValid) {
            currentTabIndex = originalTabIndex;
        }
        
        return isValid;
    }
    
    // Show validation error for a field
    function showValidationError(fieldId, message) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        // Add error class
        field.classList.add('is-invalid');
        
        // Check if error message element already exists
        let errorElement = field.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('invalid-feedback')) {
            // Create error message element
            errorElement = document.createElement('div');
            errorElement.classList.add('invalid-feedback');
            field.parentNode.insertBefore(errorElement, field.nextSibling);
        }
        
        // Set error message
        errorElement.textContent = message;
        
        // Scroll to error field
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    // Clear validation error for a field
    function clearValidationError(fieldId) {
        const field = document.getElementById(fieldId);
        if (!field) return;
        
        // Remove error class
        field.classList.remove('is-invalid');
        
        // Remove error message element if it exists
        const errorElement = field.nextElementSibling;
        if (errorElement && errorElement.classList.contains('invalid-feedback')) {
            errorElement.remove();
        }
    }
    
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
            imagePreview.innerHTML = `
                <img src="${event.target.result}" style="max-width: 100%; max-height: 200px; object-fit: contain;">
                <button type="button" class="btn btn-sm btn-danger mt-2" id="removeImage">
                    <i class="fas fa-trash"></i> سڕینەوەی وێنە
                </button>
            `;

            // زیادکردنی event listener بۆ دوگمەی سڕینەوە
            document.getElementById('removeImage').addEventListener('click', function() {
                productImageInput.value = '';
                imagePreview.innerHTML = `
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>وێنە هەڵبژێرە</p>
                `;
            });
        };
        
        reader.readAsDataURL(file);
    }
    
    function resetImageUpload() {
        imagePreview.innerHTML = `
            <i class="fas fa-cloud-upload-alt"></i>
            <p>وێنە هەڵبژێرە</p>
        `;
    }
    
    function updateProfit() {
        const buyingPrice = parseFloat(buyingPriceInput.value) || 0;
        const sellingPrice = parseFloat(sellingPriceInput.value) || 0;
        
        // Calculate profit amount
        const profitAmount = sellingPrice - buyingPrice;
        
        // Calculate profit margin as percentage
        let profitMargin = 0;
        if (buyingPrice > 0) {
            profitMargin = (profitAmount / buyingPrice) * 100;
        }
        
        // Update profit fields if they exist
        const profitAmountField = document.getElementById('profitAmount');
        const profitMarginField = document.getElementById('profitMargin');
        
        if (profitAmountField) {
            profitAmountField.value = profitAmount.toFixed(2);
            addHighlightEffect(profitAmountField);
        }
        
        if (profitMarginField) {
            profitMarginField.value = profitMargin.toFixed(2);
            addHighlightEffect(profitMarginField);
        }
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
        
        // Insert at the top of the form
        const formCard = addProductForm.closest('.card-body');
        formCard.insertBefore(successAlert, formCard.firstChild);
        
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
    
    // زیادکردنی فانکشن بۆ پاککردنەوەی کۆماکان لە ژمارەکان
    function cleanNumberInputs() {
        const numberFields = [
            'buyingPrice',
            'sellingPrice',
            'selling_price_wholesale',
            'piecesPerBox',
            'boxesPerSet',
            'min_quantity',
            'initialQuantity'
        ];
        
        numberFields.forEach(field => {
            const input = document.getElementById(field);
            if (input && input.value) {
                // لابردنی کۆماکان
                input.value = input.value.replace(/,/g, '');
                console.log(`Cleaned ${field}: ${input.value}`);
            }
        });
        
        // زیادکردنی پشتڕاستکردنەوە بۆ لەبار هەموو بەهاکان
        return true;
    }
    
    // Initialize by making sure we're on the correct tab and buttons are set up
    switchToTab('basic-info');

    // Form submission handler
    if (addProductForm) {
        addProductForm.addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent the form from submitting normally
            
            // Validate all tabs before submission
            if (!validateAllTabs()) {
                return;
            }
            
            // Clean number inputs (remove commas)
            cleanNumberInputs();
            
            // Create FormData object
            const formData = new FormData(this);
            
            try {
                // Show loading indicator
                Swal.fire({
                    title: 'تکایە چاوەڕێ بکە...',
                    text: 'زیادکردنی کاڵا بەردەوامە',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Submit form using fetch
                const response = await fetch('../../process/add_product.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) {
                    throw new Error(`Server responded with status: ${response.status}`);
                }
                
                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    console.error('Error parsing JSON response:', await response.text());
                    throw new Error('Invalid JSON response from server');
                }
                
                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: data.message || 'کاڵاکە بە سەرکەوتوویی زیاد کرا',
                        confirmButtonText: 'باشە'
                    });
                    
                    // Reset form and image preview
                    this.reset();
                    resetImagePreview();
                    
                    // Reset to first tab
                    switchToTab('basic-info');
                    
                    // Refresh the latest products list
                    updateLatestProducts();
                    
                    // Clear any validation errors
                    clearAllValidationErrors();
                    
                    // Reset unit quantity fields
                    const unitQuantityContainer = document.getElementById('unitQuantityContainer');
                    if (unitQuantityContainer) {
                        unitQuantityContainer.style.display = 'none';
                    }
                    
                    // Reset select elements
                    const selects = this.querySelectorAll('select');
                    selects.forEach(select => {
                        select.value = '';
                    });
                    
                } else {
                    throw new Error(data.message || 'هەڵەیەک ڕوویدا لە کاتی زیادکردنی کاڵا');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: error.message || 'هەڵەیەک ڕوویدا لە کاتی زیادکردنی کاڵا',
                    confirmButtonText: 'باشە'
                });
            }
        });
    }

    // Function to clear all validation errors
    function clearAllValidationErrors() {
        const invalidFields = document.querySelectorAll('.is-invalid');
        invalidFields.forEach(field => {
            field.classList.remove('is-invalid');
            const errorElement = field.nextElementSibling;
            if (errorElement && errorElement.classList.contains('invalid-feedback')) {
                errorElement.remove();
            }
        });
    }

    // Reset image preview
    function resetImagePreview() {
        const imagePreview = document.querySelector('.image-preview');
        if (imagePreview) {
            imagePreview.innerHTML = `
                <i class="fas fa-cloud-upload-alt"></i>
                <p>وێنە هەڵبژێرە</p>
            `;
        }
    }
});

async function updateLatestProducts() {
    try {
        const response = await fetch('../../process/get_latest_products.php');
        if (!response.ok) {
            throw new Error(`Server responded with status: ${response.status}`);
        }
        
        let data;
        try {
            data = await response.json();
        } catch (parseError) {
            console.error('Error parsing JSON response:', await response.text());
            throw new Error('Invalid JSON response from server');
        }
        
        const productsList = document.querySelector('.list-group');
        
        if (productsList) {
            if (data && data.length > 0) {
                let html = '';
                data.forEach(product => {
                    html += `
                        <li class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="product-icon me-3">
                                    ${product.image 
                                        ? `<img src="${product.image}" alt="${product.name}" class="product-thumbnail">` 
                                        : '<i class="fas fa-box"></i>'}
                                </div>
                                <div class="product-info flex-grow-1">
                                    <h6 class="mb-0">${product.name}</h6>
                                    <small class="text-muted">
                                        کۆد: ${product.code} | 
                                        زیادکرا: ${new Date(product.created_at).toLocaleString('ku-IQ')}
                                    </small>
                                </div>
                                <span class="badge bg-success">نوێ</span>
                            </div>
                        </li>
                    `;
                });
                productsList.innerHTML = html;
            } else {
                productsList.innerHTML = `
                    <li class="list-group-item text-center text-muted">
                        هیچ کاڵایەک نەدۆزرایەوە
                    </li>
                `;
            }
        }
    } catch (error) {
        console.error('Error updating latest products:', error);
        const productsList = document.querySelector('.list-group');
        if (productsList) {
            productsList.innerHTML = `
                <li class="list-group-item text-center text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    هەڵە لە وەرگرتنی زانیاریەکان: ${error.message}
                </li>
            `;
        }
    }
}

// Format numbers with commas
function formatNumber(input) {
    // Remove existing commas
    let value = input.value.replace(/,/g, '');
    
    // Only allow numbers
    value = value.replace(/[^\d]/g, '');
    
    // Add commas
    value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    
    // Update input value
    input.value = value;
} 