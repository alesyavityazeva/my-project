<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title>VityaNails</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/style2.css')); ?>">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1 class="logo">VityaNails</h1>
            <form class="search-form" action="" method="GET">
                <div class="search-group">
                    <input type="text" name="search" class="search-input" placeholder="Поиск по названию" value="<?php echo e($searchQuery); ?>">
                    <select name="category" class="category-select">
                        <option value="">Все категории</option>
                        <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($category->id_kategori); ?>" <?php echo e($categoryFilter == $category->id_kategori ? 'selected' : ''); ?>>
                                <?php echo e($category->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button type="submit" class="search-btn">Поиск</button>
                </div>
            </form>
            <div class="header-buttons">
                <?php if(auth()->guard()->check()): ?>
                    <a class="favorite-btn" href="<?php echo e(route('favorites')); ?>" title="Избранное">&#10084;</a>
                <?php endif; ?>
                <a class="profile-btn" href="<?php echo e(auth()->check() ? route('profile') : route('login')); ?>">
                    <?php echo e(auth()->check() ? 'Профиль' : 'Войти'); ?>

                </a>
            </div>
        </header>

        <?php if(session('notification')): ?>
            <div class="notification <?php echo e(session('notification_type', 'success') === 'error' ? 'notification-error' : (session('notification_type') === 'info' ? 'notification-info' : 'notification-success')); ?> show">
                <?php echo e(session('notification')); ?>

            </div>
        <?php endif; ?>
        <?php if(session('registration_success')): ?>
            <div class="notification notification-success show"><?php echo e(session('registration_success')); ?></div>
        <?php endif; ?>
        <?php if(session('login_success')): ?>
            <div class="notification notification-success show"><?php echo e(session('login_success')); ?></div>
        <?php endif; ?>

        <div class="welcome-text">
            <h2>Добро пожаловать в студию маникюра VityaNails!</h2>
            <p>Запишитесь на процедуру прямо сейчас и подарите своим ногтям роскошный уход!</p>
        </div>

        <?php if($services->count() > 0): ?>
            <div class="service-container">
                <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $product): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php $is_favorited = in_array($product->id_yslygi, $userFavorites); ?>
                    <div class="service-card" onclick="openModalWithService(<?php echo e($product->id_yslygi); ?>)">
                        <div class="service-title"><?php echo e($product->name); ?></div>
                        <span class="service-price"><?php echo e($product->price); ?> &#8381;</span>
                        <span class="service-duration">&#9201; <?php echo e($product->duration_minutes); ?> мин.</span>
                        <div class="service-description"><?php echo nl2br(e($product->opisanie)); ?></div>
                        <?php if(auth()->guard()->check()): ?>
                            <form method="POST" action="<?php echo e(route('favorites.add')); ?>" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="product_id" value="<?php echo e($product->id_yslygi); ?>">
                                <button onclick="event.stopPropagation(); addToFavorites(<?php echo e($product->id_yslygi); ?>, this)" class="favorite-btn <?php echo e($is_favorited ? 'favorited' : ''); ?>">&#10084;</button>
                            </form>
                        <?php endif; ?>
                        <img src="<?php echo e(asset('foto/' . $product->foto)); ?>" alt="Фото услуги" class="service-image">
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="no-products"><p>Нет доступных услуг.</p></div>
        <?php endif; ?>

        <button class="book-btn" onclick="openModal()">Записаться</button>

        <footer class="footer">
            <p>Адрес нашего салона: г. Иркутск ул. Ярославского 252</p>
            <p>Номер телефона для помощи: +7(952)639-58-67</p>
            <?php if(auth()->guard()->check()): ?>
                <a href="<?php echo e(route('help')); ?>" class="help-btn">Помощь</a>
            <?php endif; ?>
        </footer>

        <!-- Booking Modal -->
        <div id="bookingModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()">&times;</span>
                <h2>Выберите услугу</h2>
                <select class="service-select" id="serviceSelect">
                    <?php $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($service->id_yslygi); ?>"><?php echo e($service->name); ?> - <?php echo e($service->price); ?>&#8381;</option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <button class="book-btn" onclick="openDatetimeModal()">Подтвердить</button>
            </div>
        </div>

        <!-- Datetime Modal -->
        <div id="datetimeModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeDatetimeModal()">&times;</span>
                <h2>Выберите дату и время</h2>
                <input type="date" id="dateSelect" min="<?php echo e(date('Y-m-d')); ?>" onchange="loadAvailableTimeSlots()">
                <label for="timeSelect">Доступное время:</label>
                <select class="service-select" id="timeSelect">
                    <option value="">Сначала выберите дату</option>
                </select>
                <button class="book-btn" onclick="loadAvailableMasters()" id="confirmTimeBtn" disabled>Подтвердить время</button>
            </div>
        </div>

        <!-- Master Modal -->
        <div id="masterModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeMasterModal()">&times;</span>
                <h2>Выберите мастера</h2>
                <p id="selectedDateTime" style="color: #666; margin-bottom: 15px;"></p>
                <select class="service-select" id="masterSelect"></select>
                <button class="book-btn" onclick="confirmBooking()">Подтвердить</button>
            </div>
        </div>

        <!-- Edit Booking Modal -->
        <div id="editBookingModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditBookingModal()">&times;</span>
                <h2>Редактировать запись</h2>
                <p id="editCurrentDateTime" style="color: #666; margin-bottom: 15px;"></p>
                <input type="hidden" id="editBookingId">
                <input type="date" id="editDateSelect" min="<?php echo e(date('Y-m-d')); ?>">
                <select class="service-select" id="editTimeSelect">
                    <?php for($h = 9; $h <= 18; $h++): ?>
                        <option value="<?php echo e(str_pad($h, 2, '0', STR_PAD_LEFT)); ?>:00"><?php echo e(str_pad($h, 2, '0', STR_PAD_LEFT)); ?>:00</option>
                    <?php endfor; ?>
                </select>
                <button class="book-btn" onclick="updateBooking()">Сохранить изменения</button>
            </div>
        </div>
    </div>

    <script>
    let selectedServiceId = null;
    let selectedMasterId = null;
    let selectedDate = null;
    let selectedTime = null;

    function showNotification(message, isError = false) {
        const notification = document.createElement('div');
        notification.className = isError ? 'notification notification-error show' : 'notification show';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => { notification.remove(); }, 3000);
    }

    const searchInput = document.querySelector('.search-input');
    const categorySelect = document.querySelector('.category-select');

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => { clearTimeout(timeout); func(...args); };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    function performLiveSearch() {
        const searchValue = searchInput.value;
        const categoryValue = categorySelect.value;
        fetch(`<?php echo e(route('search.services')); ?>?search=${encodeURIComponent(searchValue)}&category=${encodeURIComponent(categoryValue)}`)
            .then(response => response.json())
            .then(data => { updateServiceCards(data); })
            .catch(error => { console.error('Error:', error); });
    }

    function updateServiceCards(services) {
        const container = document.querySelector('.service-container');
        if (!services || services.length === 0) {
            container.innerHTML = '<div class="no-products"><p>Нет доступных услуг.</p></div>';
            return;
        }
        const userFavorites = <?php echo json_encode($userFavorites, 15, 512) ?>;
        const isLoggedIn = <?php echo e(auth()->check() ? 'true' : 'false'); ?>;
        container.innerHTML = '';
        services.forEach(product => {
            const isFavorited = userFavorites.includes(product.id_yslygi);
            const favoriteClass = isFavorited ? 'favorited' : '';
            const card = document.createElement('div');
            card.className = 'service-card';
            card.onclick = () => openModalWithService(product.id_yslygi);
            let favoriteButton = '';
            if (isLoggedIn) {
                favoriteButton = `<form method="POST" action="<?php echo e(route('favorites.add')); ?>" style="display: inline;"><input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>"><input type="hidden" name="product_id" value="${product.id_yslygi}"><button onclick="event.stopPropagation(); addToFavorites(${product.id_yslygi}, this)" class="favorite-btn ${favoriteClass}">&#10084;</button></form>`;
            }
            card.innerHTML = `
                <div class="service-title">${escapeHtml(product.name)}</div>
                <span class="service-price">${escapeHtml(String(product.price))} &#8381;</span>
                <div class="service-description">${escapeHtml(product.opisanie || '').replace(/\n/g, '<br>')}</div>
                ${favoriteButton}
                <img src="<?php echo e(asset('foto')); ?>/${escapeHtml(product.foto)}" alt="Фото услуги" class="service-image">
            `;
            container.appendChild(card);
        });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    searchInput.addEventListener('input', debounce(performLiveSearch, 300));
    categorySelect.addEventListener('change', performLiveSearch);

    function openModalWithService(serviceId) {
        const isLoggedIn = <?php echo e(auth()->check() ? 'true' : 'false'); ?>;
        if (!isLoggedIn) {
            if (confirm('Для записи необходимо войти в систему. Перейти на страницу входа?')) {
                window.location.href = '<?php echo e(route("login")); ?>';
            }
            return;
        }
        selectedServiceId = serviceId;
        document.getElementById('bookingModal').style.display = 'block';
        document.getElementById('serviceSelect').value = serviceId;
    }

    function openModal() {
        const isLoggedIn = <?php echo e(auth()->check() ? 'true' : 'false'); ?>;
        if (!isLoggedIn) {
            if (confirm('Для записи необходимо войти в систему. Перейти на страницу входа?')) {
                window.location.href = '<?php echo e(route("login")); ?>';
            }
            return;
        }
        document.getElementById('bookingModal').style.display = 'block';
    }

    function closeModal() { document.getElementById('bookingModal').style.display = 'none'; }

    function openDatetimeModal() {
        selectedServiceId = document.getElementById('serviceSelect').value;
        if (!selectedServiceId) { alert("Выберите услугу."); return; }
        document.getElementById('dateSelect').value = '';
        document.getElementById('timeSelect').innerHTML = '<option value="">Сначала выберите дату</option>';
        document.getElementById('confirmTimeBtn').disabled = true;
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('dateSelect').min = today;
        document.getElementById('dateSelect').value = today;
        document.getElementById('bookingModal').style.display = 'none';
        document.getElementById('datetimeModal').style.display = 'block';
        setTimeout(() => { loadAvailableTimeSlots(); }, 100);
    }

    function closeDatetimeModal() { document.getElementById('datetimeModal').style.display = 'none'; }

    function loadAvailableTimeSlots() {
        const dateSelect = document.getElementById('dateSelect').value;
        if (!dateSelect) { document.getElementById('timeSelect').innerHTML = '<option value="">Сначала выберите дату</option>'; document.getElementById('confirmTimeBtn').disabled = true; return; }
        if (!selectedServiceId) { alert("Сначала выберите услугу"); return; }
        document.getElementById('timeSelect').innerHTML = '<option value="">Загрузка...</option>';
        document.getElementById('confirmTimeBtn').disabled = true;
        fetch(`<?php echo e(route('booking.available_slots')); ?>?service_id=${selectedServiceId}&date=${dateSelect}`)
            .then(response => response.json())
            .then(data => {
                const timeSelect = document.getElementById('timeSelect');
                if (data.status === 'success' && data.available_slots.length > 0) {
                    timeSelect.innerHTML = '<option value="">Выберите время</option>';
                    data.available_slots.forEach(slot => {
                        const option = document.createElement('option');
                        option.value = slot.time;
                        option.textContent = slot.display || slot.time;
                        timeSelect.appendChild(option);
                    });
                    document.getElementById('confirmTimeBtn').disabled = false;
                } else {
                    timeSelect.innerHTML = '<option value="">Нет доступного времени</option>';
                    document.getElementById('confirmTimeBtn').disabled = true;
                }
            })
            .catch(error => { console.error('Error:', error); });
    }

    function loadAvailableMasters() {
        const dateSelect = document.getElementById('dateSelect').value;
        const timeSelect = document.getElementById('timeSelect').value;
        if (!dateSelect || !timeSelect) { alert("Выберите дату и время."); return; }
        selectedDate = dateSelect;
        selectedTime = timeSelect;
        document.getElementById('datetimeModal').style.display = 'none';
        document.getElementById('masterModal').style.display = 'block';
        const masterSelect = document.getElementById('masterSelect');
        masterSelect.innerHTML = '<option>Загрузка...</option>';
        fetch(`<?php echo e(route('booking.available_masters')); ?>?id_yslygi=${selectedServiceId}&date=${dateSelect}&time=${timeSelect}`)
            .then(response => response.json())
            .then(data => {
                masterSelect.innerHTML = '';
                if (data.error) {
                    masterSelect.innerHTML = '<option value="">' + data.error + '</option>';
                    masterSelect.disabled = true;
                    return;
                }
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(master => {
                        const option = document.createElement('option');
                        option.value = master.id_user;
                        option.textContent = master.FIO;
                        masterSelect.appendChild(option);
                    });
                    masterSelect.disabled = false;
                } else {
                    masterSelect.innerHTML = '<option value="">Нет доступных мастеров</option>';
                    masterSelect.disabled = true;
                }
            })
            .catch(error => { console.error('Error:', error); });
    }

    function closeMasterModal() { document.getElementById('masterModal').style.display = 'none'; }

    function confirmBooking() {
        if (!selectedServiceId || !selectedDate || !selectedTime) { alert("Не все данные выбраны."); return; }
        const masterSelect = document.getElementById('masterSelect');
        selectedMasterId = masterSelect.value;
        if (!selectedMasterId || masterSelect.disabled) { alert("Выберите мастера."); return; }
        const dateTime = `${selectedDate} ${selectedTime}:00`;
        const userId = <?php echo e(auth()->id() ?? 'null'); ?>;
        if (!userId) { window.location.href = '<?php echo e(route("login")); ?>'; return; }
        fetch('<?php echo e(route("booking.store")); ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
            body: JSON.stringify({ date_time: dateTime, yslygi_id: selectedServiceId, user_id: userId, id_master: selectedMasterId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message);
                closeMasterModal();
                selectedServiceId = null; selectedMasterId = null; selectedDate = null; selectedTime = null;
                setTimeout(() => { window.location.reload(); }, 1000);
            } else { alert('Ошибка: ' + (data.message || 'Неизвестная ошибка')); }
        })
        .catch(error => { console.error('Error:', error); alert('Произошла ошибка при записи.'); });
    }

    function openEditBookingModal(bookingId, currentDateTime) {
        const currentDate = new Date(currentDateTime);
        document.getElementById('editBookingId').value = bookingId;
        document.getElementById('editDateSelect').value = currentDate.toISOString().split('T')[0];
        document.getElementById('editTimeSelect').value = currentDate.toTimeString().substring(0, 5);
        document.getElementById('editBookingModal').style.display = 'block';
    }

    function closeEditBookingModal() { document.getElementById('editBookingModal').style.display = 'none'; }

    function updateBooking() {
        const bookingId = document.getElementById('editBookingId').value;
        const dateSelect = document.getElementById('editDateSelect').value;
        const timeSelect = document.getElementById('editTimeSelect').value;
        if (!bookingId || !dateSelect || !timeSelect) { showNotification('Выберите дату и время.', true); return; }
        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('date_time', `${dateSelect} ${timeSelect}:00`);
        formData.append('_token', '<?php echo e(csrf_token()); ?>');
        fetch('<?php echo e(route("booking.update")); ?>', { method: 'POST', body: formData })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') { showNotification(data.message); closeEditBookingModal(); setTimeout(() => { window.location.reload(); }, 1000); }
                else { showNotification(data.message || 'Ошибка', true); }
            })
            .catch(error => { console.error('Error:', error); showNotification('Ошибка', true); });
    }

    // Auto-hide notifications
    setTimeout(function() {
        document.querySelectorAll('.notification.show').forEach(n => {
            n.style.opacity = '0'; n.style.transform = 'translateY(-20px)';
            setTimeout(() => { n.remove(); }, 300);
        });
    }, 3000);
    </script>
</body>
</html>
<?php /**PATH C:\Users\alesy\OneDrive\Рабочий стол\PHP Coursach\resources\views/home.blade.php ENDPATH**/ ?>