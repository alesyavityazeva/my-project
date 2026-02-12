<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/Admin.css')); ?>">
    <title>Админ-панель</title>
</head>
<body>
<div class="container">
    <header>
        <h1>VityaNails</h1>
        <nav>
            <button onclick="showSection('shift')" id="nav-shift" class="<?php echo e($activeSection == 'shift' ? 'active' : ''); ?>">Смена</button>
            <button onclick="showSection('month')" id="nav-month" class="<?php echo e($activeSection == 'month' ? 'active' : ''); ?>">Месяц</button>
            <button onclick="showSection('yslygi')" id="nav-yslygi" class="<?php echo e($activeSection == 'yslygi' ? 'active' : ''); ?>">Услуги</button>
            <button onclick="showSection('categories')" id="nav-categories" class="<?php echo e($activeSection == 'categories' ? 'active' : ''); ?>">Категории</button>
            <button onclick="showSection('user')" id="nav-user" class="<?php echo e($activeSection == 'user' ? 'active' : ''); ?>">Пользователи</button>
            <button onclick="showSection('masters')" id="nav-masters" class="<?php echo e($activeSection == 'masters' ? 'active' : ''); ?>">Мастера</button>
            <button onclick="showSection('history')" id="nav-history" class="<?php echo e($activeSection == 'history' ? 'active' : ''); ?>">История</button>
            <button onclick="showSection('schedule')" id="nav-schedule" class="<?php echo e($activeSection == 'schedule' ? 'active' : ''); ?>">График</button>
            <button onclick="showSection('reports')" id="nav-reports" class="<?php echo e($activeSection == 'reports' ? 'active' : ''); ?>">Отчеты</button>
            <a href="<?php echo e(route('profile')); ?>"><button type="button">Профиль</button></a>
        </nav>
    </header>

    <?php if(session('message')): ?>
        <div class="notification success"><?php echo e(session('message')); ?></div>
    <?php endif; ?>
    <?php if(session('error')): ?>
        <div class="notification error"><?php echo e(session('error')); ?></div>
    <?php endif; ?>

    
    <section id="shift" class="tab-section <?php echo e($activeSection == 'shift' ? 'active' : ''); ?>">
        <div class="shift-header">
            <div class="header-top"><h2>Текущая смена - <?php echo e(date('d.m.Y')); ?></h2></div>
            <div class="header-line"></div>
            <div class="header-actions">
                <button onclick="openCreateBookingModal()" class="create-booking-btn">Создать запись</button>
            </div>
        </div>
        <?php
            $groupedByMaster = []; $totalRevenue = 0; $totalBookings = 0;
            foreach ($todaySchedule as $booking) {
                $masterName = $booking->master_name ?: 'Не назначен';
                if (!isset($groupedByMaster[$masterName])) $groupedByMaster[$masterName] = ['bookings' => [], 'total_price' => 0, 'count' => 0];
                $groupedByMaster[$masterName]['bookings'][] = $booking;
                $groupedByMaster[$masterName]['total_price'] += $booking->service_price;
                $groupedByMaster[$masterName]['count']++;
                $totalRevenue += $booking->service_price; $totalBookings++;
            }
            ksort($groupedByMaster);
        ?>
        <div class="shift-container">
            <?php if($todaySchedule->isEmpty()): ?>
                <div class="no-bookings"><p>На сегодня записей нет</p></div>
            <?php else: ?>
                <div class="stats-summary">
                    <div class="stat-card"><div class="stat-number"><?php echo e($totalBookings); ?></div><div class="stat-label">Всего записей</div></div>
                    <div class="stat-card"><div class="stat-number"><?php echo e(number_format($totalRevenue, 0)); ?> &#8381;</div><div class="stat-label">Выручка</div></div>
                    <div class="stat-card"><div class="stat-number"><?php echo e(count($groupedByMaster)); ?></div><div class="stat-label">Мастеров</div></div>
                </div>
                <div class="masters-grid">
                    <?php $__currentLoopData = $groupedByMaster; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $masterName => $masterData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php usort($masterData['bookings'], fn($a, $b) => strtotime($a->date_time) - strtotime($b->date_time)); ?>
                        <div class="master-card">
                            <div class="master-header"><h3><?php echo e($masterName); ?></h3><span class="master-badge"><?php echo e($masterData['count']); ?> зап.</span></div>
                            <div class="master-revenue">Выручка: <strong><?php echo e(number_format($masterData['total_price'], 0)); ?> &#8381;</strong></div>
                            <div class="bookings-list">
                                <?php $__currentLoopData = $masterData['bookings']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="booking-card">
                                        <div class="booking-time"><?php echo e(date('H:i', strtotime($booking->date_time))); ?> <span class="booking-price"><?php echo e(number_format($booking->service_price, 0)); ?> &#8381;</span></div>
                                        <div class="booking-service"><?php echo e($booking->service_name); ?></div>
                                        <div class="booking-client"><div><?php echo e($booking->client_name); ?></div><div class="client-phone"><?php echo e($booking->client_phone); ?></div></div>
                                        <div class="booking-actions">
                                            <button onclick="editBooking(<?php echo e($booking->id_zapis); ?>)" class="category-btn edit-btn">Редактировать</button>
                                            <form action="<?php echo e(route('admin.cancel_appointment')); ?>" method="post" onsubmit="return confirm('Отменить запись?');">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="booking_id" value="<?php echo e($booking->id_zapis); ?>">
                                                <button type="submit" class="btn-cancel">Отменить</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    
    <section id="yslygi" class="tab-section <?php echo e($activeSection == 'yslygi' ? 'active' : ''); ?>">
        <h2>Услуги</h2>
        <button id="addServiceBtn" class="create-booking-btn">Добавить услугу</button>
        <div class="service-grid">
            <?php $__currentLoopData = $yslygi_with_categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ysluga): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="service-card-admin">
                    <img src="<?php echo e(service_foto_url($ysluga->foto)); ?>" alt="Фото" class="service-card-img">
                    <div class="service-card-content">
                        <span class="service-card-duration"><?php echo e($ysluga->duration_minutes ?? 60); ?> мин.</span>
                        <?php if(!empty($ysluga->category_name)): ?>
                            <span class="service-card-category"><?php echo e($ysluga->category_name); ?></span>
                        <?php endif; ?>
                        <h3><?php echo e($ysluga->name); ?></h3>
                        <p class="service-card-price"><?php echo e($ysluga->price); ?>&#8381;</p>
                        <p class="service-card-desc"><?php echo e($ysluga->opisanie); ?></p>
                        <div class="service-card-actions">
                            <button onclick="editService(<?php echo e($ysluga->id_yslygi); ?>)" class="edit-btn-small">Изменить</button>
                            <form action="<?php echo e(route('admin.delete_service')); ?>" method="post" style="display:inline;" onsubmit="return confirm('Удалить услугу?');">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_yslygi" value="<?php echo e($ysluga->id_yslygi); ?>">
                                <button type="submit" class="category-btn delete-btn">Удалить</button>
                            </form>
                        </div>
                    </div>
                    <div id="editServiceForm<?php echo e($ysluga->id_yslygi); ?>" class="edit-service-form" style="display:none;">
                        <form action="<?php echo e(route('admin.edit_service')); ?>" method="post" enctype="multipart/form-data">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id_yslygi" value="<?php echo e($ysluga->id_yslygi); ?>">
                            <input type="text" name="name" value="<?php echo e($ysluga->name); ?>" required>
                            <input type="number" name="price" value="<?php echo e($ysluga->price); ?>" required>
                            <textarea name="opisanie" required><?php echo e($ysluga->opisanie); ?></textarea>
                            <select name="id_kategori">
                                <option value="">Без категории</option>
                                <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($cat->id_kategori); ?>" <?php echo e($ysluga->id_kategori == $cat->id_kategori ? 'selected' : ''); ?>><?php echo e($cat->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <input type="number" name="duration_minutes" value="<?php echo e($ysluga->duration_minutes ?? 60); ?>" min="15" max="480" step="15" required>
                            <input type="file" name="foto" accept="image/*">
                            <div style="display:flex;gap:10px;margin-top:10px;">
                                <button type="submit">Сохранить</button>
                                <button type="button" onclick="cancelEdit(<?php echo e($ysluga->id_yslygi); ?>)">Отмена</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div id="addServiceModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2>Добавление новой услуги</h2>
                <form action="<?php echo e(route('admin.create_service')); ?>" method="post" enctype="multipart/form-data">
                    <?php echo csrf_field(); ?>
                    <input type="text" name="name" placeholder="Название услуги" required>
                    <input type="number" name="price" placeholder="Цена" required>
                    <textarea name="opisanie" placeholder="Описание" required></textarea>
                    <select name="id_kategori"><option value="">Без категории</option><?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($cat->id_kategori); ?>"><?php echo e($cat->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                    <input type="file" name="foto" accept="image/*" required>
                    <input type="number" name="duration_minutes" value="60" min="15" max="480" step="15" required>
                    <button type="submit">Создать услугу</button>
                </form>
            </div>
        </div>
    </section>

    
    <section id="categories" class="tab-section <?php echo e($activeSection == 'categories' ? 'active' : ''); ?>">
        <h2 class="categories-page-title">Категории услуг</h2>
        <button onclick="openAddCategoryModal()" class="create-booking-btn">Добавить категорию</button>
        <div class="categories-grid">
            <?php $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $servicesInCat = \App\Models\Service::where('id_kategori', $category->id_kategori)->get(); ?>
                <div class="category-card" data-category-id="<?php echo e($category->id_kategori); ?>" data-category-name="<?php echo e(e($category->name)); ?>">
                    <div class="category-header">
                        <h3 class="category-name"><?php echo e($category->name); ?></h3>
                        <div class="category-actions">
                            <button type="button" onclick="var c=this.closest('.category-card'); openEditCategoryModal(parseInt(c.dataset.categoryId), c.dataset.categoryName)" class="category-btn edit-btn">Редактировать</button>
                            <form method="post" action="<?php echo e(route('admin.delete_category')); ?>" onsubmit="return confirm('Удалить категорию?');" class="category-action-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="category_id" value="<?php echo e($category->id_kategori); ?>">
                                <button type="submit" class="category-btn delete-btn">Удалить</button>
                            </form>
                        </div>
                    </div>
                    <div class="category-services">
                        <div class="services-label">Услуги в категории (<?php echo e($servicesInCat->count()); ?>):</div>
                        <?php if($servicesInCat->count() > 0): ?>
                            <ul class="services-list"><?php $__currentLoopData = $servicesInCat; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($s->name); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div id="addCategoryModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close" onclick="closeAddCategoryModal()">&times;</span>
                <h2>Добавление категории</h2>
                <form method="post" action="<?php echo e(route('admin.create_category')); ?>"><?php echo csrf_field(); ?><input type="text" name="category_name" placeholder="Название категории" required><button type="submit">Создать</button></form>
            </div>
        </div>
        <div id="editCategoryModal" class="modal" style="display:none;">
            <div class="modal-content">
                <span class="close" onclick="closeEditCategoryModal()">&times;</span>
                <h2>Редактирование категории</h2>
                <form method="post" action="<?php echo e(route('admin.edit_category')); ?>"><?php echo csrf_field(); ?><input type="hidden" name="category_id" id="edit_category_id"><input type="text" name="category_name" id="edit_category_name" required><button type="submit">Сохранить</button></form>
            </div>
        </div>
    </section>

    
    <section id="user" class="tab-section <?php echo e($activeSection == 'user' ? 'active' : ''); ?>">
        <h2>Пользователи</h2>
        <div class="search-filter-container">
            <input type="text" id="userSearch" placeholder="Поиск по ФИО или телефону..." onkeyup="filterUsers()">
            <select id="userRoleFilter" onchange="filterUsers()">
                <option value="">Все роли</option>
                <option value="0">Пользователь</option>
                <option value="1">Мастер</option>
                <option value="2">Администратор</option>
            </select>
        </div>
        <div class="table-responsive">
            <table class="modern-table">
                <thead><tr><th>ФИО</th><th>Телефон</th><th>Роль</th><th>Действия</th></tr></thead>
                <tbody id="userTableBody">
                    <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $usr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr data-role="<?php echo e($usr->id_roli); ?>" data-name="<?php echo e($usr->FIO); ?>" data-phone="<?php echo e($usr->nomber_tel); ?>">
                        <td>
                            <span class="user-field" id="name_display_<?php echo e($usr->id_user); ?>"><?php echo e($usr->FIO); ?></span>
                            <div id="name_edit_<?php echo e($usr->id_user); ?>" style="display:none;">
                                <input type="text" class="edit-input" id="lastname_edit_<?php echo e($usr->id_user); ?>" value="<?php echo e($usr->lastname); ?>" placeholder="Фамилия">
                                <input type="text" class="edit-input" id="firstname_edit_<?php echo e($usr->id_user); ?>" value="<?php echo e($usr->name); ?>" placeholder="Имя">
                                <input type="text" class="edit-input" id="middlename_edit_<?php echo e($usr->id_user); ?>" value="<?php echo e($usr->firstname ?? ''); ?>" placeholder="Отчество">
                            </div>
                        </td>
                        <td>
                            <span class="user-field" id="phone_display_<?php echo e($usr->id_user); ?>"><?php echo e($usr->nomber_tel); ?></span>
                            <div id="phone_edit_<?php echo e($usr->id_user); ?>" style="display:none;">
                                <input type="tel" class="edit-input" id="phone_input_<?php echo e($usr->id_user); ?>" value="<?php echo e($usr->nomber_tel); ?>">
                            </div>
                        </td>
                        <td>
                            <form method="post" action="<?php echo e(route('admin.update_user_role')); ?>" class="inline-form">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id_user" value="<?php echo e($usr->id_user); ?>">
                                <select name="id_roli" class="compact-select">
                                    <option value="0" <?php echo e($usr->id_roli == 0 ? 'selected' : ''); ?>>Пользователь</option>
                                    <option value="1" <?php echo e($usr->id_roli == 1 ? 'selected' : ''); ?>>Мастер</option>
                                    <option value="2" <?php echo e($usr->id_roli == 2 ? 'selected' : ''); ?>>Администратор</option>
                                </select>
                                <button type="submit" class="btn-small">Изменить</button>
                            </form>
                        </td>
                        <td>
                            <div class="user-actions">
                                <button onclick="editUser(<?php echo e($usr->id_user); ?>)" class="category-btn edit-btn" id="edit_btn_<?php echo e($usr->id_user); ?>">Редактировать</button>
                                <div id="save_cancel_<?php echo e($usr->id_user); ?>" style="display:none;">
                                    <button onclick="saveUser(<?php echo e($usr->id_user); ?>)" class="btn-save-user">Сохранить</button>
                                </div>
                                <form method="post" action="<?php echo e(route('admin.delete_user')); ?>" class="inline-form" onsubmit="return confirm('Удалить?');">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="id_user" value="<?php echo e($usr->id_user); ?>">
                                    <button type="submit" class="category-btn delete-btn">Удалить</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
        <div class="pagination" style="margin-top:20px;text-align:center;display:flex;justify-content:center;gap:5px;">
            <?php if($user_current_page > 1): ?>
                <a href="<?php echo e(route('admin.index', ['user_page' => $user_current_page - 1])); ?>#user" class="page-link">&laquo; Назад</a>
            <?php endif; ?>
            <?php for($i = max(1, $user_current_page - 2); $i <= min($total_user_pages, $user_current_page + 2); $i++): ?>
                <?php if($i == $user_current_page): ?>
                    <span class="page-link current"><?php echo e($i); ?></span>
                <?php else: ?>
                    <a href="<?php echo e(route('admin.index', ['user_page' => $i])); ?>#user" class="page-link"><?php echo e($i); ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if($user_current_page < $total_user_pages): ?>
                <a href="<?php echo e(route('admin.index', ['user_page' => $user_current_page + 1])); ?>#user" class="page-link">Вперед &raquo;</a>
            <?php endif; ?>
        </div>
        <p style="text-align:center;color:#666;margin-top:10px;">Страница <?php echo e($user_current_page); ?> из <?php echo e($total_user_pages); ?> | Всего: <?php echo e($total_users); ?></p>
    </section>

    
    <section id="masters" class="tab-section <?php echo e($activeSection == 'masters' ? 'active' : ''); ?>">
        <h2>Мастера</h2>
        <div class="masters-filters" style="margin-bottom:20px;">
            <input type="text" id="masterSearch" placeholder="Поиск по имени мастера..." oninput="filterMasters()" style="display:block;width:100%;max-width:400px;padding:10px 12px;border:2px solid #e0e0e0;border-radius:8px;font-size:1rem;margin-bottom:12px;">
            <select id="masterServiceFilter" onchange="filterMasters()" style="padding:10px 12px;border:2px solid #d81b60;border-radius:8px;font-size:1rem;background:white;min-width:220px;">
                <option value="">Все услуги</option>
                <?php $__currentLoopData = $yslygi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ysluga): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($ysluga->id_yslygi); ?>"><?php echo e($ysluga->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div class="masters-grid" id="mastersGrid">
            <?php $__currentLoopData = $masters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $master): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $masterSvcs = \App\Models\MasterServicePivot::where('id_master', $master->id_user)
                        ->join('yslygi', 'master_services.id_yslygi', '=', 'yslygi.id_yslygi')
                        ->select('yslygi.id_yslygi', 'yslygi.name')
                        ->get();
                    $svcIds = $masterSvcs->pluck('id_yslygi')->toArray();
                ?>
                <div class="master-card" data-master-name="<?php echo e($master->FIO); ?>" data-service-ids="<?php echo e(implode(',', $svcIds)); ?>">
                    <h3><?php echo e($master->FIO); ?></h3>
                    <div class="master-services">
                        <strong>Услуги:</strong>
                        <?php if($masterSvcs->count() > 0): ?>
                            <ul><?php $__currentLoopData = $masterSvcs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($ms->name); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul>
                        <?php else: ?>
                            <p class="no-services">Нет назначенных услуг</p>
                        <?php endif; ?>
                    </div>
                    <form method="post" action="<?php echo e(route('admin.assign_services')); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id_master" value="<?php echo e($master->id_user); ?>">
                        <label>Назначить услуги:</label>
                        <select name="id_yslygi[]" multiple size="5" class="multi-select">
                            <?php $__currentLoopData = $yslygi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ysluga): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($ysluga->id_yslygi); ?>" <?php echo e(in_array($ysluga->id_yslygi, $svcIds) ? 'selected' : ''); ?>><?php echo e($ysluga->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <button type="submit">Сохранить</button>
                    </form>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>

    
    <?php
        $historyTotalClients = count($clientVisits);
        $historyTotalVisits = 0;
        $historyTotalRevenue = 0;
        foreach ($clientVisits as $data) {
            $historyTotalVisits += $data['visit_count'];
            $historyTotalRevenue += $data['total_spent'];
        }
    ?>
    <section id="history" class="tab-section <?php echo e($activeSection == 'history' ? 'active' : ''); ?>">
        <h2 class="history-page-title">История посещений клиентов</h2>

        <div class="history-stats-banner">
            <div class="history-stat-box">
                <span id="historyStatClients" class="history-stat-value"><?php echo e($historyTotalClients); ?></span>
                <span class="history-stat-label">Всего клиентов</span>
            </div>
            <div class="history-stat-box">
                <span id="historyStatVisits" class="history-stat-value"><?php echo e($historyTotalVisits); ?></span>
                <span class="history-stat-label">Всего посещений</span>
            </div>
            <div class="history-stat-box">
                <span id="historyStatRevenue" class="history-stat-value"><?php echo e(number_format($historyTotalRevenue, 2)); ?> ₽</span>
                <span class="history-stat-label">Общая выручка</span>
            </div>
        </div>

        <div class="history-search-box">
            <h3 class="history-search-title">Поиск</h3>
            <input type="text" id="historySearch" placeholder="Поиск по ФИО или номеру телефона..." class="history-search-input">
            <div class="history-search-dates">
                <input type="date" id="historyDateFrom" class="history-date-input" placeholder="дд. мм. гггг">
                <input type="date" id="historyDateTo" class="history-date-input" placeholder="дд. мм. гггг">
            </div>
        </div>

        <div id="historyClientsList" class="history-clients-list">
            <?php $__currentLoopData = $clientVisits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clientId => $data): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="history-client-card" data-client-name="<?php echo e($data['client_name']); ?>" data-client-phone="<?php echo e($data['client_phone'] ?? ''); ?>" data-visit-count="<?php echo e($data['visit_count']); ?>" data-total-spent="<?php echo e($data['total_spent']); ?>">
                    <div class="history-client-top">
                        <div class="history-client-info">
                            <h3 class="history-client-name"><?php echo e($data['client_name']); ?></h3>
                            <p class="history-client-phone"><?php echo e($data['client_phone'] ?? '—'); ?></p>
                        </div>
                        <div class="history-client-stats">
                            <div class="history-client-stat-item history-client-stat-visits"><span class="history-client-stat-num"><?php echo e($data['visit_count']); ?></span> Посещений</div>
                            <div class="history-client-stat-item history-client-stat-spent"><span class="history-client-stat-num"><?php echo e(number_format($data['total_spent'], 2)); ?> ₽</span> Потрачено</div>
                        </div>
                    </div>
                    <button type="button" class="history-btn-show-visits" onclick="toggleClientVisits(this)">Показать посещения</button>
                    <div class="history-visits-wrap" style="display:none;">
                        <table class="history-visits-table">
                            <thead><tr><th>Дата</th><th>Услуга</th><th>Мастер</th><th>Цена</th></tr></thead>
                            <tbody>
                                <?php $__currentLoopData = $data['visits']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $visit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr data-visit-date="<?php echo e(date('Y-m-d', strtotime($visit->date_time))); ?>" data-visit-price="<?php echo e($visit->service_price); ?>">
                                        <td><?php echo e($visit->date_time); ?></td>
                                        <td><?php echo e($visit->service_name); ?></td>
                                        <td><?php echo e($visit->master_name ?? '—'); ?></td>
                                        <td><?php echo e(number_format($visit->service_price, 2)); ?> ₽</td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>

    
    <section id="month" class="tab-section <?php echo e($activeSection == 'month' ? 'active' : ''); ?>">
        <h2>Записи на месяц</h2>
        <div id="monthStatsPlaceholder"></div>
        <div id="monthCalendarContainer"><p>Загрузка...</p></div>
    </section>

    
    <section id="reports" class="tab-section <?php echo e($activeSection == 'reports' ? 'active' : ''); ?>">
        <h2 class="reports-main-title">Генерация отчетов в Excel</h2>
        <div class="reports-panel">
            
            <div class="report-card">
                <div class="report-card-header">
                    <span class="report-icon report-icon-ruble">₽</span>
                    <h3 class="report-card-title">Отчет по выручке</h3>
                </div>
                <p class="report-card-desc">Сводный отчет по доходам за выбранный период с разбивкой по услугам и мастерам</p>
                <form action="<?php echo e(route('admin.generate_excel_revenue')); ?>" method="get" class="report-form">
                    <div class="report-fields">
                        <label>Дата от:</label>
                        <input type="date" name="date_from" required placeholder="дд . мм . гггг">
                    </div>
                    <div class="report-fields">
                        <label>Дата до:</label>
                        <input type="date" name="date_to" required placeholder="дд . мм . гггг">
                    </div>
                    <div class="report-fields">
                        <label>Мастер (необязательно):</label>
                        <select name="master_id">
                            <option value="">Все мастера</option>
                            <?php $__currentLoopData = $masters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($m->id_user); ?>"><?php echo e($m->FIO); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <button type="submit" class="report-submit-btn">Сгенерировать Excel</button>
                </form>
            </div>

            
            <div class="report-card">
                <div class="report-card-header">
                    <span class="report-icon report-icon-star">★</span>
                    <h3 class="report-card-title">Эффективность мастеров</h3>
                </div>
                <p class="report-card-desc">Сравнительный анализ работы мастеров: количество записей, выручка, загруженность</p>
                <form action="<?php echo e(route('admin.generate_excel_efficiency')); ?>" method="get" class="report-form">
                    <div class="report-fields">
                        <label>Дата от:</label>
                        <input type="date" name="date_from" required placeholder="дд . мм . гггг">
                    </div>
                    <div class="report-fields">
                        <label>Дата до:</label>
                        <input type="date" name="date_to" required placeholder="дд . мм . гггг">
                    </div>
                    <button type="submit" class="report-submit-btn">Сгенерировать Excel</button>
                </form>
            </div>

            
            <div class="report-card">
                <div class="report-card-header">
                    <span class="report-icon report-icon-dollar">$</span>
                    <h3 class="report-card-title">Отчет по зарплате сотрудников</h3>
                </div>
                <p class="report-card-desc">Расчет заработной платы мастеров на основе выполненных услуг за период</p>
                <form action="<?php echo e(route('admin.generate_excel_salary')); ?>" method="get" class="report-form">
                    <div class="report-fields">
                        <label>Дата от:</label>
                        <input type="date" name="date_from" required placeholder="дд . мм . гггг">
                    </div>
                    <div class="report-fields">
                        <label>Дата до:</label>
                        <input type="date" name="date_to" required placeholder="дд . мм . гггг">
                    </div>
                    <div class="report-fields">
                        <label>Процент от выручки (%):</label>
                        <input type="number" name="percent" value="50" min="1" max="100" step="1">
                    </div>
                    <button type="submit" class="report-submit-btn">Сгенерировать Excel</button>
                </form>
            </div>
        </div>
    </section>

    
    <section id="schedule" class="tab-section <?php echo e($activeSection == 'schedule' ? 'active' : ''); ?>">
        <h2 class="schedule-page-title">График работы мастеров</h2>
        <input type="text" id="scheduleMasterSearch" placeholder="Введите ФИО мастера..." class="schedule-search-input" oninput="filterScheduleMasters()">
        <div id="scheduleMastersList">
        <?php $__currentLoopData = $masters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $master): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $masterScheduleData = ($schedules[$master->id_user] ?? collect())->keyBy('day_of_week');
                $masterDaysOff = $daysOff[$master->id_user] ?? collect();
            ?>
            <div class="schedule-master-card" data-master-fio="<?php echo e($master->FIO); ?>" style="background:white;border:1px solid #d81b60;border-radius:8px;padding:20px;margin-bottom:30px;">
                <h2 style="color:#d81b60;margin-top:0;"><?php echo e($master->FIO); ?></h2>
                <form method="post" action="<?php echo e(route('admin.update_schedule')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="id_master" value="<?php echo e($master->id_user); ?>">
                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin:20px 0;">
                        <?php $__currentLoopData = $daysOfWeek; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayNum => $dayName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $hasSched = isset($masterScheduleData[$dayNum]); $st = $hasSched ? substr($masterScheduleData[$dayNum]->start_time, 0, 5) : '09:00'; $et = $hasSched ? substr($masterScheduleData[$dayNum]->end_time, 0, 5) : '21:00'; ?>
                            <div style="background:#f9f9f9;padding:12px;border-radius:6px;border-left:4px solid #d81b60;">
                                <label style="display:flex;align-items:center;gap:10px;font-weight:bold;">
                                    <input type="checkbox" name="schedule[<?php echo e($dayNum); ?>][enabled]" <?php echo e($hasSched ? 'checked' : ''); ?> onchange="toggleDayInputs(this)"> <?php echo e($dayName); ?>

                                </label>
                                <div class="time-inputs" style="display:<?php echo e($hasSched ? 'flex' : 'none'); ?>;gap:10px;margin-left:28px;margin-top:8px;">
                                    <input type="time" name="schedule[<?php echo e($dayNum); ?>][start]" value="<?php echo e($st); ?>" <?php echo e($hasSched ? '' : 'disabled'); ?> style="padding:6px;border:1px solid #ddd;border-radius:4px;">
                                    <span>—</span>
                                    <input type="time" name="schedule[<?php echo e($dayNum); ?>][end]" value="<?php echo e($et); ?>" <?php echo e($hasSched ? '' : 'disabled'); ?> style="padding:6px;border:1px solid #ddd;border-radius:4px;">
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <button type="submit" style="background-color:#4caf50;color:white;border:none;padding:10px 20px;border-radius:4px;cursor:pointer;">Сохранить график</button>
                </form>
                <div style="margin-top:20px;padding-top:15px;border-top:2px solid #f0f0f0;">
                    <h3 style="color:#d81b60;">Выходные дни</h3>
                    <?php $__empty_1 = true; $__currentLoopData = $masterDaysOff; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayOff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div style="background:#fff3e0;padding:10px;border-radius:6px;border-left:4px solid #ff9800;display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                            <div><strong style="color:#ff9800;"><?php echo e($dayOff->date_off->format('d.m.Y')); ?></strong> <?php if($dayOff->reason): ?> <br><small><?php echo e($dayOff->reason); ?></small> <?php endif; ?></div>
                            <form method="post" action="<?php echo e(route('admin.delete_day_off')); ?>" onsubmit="return confirm('Удалить?');"><?php echo csrf_field(); ?><input type="hidden" name="id_day_off" value="<?php echo e($dayOff->id_day_off); ?>"><button type="submit" style="background:#f44336;color:white;border:none;padding:6px 12px;border-radius:4px;cursor:pointer;">Удалить</button></form>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p style="color:#999;">Нет запланированных выходных</p>
                    <?php endif; ?>
                    <form method="post" action="<?php echo e(route('admin.add_day_off')); ?>" style="background:#f9f9f9;padding:12px;border-radius:6px;display:grid;gap:8px;margin-top:10px;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="id_master" value="<?php echo e($master->id_user); ?>">
                        <input type="date" name="date_off" min="<?php echo e(date('Y-m-d')); ?>" required style="padding:8px;border:1px solid #ddd;border-radius:4px;">
                        <textarea name="reason" placeholder="Причина (необязательно)" style="padding:8px;border:1px solid #ddd;border-radius:4px;min-height:50px;"></textarea>
                        <button type="submit" class="category-btn delete-btn">Добавить выходной</button>
                    </form>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </section>

    
    <div id="createBookingModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeCreateBookingModal()">&times;</span>
            <h2>Создать запись</h2>
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('existing')">Существующий клиент</button>
                <button class="tab-btn" onclick="switchTab('new')">Новый клиент</button>
            </div>
            <div id="existingClientTab" class="tab-content active">
                <form id="createBookingForm" action="<?php echo e(route('admin.add_booking')); ?>" method="post">
                    <?php echo csrf_field(); ?>
                    <label>Клиент:</label>
                    <select name="user_id" required>
                        <option value="">Выберите клиента</option>
                        <?php $__currentLoopData = $users->where('id_roli', 0); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $usr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($usr->id_user); ?>"><?php echo e($usr->FIO); ?> (<?php echo e($usr->nomber_tel); ?>)</option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <label>Услуга:</label>
                    <select name="yslygi_id" id="create_service_id" required onchange="loadMastersForService(this.value, 'create_master')">
                        <option value="">Выберите услугу</option>
                        <?php $__currentLoopData = $yslygi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $svc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($svc->id_yslygi); ?>"><?php echo e($svc->name); ?> - <?php echo e($svc->price); ?>&#8381;</option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <label>Дата:</label>
                    <input type="date" name="booking_date" min="<?php echo e(date('Y-m-d')); ?>" value="<?php echo e(date('Y-m-d')); ?>" required>
                    <label>Время:</label>
                    <select name="time_slot" id="create_time_select" required>
                        <?php for($h = 9; $h <= 20; $h++): ?><option value="<?php echo e(str_pad($h, 2, '0', STR_PAD_LEFT)); ?>:00"><?php echo e(str_pad($h, 2, '0', STR_PAD_LEFT)); ?>:00</option><?php endfor; ?>
                    </select>
                    <label>Мастер:</label>
                    <select name="id_master" id="create_master" required><option value="">Сначала выберите услугу</option></select>
                    <button type="submit">Создать запись</button>
                </form>
            </div>
            <div id="newClientTab" class="tab-content" style="display:none;">
                <form action="<?php echo e(route('admin.create_client_booking')); ?>" method="post">
                    <?php echo csrf_field(); ?>
                    <h3>Данные нового клиента</h3>
                    <input type="text" name="client_lastname" required placeholder="Фамилия">
                    <input type="text" name="client_name" required placeholder="Имя">
                    <input type="text" name="client_firstname" placeholder="Отчество">
                    <input type="tel" name="client_phone" required placeholder="+7 (___) ___-__-__">
                    <input type="password" name="client_password" required minlength="6" placeholder="Пароль">
                    <h3>Данные записи</h3>
                    <select name="yslygi_id" required>
                        <option value="">Выберите услугу</option>
                        <?php $__currentLoopData = $yslygi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $svc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($svc->id_yslygi); ?>"><?php echo e($svc->name); ?> - <?php echo e($svc->price); ?>&#8381;</option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <input type="date" name="booking_date" min="<?php echo e(date('Y-m-d')); ?>" value="<?php echo e(date('Y-m-d')); ?>" required>
                    <select name="time_slot" required>
                        <?php for($h = 9; $h <= 20; $h++): ?><option value="<?php echo e(str_pad($h, 2, '0', STR_PAD_LEFT)); ?>:00"><?php echo e(str_pad($h, 2, '0', STR_PAD_LEFT)); ?>:00</option><?php endfor; ?>
                    </select>
                    <select name="id_master" required>
                        <?php $__currentLoopData = $masters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($m->id_user); ?>"><?php echo e($m->FIO); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button type="submit">Создать клиента и запись</button>
                </form>
            </div>
        </div>
    </div>

    
    <div id="editBookingModal" class="modal" style="display:none;">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Редактирование записи</h2>
            <form action="<?php echo e(route('admin.edit_appointment')); ?>" method="post">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="booking_id" id="edit_booking_id">
                <label>Дата:</label>
                <input type="date" name="booking_date" id="edit_booking_date" min="<?php echo e(date('Y-m-d')); ?>" required>
                <label>Время:</label>
                <select name="time_slot" id="edit_time_select" required>
                    <?php for($h = 9; $h <= 20; $h++): ?><option value="<?php echo e(str_pad($h, 2, '0', STR_PAD_LEFT)); ?>:00"><?php echo e(str_pad($h, 2, '0', STR_PAD_LEFT)); ?>:00</option><?php endfor; ?>
                </select>
                <label>Услуга:</label>
                <select name="yslygi_id" id="edit_service_id" required>
                    <?php $__currentLoopData = $yslygi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $svc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($svc->id_yslygi); ?>"><?php echo e($svc->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <label>Мастер:</label>
                <select name="id_master" id="edit_master" required>
                    <?php $__currentLoopData = $masters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($m->id_user); ?>"><?php echo e($m->FIO); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <button type="submit">Сохранить изменения</button>
            </form>
        </div>
    </div>
</div>

<script>
function showSection(sectionId) {
    // Скрыть все секции и убрать active
    document.querySelectorAll('.tab-section').forEach(s => {
        s.classList.remove('active');
        s.style.display = 'none';
    });
    // Убрать active у всех кнопок навигации
    document.querySelectorAll('nav button').forEach(b => b.classList.remove('active'));
    // Показать выбранную секцию
    const section = document.getElementById(sectionId);
    if (section) {
        section.classList.add('active');
        section.style.display = 'block';
    }
    const navBtn = document.getElementById('nav-' + sectionId);
    if (navBtn) navBtn.classList.add('active');
    // Загрузить календарь при первом переключении на вкладку "Месяц"
    if (sectionId === 'month' && !monthLoaded) {
        loadMonthCalendar();
    }
    // Сохранить активную секцию на сервере
    fetch('<?php echo e(route("admin.save_section")); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '<?php echo e(csrf_token()); ?>' },
        body: JSON.stringify({ section: sectionId })
    });
}

function editService(id) {
    const form = document.getElementById('editServiceForm' + id);
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
}
function cancelEdit(id) { document.getElementById('editServiceForm' + id).style.display = 'none'; }

document.getElementById('addServiceBtn')?.addEventListener('click', () => { document.getElementById('addServiceModal').style.display = 'block'; });
document.querySelectorAll('.modal .close').forEach(btn => { btn.addEventListener('click', function() { this.closest('.modal').style.display = 'none'; }); });

function openAddCategoryModal() { document.getElementById('addCategoryModal').style.display = 'block'; }
function closeAddCategoryModal() { document.getElementById('addCategoryModal').style.display = 'none'; }
function openEditCategoryModal(id, name) { document.getElementById('edit_category_id').value = id; document.getElementById('edit_category_name').value = name; document.getElementById('editCategoryModal').style.display = 'block'; }
function closeEditCategoryModal() { document.getElementById('editCategoryModal').style.display = 'none'; }

function openCreateBookingModal() { document.getElementById('createBookingModal').style.display = 'block'; }
function closeCreateBookingModal() { document.getElementById('createBookingModal').style.display = 'none'; }

function switchTab(tab) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => { c.classList.remove('active'); c.style.display = 'none'; });
    if (tab === 'existing') { document.getElementById('existingClientTab').style.display = 'block'; document.getElementById('existingClientTab').classList.add('active'); document.querySelectorAll('.tab-btn')[0].classList.add('active'); }
    else { document.getElementById('newClientTab').style.display = 'block'; document.getElementById('newClientTab').classList.add('active'); document.querySelectorAll('.tab-btn')[1].classList.add('active'); }
}

function editBooking(id) {
    document.getElementById('edit_booking_id').value = id;
    fetch(`<?php echo e(route('admin.get_booking')); ?>?booking_id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data) {
                const dt = new Date(data.date_time);
                document.getElementById('edit_booking_date').value = dt.toISOString().split('T')[0];
                document.getElementById('edit_service_id').value = data.yslygi_id;
                document.getElementById('edit_master').value = data.id_master;
            }
            document.getElementById('editBookingModal').style.display = 'block';
        });
}
function closeEditModal() { document.getElementById('editBookingModal').style.display = 'none'; }

function editUser(userId) {
    document.getElementById('name_display_' + userId).style.display = 'none';
    document.getElementById('name_edit_' + userId).style.display = 'block';
    document.getElementById('phone_display_' + userId).style.display = 'none';
    document.getElementById('phone_edit_' + userId).style.display = 'block';
    document.getElementById('edit_btn_' + userId).style.display = 'none';
    document.getElementById('save_cancel_' + userId).style.display = 'flex';
}

function saveUser(userId) {
    const formData = new FormData();
    formData.append('user_id', userId);
    formData.append('lastname', document.getElementById('lastname_edit_' + userId).value.trim());
    formData.append('name', document.getElementById('firstname_edit_' + userId).value.trim());
    formData.append('firstname', document.getElementById('middlename_edit_' + userId).value.trim());
    formData.append('phone', document.getElementById('phone_input_' + userId).value.trim());
    formData.append('_token', '<?php echo e(csrf_token()); ?>');
    fetch('<?php echo e(route("admin.update_user_info")); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('name_display_' + userId).textContent = data.full_name;
                document.getElementById('phone_display_' + userId).textContent = data.phone;
                document.getElementById('name_display_' + userId).style.display = 'block';
                document.getElementById('name_edit_' + userId).style.display = 'none';
                document.getElementById('phone_display_' + userId).style.display = 'block';
                document.getElementById('phone_edit_' + userId).style.display = 'none';
                document.getElementById('edit_btn_' + userId).style.display = 'inline-block';
                document.getElementById('save_cancel_' + userId).style.display = 'none';
                alert(data.message);
            } else { alert(data.message || 'Ошибка'); }
        })
        .catch(error => { alert('Ошибка при сохранении'); });
}

function filterUsers() {
    const search = document.getElementById('userSearch').value.toLowerCase();
    const role = document.getElementById('userRoleFilter').value;
    document.querySelectorAll('#userTableBody tr').forEach(row => {
        const name = row.dataset.name.toLowerCase();
        const phone = row.dataset.phone.toLowerCase();
        const rowRole = row.dataset.role;
        const matchSearch = name.includes(search) || phone.includes(search);
        const matchRole = !role || rowRole === role;
        row.style.display = matchSearch && matchRole ? '' : 'none';
    });
}

function loadMastersForService(serviceId, selectId) {
    const select = document.getElementById(selectId);
    select.innerHTML = '<option>Загрузка...</option>';
    fetch(`<?php echo e(route('booking.masters')); ?>?service_id=${serviceId}`)
        .then(r => r.json())
        .then(data => {
            select.innerHTML = '<option value="">Выберите мастера</option>';
            data.forEach(m => { select.innerHTML += `<option value="${m.id_user}">${m.FIO}</option>`; });
        });
}

function toggleDayInputs(checkbox) {
    const container = checkbox.closest('div').parentElement;
    const inputs = container.querySelector('.time-inputs');
    const timeInputs = inputs.querySelectorAll('input[type="time"]');
    if (checkbox.checked) { inputs.style.display = 'flex'; timeInputs.forEach(i => i.disabled = false); }
    else { inputs.style.display = 'none'; timeInputs.forEach(i => i.disabled = true); }
}

// ===== MONTH CALENDAR =====
let currentMonth = <?php echo e(date('n')); ?>;
let currentYear = <?php echo e(date('Y')); ?>;
let monthLoaded = false;

function loadMonthCalendar(month, year) {
    if (month !== undefined) currentMonth = month;
    if (year !== undefined) currentYear = year;

    const container = document.getElementById('monthCalendarContainer');
    const statsPlaceholder = document.getElementById('monthStatsPlaceholder');
    if (statsPlaceholder) statsPlaceholder.innerHTML = '';
    container.innerHTML = '<p style="text-align:center;padding:40px;color:#999;">Загрузка...</p>';

    fetch(`<?php echo e(route('admin.month_ajax')); ?>?month=${currentMonth}&year=${currentYear}`)
        .then(r => r.json())
        .then(data => {
            renderMonthCalendar(data);
            monthLoaded = true;
        })
        .catch(err => {
            container.innerHTML = '<p style="text-align:center;padding:40px;color:#f44336;">Ошибка загрузки данных</p>';
            console.error('Month load error:', err);
        });
}

function renderMonthCalendar(data) {
    const container = document.getElementById('monthCalendarContainer');
    const month = data.month;
    const year = data.year;
    const bookings = data.bookings || {};

    const monthNames = ['', 'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
        'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'];
    const dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

    // Количество дней в месяце
    const daysInMonth = new Date(year, month, 0).getDate();
    // День недели первого числа (0=Вс, 1=Пн, ...) -> переводим в Пн=0
    let firstDayOfWeek = new Date(year, month - 1, 1).getDay();
    firstDayOfWeek = firstDayOfWeek === 0 ? 6 : firstDayOfWeek - 1; // Пн=0, Вт=1, ... Вс=6

    // Предыдущий/следующий месяц
    let prevMonth = month - 1, prevYear = year;
    if (prevMonth < 1) { prevMonth = 12; prevYear--; }
    let nextMonth = month + 1, nextYear = year;
    if (nextMonth > 12) { nextMonth = 1; nextYear++; }

    // Подсчёт общих показателей и уникальных клиентов
    let totalBookings = 0, totalRevenue = 0, uniqueClientIds = new Set();
    Object.values(bookings).forEach(dayBookings => {
        dayBookings.forEach(b => {
            totalBookings++;
            totalRevenue += parseFloat(b.service_price || 0);
            if (b.user_id) uniqueClientIds.add(b.user_id);
        });
    });
    const uniqueClients = uniqueClientIds.size;

    // Статистика за месяц сверху (вместо фильтра)
    const statsPlaceholder = document.getElementById('monthStatsPlaceholder');
    if (statsPlaceholder) {
        statsPlaceholder.innerHTML = '<div class="month-stats-block">' +
            '<p class="month-stats-subtitle">Статистика за ' + monthNames[month] + ' ' + year + '</p>' +
            '<div class="month-stats-cards">' +
            '<div class="month-stat-card"><div class="month-stat-value">' + totalBookings + '</div><div class="month-stat-label">Всего записей</div></div>' +
            '<div class="month-stat-card"><div class="month-stat-value">' + uniqueClients + '</div><div class="month-stat-label">Уникальных клиентов</div></div>' +
            '<div class="month-stat-card"><div class="month-stat-value">' + totalRevenue.toFixed(2) + ' ₽</div><div class="month-stat-label">Общая выручка</div></div>' +
            '</div></div>';
    }

    let html = '<div class="calendar-container">';

    // Заголовок с навигацией
    html += '<div class="calendar-header">';
    html += `<a href="javascript:void(0)" onclick="loadMonthCalendar(${prevMonth}, ${prevYear})">&larr;</a>`;
    html += `<h1 style="margin:0;font-size:1.8rem;">${monthNames[month]} ${year}</h1>`;
    html += `<a href="javascript:void(0)" onclick="loadMonthCalendar(${nextMonth}, ${nextYear})">&rarr;</a>`;
    html += '</div>';

    // Дни недели
    html += '<div class="weekdays">';
    dayNames.forEach(d => { html += `<div>${d}</div>`; });
    html += '</div>';

    // Сетка дней
    html += '<div class="days">';

    // Пустые ячейки до первого дня
    for (let i = 0; i < firstDayOfWeek; i++) {
        html += '<div class="day empty"></div>';
    }

    // Дни месяца
    for (let d = 1; d <= daysInMonth; d++) {
        const dayBookings = bookings[d] || [];
        const hasEvents = dayBookings.length > 0;
        const cls = hasEvents ? 'day has-events' : 'day';

        html += `<div class="${cls}">`;
        html += `<span class="date">${d}</span>`;

        if (hasEvents) {
            html += '<div class="events">';
            dayBookings.forEach(b => {
                const time = b.date_time ? b.date_time.substring(11, 16) : '';
                const btnId = 'eventBtns_' + b.id_zapis;
                html += `<div class="event" style="cursor:pointer;transition:transform 0.15s,box-shadow 0.15s;" onclick="toggleEventButtons('${btnId}')" onmouseenter="this.style.transform='scale(1.03)';this.style.boxShadow='0 3px 8px rgba(216,27,96,0.25)';" onmouseleave="this.style.transform='';this.style.boxShadow='';">`;
                html += `<p class="event-time">${time}</p>`;
                html += `<p class="event-service">${escapeHtmlAdmin(b.service_name || '')}</p>`;
                html += `<p class="event-client">Клиент: ${escapeHtmlAdmin(b.client_name || '')}</p>`;
                html += `<p class="event-phone">Телефон: ${escapeHtmlAdmin(b.client_phone || '')}</p>`;
                if (b.master_name) html += `<p class="event-master">Мастер: ${escapeHtmlAdmin(b.master_name)}</p>`;
                html += `<p class="event-price">${parseFloat(b.service_price || 0).toFixed(2)} ₽</p>`;
                html += `<div id="${btnId}" style="display:none;margin-top:6px;gap:6px;justify-content:center;" class="event-action-buttons">`;
                html += `<button onclick="event.stopPropagation(); editBooking(${b.id_zapis})" style="flex:1;padding:5px 8px;font-size:0.75rem;background:#4caf50;color:white;border:none;border-radius:4px;cursor:pointer;">Редактировать</button>`;
                html += `<button onclick="event.stopPropagation(); cancelBookingFromCalendar(${b.id_zapis})" style="flex:1;padding:5px 8px;font-size:0.75rem;background:#e91e63;color:white;border:none;border-radius:4px;cursor:pointer;">Отменить</button>`;
                html += `</div>`;
                html += '</div>';
            });
            html += '</div>';
        }

        html += '</div>';
    }

    // Пустые ячейки после последнего дня (до заполнения 7 столбцов)
    const totalCells = firstDayOfWeek + daysInMonth;
    const remaining = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);
    for (let i = 0; i < remaining; i++) {
        html += '<div class="day empty"></div>';
    }

    html += '</div>'; // .days

    html += '</div>'; // .calendar-container

    container.innerHTML = html;
}

function escapeHtmlAdmin(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== EVENT INLINE BUTTONS (month calendar) =====
function toggleEventButtons(btnId) {
    // Скрыть все остальные открытые кнопки
    document.querySelectorAll('.event-action-buttons').forEach(el => {
        if (el.id !== btnId) el.style.display = 'none';
    });
    const btns = document.getElementById(btnId);
    if (btns) {
        btns.style.display = btns.style.display === 'flex' ? 'none' : 'flex';
    }
}

function cancelBookingFromCalendar(bookingId) {
    if (!confirm('Вы уверены, что хотите отменить эту запись?')) return;

    const formData = new FormData();
    formData.append('booking_id', bookingId);
    formData.append('_token', '<?php echo e(csrf_token()); ?>');

    fetch('<?php echo e(route("admin.cancel_appointment")); ?>', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        monthLoaded = false;
        loadMonthCalendar();
        const notif = document.createElement('div');
        notif.className = 'notification success';
        notif.textContent = data.message || 'Запись успешно отменена';
        document.body.appendChild(notif);
        setTimeout(() => notif.remove(), 3000);
    })
    .catch(err => {
        console.error('Cancel error:', err);
        alert('Ошибка при отмене записи');
    });
}

// ===== HISTORY PAGE: show/hide visits and filters =====
function toggleClientVisits(btn) {
    const wrap = btn.closest('.history-client-card').querySelector('.history-visits-wrap');
    if (!wrap) return;
    const isHidden = wrap.style.display === 'none';
    wrap.style.display = isHidden ? 'block' : 'none';
    btn.textContent = isHidden ? 'Скрыть посещения' : 'Показать посещения';
}

function applyHistoryFilters() {
    const search = (document.getElementById('historySearch')?.value || '').trim().toLowerCase();
    const dateFrom = (document.getElementById('historyDateFrom')?.value || '').trim();
    const dateTo = (document.getElementById('historyDateTo')?.value || '').trim();

    let totalClients = 0, totalVisits = 0, totalRevenue = 0;

    document.querySelectorAll('#historyClientsList .history-client-card').forEach(card => {
        const name = (card.getAttribute('data-client-name') || '').toLowerCase();
        const phone = (card.getAttribute('data-client-phone') || '').replace(/\D/g, '');
        const searchNum = search.replace(/\D/g, '');
        const matchSearch = !search || name.indexOf(search) !== -1 || (searchNum && phone.indexOf(searchNum) !== -1);

        const rows = card.querySelectorAll('.history-visits-table tbody tr');
        let visibleRows = 0, rowRevenue = 0;
        rows.forEach(tr => {
            const rowDate = tr.getAttribute('data-visit-date') || '';
            const inRange = (!dateFrom || rowDate >= dateFrom) && (!dateTo || rowDate <= dateTo);
            tr.style.display = inRange ? '' : 'none';
            if (inRange) {
                visibleRows++;
                rowRevenue += parseFloat(tr.getAttribute('data-visit-price') || 0);
            }
        });

        const matchDate = !dateFrom && !dateTo || visibleRows > 0;
        const show = matchSearch && matchDate;
        card.style.display = show ? '' : 'none';
        if (show) {
            totalClients++;
            totalVisits += visibleRows;
            totalRevenue += rowRevenue;
        }
        // Обновить отображаемые в карточке «Посещений» и «Потрачено» при фильтре по датам
        const visitsEl = card.querySelector('.history-client-stat-visits .history-client-stat-num');
        const spentEl = card.querySelector('.history-client-stat-spent .history-client-stat-num');
        if (dateFrom || dateTo) {
            if (visitsEl) visitsEl.textContent = visibleRows;
            if (spentEl) spentEl.textContent = rowRevenue.toFixed(2) + ' ₽';
        } else {
            if (visitsEl) visitsEl.textContent = card.getAttribute('data-visit-count');
            if (spentEl) spentEl.textContent = parseFloat(card.getAttribute('data-total-spent') || 0).toFixed(2) + ' ₽';
        }
    });

    const statClients = document.getElementById('historyStatClients');
    const statVisits = document.getElementById('historyStatVisits');
    const statRevenue = document.getElementById('historyStatRevenue');
    if (statClients) statClients.textContent = totalClients;
    if (statVisits) statVisits.textContent = totalVisits;
    if (statRevenue) statRevenue.textContent = totalRevenue.toFixed(2) + ' ₽';
}

document.addEventListener('DOMContentLoaded', function() {
    const historySearch = document.getElementById('historySearch');
    const historyDateFrom = document.getElementById('historyDateFrom');
    const historyDateTo = document.getElementById('historyDateTo');
    if (historySearch) historySearch.addEventListener('input', applyHistoryFilters);
    if (historyDateFrom) historyDateFrom.addEventListener('change', applyHistoryFilters);
    if (historyDateTo) historyDateTo.addEventListener('change', applyHistoryFilters);
});

// ===== SCHEDULE PAGE: search master by FIO =====
function filterScheduleMasters() {
    const search = (document.getElementById('scheduleMasterSearch')?.value || '').trim().toLowerCase();
    document.querySelectorAll('#scheduleMastersList .schedule-master-card').forEach(card => {
        const fio = (card.getAttribute('data-master-fio') || '').toLowerCase();
        card.style.display = !search || fio.indexOf(search) !== -1 ? '' : 'none';
    });
}

// ===== MASTERS PAGE: search and filter =====
function filterMasters() {
    const search = (document.getElementById('masterSearch')?.value || '').trim().toLowerCase();
    const serviceId = (document.getElementById('masterServiceFilter')?.value || '').trim();
    document.querySelectorAll('#mastersGrid .master-card').forEach(card => {
        const name = (card.getAttribute('data-master-name') || '').toLowerCase();
        const ids = (card.getAttribute('data-service-ids') || '').split(',').filter(Boolean);
        const matchName = !search || name.indexOf(search) !== -1;
        const matchService = !serviceId || ids.indexOf(serviceId) !== -1;
        card.style.display = (matchName && matchService) ? '' : 'none';
    });
}

// Init: show active section on page load
document.addEventListener('DOMContentLoaded', function() {
    const active = '<?php echo e($activeSection); ?>' || 'shift';
    // Сначала скрываем все секции, потом показываем активную
    document.querySelectorAll('.tab-section').forEach(s => {
        s.classList.remove('active');
        s.style.display = 'none';
    });
    document.querySelectorAll('nav button').forEach(b => b.classList.remove('active'));
    const section = document.getElementById(active);
    if (section) {
        section.classList.add('active');
        section.style.display = 'block';
    }
    const navBtn = document.getElementById('nav-' + active);
    if (navBtn) navBtn.classList.add('active');
    // Если активная вкладка — "Месяц", загрузить календарь
    if (active === 'month') {
        loadMonthCalendar();
    }
});
</script>
</body>
</html>
<?php /**PATH C:\Users\alesy\OneDrive\Рабочий стол\PHP Coursach\resources\views/admin/index.blade.php ENDPATH**/ ?>