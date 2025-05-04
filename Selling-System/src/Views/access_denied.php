<?php
require_once '../includes/auth.php';
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دەسەڵات نییە - سیستەمی کۆگا</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <style>
        @font-face {
            font-family: 'Rabar_021';
            src: url('../assets/fonts/Rabar_021.ttf') format('truetype');
        }

        :root {
            --primary-color: rgb(125, 26, 255);
            --secondary-color: #0d47a1;
            --danger-color: #dc3545;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Rabar_021', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }

        .access-denied-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }

        .icon-large {
            font-size: 5rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }

        .back-button {
            margin-top: 1.5rem;
        }

        h2 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-weight: bold;
        }

        p {
            margin-bottom: 30px;
            color: #555;
            font-size: 18px;
            line-height: 1.6;
        }

        .btn-back {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-size: 18px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
            color: white;
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <i class="fas fa-exclamation-circle icon-large"></i>
        <h2 class="mb-4">دەسەڵات نییە</h2>
        <p class="mb-4">ببورە، تۆ دەسەڵاتی بینینی ئەم پەڕەیەت نییە.</p>
        <div class="back-button">
            <a href="javascript:history.back()" class="btn btn-primary">
                <i class="fas fa-arrow-right me-2"></i>
                گەڕانەوە
            </a>
            <a href="/Selling-System/src/views/admin/dashboard.php" class="btn btn-outline-primary">
                <i class="fas fa-home me-2"></i>
                سەرەکی
            </a>
        </div>
    </div>
</body>
</html> 