<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ku" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ڕێپێنەدراو - سیستەمی کۆگا</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @font-face {
            font-family: 'Rabar_021';
            src: url('../assets/fonts/Rabar_021.ttf') format('truetype');
        }
        
        * {
            font-family: 'Rabar_021', sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .access-denied-container {
            max-width: 600px;
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .icon-container {
            margin-bottom: 2rem;
        }
        
        .fa-ban {
            font-size: 5rem;
            color: #dc3545;
        }
        
        h1 {
            color: #333;
            margin-bottom: 1.5rem;
            font-weight: bold;
        }
        
        p {
            color: #6c757d;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, rgb(125, 26, 255), #0d47a1);
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            font-weight: bold;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, rgb(105, 6, 235), #083a89);
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="icon-container">
            <i class="fas fa-ban"></i>
        </div>
        <h1>ڕێپێنەدراو</h1>
        <p>
            ببورە، تۆ دەسەڵاتی بینینی ئەم پەڕەیەت نییە. 
            ئەمە لەوانەیە بەهۆی ئەوە بێت کە تۆ ڕۆڵی پێویستت نییە، یان پەڕەکە بۆ تەنها بەڕێوەبەرەکان بەردەستە.
        </p>
        <a href="/Selling-System/src/views/admin/dashboard.php" class="btn btn-primary">
            <i class="fas fa-home me-2"></i> گەڕانەوە بۆ سەرەتا
        </a>
    </div>
</body>
</html> 