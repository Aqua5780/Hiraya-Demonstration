<?php

use App\Http\Controllers\Admin\AcademicYearController as AdminAcademicYearController;
use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\ClassOfferingController as AdminClassOfferingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Faculty\AnnouncementController as FacultyAnnouncementController;
use App\Http\Controllers\Faculty\ClassController as FacultyClassController;
use App\Http\Controllers\Faculty\DashboardController as FacultyDashboardController;
use App\Http\Controllers\Faculty\GradeController as FacultyGradeController;
use App\Http\Controllers\Faculty\ScheduleController as FacultyScheduleController;
use App\Http\Controllers\Finance\AccountController as FinanceAccountController;
use App\Http\Controllers\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\Finance\PaymentController as FinancePaymentController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\PaymentDocumentController;
use App\Http\Controllers\Registrar\DashboardController as RegistrarDashboardController;
use App\Http\Controllers\Registrar\ImportController as RegistrarImportController;
use App\Http\Controllers\Registrar\StudentController as RegistrarStudentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Student\AnnouncementController as StudentAnnouncementController;
use App\Http\Controllers\Student\ClassController as StudentClassController;
use App\Http\Controllers\Student\DashboardController as StudentDashboardController;
use App\Http\Controllers\Student\EnrollmentController as StudentEnrollmentController;
use App\Http\Controllers\Student\GradeController as StudentGradeController;
use App\Http\Controllers\Student\PaymentController as StudentPaymentController;
use App\Http\Controllers\Student\ScheduleController as StudentScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route(auth()->user()->dashboardRoute());
    }

    return redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->name('password.email');
    Route::get('/forgot-password/verify', [ForgotPasswordController::class, 'showVerify'])->name('password.verify');
    Route::post('/forgot-password/verify', [ForgotPasswordController::class, 'verify'])->name('password.verify.store');
    Route::get('/reset-password', [ForgotPasswordController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');
});

Route::get('/activate/{token}', [ActivationController::class, 'show'])->name('activate.show');
Route::post('/activate', [ActivationController::class, 'store'])->name('activate.store');

Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/help', HelpController::class)->name('help.index');
    Route::get('/announcements/{announcement}/attachment', [AttachmentController::class, 'downloadAnnouncement'])->name('announcements.attachment');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');

    Route::get('/payments/{payment}/receipt', [PaymentDocumentController::class, 'receipt'])->name('payments.receipt');
    Route::get('/payments/{payment}/proof', [PaymentDocumentController::class, 'proof'])->name('payments.proof');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', AdminDashboardController::class)->name('dashboard');

    Route::get('/academic-years', [AdminAcademicYearController::class, 'index'])->name('academic-years.index');
    Route::post('/academic-years', [AdminAcademicYearController::class, 'store'])->name('academic-years.store');
    Route::patch('/academic-years/{academicYear}', [AdminAcademicYearController::class, 'update'])->name('academic-years.update');
    Route::post('/academic-years/{academicYear}/activate', [AdminAcademicYearController::class, 'activate'])->name('academic-years.activate');

    Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
    Route::post('/master-data/programs', [MasterDataController::class, 'storeProgram'])->name('master-data.programs.store');
    Route::patch('/master-data/programs/{program}', [MasterDataController::class, 'updateProgram'])->name('master-data.programs.update');
    Route::post('/master-data/year-levels', [MasterDataController::class, 'storeYearLevel'])->name('master-data.year-levels.store');
    Route::patch('/master-data/year-levels/{yearLevel}', [MasterDataController::class, 'updateYearLevel'])->name('master-data.year-levels.update');
    Route::post('/master-data/sections', [MasterDataController::class, 'storeSection'])->name('master-data.sections.store');
    Route::patch('/master-data/sections/{section}', [MasterDataController::class, 'updateSection'])->name('master-data.sections.update');
    Route::post('/master-data/subjects', [MasterDataController::class, 'storeSubject'])->name('master-data.subjects.store');
    Route::patch('/master-data/subjects/{subject}', [MasterDataController::class, 'updateSubject'])->name('master-data.subjects.update');

    Route::get('/class-offerings', [AdminClassOfferingController::class, 'index'])->name('class-offerings.index');
    Route::post('/class-offerings', [AdminClassOfferingController::class, 'store'])->name('class-offerings.store');
    Route::patch('/class-offerings/{classOffering}', [AdminClassOfferingController::class, 'update'])->name('class-offerings.update');

    Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
    Route::patch('/announcements/{announcement}', [AdminAnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [AdminAnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::get('/accounts', [AdminAccountController::class, 'index'])->name('accounts.index');
    Route::post('/accounts/invite', [AdminAccountController::class, 'invite'])->name('accounts.invite');
    Route::post('/accounts/students', [AdminAccountController::class, 'storeStudentAccount'])->name('accounts.students.store');
    Route::post('/accounts/{user}/resend', [AdminAccountController::class, 'resend'])->name('accounts.resend');
    Route::patch('/accounts/{user}/password', [AdminAccountController::class, 'updatePassword'])->name('accounts.password');
    Route::patch('/accounts/{user}/status', [AdminAccountController::class, 'updateStatus'])->name('accounts.status');

    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
});

Route::middleware(['auth', 'role:registrar'])->prefix('registrar')->name('registrar.')->group(function (): void {
    Route::get('/', RegistrarDashboardController::class)->name('dashboard');

    Route::get('/students', [RegistrarStudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [RegistrarStudentController::class, 'show'])->name('students.show');
    Route::get('/students/{student}/edit', [RegistrarStudentController::class, 'edit'])->name('students.edit');
    Route::patch('/students/{student}', [RegistrarStudentController::class, 'update'])->name('students.update');

    Route::get('/import', [RegistrarImportController::class, 'index'])->name('import.index');
    Route::get('/import/template', [RegistrarImportController::class, 'template'])->name('import.template');
    Route::post('/import', [RegistrarImportController::class, 'store'])->name('import.store');
});

Route::middleware(['auth', 'role:faculty'])->prefix('faculty')->name('faculty.')->group(function (): void {
    Route::get('/', FacultyDashboardController::class)->name('dashboard');

    Route::get('/schedule', FacultyScheduleController::class)->name('schedule.index');
    Route::get('/classes', [FacultyClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/{classOffering}', [FacultyClassController::class, 'show'])->name('classes.show');

    Route::get('/grades', [FacultyGradeController::class, 'index'])->name('grades.index');
    Route::get('/grades/{classOffering}', [FacultyGradeController::class, 'show'])->name('grades.show');
    Route::post('/grades/{classOffering}', [FacultyGradeController::class, 'store'])->name('grades.store');

    Route::get('/announcements', [FacultyAnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [FacultyAnnouncementController::class, 'store'])->name('announcements.store');
    Route::patch('/announcements/{announcement}', [FacultyAnnouncementController::class, 'update'])->name('announcements.update');
    Route::delete('/announcements/{announcement}', [FacultyAnnouncementController::class, 'destroy'])->name('announcements.destroy');
});

Route::middleware(['auth', 'role:finance'])->prefix('finance')->name('finance.')->group(function (): void {
    Route::get('/', FinanceDashboardController::class)->name('dashboard');

    Route::get('/payments', [FinancePaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [FinancePaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [FinancePaymentController::class, 'store'])->name('payments.store');
    Route::post('/payments/{payment}/verify', [FinancePaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [FinancePaymentController::class, 'reject'])->name('payments.reject');

    Route::get('/accounts', [FinanceAccountController::class, 'index'])->name('accounts.index');
    Route::get('/accounts/{student}', [FinanceAccountController::class, 'show'])->name('accounts.show');
});

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function (): void {
    Route::get('/', StudentDashboardController::class)->name('dashboard');

    Route::get('/enrollment', [StudentEnrollmentController::class, 'index'])->name('enrollment.index');
    Route::post('/enrollment', [StudentEnrollmentController::class, 'store'])->name('enrollment.store');

    Route::get('/schedule', StudentScheduleController::class)->name('schedule.index');
    Route::get('/classes', [StudentClassController::class, 'index'])->name('classes.index');
    Route::get('/classes/{classOffering}', [StudentClassController::class, 'show'])->name('classes.show');

    Route::get('/grades', StudentGradeController::class)->name('grades.index');
    Route::get('/announcements', StudentAnnouncementController::class)->name('announcements.index');

    Route::get('/payments', [StudentPaymentController::class, 'index'])->name('payments.index');
    Route::post('/payments', [StudentPaymentController::class, 'store'])->name('payments.store');
});
