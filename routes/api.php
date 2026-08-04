<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\MedicalRecordController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ProfileController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/public/stats', [ReportController::class, 'summary']);
Route::post('/forgot-password', [AuthController::class, 'requestPasswordReset']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::apiResource('doctors', DoctorController::class);
    Route::apiResource('patients', PatientController::class);
    Route::apiResource('bookings', BookingController::class);
    Route::apiResource('schedules', ScheduleController::class);

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/read-all', [NotificationController::class, 'markAllRead']);
        Route::post('/{notification}/read', [NotificationController::class, 'markRead']);
    });
    Route::get(
        '/medical-records/available-bookings',
        [MedicalRecordController::class, 'availableBookings']
    );

    Route::get(
        '/medical-records/export',
        [MedicalRecordController::class, 'export']
    );

    Route::apiResource(
        'medical-records',
        MedicalRecordController::class
    );

    Route::apiResource('users', UserController::class);
    Route::middleware('auth:sanctum')->group(function () {

    Route::get(
        '/reports/summary',
        [ReportController::class, 'summary']
    );

});
});