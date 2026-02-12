<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница не найдена - VityaNails</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #ffeef8 0%, #ffe0f0 100%);
        }
        .error-container {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(255, 105, 180, 0.2);
        }
        h1 { color: #ff69b4; font-size: 3em; margin-bottom: 10px; }
        p { color: #666; font-size: 1.2em; margin-bottom: 20px; }
        a {
            background: linear-gradient(135deg, #ff69b4, #ff1493);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        a:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(255, 105, 180, 0.4); }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>404</h1>
        <p>Страница не найдена.</p>
        <a href="<?php echo e(url('/')); ?>">На главную</a>
    </div>
</body>
</html>
<?php /**PATH C:\Users\alesy\OneDrive\Рабочий стол\PHP Coursach\resources\views/errors/404.blade.php ENDPATH**/ ?>