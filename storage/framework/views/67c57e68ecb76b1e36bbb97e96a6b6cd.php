<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Избранное - VityaNails</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/style2.css')); ?>">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1 class="logo">VityaNails - Избранное</h1>
            <a class="profile-btn" href="<?php echo e(route('home')); ?>">На главную</a>
        </header>

        <div class="service-container">
            <?php if($favorites->count() > 0): ?>
                <?php $__currentLoopData = $favorites; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="service-card">
                        <div class="service-title"><?php echo e($service->name); ?></div>
                        <span class="service-price"><?php echo e($service->price); ?> &#8381;</span>
                        <div class="service-description"><?php echo nl2br(e($service->opisanie)); ?></div>
                        <img src="<?php echo e(asset('foto/' . $service->foto)); ?>" alt="Фото услуги" class="service-image">
                        <form method="POST" action="<?php echo e(route('favorites.remove')); ?>">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="product_id" value="<?php echo e($service->id_yslygi); ?>">
                            <button type="submit" class="remove-favorite-btn" onclick="return handleRemoveFavorite(event, this.form)">Удалить из избранного</button>
                        </form>
                        <button class="book-btn" onclick="openModal(<?php echo e($service->id_yslygi); ?>)">Записаться</button>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <?php else: ?>
                <p>У вас пока нет избранных услуг.</p>
            <?php endif; ?>
        </div>
    </div>

    <div id="masterModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeMasterModal()">&times;</span>
            <h2>Выберите мастера</h2>
            <select class="service-select" id="masterSelect"></select>
            <button class="book-btn" onclick="submitBooking()">Подтвердить</button>
        </div>
    </div>
    <div id="datetimeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDatetimeModal()">&times;</span>
            <h2>Выберите дату и время</h2>
            <input type="date" id="dateSelect" min="<?php echo e(date('Y-m-d')); ?>">
            <select class="service-select" id="timeSelect">
                <option value="11:00">11:00</option>
                <option value="13:00">13:00</option>
                <option value="15:00">15:00</option>
                <option value="17:00">17:00</option>
            </select>
            <button class="book-btn" onclick="confirmBooking()">Подтвердить</button>
        </div>
    </div>

    <script>
    let selectedServiceId = null;
    let selectedMasterId = null;

    function showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'notification show';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => { notification.remove(); }, 3000);
    }

    function handleRemoveFavorite(event, form) {
        event.preventDefault();
        const formData = new FormData(form);
        fetch('<?php echo e(route("favorites.remove")); ?>', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') { showNotification('Удалено из избранного!'); setTimeout(() => { window.location.reload(); }, 1000); }
            else { showNotification('Ошибка: ' + (data.message || 'Произошла ошибка')); }
        })
        .catch(error => { console.error('Error:', error); showNotification('Ошибка при удалении'); });
        return false;
    }

    function openModal(serviceId) {
        selectedServiceId = serviceId;
        document.getElementById('masterModal').style.display = 'block';
        const masterSelect = document.getElementById('masterSelect');
        masterSelect.innerHTML = '<option>Загрузка...</option>';
        fetch(`<?php echo e(route('booking.masters')); ?>?service_id=${serviceId}`)
            .then(response => response.json())
            .then(data => {
                masterSelect.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(master => {
                        const option = document.createElement('option');
                        option.value = master.id_user;
                        option.textContent = master.FIO;
                        masterSelect.appendChild(option);
                    });
                } else { masterSelect.innerHTML = '<option>Нет доступных мастеров</option>'; }
            })
            .catch(error => { console.error('Error:', error); });
    }

    function closeMasterModal() { document.getElementById('masterModal').style.display = 'none'; }
    function closeDatetimeModal() { document.getElementById('datetimeModal').style.display = 'none'; }

    function submitBooking() {
        selectedMasterId = document.getElementById('masterSelect').value;
        if (!selectedMasterId) { alert("Выберите мастера."); return; }
        document.getElementById('masterModal').style.display = 'none';
        document.getElementById('datetimeModal').style.display = 'block';
    }

    function confirmBooking() {
        const dateSelect = document.getElementById('dateSelect').value;
        const timeSelect = document.getElementById('timeSelect').value;
        if (!dateSelect || !timeSelect) { alert("Выберите дату и время."); return; }
        const userId = <?php echo e(auth()->id() ?? 'null'); ?>;
        fetch('<?php echo e(route("booking.store")); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
            body: JSON.stringify({ date_time: `${dateSelect} ${timeSelect}`, yslygi_id: selectedServiceId, user_id: userId, id_master: selectedMasterId })
        })
        .then(response => response.json())
        .then(data => { alert(data.message); if (data.status === 'success') closeDatetimeModal(); })
        .catch(error => { console.error('Error:', error); alert('Ошибка при записи.'); });
    }
    </script>
</body>
</html>
<?php /**PATH C:\Users\alesy\OneDrive\Рабочий стол\PHP Coursach\resources\views/favorites.blade.php ENDPATH**/ ?>