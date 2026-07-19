<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExamSessionController;
use App\Http\Controllers\Admin\ExamSimulationController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\ExamTokenController;
use App\Http\Controllers\Admin\MasterCategoryController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ExamAccessController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\MonitoringSmokeTestController;
use App\Http\Controllers\ParticipantAuthController;
use App\Http\Controllers\ParticipantPortalController;
use App\Http\Controllers\VersionInfoController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthCheckController::class, 'health'])->name('monitoring.health');
Route::get('/status', [HealthCheckController::class, 'status'])->name('monitoring.status');
Route::get('/version', [VersionInfoController::class, 'version'])->name('monitoring.version');
Route::get('/build-info', [VersionInfoController::class, 'version'])->name('monitoring.build-info');
if (app()->environment(['local', 'development', 'testing'])) {
    Route::get('/monitoring/smoke-error-log', [MonitoringSmokeTestController::class, 'fire'])
        ->name('monitoring.smoke-error-log');
}

Route::redirect('/', '/login');

Route::get('/login', [ParticipantAuthController::class, 'showLogin'])->name('participant.login');
Route::post('/login', [ParticipantAuthController::class, 'login'])->name('participant.login.submit');
Route::post('/logout', [ParticipantAuthController::class, 'logout'])->name('participant.logout');

Route::middleware('participant.auth')->group(function (): void {
    Route::get('/dashboard', [ParticipantPortalController::class, 'dashboard'])->name('participant.dashboard');
    Route::get('/profile/edit', [ParticipantPortalController::class, 'editProfile'])->name('participant.profile.edit');
    Route::put('/profile', [ParticipantPortalController::class, 'updateProfile'])->name('participant.profile.update');
    Route::get('/exam/access', [ExamAccessController::class, 'showAccessForm'])->name('exam.access');
    Route::post('/exam/access', [ExamAccessController::class, 'start'])->name('exam.start');
});

Route::prefix('exam')->middleware('exam.guard')->group(function (): void {
    Route::get('/', [ExamController::class, 'show'])->name('exam.show');
    Route::post('/save-answer', [ExamController::class, 'saveAnswer'])->name('exam.save-answer');
    Route::post('/submit', [ExamController::class, 'submit'])->name('exam.submit');
});

Route::get('/exam/completed', [ExamController::class, 'complete'])->name('exam.complete');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', static fn () => redirect()->route('participant.login'))->name('login');
    Route::post('/login', [ParticipantAuthController::class, 'login'])->name('login.submit');

    Route::middleware('admin.auth')->group(function (): void {
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::put('/settings/profile', [SystemSettingController::class, 'updateProfile'])->name('settings.profile.update');
        Route::get('/results', [ResultController::class, 'index'])->name('results');
        Route::get('/results/export/csv', [ResultController::class, 'exportSummaryCsv'])->name('results.export.csv');
        Route::get('/results/export/excel', [ResultController::class, 'exportSummaryExcel'])->name('results.export.excel');
        Route::get('/results/{attempt}', [ResultController::class, 'show'])->name('results.show');
        Route::get('/results/{attempt}/export/csv', [ResultController::class, 'exportAttemptCsv'])->name('results.export.attempt.csv');

        Route::middleware('role:admin')->group(function (): void {
            Route::get('/location-settings', static fn () => redirect()->route('admin.settings.index', ['tab' => 'location']))->name('location-settings.edit');
            Route::put('/location-settings', [SystemSettingController::class, 'updateLocation'])->name('location-settings.update');
            Route::resource('users', UserController::class)->except(['show']);
            Route::post('/users/bulk', [UserController::class, 'bulk'])->name('users.bulk');
            Route::prefix('simulation')->name('simulation.')->group(function (): void {
                Route::get('/', [ExamSimulationController::class, 'index'])->name('index');
                Route::post('/start', [ExamSimulationController::class, 'start'])->name('start');
                Route::get('/run', [ExamSimulationController::class, 'run'])->name('run');
                Route::post('/save-answer', [ExamSimulationController::class, 'saveAnswer'])->name('save-answer');
                Route::post('/submit', [ExamSimulationController::class, 'submit'])->name('submit');
                Route::get('/result', [ExamSimulationController::class, 'result'])->name('result');
            });
            Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
            Route::put('/settings/location', [SystemSettingController::class, 'updateLocation'])->name('settings.location.update');
        });
        Route::get('/instructions', [ExamSessionController::class, 'instructions'])->name('instructions.index');
        Route::put('/instructions/{session}', [ExamSessionController::class, 'updateInstructions'])->name('instructions.update');
        Route::get('/sessions/{session}/participants/bulk', [ExamSessionController::class, 'participantsBulkForm'])->name('sessions.participants.bulk.form');
        Route::post('/sessions/{session}/participants/bulk', [ExamSessionController::class, 'participantsBulkStore'])->name('sessions.participants.bulk.store');
        Route::post('/sessions/{session}/status', [ExamSessionController::class, 'updateStatus'])->name('sessions.status');
        Route::resource('sessions', ExamSessionController::class)->except(['show'])->parameters([
            'sessions' => 'session',
        ]);
        Route::post('/sessions/bulk', [ExamSessionController::class, 'bulk'])->name('sessions.bulk');
        Route::get('/participants/{participant}/history', [ParticipantController::class, 'history'])->name('participants.history');
        Route::get('/participants/bulk-create', [ParticipantController::class, 'bulkCreateForm'])->name('participants.bulk-create.form');
        Route::post('/participants/bulk-create', [ParticipantController::class, 'bulkCreateStore'])->name('participants.bulk-create.store');
        Route::resource('participants', ParticipantController::class)->except(['show']);
        Route::post('/participants/bulk', [ParticipantController::class, 'bulk'])->name('participants.bulk');
        Route::resource('tokens', ExamTokenController::class)->except(['show'])->parameters([
            'tokens' => 'token',
        ]);
        Route::get('/tokens/bulk', [ExamTokenController::class, 'bulkForm'])->name('tokens.bulk.form');
        Route::post('/tokens/bulk', [ExamTokenController::class, 'bulk'])->name('tokens.bulk');
        Route::post('/tokens/generate', [ExamTokenController::class, 'generate'])->name('tokens.generate');
        Route::resource('categories', MasterCategoryController::class)->except(['show'])->parameters([
            'categories' => 'category',
        ]);
        Route::post('/categories/bulk', [MasterCategoryController::class, 'bulk'])->name('categories.bulk');
        Route::resource('questions', QuestionController::class)->except(['show']);
        Route::post('/questions/reorder', [QuestionController::class, 'reorder'])->name('questions.reorder');
        Route::post('/questions/bulk', [QuestionController::class, 'bulk'])->name('questions.bulk');
    });
});
