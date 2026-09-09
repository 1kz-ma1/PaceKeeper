<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkLogController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\AdminTemplateController;
use App\Http\Controllers\PublicPlanController;
use App\Http\Controllers\MyPlanController;
use App\Http\Controllers\AiTaskAssistantController;
use App\Http\Controllers\PlanReviewAssistantController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\BehaviorEventController;
use App\Http\Controllers\NavigationController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\WorkSessionController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/dashboard/tools', [HomeController::class, 'legacy'])->name('dashboard.tools');

Route::post('/behavior/events', [BehaviorEventController::class, 'store'])->name('behavior_events.store');
Route::post('/recommendations/alternative', [RecommendationController::class, 'alternative'])->name('recommendations.alternative');

Route::get('/navigate', [NavigationController::class, 'index'])->name('navigation.index');
Route::post('/navigate/intent', [NavigationController::class, 'chooseIntent'])->name('navigation.intent');
Route::post('/navigate/time', [NavigationController::class, 'chooseTime'])->name('navigation.time');
Route::post('/navigate/alternative', [NavigationController::class, 'alternative'])->name('navigation.alternative');
Route::post('/navigate/reset', [NavigationController::class, 'reset'])->name('navigation.reset');

Route::post('/work-sessions', [WorkSessionController::class, 'start'])->name('work_sessions.start');
Route::get('/work-sessions/{workSession}', [WorkSessionController::class, 'active'])->name('work_sessions.active');
Route::post('/work-sessions/{workSession}/pause', [WorkSessionController::class, 'pause'])->name('work_sessions.pause');
Route::post('/work-sessions/{workSession}/resume', [WorkSessionController::class, 'resume'])->name('work_sessions.resume');
Route::post('/work-sessions/{workSession}/complete', [WorkSessionController::class, 'complete'])->name('work_sessions.complete');
Route::get('/work-sessions/{workSession}/review', [WorkSessionController::class, 'review'])->name('work_sessions.review');
Route::post('/work-sessions/{workSession}/review', [WorkSessionController::class, 'storeReview'])->name('work_sessions.review.store');
Route::post('/work-sessions/{workSession}/interrupt', [WorkSessionController::class, 'interrupt'])->name('work_sessions.interrupt');

// ダッシュボード共通のAI JSON入力
Route::post('/dashboard/ai-json/preview', [PlanReviewAssistantController::class, 'previewFromDashboard'])
    ->name('dashboard.ai_json.preview');
Route::post('/dashboard/ai-json/resolve-legacy', [PlanReviewAssistantController::class, 'resolveLegacyFromDashboard'])
    ->name('dashboard.ai_json.resolve_legacy');
Route::post('/dashboard/ai-json/reset', [PlanReviewAssistantController::class, 'resetFromDashboard'])
    ->name('dashboard.ai_json.reset');

// 日常操作の入口となるチャットUI
Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
Route::get('/achievements', [ChatController::class, 'achievements'])->name('achievements.index');
Route::get('/achievements/{plan}', [ChatController::class, 'achievement'])->name('achievements.show');
Route::post('/chat/start/{flow}', [ChatController::class, 'start'])->name('chat.start');
Route::post('/chat/answer', [ChatController::class, 'answer'])->name('chat.answer');
Route::post('/chat/confirm', [ChatController::class, 'confirm'])->name('chat.confirm');
Route::post('/chat/context-exported', [ChatController::class, 'markContextExported'])->name('chat.context_exported');
Route::post('/chat/reset', [ChatController::class, 'reset'])->name('chat.reset');
Route::get('/my-plans', [MyPlanController::class, 'index'])->name('my_plans.index');

// 公開計画
Route::get('/public-plans', [PublicPlanController::class, 'index'])->name('public_plans.index');
Route::get('/p/{publicSlug}', [PublicPlanController::class, 'show'])->name('public_plans.show');

// 計画
Route::get('/plans/create', [PlanController::class, 'create'])->name('plans.create');
Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
Route::get('/plans/{plan}', [PlanController::class, 'show'])->name('plans.show');
Route::get('/plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
Route::put('/plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
Route::delete('/plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');
Route::get('/plans/{plan}/ai-task-assistant', [AiTaskAssistantController::class, 'show'])
    ->name('plans.ai_task_assistant.show');

Route::post('/plans/{plan}/ai-task-assistant/import', [AiTaskAssistantController::class, 'import'])
    ->name('plans.ai_task_assistant.import');


// 実績をもとに計画を見直すAI支援
Route::get('/plans/{plan}/review-assistant', [PlanReviewAssistantController::class, 'show'])
    ->name('plans.review_assistant.show');
Route::post('/plans/{plan}/review-assistant/prompt', [PlanReviewAssistantController::class, 'generatePrompt'])
    ->name('plans.review_assistant.prompt');
Route::post('/plans/{plan}/review-assistant/preview', [PlanReviewAssistantController::class, 'preview'])
    ->name('plans.review_assistant.preview');
Route::post('/plans/{plan}/review-assistant/apply', [PlanReviewAssistantController::class, 'apply'])
    ->name('plans.review_assistant.apply');
Route::post('/plans/{plan}/review-assistant/reset', [PlanReviewAssistantController::class, 'reset'])
    ->name('plans.review_assistant.reset');

// タスク
Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

// 作業ログ
Route::delete('/work-logs/{workLog}', [WorkLogController::class, 'destroy'])->name('work_logs.destroy');

// テンプレート
Route::get('/templates', [TemplateController::class, 'index'])->name('templates.index');
Route::get('/templates/{planTemplate}', [TemplateController::class, 'show'])->name('templates.show');
Route::post('/templates/{planTemplate}/use', [TemplateController::class, 'use'])->name('templates.use');

// 管理者用テンプレート
Route::get('/admin/templates/login', [AdminTemplateController::class, 'login'])->name('admin.templates.login');
Route::post('/admin/templates/login', [AdminTemplateController::class, 'authenticate'])->name('admin.templates.authenticate');
Route::get('/admin/templates/create', [AdminTemplateController::class, 'create'])->name('admin.templates.create');
Route::post('/admin/templates', [AdminTemplateController::class, 'store'])->name('admin.templates.store');
