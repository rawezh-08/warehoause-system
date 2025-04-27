<?php
// Include the login handler
require_once 'Selling-System/src/process/login_handler.php';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>چوونەژوورەوە - سیستەمی کۆگا</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @font-face {
            font-family: 'Rabar_021';
            src: url('Selling-System/src/assets/fonts/Rabar_021.ttf') format('truetype');
        }
        body {
            font-family: 'Rabar_021', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .btn-primary, .text-primary, .input-group-text .fa-user, .input-group-text .fa-lock {
    background: var(--primary-color) !important;
    border-color: var(--primary-color) !important;
    color: #fff !important;
}
.text-primary {
    color: var(--primary-color) !important;
}
    </style>
</head>
<body>
<div class="container min-vh-100 d-flex align-items-center justify-content-center">
  <div class="row w-100">
    <div class="col-12 col-sm-10 col-md-6 col-lg-5 mx-auto">
      <div class="bg-white rounded-4 shadow p-4 p-md-5">
        <div class="text-center mb-4">
          <h2 class="text-primary fw-bold">چوونەژوورەوە</h2>
          <p class="text-muted">تکایە زانیاریەکان بنووسە</p>
        </div>
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST" action="">
          <div class="mb-3 position-relative">
            <label for="username" class="form-label">ناوی بەکارهێنەر</label>
            <div class="input-group flex-row-reverse">
              <span class="input-group-text bg-light"><i class="fas fa-user text-primary"></i></span>
              <input type="text" class="form-control" id="username" name="username" required>
            </div>
          </div>
          <div class="mb-4 position-relative">
            <label for="password" class="form-label">وشەی نهێنی</label>
            <div class="input-group flex-row-reverse">
              <span class="input-group-text bg-light"><i class="fas fa-lock text-primary"></i></span>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">چوونەژوورەوە</button>
        </form>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 