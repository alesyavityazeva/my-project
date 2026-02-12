<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>Профиль пользователя</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/style.css')); ?>">
    <script>
        function confirmCancellation(form) {
            if (confirm('Вы уверены, что хотите отменить запись?')) {
                const formData = new FormData(form);
                fetch('<?php echo e(route("booking.cancel")); ?>', { method: 'POST', body: formData })
                .then(() => { showNotification('Запись успешно отменена!'); setTimeout(() => { window.location.reload(); }, 1000); })
                .catch(error => { console.error('Error:', error); alert('Ошибка при отмене записи'); });
            }
        }

        function confirmLogout() {
            if (confirm('Действительно ли вы хотите выйти?')) {
                window.location.href = '<?php echo e(route("logout")); ?>';
            }
        }

        function openEditModal(bookingId, serviceId, masterId, dateTime) {
            document.getElementById('edit_booking_id').value = bookingId;
            document.getElementById('edit_date_time').value = dateTime;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) closeEditModal();
        }

        function showNotification(message, isError = false) {
            const notification = document.createElement('div');
            notification.className = isError ? 'notification notification-error show' : 'notification show';
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => { notification.remove(); }, 3000);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const profileForm = document.querySelector('.profile-info');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch('<?php echo e(route("profile.update")); ?>', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') { showNotification(data.message); setTimeout(() => { window.location.reload(); }, 1000); }
                        else { showNotification(data.message || 'Ошибка', true); }
                    })
                    .catch(error => { console.error('Error:', error); showNotification('Ошибка при обновлении профиля', true); });
                });
            }

            const editForm = document.querySelector('#editModal form');
            if (editForm) {
                editForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    fetch('<?php echo e(route("booking.update")); ?>', { method: 'POST', body: formData })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') { showNotification(data.message); closeEditModal(); setTimeout(() => { window.location.reload(); }, 1000); }
                        else { showNotification(data.message || 'Ошибка', true); }
                    })
                    .catch(error => { console.error('Error:', error); showNotification('Ошибка', true); });
                });
            }
        });

        function toggleHistory() {
            const content = document.getElementById('historyContent');
            const toggle = document.getElementById('historyToggle');
            if (content.style.display === 'none') { content.style.display = 'block'; toggle.textContent = '\u25BC'; }
            else { content.style.display = 'none'; toggle.textContent = '\u25B6'; }
        }
    </script>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding: 0 10px;">
            <h1 style="text-align: left; margin: 0;">Профиль пользователя</h1>
            <div style="display: flex; gap: 15px;">
                <?php if($user_role == 2): ?>
                    <a href="<?php echo e(route('admin.index')); ?>" class="nav-btn" style="margin: 0; padding: 10px 20px; font-size: 0.95em;">Панель админа</a>
                    <a href="<?php echo e(route('home')); ?>" class="nav-btn" style="margin: 0; padding: 10px 20px; font-size: 0.95em;">Клиентская страница</a>
                <?php elseif($user_role == 1): ?>
                    <a href="<?php echo e(route('master.index')); ?>" class="nav-btn" style="margin: 0; padding: 10px 20px; font-size: 0.95em;">Панель мастера</a>
                    <a href="<?php echo e(route('home')); ?>" class="nav-btn" style="margin: 0; padding: 10px 20px; font-size: 0.95em;">Клиентская страница</a>
                <?php else: ?>
                    <a href="<?php echo e(route('home')); ?>" class="nav-btn" style="margin: 0; padding: 10px 20px; font-size: 0.95em;">На главную</a>
                <?php endif; ?>
                <button onclick="confirmLogout()" class="logout-btn" style="margin: 0; padding: 10px 20px; font-size: 0.95em;">Выйти</button>
            </div>
        </div>

        <?php if($user_role == 1 && $stats): ?>
        <div class="profile-container statistics-container">
            <h2>Статистика мастера</h2>
            <form method="get" class="date-filter-form">
                <div class="date-inputs">
                    <div class="date-input-group">
                        <label for="start_date">Период с:</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo e($start_date); ?>">
                    </div>
                    <div class="date-input-group">
                        <label for="end_date">по:</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo e($end_date); ?>">
                    </div>
                    <button type="submit" class="filter-btn">Применить</button>
                </div>
            </form>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">&#128202;</div>
                    <div class="stat-value"><?php echo e($stats->total_bookings ?? 0); ?></div>
                    <div class="stat-label">Выполнено записей</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">&#128176;</div>
                    <div class="stat-value"><?php echo e(number_format($stats->total_revenue ?? 0, 2)); ?> &#8381;</div>
                    <div class="stat-label">Общая выручка</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">&#128181;</div>
                    <div class="stat-value"><?php echo e(number_format($salary ?? 0, 2)); ?> &#8381;</div>
                    <div class="stat-label">Заработная плата (50%)</div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="profile-container">
            <h2>Мои данные</h2>
            <form action="<?php echo e(route('profile.update')); ?>" method="post" class="profile-info">
                <?php echo csrf_field(); ?>
                <label for="lastname">Фамилия:</label>
                <input type="text" id="lastname" name="lastname" value="<?php echo e($user_info->lastname ?? ''); ?>" required pattern="[А-Яа-яЁё\s\-]+">
                <label for="name">Имя:</label>
                <input type="text" id="name" name="name" value="<?php echo e($user_info->name ?? ''); ?>" required pattern="[А-Яа-яЁё\s\-]+">
                <label for="firstname">Отчество:</label>
                <input type="text" id="firstname" name="firstname" value="<?php echo e($user_info->firstname ?? ''); ?>" pattern="[А-Яа-яЁё\s\-]*">
                <label for="phone">Номер телефона:</label>
                <input type="tel" id="phone" name="nomber_tel" value="<?php echo e($user_info->nomber_tel ?? ''); ?>" required>
                <label for="new_password">Новый пароль (оставьте пустым, если не хотите менять):</label>
                <input type="password" id="new_password" name="new_password" minlength="6" placeholder="Минимум 6 символов">
                <button type="submit">Обновить профиль</button>
            </form>
        </div>

        <div class="profile-container">
            <h2>Мои записи</h2>
            <?php if($active_bookings->count() > 0): ?>
                <div class="total-price">
                    <strong>Общая стоимость активных записей:</strong>
                    <span class="price-highlight"><?php echo e(number_format($total_price, 2)); ?> &#8381;</span>
                </div>
                <ul class="bookings-list">
                <?php $__currentLoopData = $active_bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="booking-item">
                        <strong>Услуга:</strong> <?php echo e($booking->service_name); ?><br>
                        <strong>Цена:</strong> <span class="price-text"><?php echo e(number_format($booking->service_price, 2)); ?> &#8381;</span><br>
                        <strong>Мастер:</strong> <?php echo e($booking->master_name); ?><br>
                        <strong>Дата и время:</strong> <?php echo e($booking->date_time); ?><br>
                        <div class="booking-actions">
                            <button type="button" class="edit-btn" onclick="openEditModal(<?php echo e($booking->id_zapis); ?>, 0, 0, '<?php echo e($booking->date_time); ?>')">Редактировать</button>
                            <form action="<?php echo e(route('booking.cancel')); ?>" method="post" style="display: inline;" onsubmit="event.preventDefault(); confirmCancellation(this);">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="booking_id" value="<?php echo e($booking->id_zapis); ?>">
                                <button type="submit" class="cancel-btn">Отменить запись</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php else: ?>
                <p>У вас пока нет активных записей.</p>
            <?php endif; ?>
        </div>

        <div class="profile-container">
            <h2 style="cursor: pointer; user-select: none;" onclick="toggleHistory()">
                История посещений <span id="historyToggle" style="float: right;">&#9660;</span>
            </h2>
            <div id="historyContent" style="display: block;">
                <?php if($past_bookings->count() > 0): ?>
                    <ul class="bookings-list">
                    <?php $__currentLoopData = $past_bookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="booking-item history-item">
                            <strong>Услуга:</strong> <?php echo e($booking->service_name); ?><br>
                            <strong>Цена:</strong> <span class="price-text"><?php echo e(number_format($booking->service_price, 2)); ?> &#8381;</span><br>
                            <strong>Мастер:</strong> <?php echo e($booking->master_name); ?><br>
                            <strong>Дата и время:</strong> <?php echo e($booking->date_time); ?><br>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php else: ?>
                    <p>История посещений пуста.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h2>Редактировать запись</h2>
                <form action="<?php echo e(route('booking.update')); ?>" method="post">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" id="edit_booking_id" name="booking_id">
                    <label for="edit_date_time">Новая дата и время:</label>
                    <input type="datetime-local" id="edit_date_time" name="date_time" required>
                    <button type="submit" class="submit-btn">Сохранить изменения</button>
                    <button type="button" class="cancel-modal-btn" onclick="closeEditModal()">Отмена</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\Users\alesy\OneDrive\Рабочий стол\PHP Coursach\resources\views/profile.blade.php ENDPATH**/ ?>