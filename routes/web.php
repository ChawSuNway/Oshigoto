<?php

use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EarlyLeaveController;
use App\Http\Controllers\HalfDayLeaveController;
use App\Http\Controllers\LateComingController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemIdController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    $start = now()->startOfMonth()->toDateString();
    $end   = now()->endOfMonth()->toDateString();

    // Managers see the reports addressed to them; everyone else their own.
    $reports = fn () => $user->isManager() ? $user->receivedReports() : $user->reports();

    $metrics = [
        'reports_total' => $reports()->count(),
        'reports_month' => $reports()->whereBetween('report_date', [$start, $end])->count(),
        'hours_month'   => (float) $reports()->whereBetween('report_date', [$start, $end])->sum('total_hours'),
        'drafts'        => $user->isManager() ? 0 : $user->reports()->whereNull('sent_at')->count(),
        'late'          => $user->lateComings()->count(),
        'early'         => $user->earlyLeaves()->count(),
    ];

    $recent = $reports()
        ->with($user->isManager() ? 'user' : 'manager')
        ->latest('report_date')
        ->take(6)
        ->get();

    return view('dashboard', compact('metrics', 'recent'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Quick mail deliverability test. Visit /mail/test (optionally ?to=you@example.com&mailer=smtp)
    // to send a plain message through the given mailer and see the outcome as JSON.
    Route::get('/mail/test', function (Request $request) {
        $to     = $request->query('to', $request->user()->email);
        $mailer = $request->query('mailer', config('mail.default'));

        try {
            Mail::mailer($mailer)->raw(
                'Test email from ' . config('app.name') . ' sent at ' . now()->toDateTimeString()
                    . ' (' . config('app.timezone') . ').',
                fn ($message) => $message->to($to)->subject('[Test] ' . config('app.name') . ' mail check'),
            );

            return response()->json([
                'status' => 'ok',
                'mailer' => $mailer,
                'to'     => $to,
                'from'   => config('mail.from.address'),
                'host'   => config("mail.mailers.{$mailer}.host"),
                'port'   => config("mail.mailers.{$mailer}.port"),
                'hint'   => $mailer === 'log'
                    ? 'MAIL_MAILER=log — written to storage/logs/laravel.log, NOT delivered. Add ?mailer=smtp to send for real.'
                    : "Message handed to the '{$mailer}' transport. Check the inbox.",
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'mailer' => $mailer,
                'to'     => $to,
                'error'  => $e->getMessage(),
            ], 500, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }
    })->name('mail.test');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Monthly report export (declared before the resource so "monthly" isn't treated as a {report} id).
    Route::get('/reports/monthly', [ReportController::class, 'monthly'])->name('reports.monthly');
    Route::get('/reports/monthly/export', [ReportController::class, 'monthlyExport'])->name('reports.monthly.export');

    // Daily work reports.
    Route::post('/reports/{report}/send', [ReportController::class, 'send'])->name('reports.send');
    Route::resource('reports', ReportController::class);

    // Inform late coming.
    Route::post('/late/{late}/send', [LateComingController::class, 'send'])->name('late.send');
    Route::resource('late', LateComingController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    // Early leave application.
    Route::post('/early/{early}/send', [EarlyLeaveController::class, 'send'])->name('early.send');
    Route::resource('early', EarlyLeaveController::class)->only(['index', 'create', 'store', 'show', 'destroy']);

    // Half day leave application.
    Route::post('/half/{half}/send', [HalfDayLeaveController::class, 'send'])->name('half.send');
    Route::resource('half', HalfDayLeaveController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    // Leave application (full / multi-day).
    Route::post('/leave/{leave}/send', [LeaveApplicationController::class, 'send'])->name('leave.send');
    Route::resource('leave', LeaveApplicationController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);

    // Department master — every user can view the list.
    Route::get('departments', [DepartmentController::class, 'index'])->name('departments.index');

    // Admin-only user & role management.
    Route::middleware('role:admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('system-ids', SystemIdController::class)->except(['show']);
        // Only admins may create/update/delete departments.
        Route::resource('departments', DepartmentController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    });
});

require __DIR__.'/auth.php';
