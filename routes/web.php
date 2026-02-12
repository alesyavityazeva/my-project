<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\Master\MasterController;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Support\Facades\Route;

// ===== Public Routes =====
Route::get('/', [HomeController::class, 'redirectByRole'])->name('index');
Route::get('/home', [HomeController::class, 'index'])->name('home');
Route::get('/search-services', [HomeController::class, 'searchServices'])->name('search.services');

// ===== Auth Routes =====
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

// ===== Booking API (available to all authenticated users) =====
Route::post('/add-booking', [BookingController::class, 'store'])->name('booking.store');
Route::post('/edit-booking', [BookingController::class, 'update'])->name('booking.update');
Route::get('/get-available-slots', [BookingController::class, 'getAvailableSlots'])->name('booking.available_slots');
Route::get('/get-available-masters', [BookingController::class, 'getAvailableMasters'])->name('booking.available_masters');
Route::get('/get-masters', [BookingController::class, 'getMasters'])->name('booking.masters');

// ===== Authenticated Routes =====
Route::middleware('auth')->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
    Route::post('/update-profile', [ProfileController::class, 'update'])->name('profile.update');

    // Booking actions
    Route::post('/cancel-booking', [BookingController::class, 'cancel'])->name('booking.cancel');

    // Favorites
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites');
    Route::post('/add-to-favorites', [FavoriteController::class, 'add'])->name('favorites.add');
    Route::post('/remove-from-favorites', [FavoriteController::class, 'remove'])->name('favorites.remove');

    // Help pages
    Route::get('/help', function () {
        $role = auth()->user()->id_roli;
        return match ($role) {
            2 => view('help.admin'),
            1 => view('help.master'),
            default => view('help.client'),
        };
    })->name('help');
});

// ===== Master Routes =====
Route::middleware(['auth', 'role:1'])->prefix('master')->name('master.')->group(function () {
    Route::get('/', [MasterController::class, 'index'])->name('index');
    Route::get('/client-history', [MasterController::class, 'getClientHistory'])->name('client_history');
});

// ===== Admin Routes =====
Route::middleware(['auth', 'role:2'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::post('/save-section', [AdminController::class, 'saveActiveSection'])->name('save_section');

    // Services
    Route::post('/create-service', [AdminController::class, 'createService'])->name('create_service');
    Route::post('/edit-service', [AdminController::class, 'editService'])->name('edit_service');
    Route::post('/delete-service', [AdminController::class, 'deleteService'])->name('delete_service');

    // Categories
    Route::post('/create-category', [AdminController::class, 'createCategory'])->name('create_category');
    Route::post('/edit-category', [AdminController::class, 'editCategory'])->name('edit_category');
    Route::post('/delete-category', [AdminController::class, 'deleteCategory'])->name('delete_category');

    // Users
    Route::post('/create-client', [AdminController::class, 'createClient'])->name('create_client');
    Route::post('/update-user-role', [AdminController::class, 'updateUserRole'])->name('update_user_role');
    Route::post('/update-user-info', [AdminController::class, 'updateUserInfo'])->name('update_user_info');
    Route::post('/delete-user', [AdminController::class, 'deleteUser'])->name('delete_user');

    // Master services
    Route::post('/assign-services', [AdminController::class, 'assignServices'])->name('assign_services');

    // Schedule
    Route::post('/update-schedule', [AdminController::class, 'updateSchedule'])->name('update_schedule');
    Route::post('/add-day-off', [AdminController::class, 'addDayOff'])->name('add_day_off');
    Route::post('/delete-day-off', [AdminController::class, 'deleteDayOff'])->name('delete_day_off');

    // Bookings
    Route::post('/cancel-appointment', [AdminController::class, 'cancelAppointment'])->name('cancel_appointment');
    Route::post('/edit-appointment', [AdminController::class, 'editAppointment'])->name('edit_appointment');
    Route::get('/get-booking', [AdminController::class, 'getBooking'])->name('get_booking');
    Route::post('/add-booking', [AdminController::class, 'adminAddBooking'])->name('add_booking');
    Route::post('/create-client-booking', [AdminController::class, 'adminCreateClientBooking'])->name('create_client_booking');
    Route::get('/get-categories', [AdminController::class, 'getCategories'])->name('get_categories');
    Route::get('/month-ajax', [AdminController::class, 'monthAjax'])->name('month_ajax');

    // Reports (Excel export)
    Route::get('/generate-excel-revenue', [AdminController::class, 'generateExcelRevenue'])->name('generate_excel_revenue');
    Route::get('/generate-excel-efficiency', [AdminController::class, 'generateExcelEfficiency'])->name('generate_excel_efficiency');
    Route::get('/generate-excel-salary', [AdminController::class, 'generateExcelSalary'])->name('generate_excel_salary');
});
