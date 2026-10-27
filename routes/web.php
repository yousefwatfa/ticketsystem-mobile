<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AdminReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ViewerTicketController;
use App\Http\Controllers\AdminNoteController;
use App\Http\Controllers\FinishedTicketController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StructureController;
use App\Http\Controllers\PushSubscriptionController;
use App\Events\RealTimeNotification;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

Route::view('install', 'pwa.install')->name('pwa.install');

Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthController::class, 'login']);
// Route::get('register', [AuthController::class, 'showRegisterForm'])->name('register');
// Route::post('register', [AuthController::class, 'register']);
Route::post('logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {

Route::post('/notifications/mark-as-read', function () {
    auth()->user()->unreadNotifications->markAsRead();
    return response()->json(['status' => 'success']);
})->name('notifications.markRead');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Web push subscriptions (PWA)
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');

    Route::prefix('profile')->name('profile.')->controller(ProfileController::class)->group(function () {

        Route::get('/', 'index')->name('index');
        Route::put('/update','update')->name('update');
        Route::put('/update-phone',  'updatePhone')->name('update-phone');
        Route::put('/update-national',  'updateNationalId')->name('update-national');
        Route::put('/update-office',  'updateOfficeLocation')->name('update-office');
        Route::post('/telegram-link', 'generateTelegramLink')->name('telegram-link.generate');
        Route::match(['get','post'], '/telegram-test', 'testTelegramNotification')->name('telegram.test');


    });

    // Member Routes
    Route::delete('tickets/{attachment}/file-destroy', [TicketController::class, 'filesDestroy'])->name('tickets.file_destroy');
    Route::post('tickets/{ticket}/add-attachment', [TicketController::class, 'addAttachment'])->name('tickets.add_attachment');
    Route::put('tickets/{ticket}/finish', [TicketController::class, 'finishTicket'])->name('tickets.finish');
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show']);
    // Messages
    Route::post('messages', [MessageController::class, 'store'])->name('messages.store');

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
            Route::middleware('can:access_finished_tickets')->group(function () {
                Route::get('/finished-ticket',[FinishedTicketController::class, 'create'])->name('finished_tickets.create');
                Route::post('/finished-ticket',[FinishedTicketController::class, 'store'])->name('finished_tickets.store');
                Route::get('/users/search', [FinishedTicketController::class, 'searchUsers'])->name('users.search');
            });

        Route::put('tickets/{ticket}/reassign', [AdminTicketController::class, 'reassignTicketCategory'])->name('tickets.reassign');
        Route::get('tickets/{ticket}/details', [AdminTicketController::class, 'getTicketDetails'])->name('tickets.getTicketDetails');
        Route::put('tickets/{ticket}/reopen', [AdminTicketController::class, 'reopenTickets'])->name('tickets.reopen');
        Route::get('tickets/{id}/print', [AdminTicketController::class, 'printTicket'])->name('tickets.print');
        Route::put('tickets/{ticket}/mark-as-finished', [AdminTicketController::class, 'markAsFinishedTicket'])->name('tickets.markAsFinishedTicket');



       Route::post('tickets/{ticket}/internal-note', [AdminNoteController::class, 'store'])
         ->name('tickets.internal-note.store');
           Route::put('tickets/{ticket}/notes/{note}', [AdminNoteController::class, 'update'])
         ->name('tickets.internal-note.update');
           Route::delete('tickets/{ticket}/notes/{note}', [AdminNoteController::class, 'destroy'])
         ->name('tickets.internal-note.destroy');






        Route::resource('tickets', AdminTicketController::class)->only(['index', 'show', 'update']);

        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'edit','update','destroy'])->middleware('can:is_root');
        Route::get('users/{user}/details', [ProfileController::class, 'getUserDetails'])->name('users.details');
        Route::resource('users', AdminUserController::class)->only(['index', 'create', 'store', 'edit', 'update'])->middleware('can:is_root');

        Route::resource('departments', DepartmentController::class)->only(['index','store','destroy'])->middleware('can:is_root');
        // Department Telegram chats management
        Route::post('departments/{department}/telegram-chats', [DepartmentController::class, 'addTelegramChat'])
            ->name('departments.telegram_chats.store')
            ->middleware('can:is_root');

        Route::delete('departments/telegram-chats/{id}', [DepartmentController::class, 'removeTelegramChat'])
            ->name('departments.telegram_chats.destroy')
            ->middleware('can:is_root');

        Route::post('main_departments/bind', [DepartmentController::class, 'bindToMain'])
            ->name('main_departments.bindToMain')
            ->middleware('can:is_root');
              Route::delete('main_departments/unbind/{id}', [DepartmentController::class, 'unbindDepartment'])
            ->name('main_departments.unbindDepartment')
            ->middleware('can:is_root');
            Route::post('main_departments/store', [DepartmentController::class, 'mainDepartment_store'])
            ->name('main_departments.mainDepartment_store')
            ->middleware('can:is_root');
            Route::delete('main_departments/{main_department}', [DepartmentController::class, 'mainDepartment_destroy'])
            ->name('main_departments.mainDepartment_destroy')
            ->middleware('can:is_root');
            Route::get('structure', [StructureController::class, 'index'])
                ->name('structure.index')
                ->middleware('can:is_root');


        });

        Route::middleware('admin')->prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [AdminReportController::class, 'index'])->name('index');
            Route::get('/pdf', [AdminReportController::class, 'generatePdf'])->name('pdf');
        });


       Route::middleware('viewer')->prefix('viewer')->name('viewer.')->group(function () {
            Route::get('/tickets/view', [ViewerTicketController::class, 'index'])->name('dashboard');
        });


        });
