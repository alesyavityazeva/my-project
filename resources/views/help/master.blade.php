<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/master.css') }}">
    <title>Помощь мастера - VityaNails</title>
</head>
<body>
    <div class="container">
        <header><h1>VityaNails - Помощь мастера</h1>
            <nav><button onclick="window.location.href='{{ route('master.index') }}'">Назад</button></nav>
        </header>
        <main>
            <h2>Инструкция для мастеров</h2>
            <div style="padding: 20px;">
                <h3>Текущая смена</h3>
                <p>На вкладке "Текущая смена" отображаются все записи на сегодня: имя клиента, услуга, время и стоимость.</p>
                <h3>Календарь</h3>
                <p>Вкладка "Календарь" показывает расписание на месяц. Рабочие дни подсвечены, дни с записями отмечены.</p>
                <h3>История клиента</h3>
                <p>Нажмите на имя клиента, чтобы увидеть историю его посещений.</p>
                <h3>Профиль и статистика</h3>
                <p>В профиле доступна статистика: количество выполненных записей, выручка и заработная плата за выбранный период.</p>
            </div>
        </main>
    </div>
</body>
</html>
