<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دەسەڵاتت نییە - سیستەمی کۆگا</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .access-denied-container {
            width: 100%;
            max-width: 600px;
            text-align: center;
            background-color: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .error-icon {
            color: var(--danger-color);
            font-size: 80px;
            margin-bottom: 20px;
        }

        h1 {
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
        <i class="fas fa-exclamation-circle error-icon"></i>
        <h1>دەسەڵاتت نییە</h1>
        <p>
            ببورە، تۆ دەسەڵاتت نییە بۆ بینینی ئەم پەڕەیە. 
            تکایە پەیوەندی بکە بە بەڕێوەبەری سیستەم بۆ وەرگرتنی ڕێگەپێدان.
        </p>
        <a href="../views/admin/dashboard.php" class="btn btn-back">
            <i class="fas fa-arrow-right ml-2"></i> گەڕانەوە بۆ بەشی سەرەکی
        </a>
    </div>
</body>
</html> 