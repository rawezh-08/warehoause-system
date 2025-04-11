<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?= htmlspecialchars($title) ?></h1>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['success']) ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['error']) ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-table me-1"></i>
                Products List
            </div>
            <div>
                <a href="/products/create" class="btn btn-primary">Add New Product</a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="productsTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Category</th>
                            <th>Unit</th>
                            <th>Stock</th>
                            <th>Purchase Price</th>
                            <th>Selling Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['id']) ?></td>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= htmlspecialchars($product['code']) ?></td>
                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                <td><?= htmlspecialchars($product['unit_name']) ?></td>
                                <td>
                                    <?php if ($product['stock'] <= $product['min_stock']): ?>
                                        <span class="badge bg-danger"><?= htmlspecialchars($product['stock']) ?></span>
                                    <?php else: ?>
                                        <?= htmlspecialchars($product['stock']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(number_format($product['purchase_price'], 2)) ?></td>
                                <td><?= htmlspecialchars(number_format($product['selling_price'], 2)) ?></td>
                                <td>
                                    <a href="/products/edit/<?= $product['id'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger delete-product" 
                                            data-id="<?= $product['id'] ?>" 
                                            data-name="<?= htmlspecialchars($product['name']) ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <span id="productName"></span>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" method="POST">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize DataTable
    $('#productsTable').DataTable({
        order: [[0, 'desc']],
        pageLength: 25
    });

    // Delete product handling
    const deleteModal = document.getElementById('deleteModal');
    const productNameSpan = document.getElementById('productName');
    const deleteForm = document.getElementById('deleteForm');

    document.querySelectorAll('.delete-product').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;
            const productName = this.dataset.name;
            
            productNameSpan.textContent = productName;
            deleteForm.action = `/products/delete/${productId}`;
            
            new bootstrap.Modal(deleteModal).show();
        });
    });
});
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?> 