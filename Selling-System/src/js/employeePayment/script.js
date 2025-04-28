// Handle employee payment form submission
document.getElementById('addEmployeePaymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this);
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> زیادکردن...';
    submitBtn.disabled = true;
    
    // Send data to server
    fetch('../../process/add_employee_payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            Swal.fire({
                title: 'سەرکەوتوو',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'باشە'
            }).then(() => {
                // Reset form
                this.reset();
            });
        } else {
            // Show error message
            Swal.fire({
                title: 'هەڵە',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'باشە'
            });
        }
    })
    .catch(error => {
        // Show error message
        Swal.fire({
            title: 'هەڵە',
            text: 'هەڵەیەک ڕوویدا لە کاتی ناردنی زانیاریەکان',
            icon: 'error',
            confirmButtonText: 'باشە'
        });
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    });
});

// Handle withdrawal form submission
document.getElementById('addWithdrawalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this);
    
    // Show loading state
    const submitBtn = document.getElementById('submitWithdrawalBtn');
    const originalBtnText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> زیادکردن...';
    submitBtn.disabled = true;
    
    // Send data to server
    fetch('../../process/add_withdrawal.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            Swal.fire({
                title: 'سەرکەوتوو',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'باشە'
            }).then(() => {
                // Reset form
                this.reset();
            });
        } else {
            // Show error message
            Swal.fire({
                title: 'هەڵە',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'باشە'
            });
        }
    })
    .catch(error => {
        // Show error message
        Swal.fire({
            title: 'هەڵە',
            text: 'هەڵەیەک ڕوویدا لە کاتی ناردنی زانیاریەکان',
            icon: 'error',
            confirmButtonText: 'باشە'
        });
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
    });
}); 