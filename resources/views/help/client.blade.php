<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/style2.css') }}">
    <title>Помощь - VityaNails</title>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1 class="logo">VityaNails - Помощь</h1>
            <a class="profile-btn" href="{{ route('home') }}">На главную</a>
        </header>
        <div class="welcome-text">
            <h2>Помощь для клиентов</h2>
        </div>
        <div style="max-width: 800px; margin: 0 auto; padding: 20px; background: white; border-radius: 15px; box-shadow: 0 4px 12px rgba(255,105,180,0.1);">
            <h3 style="color: #ff69b4;">Как записаться на услугу?</h3>
            <ol style="line-height: 2;">
                <li>На главной странице выберите интересующую услугу или нажмите кнопку "Записаться"</li>
                <li>Выберите удобную дату</li>
                <li>Выберите доступное время из предложенных вариантов</li>
                <li>Выберите мастера</li>
                <li>Подтвердите запись</li>
            </ol>
            <h3 style="color: #ff69b4; margin-top: 20px;">Как отменить или изменить запись?</h3>
            <p>Перейдите в свой профиль, найдите нужную запись и нажмите "Редактировать" или "Отменить запись".</p>
            <h3 style="color: #ff69b4; margin-top: 20px;">Избранное</h3>
            <p>Нажмите на сердечко рядом с услугой, чтобы добавить ее в избранное. Доступ к избранным услугам - через иконку сердца в шапке сайта.</p>
            <h3 style="color: #ff69b4; margin-top: 20px;">Контакты</h3>
            <p>Телефон: +7(952)639-58-67</p>
            <p>Адрес: г. Иркутск, ул. Ярославского 252</p>
        </div>
    </div>
</body>
</html>
