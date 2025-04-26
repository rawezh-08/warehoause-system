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

        :root {
            --primary-color:rgb(125, 26, 255);
            --secondary-color: #0d47a1;
            --accent-color: #ff4081;
            --text-color: #333;
            --light-bg: #f5f5f5;
            --danger-color: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Rabar_021', sans-serif;
        }

        body {
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1200px;
            display: flex;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .login-image {
            flex: 1;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
        }

        .login-image img {
            max-width: 80%;
            margin-bottom: 30px;
        }

        .login-image h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .login-image p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .login-form {
            flex: 1;
            padding: 60px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            color: var(--primary-color);
            font-size: 2.2rem;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .login-header p {
            color: var(--text-color);
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            padding-right: 15px;
            padding-left: 45px;
            height: 48px; /* Fixed height for inputs */
            line-height: 24px; /* Ensure text is centered vertically */
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 40px; /* Position aligned with the form-control */
            transform: translateY(50%); /* Center vertically within the input */
            color: #666;
            font-size: 18px; /* Increased size for better visibility */
        }

        .btn-login {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 10px;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
        }

        .alert {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            border: none;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            border-left: 4px solid var(--danger-color);
        }

        @media (max-width: 992px) {
            .login-wrapper {
                flex-direction: column;
            }
            
            .login-image {
                padding: 30px;
            }
            
            .login-form {
                padding: 40px;
            }
        }

        @media (max-width: 576px) {
            .login-form {
                padding: 30px;
            }
            
            .login-image h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-image">
            <img src="Selling-System/src/assets/img/warehouse-icon.png" alt="Warehouse System">
            <h1>سیستەمی بەڕێوەبردنی کۆگا</h1>
            <p>بەڕێوەبردنی کۆگا بە شێوازێکی سەردەمیانە و کارامە</p>
        </div>
        
        <div class="login-form">
            <div class="login-header">
                <h2>چوونەژوورەوە</h2>
                <p>تکایە زانیاریەکان بنووسە</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">ناوی بەکارهێنەر</label>
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">وشەی نهێنی</label>
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-login">چوونەژوورەوە</button>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 