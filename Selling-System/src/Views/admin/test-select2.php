<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تاقیکردنەوەی سێلێکشنەکان</title>
    
    <!-- Bootstrap 5 RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .card-header {
            background-color: #007bff;
        }
    </style>
</head>
<body>
<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">تاقیکردنەوەی سێلێکشنەکان</h5>
                </div>
                <div class="card-body">
                    <!-- Tab navigation -->
                    <ul class="nav nav-tabs" id="testTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-tab1" data-bs-toggle="tab" data-bs-target="#tab1" type="button" role="tab">
                                تاب ١
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-tab2" data-bs-toggle="tab" data-bs-target="#tab2" type="button" role="tab">
                                تاب ٢
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-tab3" data-bs-toggle="tab" data-bs-target="#tab3" type="button" role="tab">
                                تاب ٣
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab content -->
                    <div class="tab-content" id="testTabsContent">
                        <!-- Tab 1 -->
                        <div class="tab-pane fade show active" id="tab1" role="tabpanel" data-tab-id="1">
                            <div class="p-3">
                                <h4>تاب ١</h4>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label>کڕیار</label>
                                        <select class="customer-select form-control" id="customer1"></select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>کاڵا</label>
                                        <select class="product-select form-control" id="product1"></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab 2 -->
                        <div class="tab-pane fade" id="tab2" role="tabpanel" data-tab-id="2">
                            <div class="p-3">
                                <h4>تاب ٢</h4>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label>کڕیار</label>
                                        <select class="customer-select form-control" id="customer2"></select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>کاڵا</label>
                                        <select class="product-select form-control" id="product2"></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tab 3 -->
                        <div class="tab-pane fade" id="tab3" role="tabpanel" data-tab-id="3">
                            <div class="p-3">
                                <h4>تاب ٣</h4>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label>کڕیار</label>
                                        <select class="customer-select form-control" id="customer3"></select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>کاڵا</label>
                                        <select class="product-select form-control" id="product3"></select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Select2 -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    // Mock customer data for testing
    const mockCustomers = [
        { id: 1, text: 'کڕیار ١' },
        { id: 2, text: 'کڕیار ٢' },
        { id: 3, text: 'کڕیار ٣' },
    ];

    // Mock product data for testing
    const mockProducts = [
        { id: 1, text: 'کاڵا ١', retail_price: 100, wholesale_price: 80 },
        { id: 2, text: 'کاڵا ٢', retail_price: 200, wholesale_price: 160 },
        { id: 3, text: 'کاڵا ٣', retail_price: 300, wholesale_price: 240 },
    ];

    // Initialize Select2 for current tab only
    function initializeTabSelects(tabId) {
        console.log(`Initializing selects for tab ${tabId}`);
        
        // Find selects in the current tab that aren't already initialized
        $(`#tab${tabId} .customer-select:not(.select2-hidden-accessible)`).each(function() {
            console.log(`Initializing customer select in tab ${tabId} with ID: ${$(this).attr('id')}`);
            
            if ($(this).hasClass('select2-hidden-accessible')) {
                try {
                    $(this).select2('destroy');
                } catch (e) {
                    console.error('Error destroying select2:', e);
                }
            }
            
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                data: mockCustomers,
                placeholder: 'کڕیار هەڵبژێرە...',
                language: {
                    searching: function() {
                        return 'گەڕان...';
                    },
                    noResults: function() {
                        return 'هیچ ئەنجامێک نەدۆزرایەوە';
                    }
                }
            }).on('select2:opening', function() {
                console.log(`Customer select opening: ${$(this).attr('id')}`);
            }).on('select2:open', function() {
                console.log(`Customer select opened: ${$(this).attr('id')}`);
                setTimeout(function() {
                    $('.select2-search__field:visible').focus();
                }, 100);
            });
        });
        
        // Product selects
        $(`#tab${tabId} .product-select:not(.select2-hidden-accessible)`).each(function() {
            console.log(`Initializing product select in tab ${tabId} with ID: ${$(this).attr('id')}`);
            
            if ($(this).hasClass('select2-hidden-accessible')) {
                try {
                    $(this).select2('destroy');
                } catch (e) {
                    console.error('Error destroying select2:', e);
                }
            }
            
            $(this).select2({
                theme: 'bootstrap-5',
                width: '100%',
                data: mockProducts,
                placeholder: 'کاڵا هەڵبژێرە...',
                language: {
                    searching: function() {
                        return 'گەڕان...';
                    },
                    noResults: function() {
                        return 'هیچ ئەنجامێک نەدۆزرایەوە';
                    }
                }
            }).on('select2:opening', function() {
                console.log(`Product select opening: ${$(this).attr('id')}`);
            }).on('select2:open', function() {
                console.log(`Product select opened: ${$(this).attr('id')}`);
                setTimeout(function() {
                    $('.select2-search__field:visible').focus();
                }, 100);
            }).on('select2:select', function(e) {
                const data = e.params.data;
                console.log('Product selected:', data);
            });
        });
    }
    
    // Initialize the first tab
    initializeTabSelects(1);
    
    // Initialize selects when tab is shown
    $('.nav-link').on('shown.bs.tab', function(e) {
        const tabId = $(e.target).attr('data-bs-target').replace('#tab', '');
        console.log('Tab shown event triggered for tab', tabId);
        initializeTabSelects(tabId);
    });
    
    // Add debug console logs to help identify tab change events
    $('.nav-link').on('click', function() {
        console.log('Tab clicked:', $(this).attr('data-bs-target'));
    });
});
</script>
</body>
</html> 