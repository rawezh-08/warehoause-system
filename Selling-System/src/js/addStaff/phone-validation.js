// Function to check if a phone number exists
async function checkPhoneNumber(phoneInput) {
    const phone = phoneInput.value.trim();
    
    // Skip validation if phone is empty
    if (!phone) return true;
    
    try {
        const response = await fetch('../../process/check_phone.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `phone=${encodeURIComponent(phone)}`
        });
        
        const data = await response.json();
        
        if (data.exists) {
            // Show error message
            Swal.fire({
                title: 'هەڵە',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'باشە'
            });
            
            // Clear the input
            phoneInput.value = '';
            phoneInput.focus();
            return false;
        }
        
        return true;
    } catch (error) {
        console.error('Error checking phone number:', error);
        return true; // Allow submission if there's an error
    }
}

// Add event listeners to all phone input fields
document.addEventListener('DOMContentLoaded', function() {
    // Customer form phone inputs
    const customerPhone1 = document.getElementById('phone1');
    const customerPhone2 = document.getElementById('phone2');
    
    // Supplier form phone inputs
    const supplierPhone = document.getElementById('supplierPhone');
    const supplierPhone2 = document.getElementById('supplierPhone2');
    
    // Business partner form phone inputs
    const partnerPhone1 = document.getElementById('partnerPhone1');
    const partnerPhone2 = document.getElementById('partnerPhone2');
    
    // Add blur event listeners to all phone inputs
    [customerPhone1, customerPhone2, supplierPhone, supplierPhone2, partnerPhone1, partnerPhone2].forEach(input => {
        if (input) {
            input.addEventListener('blur', async function() {
                if (this.value.trim()) {
                    await checkPhoneNumber(this);
                }
            });
        }
    });
    
    // Add form submit validation
    const forms = ['customerForm', 'supplierForm', 'businessPartnerForm'];
    
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Get all phone inputs in the form
                const phoneInputs = form.querySelectorAll('input[type="tel"]');
                let isValid = true;
                
                // Check each phone number
                for (const input of phoneInputs) {
                    if (input.value.trim()) {
                        const isUnique = await checkPhoneNumber(input);
                        if (!isUnique) {
                            isValid = false;
                            break;
                        }
                    }
                }
                
                // If all phone numbers are valid, submit the form
                if (isValid) {
                    form.submit();
                }
            });
        }
    });
}); 