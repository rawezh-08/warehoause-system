/**
 * Add Product Page JavaScript
 * Handles tab navigation, form validation, image uploads, and other interactions
 */

// Global function for resetting image preview so it can be called from anywhere
function resetImagePreview() {
    const imagePreview = document.querySelector('.image-preview');
    if (imagePreview) {
        imagePreview.innerHTML = `
            <i class="fas fa-cloud-upload-alt"></i>
            <p>وێنە هەڵبژێرە</p>
        `;
    }
}

// Global function for tab switching
function switchToTab(tabId) {
    // Get tab items and content
    const tabItems = document.querySelectorAll('.tab-item');
    const tabContents = document.querySelectorAll('.tab-content');
    const tabIds = ['basic-info', 'price-info'];
    
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
    let currentTabIndex = tabIds.indexOf(tabId);

    // Update navigation buttons
    updateNavigationButtons(currentTabIndex, tabIds);
}

// Helper function for updating navigation buttons
function updateNavigationButtons(currentTabIndex, tabIds) {
    const isLastTab = currentTabIndex === tabIds.length - 1;
    const isFirstTab = currentTabIndex === 0;
    
    // Update previous buttons
    const allPrevButtons = ['prevTabBtn', 'prevTabBtn2'];
    allPrevButtons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.style.display = isFirstTab ? 'none' : 'block';
        }
    });

    // Update next buttons
    const allNextButtons = ['nextTabBtn'];
    allNextButtons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.style.display = isLastTab ? 'none' : 'block';
        }
    });

    // Update submit buttons
    const allSubmitButtons = ['submitBtn', 'submitBtn2'];
    allSubmitButtons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.style.display = isLastTab ? 'block' : 'none';
        }
    });
}

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
    const wrapper = document.getElementById('wrapper');
    
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
    if (toggleSidebarBtn && sidebar && wrapper) {
        toggleSidebarBtn.addEventListener('click', function(e) {
            e.preventDefault();
            wrapper.classList.toggle('sidebar-collapsed');
            document.body.classList.toggle('sidebar-active');
            
            let overlay = document.querySelector('.overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'overlay';
                document.body.appendChild(overlay);
                
                overlay.addEventListener('click', function() {
                    document.body.classList.remove('sidebar-active');
                    wrapper.classList.add('sidebar-collapsed');
                });
            }
        });
    }
    
    // Handle clicks outside sidebar to close it on mobile
    document.addEventListener('click', function(event) {
        // Skip if sidebar or toggle button are not found
        if (!sidebar || !toggleSidebarBtn || !wrapper) return;
        
        try {
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnToggleBtn = toggleSidebarBtn.contains(event.target);
            
            if (!isClickInsideSidebar && !isClickOnToggleBtn && window.innerWidth < 992) {
                // Remove both toggle classes to ensure consistency
                document.body.classList.remove('sidebar-active');
                wrapper.classList.add('sidebar-collapsed');
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
        productImageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Show loading indicator for the image
                const preview = document.querySelector('.image-preview');
                if (preview) {
                    preview.innerHTML = `
                        <div class="d-flex justify-content-center align-items-center flex-column">
                            <div class="spinner-border text-primary mb-2" role="status">
                                <span class="visually-hidden">چاوەڕێ بکە...</span>
                            </div>
                            <p>ئامادەکردنی وێنە...</p>
                        </div>
                    `;
                }

                // Check file size (20MB max)
                if (file.size > 20 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'قەبارەی وێنە دەبێت کەمتر بێت لە 20 مێگابایت'
                    });
                    this.value = '';
                    if (preview) {
                        preview.innerHTML = `
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>وێنە هەڵبژێرە</p>
                        `;
                    }
                    return;
                }

                // Check if file is an image
                if (!file.type.startsWith('image/')) {
                    Swal.fire({
                        icon: 'error',
                        title: 'هەڵە',
                        text: 'تەنها فایلی وێنە قبوڵ دەکرێت'
                    });
                    this.value = '';
                    if (preview) {
                        preview.innerHTML = `
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>وێنە هەڵبژێرە</p>
                        `;
                    }
                    return;
                }

                // Compress image client-side before uploading to reduce upload time
                compressImage(file, 1.5, 600) // Compress to max 1.5MB and max width/height 600px
                    .then(compressedFile => {
                        // Store the compressed file for form submission
                        this.compressedImage = compressedFile;
                        
                        // Preview the compressed image
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            if (preview) {
                                preview.innerHTML = `
                                    <img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px;">
                                    <p class="text-muted small mt-2">وێنە ئامادەیە بۆ ئەپلۆدکردن</p>
                                    <p class="text-muted small">قەبارە: ${Math.round(compressedFile.size / 1024)} KB</p>
                                `;
                            }
                        };
                        reader.readAsDataURL(compressedFile);
                    })
                    .catch(error => {
                        console.error('Error compressing image:', error);
                        // If compression fails, use the original file
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            if (preview) {
                                preview.innerHTML = `
                                    <img src="${e.target.result}" alt="Preview" style="max-width: 100%; max-height: 200px;">
                                    <p class="text-muted small mt-2">وێنەکە بەشێوەیەکی ئۆتۆماتیکی بچووک دەکرێتەوە ئەگەر پێویست بێت</p>
                                `;
                            }
                        };
                        reader.readAsDataURL(file);
                    });
            }
        });
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
            const piecesPerBoxInput = document.getElementById('piecesPerBox');
            const boxesPerSetInput = document.getElementById('boxesPerSet');

            if (unitQuantityContainer && piecesPerBoxContainer && boxesPerSetContainer) {
                // First hide all containers
                unitQuantityContainer.style.display = 'none';
                piecesPerBoxContainer.style.display = 'none';
                boxesPerSetContainer.style.display = 'none';

                // Remove required attribute from all fields initially
                if (piecesPerBoxInput) piecesPerBoxInput.removeAttribute('required');
                if (boxesPerSetInput) boxesPerSetInput.removeAttribute('required');

                // Show relevant containers based on unit selection
                switch (selectedUnit) {
                    case '1': // دانە
                        break;
                    case '2': // دانە و کارتۆن
                        unitQuantityContainer.style.display = 'flex';
                        piecesPerBoxContainer.style.display = 'block';
                        // Add required attribute for visible fields
                        if (piecesPerBoxInput) piecesPerBoxInput.setAttribute('required', 'required');
                        break;
                    case '3': // دانە و کارتۆن و سێت
                        unitQuantityContainer.style.display = 'flex';
                        piecesPerBoxContainer.style.display = 'block';
                        boxesPerSetContainer.style.display = 'block';
                        // Add required attribute for visible fields
                        if (piecesPerBoxInput) piecesPerBoxInput.setAttribute('required', 'required');
                        if (boxesPerSetInput) boxesPerSetInput.setAttribute('required', 'required');
                        break;
                }

                // Clear values when hiding
                if (selectedUnit === '1') {
                    if (piecesPerBoxInput) piecesPerBoxInput.value = '';
                    if (boxesPerSetInput) boxesPerSetInput.value = '';
                } else if (selectedUnit === '2') {
                    if (boxesPerSetInput) boxesPerSetInput.value = '';
                }
            }
        });
    }
    
    // زیادکردنی event listener بۆ دوگمەی هەڵبژاردنی وێنە
    uploadBtn.addEventListener('click', function() {
        productImageInput.click();
    });
    
    // Functions
    
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
    
    // Validate current tab
    function validateCurrentTab() {
        let isValid = true;
        const currentTabId = tabIds[currentTabIndex];
        
        // Basic info tab validation
        if (currentTabId === 'basic-info') {
            const productName = document.getElementById('productName').value;
            const productCode = document.getElementById('productCode').value;
            const categoryId = document.getElementById('category_id').value;
            const unitId = document.getElementById('unit_id').value;
            
            if (!productName) {
                showValidationError('productName', 'تکایە ناوی کاڵا بنووسە');
                isValid = false;
            } else {
                clearValidationError('productName');
            }
            
            if (!categoryId) {
                showValidationError('category_id', 'تکایە جۆری کاڵا هەڵبژێرە');
                isValid = false;
            } else {
                clearValidationError('category_id');
            }
            
            if (!productCode) {
                showValidationError('productCode', 'تکایە کۆدی کاڵا بنووسە');
                isValid = false;
            } else {
                clearValidationError('productCode');
            }
            
            if (!unitId) {
                showValidationError('unit_id', 'تکایە یەکەی کاڵا هەڵبژێرە');
                isValid = false;
            } else {
                clearValidationError('unit_id');
                
                // Validate unit quantities based on selected unit
                if (unitId === '2') { // دانە و کارتۆن
                    const piecesPerBoxEl = document.getElementById('piecesPerBox');
                    if (piecesPerBoxEl && piecesPerBoxEl.style.display !== 'none') {
                        const piecesPerBox = piecesPerBoxEl.value;
                        if (!piecesPerBox) {
                            showValidationError('piecesPerBox', 'تکایە ژمارەی دانە لە کارتۆن بنووسە');
                            isValid = false;
                        } else {
                            clearValidationError('piecesPerBox');
                        }
                    }
                } else if (unitId === '3') { // دانە و کارتۆن و سێت
                    const piecesPerBoxEl = document.getElementById('piecesPerBox');
                    const boxesPerSetEl = document.getElementById('boxesPerSet');
                    
                    if (piecesPerBoxEl && piecesPerBoxEl.style.display !== 'none') {
                        const piecesPerBox = piecesPerBoxEl.value;
                        if (!piecesPerBox) {
                            showValidationError('piecesPerBox', 'تکایە ژمارەی دانە لە کارتۆن بنووسە');
                            isValid = false;
                        } else {
                            clearValidationError('piecesPerBox');
                        }
                    }
                    
                    if (boxesPerSetEl && boxesPerSetEl.style.display !== 'none') {
                        const boxesPerSet = boxesPerSetEl.value;
                        if (!boxesPerSet) {
                            showValidationError('boxesPerSet', 'تکایە ژمارەی کارتۆن لە سێت بنووسە');
                            isValid = false;
                        } else {
                            clearValidationError('boxesPerSet');
                        }
                    }
                }
            }
        }
        
        // Price info tab validation
        else if (currentTabId === 'price-info') {
            const buyingPrice = document.getElementById('buyingPrice').value;
            const sellingPrice = document.getElementById('sellingPrice').value;
            const minQuantity = document.getElementById('min_quantity').value;
            const currentQuantity = document.getElementById('current_quantity').value;
            
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
            
            if (!minQuantity) {
                showValidationError('min_quantity', 'تکایە کەمترین بڕ بنووسە');
                isValid = false;
            } else {
                clearValidationError('min_quantity');
            }
            
            if (!currentQuantity) {
                showValidationError('current_quantity', 'تکایە بڕی بەردەست بنووسە');
                isValid = false;
            } else {
                clearValidationError('current_quantity');
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
        const formCard = document.querySelector('.card-body');
        if (formCard) {
            formCard.insertBefore(successAlert, formCard.firstChild);
            
            // Auto dismiss after 3 seconds
            setTimeout(() => {
                successAlert.classList.remove('show');
                setTimeout(() => successAlert.remove(), 300);
            }, 3000);
        }
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
    
    // Function to clean and format number inputs
    function cleanNumberInputs() {
        const numberInputs = document.querySelectorAll('input[type="number"]');
        numberInputs.forEach(input => {
            // Add input event listener for formatting
            input.addEventListener('input', function(e) {
                let value = e.target.value;
                
                // Remove any non-numeric characters except dots
                value = value.replace(/[^\d.]/g, '');
                
                // Ensure only one decimal point
                const parts = value.split('.');
                if (parts.length > 2) {
                    value = parts[0] + '.' + parts.slice(1).join('');
                }
                
                // Update the input value
                e.target.value = value;
            });
        });
    }

    // Function to format numbers with commas
    function formatNumber(input) {
        // Remove existing commas and get the value
        let value = input.value.replace(/,/g, '');
        
        // Check if it's a valid number (including decimals)
        if (!isNaN(value) && value.trim() !== '') {
            // Split number into integer and decimal parts
            let [integerPart, decimalPart] = value.split('.');
            
            // Format integer part with commas
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            
            // Combine with decimal part if it exists
            input.value = decimalPart ? `${integerPart}.${decimalPart}` : integerPart;
        }
    }
    
    // Function to handle unit type changes
    function handleUnitTypeChange() {
        const unitSelect = document.getElementById('unit_id');
        const piecesPerBoxContainer = document.getElementById('piecesPerBoxContainer');
        const boxesPerSetContainer = document.getElementById('boxesPerSetContainer');
        
        if (!unitSelect) return;
        
        const selectedUnit = unitSelect.value;
        
        // Show/hide containers based on unit type
        if (selectedUnit === '2') { // Box
            piecesPerBoxContainer.style.display = 'block';
            boxesPerSetContainer.style.display = 'none';
        } else if (selectedUnit === '3') { // Set
            piecesPerBoxContainer.style.display = 'block';
            boxesPerSetContainer.style.display = 'block';
        } else { // Piece
            piecesPerBoxContainer.style.display = 'none';
            boxesPerSetContainer.style.display = 'none';
        }
    }

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize number formatting
        const numberInputs = [
            'buyingPrice',
            'sellingPrice',
            'selling_price_wholesale',
            'piecesPerBox',
            'boxesPerSet',
            'min_quantity',
            'current_quantity'
        ];

        numberInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.setAttribute('type', 'text');
                input.addEventListener('input', function() {
                    formatNumber(this);
                });
            }
        });

        // Initialize unit type handling
        const unitSelect = document.getElementById('unit_id');
        if (unitSelect) {
            unitSelect.addEventListener('change', handleUnitTypeChange);
            // Call once on page load to set initial state
            handleUnitTypeChange();
        }

        // Initialize tab navigation
        handleTabNavigation();

        // DO NOT initialize form submission here - it's already handled in the main code
        // handleFormSubmission();

        // Initialize refresh button
        const refreshButton = document.querySelector('.refresh-products');
        if (refreshButton) {
            refreshButton.addEventListener('click', updateLatestProducts);
        }
    });

    // Form submission handler
    if (addProductForm) {
        let isSubmitting = false; // Flag to prevent double submission
        
        addProductForm.addEventListener('submit', async function(e) {
            e.preventDefault(); // Prevent the form from submitting normally
            
            // Prevent double submission
            if (isSubmitting) {
                return;
            }
            
            // Validate the form with browser's built-in validation
            if (!this.checkValidity()) {
                // Show custom error message
                Swal.fire({
                    icon: 'error',
                    title: 'هەڵە',
                    text: 'تکایە هەموو خانەکان پڕ بکەوە',
                    confirmButtonText: 'باشە'
                });
                return false;
            }
            
            isSubmitting = true;
            
            // Disable submit buttons to prevent double submission
            const submitButtons = this.querySelectorAll('button[type="submit"]');
            submitButtons.forEach(button => {
                button.disabled = true;
            });
            
            // Validate all tabs before submission
            if (!validateAllTabs()) {
                isSubmitting = false;
                submitButtons.forEach(button => {
                    button.disabled = false;
                });
                return;
            }
            
            // Clean number inputs (remove commas)
            cleanNumberInputs();
            
            // Set selling_price_wholesale to selling_price_single if empty
            const sellingPriceWholesale = document.getElementById('selling_price_wholesale');
            if (sellingPriceWholesale && !sellingPriceWholesale.value) {
                sellingPriceWholesale.value = document.getElementById('sellingPrice').value;
            }
            
            // Create FormData object
            const formData = new FormData(this);
            
            // Check if we have a compressed image and use it instead
            const fileInput = document.getElementById('productImage');
            if (fileInput && fileInput.files.length > 0 && fileInput.compressedImage) {
                // Replace the file with the compressed version
                formData.delete('image');
                formData.append('image', fileInput.compressedImage);
            }
            
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
                    const errorText = await response.text();
                    console.error('Server response:', errorText);
                    throw new Error(`هەڵەیەک ڕوویدا لە کاتی پەیوەندیکردن بە سێرڤەرەوە`);
                }
                
                let data;
                try {
                    data = await response.json();
                } catch (parseError) {
                    console.error('Error parsing JSON response:', await response.text());
                    throw new Error('هەڵەیەک ڕوویدا لە کاتی وەرگرتنی وەڵامەکە');
                }
                
                if (data.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'سەرکەوتوو',
                        text: data.message || 'کاڵاکە بە سەرکەوتوویی زیاد کرا',
                        confirmButtonText: 'باشە'
                    });
                    
                    // Show success message
                    showSuccessMessage();
                    
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
            } finally {
                // Reset submission flag and enable buttons
                isSubmitting = false;
                submitButtons.forEach(button => {
                    button.disabled = false;
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
                    // Extract just the filename from the full path
                    const imagePath = product.image ? product.image.split('/').pop() : null;
                    const imageUrl = imagePath ? `../../uploads/products/${imagePath}` : null;
                    
                    html += `
                        <li class="list-group-item">
                            <div class="d-flex align-items-center">
                                <div class="product-icon me-3">
                                    ${imageUrl 
                                        ? `<img src="${imageUrl}" alt="${product.name}" class="product-thumbnail">` 
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

// Function to handle unit type changes
function handleUnitTypeChange() {
    const unitSelect = document.getElementById('unit_id');
    const piecesPerBoxContainer = document.getElementById('piecesPerBoxContainer');
    const boxesPerSetContainer = document.getElementById('boxesPerSetContainer');
    
    if (!unitSelect) return;
    
    const selectedUnit = unitSelect.value;
    
    // Show/hide containers based on unit type
    if (selectedUnit === '2') { // Box
        piecesPerBoxContainer.style.display = 'block';
        boxesPerSetContainer.style.display = 'none';
    } else if (selectedUnit === '3') { // Set
        piecesPerBoxContainer.style.display = 'block';
        boxesPerSetContainer.style.display = 'block';
    } else { // Piece
        piecesPerBoxContainer.style.display = 'none';
        boxesPerSetContainer.style.display = 'none';
    }
}

// Function to handle tab navigation
function handleTabNavigation() {
    const basicInfoTab = document.querySelector('[data-tab="basic-info"]');
    const priceInfoTab = document.querySelector('[data-tab="price-info"]');
    const basicInfoContent = document.getElementById('basic-info-content');
    const priceInfoContent = document.getElementById('price-info-content');
    const prevTabBtn = document.getElementById('prevTabBtn');
    const nextTabBtn = document.getElementById('nextTabBtn');
    const submitBtn = document.getElementById('submitBtn');

    if (basicInfoTab && priceInfoTab && basicInfoContent && priceInfoContent) {
        basicInfoTab.addEventListener('click', function() {
            basicInfoTab.classList.add('active');
            priceInfoTab.classList.remove('active');
            basicInfoContent.style.display = 'block';
            priceInfoContent.style.display = 'none';
            prevTabBtn.style.display = 'none';
            nextTabBtn.style.display = 'block';
            submitBtn.style.display = 'none';
        });

        priceInfoTab.addEventListener('click', function() {
            priceInfoTab.classList.add('active');
            basicInfoTab.classList.remove('active');
            priceInfoContent.style.display = 'block';
            basicInfoContent.style.display = 'none';
            prevTabBtn.style.display = 'block';
            nextTabBtn.style.display = 'none';
            submitBtn.style.display = 'block';
        });
    }
}

// Function to handle form submission
function handleFormSubmission() {
    console.log("Form submission handler initialization is disabled to prevent double submission");
    // This function is not used anymore to prevent double submission
    // We're keeping it empty as a placeholder in case other code references it
}

// Add this function for client-side image compression
function compressImage(file, maxSizeMB, maxDimension) {
    return new Promise((resolve, reject) => {
        // Create a FileReader to read the file
        const reader = new FileReader();
        
        // Set up FileReader onload handler
        reader.onload = function(readerEvent) {
            // Create an HTMLImageElement to get image dimensions
            const img = new Image();
            
            img.onload = function() {
                // Calculate new dimensions while maintaining aspect ratio
                let width = img.width;
                let height = img.height;
                
                if (width > height && width > maxDimension) {
                    height = Math.round(height * maxDimension / width);
                    width = maxDimension;
                } else if (height > maxDimension) {
                    width = Math.round(width * maxDimension / height);
                    height = maxDimension;
                }
                
                // Create a canvas element to compress the image
                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                
                // Draw the image on the canvas
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                // Start with high quality
                let quality = 0.8;
                let compressedDataUrl;
                let compressedBlob;
                
                // Function to check if we've reached target size
                const checkSize = (dataUrl) => {
                    // Convert data URL to Blob
                    const byteString = atob(dataUrl.split(',')[1]);
                    const mimeType = dataUrl.split(',')[0].split(':')[1].split(';')[0];
                    const ab = new ArrayBuffer(byteString.length);
                    const ia = new Uint8Array(ab);
                    for (let i = 0; i < byteString.length; i++) {
                        ia[i] = byteString.charCodeAt(i);
                    }
                    const blob = new Blob([ab], { type: mimeType });
                    
                    // Check size and proceed accordingly
                    if (blob.size <= maxSizeMB * 1024 * 1024) {
                        // Size is good, create a File object from the blob
                        compressedBlob = blob;
                        const fileName = file.name.split('.')[0] + '.jpg';
                        const compressedFile = new File([blob], fileName, { type: 'image/jpeg' });
                        resolve(compressedFile);
                    } else if (quality > 0.1) {
                        // If file is still too large, reduce quality and try again
                        quality -= 0.1;
                        compressedDataUrl = canvas.toDataURL('image/jpeg', quality);
                        checkSize(compressedDataUrl);
                    } else {
                        // Can't compress enough, return the best we can do
                        const fileName = file.name.split('.')[0] + '.jpg';
                        const compressedFile = new File([compressedBlob], fileName, { type: 'image/jpeg' });
                        resolve(compressedFile);
                    }
                };
                
                // Start compression
                compressedDataUrl = canvas.toDataURL('image/jpeg', quality);
                checkSize(compressedDataUrl);
            };
            
            // Set the HTMLImageElement source to FileReader result
            img.src = readerEvent.target.result;
        };
        
        // Handle FileReader errors
        reader.onerror = function() {
            reject(new Error('Failed to read file'));
        };
        
        // Read the file as a data URL (base64 encoded string)
        reader.readAsDataURL(file);
    });
} 