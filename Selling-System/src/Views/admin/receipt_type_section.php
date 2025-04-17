<!-- Receipt Type Section -->
<style>
    .receipt-section {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .receipt-type-container {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .receipt-type-header {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .receipt-type-header h5 {
        font-size: 18px;
        color: var(--dark-color);
        margin: 0;
        font-weight: 600;
    }

    .receipt-types {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .receipt-type-btn {
        padding: 10px 25px;
        border: 2px solid var(--primary-color);
        background: transparent;
        color: var(--primary-color);
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .receipt-type-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 0;
        height: 100%;
        background: var(--primary-color);
        transition: width 0.3s ease;
        z-index: 0;
    }

    .receipt-type-btn span {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .receipt-type-btn:hover::before {
        width: 100%;
    }

    .receipt-type-btn:hover {
        color: white;
    }

    .receipt-type-btn:hover i {
        transform: scale(1.1);
    }

    .receipt-type-btn.active {
        background: var(--primary-color);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(115, 128, 236, 0.3);
    }

    /* Icon styles */
    .receipt-type-btn i {
        font-size: 16px;
        transition: transform 0.3s ease;
    }

    /* Responsive styles */
    @media (max-width: 768px) {
        .receipt-types {
            width: 100%;
        }
        
        .receipt-type-btn {
            flex: 1;
            min-width: calc(33.333% - 10px);
            text-align: center;
            padding: 12px 15px;
            font-size: 14px;
        }
    }

    @media (max-width: 576px) {
        .receipt-type-btn {
            min-width: calc(50% - 10px);
        }
        
        .receipt-type-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 400px) {
        .receipt-type-btn {
            min-width: 100%;
        }
        
        .receipt-type-btn span {
            justify-content: flex-start;
        }
    }
</style>

<div class="receipt-section">
    <div class="receipt-type-container">
        <div class="receipt-type-header">
            <h5>جۆری پسوڵە</h5>
        </div>
        <div class="receipt-types">
            <button class="receipt-type-btn active" data-type="selling">
                <span>
                    <i class="fas fa-shopping-cart"></i>
                    فرۆشتن
                </span>
            </button>
            <button class="receipt-type-btn" data-type="buying">
                <span>
                    <i class="fas fa-store"></i>
                    کڕین
                </span>
            </button>
            <button class="receipt-type-btn" data-type="wasting">
                <span>
                    <i class="fas fa-trash-alt"></i>
                    بەفیڕۆچوو
                </span>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.receipt-type-btn');
    
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all buttons
            buttons.forEach(btn => {
                btn.classList.remove('active');
                // Reset transform and shadow
                btn.style.transform = '';
                btn.style.boxShadow = '';
            });
            
            // Add active class and enhance active button
            this.classList.add('active');
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 4px 12px rgba(115, 128, 236, 0.3)';
            
            // Trigger ripple effect
            const ripple = document.createElement('div');
            ripple.classList.add('ripple');
            this.appendChild(ripple);
            
            // Remove ripple after animation
            setTimeout(() => ripple.remove(), 1000);
        });
    });
});
</script> 