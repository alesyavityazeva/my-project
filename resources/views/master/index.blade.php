<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{ asset('css/master.css') }}">
    <title>Расписание мастера</title>
</head>
<body>
    <div class="container">
        <header>
            <h1>Добро пожаловать</h1>
            <nav>
                <button onclick="window.location.href='{{ route('master.index', ['tab' => 'shift']) }}'" class="{{ $tab === 'shift' ? 'active' : '' }}">Текущая смена</button>
                <button onclick="window.location.href='{{ route('master.index', ['tab' => 'calendar', 'month' => $month, 'year' => $year]) }}'" class="{{ $tab === 'calendar' ? 'active' : '' }}">Календарь</button>
                <button onclick="window.location.href='{{ route('profile') }}'">Профиль</button>
            </nav>
        </header>
        <main>
            @if($tab === 'shift')
                <h2>Текущая смена - {{ date('d.m.Y') }}</h2>
                <div class="shift-container">
                    @if($todaySchedule->isEmpty())
                        <div class="no-bookings"><p>На сегодня записей нет</p></div>
                    @else
                        <div class="timeline">
                            @foreach($todaySchedule as $booking)
                                <div class="timeline-item">
                                    <div class="timeline-time">{{ date('H:i', strtotime($booking->date_time)) }}</div>
                                    <div class="timeline-content">
                                        <h3>{{ $booking->service_name }}</h3>
                                        <p class="client-name">
                                            <strong>Клиент:</strong>
                                            <span class="client-link" onclick="showClientHistory('{{ e($booking->client_name) }}', '{{ $booking->date_time }}')">
                                                {{ $booking->client_name }}
                                            </span>
                                        </p>
                                        <p class="client-phone"><strong>Телефон:</strong> {{ $booking->client_phone }}</p>
                                        <p class="service-price"><strong>Стоимость:</strong> {{ number_format($booking->service_price, 2) }} &#8381;</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="shift-summary">
                            <h3>Итого за смену:</h3>
                            <p class="total-bookings">Записей: {{ $todaySchedule->count() }}</p>
                            <p class="total-revenue">Выручка: {{ number_format($todaySchedule->sum('service_price'), 2) }} &#8381;</p>
                        </div>
                    @endif
                </div>
            @else
                <h2>Ваше расписание на {{ $monthName }} {{ $year }}</h2>
                <div class="calendar">
                    <div class="calendar-header">
                        <a href="{{ route('master.index', ['tab' => 'calendar', 'month' => $month-1, 'year' => $year]) }}">&lt;</a>
                        <h3>{{ $monthName }} {{ $year }}</h3>
                        <a href="{{ route('master.index', ['tab' => 'calendar', 'month' => $month+1, 'year' => $year]) }}">&gt;</a>
                    </div>
                    <div class="weekdays">
                        <div>Пн</div><div>Вт</div><div>Ср</div><div>Чт</div><div>Пт</div><div>Сб</div><div>Вс</div>
                    </div>
                    <div class="days">
                        @for($i = 0; $i < ($dayOfWeek == 0 ? 6 : $dayOfWeek - 1); $i++)
                            <div class="day empty"></div>
                        @endfor

                        @for($day = 1; $day <= $numberDays; $day++)
                            @php
                                $date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
                                $dayOfWeekNum = date('w', mktime(0, 0, 0, $month, $day, $year));
                                $isWorkingDay = in_array($dayOfWeekNum, $working_days);
                                $dayClass = isset($scheduledDays[$date]) ? 'day has-events' : 'day';
                                if ($isWorkingDay) $dayClass .= ' working-day';
                            @endphp
                            <div class="{{ $dayClass }}">
                                <span class="date">{{ $day }}</span>
                                @if(isset($scheduledDays[$date]))
                                    <div class="events">
                                        @foreach($scheduledDays[$date] as $event)
                                            <div class="event">
                                                <p class="event-time">{{ date('H:i', strtotime($event->date_time)) }}</p>
                                                <p class="event-service">{{ $event->service_name }}</p>
                                                <p class="event-client">
                                                    <span class="client-link" onclick="showClientHistory('{{ e($event->client_name) }}', '{{ $event->date_time }}')">
                                                        {{ $event->client_name }}
                                                    </span>
                                                </p>
                                                <p class="event-phone">{{ $event->client_phone }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>
                </div>
            @endif
        </main>
    </div>

    <div id="clientHistoryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeClientHistory()">&times;</span>
            <h2 id="clientHistoryTitle">История посещений</h2>
            <div id="clientHistoryContent" class="history-content"><p class="loading">Загрузка...</p></div>
        </div>
    </div>

    <script>
    function showClientHistory(clientName, currentDateTime) {
        const modal = document.getElementById('clientHistoryModal');
        const title = document.getElementById('clientHistoryTitle');
        const content = document.getElementById('clientHistoryContent');
        title.textContent = `История посещений: ${clientName}`;
        content.innerHTML = '<p class="loading">Загрузка...</p>';
        modal.style.display = 'block';
        fetch(`{{ route('master.client_history') }}?client_name=${encodeURIComponent(clientName)}&current_date=${encodeURIComponent(currentDateTime)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) { content.innerHTML = `<p class="error">${data.error}</p>`; return; }
                if (data.length === 0) { content.innerHTML = '<p class="no-history">Предыдущих посещений не найдено</p>'; return; }
                let html = '<div class="history-list">';
                data.forEach(visit => {
                    const date = new Date(visit.date_time);
                    html += `<div class="history-item"><div class="history-date"><strong>${date.toLocaleDateString('ru-RU')}</strong> в ${date.toLocaleTimeString('ru-RU', {hour:'2-digit',minute:'2-digit'})}</div><div class="history-details"><p><strong>Услуга:</strong> ${visit.service_name}</p><p><strong>Мастер:</strong> ${visit.master_name}</p><p><strong>Стоимость:</strong> ${parseFloat(visit.service_price).toFixed(2)} &#8381;</p></div></div>`;
                });
                html += '</div>';
                content.innerHTML = html;
            })
            .catch(error => { content.innerHTML = '<p class="error">Ошибка загрузки</p>'; });
    }

    function closeClientHistory() { document.getElementById('clientHistoryModal').style.display = 'none'; }
    window.onclick = function(event) { if (event.target === document.getElementById('clientHistoryModal')) closeClientHistory(); }
    </script>
</body>
</html>
