# Deferred Feature Modules

These modules are **staged but intentionally not connected** to the running application:

- Payroll
- Transaction reversal
- Formal reports
- Messaging / inbox
- Automated student account provisioning

The CE/CP3 system runs without them. There are:

- **No route registrations** for these modules
- **No navigation links** for them
- **No migrations in the auto-run migration path**
- **No seeders** for their tables
- **No automated tests** for their endpoints yet

All feature code is under `app/Features/` or `resources/js/Pages/Deferred/`. The migrations are under `database/migrations/deferred/`, which Laravel does not scan during `php artisan migrate`.

> Line numbers below refer to the current checkout. If the files change later, use the surrounding code as the anchor.

---

## 1. Payroll

### Files

| Purpose | File |
|---|---|
| Migration | `database/migrations/deferred/2026_10_24_200001_create_payroll_tables.php` |
| Models | `app/Features/Payroll/Models/CompensationRule.php`, `PayPeriod.php`, `Payroll.php`, `PayrollItem.php` |
| Service | `app/Features/Payroll/Services/PayrollService.php` |
| Controller | `app/Features/Payroll/Http/Controllers/PayrollController.php` |
| UI | `resources/js/Pages/Deferred/Payroll/Index.vue`, `Show.vue` |

### 1.1 Activate the migration

```powershell
Move-Item "database/migrations/deferred/2026_10_24_200001_create_payroll_tables.php" "database/migrations/"
..\.tools\php\php.exe artisan migrate
```

### 1.2 Add the import

In `routes/web.php`, add this with the other `use` statements near the top:

```php
use App\Features\Payroll\Http\Controllers\PayrollController;
```

### 1.3 Add the routes

In `routes/web.php`, inside the existing finance group that starts at **line 139**:

```php
Route::middleware(['auth', 'role:finance'])->prefix('finance')->name('finance.')->group(function (): void {
    // ... existing finance routes ...

    Route::prefix('payroll')->name('payroll.')->group(function (): void {
        Route::post('/periods', [PayrollController::class, 'storePeriod'])->name('periods.store');
        Route::post('/rules', [PayrollController::class, 'storeRule'])->name('rules.store');
        Route::post('/periods/{payPeriod}/generate', [PayrollController::class, 'generate'])->name('generate');
        Route::get('/', [PayrollController::class, 'index'])->name('index');
        Route::get('/{payroll}', [PayrollController::class, 'show'])->name('show');
        Route::post('/{payroll}/submit', [PayrollController::class, 'submit'])->name('submit');
        Route::post('/{payroll}/approve', [PayrollController::class, 'approve'])->name('approve');
        Route::post('/{payroll}/return', [PayrollController::class, 'return'])->name('return');
        Route::post('/{payroll}/post', [PayrollController::class, 'post'])->name('post');
    });
});
```

Because this group sits inside the finance group, the resulting route names are `finance.payroll.*` (for example, `finance.payroll.show`).

### 1.4 Add navigation

In `resources/js/Components/AppShell.vue`, add these icons to the `@lucide/vue` import:

```ts
FileBarChart,
RotateCcw,
```

Inside the `finance` array (currently around **line 83**), add:

```ts
{ label: 'Payroll', href: '/finance/payroll', icon: Wallet },
{ label: 'Reports', href: '/finance/reports', icon: FileBarChart },
{ label: 'Transaction Reversal', href: '/finance/reversals', icon: RotateCcw },
```

`Reports` and `Transaction Reversal` are activated in their own sections below.

### 1.5 Seed a pay period and compensation rule

After migrating, run this once through Tinker:

```powershell
..\.tools\php\php.exe artisan tinker --execute '...'
```

Use this code:

```php
\App\Features\Payroll\Models\CompensationRule::create([
    'name' => 'Default Faculty Rate',
    'basis' => 'hourly',
    'rate' => 350,
    'allowance' => 1000,
    'deduction' => 0,
    'effective_from' => '2026-06-01',
    'status' => 'active',
]);

\App\Features\Payroll\Models\PayPeriod::create([
    'academic_year_id' => \App\Models\AcademicYear::where('is_active', true)->value('id'),
    'name' => 'First Pay Period',
    'start_date' => '2026-06-01',
    'end_date' => '2026-06-15',
    'status' => 'open',
]);
```

A rule with `faculty_id = null` applies to all faculty. A faculty-specific rule takes precedence.

### 1.6 Build and verify

```powershell
npm run build
```

Test flow:

1. Open **Finance → Payroll**.
2. Create a pay period.
3. Create a compensation rule.
4. Generate a draft.
5. Submit it.
6. Approve it with a **different** finance account.
7. Post it.

The service prevents the preparer from approving their own payroll. The payroll register report is available under Reports after both modules are enabled.

---

## 2. Transaction Reversal

### Files

| Purpose | File |
|---|---|
| Migration | `database/migrations/deferred/2026_10_24_200002_create_payment_reversals_table.php` |
| Model | `app/Features/Reversal/Models/PaymentReversal.php` |
| Service | `app/Features/Reversal/Services/PaymentReversalService.php` |
| Controller | `app/Features/Reversal/Http/Controllers/PaymentReversalController.php` |
| UI | `resources/js/Pages/Deferred/Reversal/Index.vue` |

### 2.1 Activate the migration

```powershell
Move-Item "database/migrations/deferred/2026_10_24_200002_create_payment_reversals_table.php" "database/migrations/"
..\.tools\php\php.exe artisan migrate
```

### 2.2 Add the import

In `routes/web.php`:

```php
use App\Features\Reversal\Http\Controllers\PaymentReversalController;
```

### 2.3 Add the routes

Inside the finance group at **line 139**, after the existing finance routes:

```php
Route::prefix('reversals')->name('deferred.reversals.')->group(function (): void {
    Route::get('/', [PaymentReversalController::class, 'index'])->name('index');
    Route::post('/', [PaymentReversalController::class, 'store'])->name('store');
    Route::post('/{reversal}/approve', [PaymentReversalController::class, 'approve'])->name('approve');
    Route::post('/{reversal}/reject', [PaymentReversalController::class, 'reject'])->name('reject');
});
```

### 2.4 Add navigation

In the `finance` array in `AppShell.vue` (around **line 83**):

```ts
{ label: 'Transaction Reversal', href: '/finance/reversals', icon: RotateCcw },
```

### 2.5 How it works

- Only verified payments can be reversed.
- A reversal starts as `pending`.
- Approval creates a **debit** ledger entry, increases the student's balance, and marks the reversal approved.
- Rejection records the reason and does not touch the ledger.

When wiring it, add feature tests for:

- reversal of a verified payment
- rejection with no ledger change
- preventing duplicate approved reversals
- authorization by finance/admin

---

## 3. Formal Reports

Reports do **not** need a migration. They are query-based exports.

### Files

| Purpose | File |
|---|---|
| Service | `app/Features/Reports/Services/ReportService.php` |
| Controller | `app/Features/Reports/Http/Controllers/ReportController.php` |
| UI | `resources/js/Pages/Deferred/Reports/Index.vue` |

### 3.1 Add the import

In `routes/web.php`:

```php
use App\Features\Reports\Http\Controllers\ReportController;
```

### 3.2 Add the routes

Inside the finance group at **line 139**:

```php
Route::prefix('reports')->name('deferred.reports.')->group(function (): void {
    Route::get('/', [ReportController::class, 'index'])->name('index');
    Route::get('/export', [ReportController::class, 'export'])->name('export');
});
```

If you want registrars and administrators to use it too, move the routes outside the finance group and protect them with:

```php
Route::middleware(['auth', 'role:admin,registrar,finance'])
    ->prefix('reports')
    ->name('deferred.reports.')
    ->group(function (): void {
        // same two routes
    });
```

### 3.3 Add navigation

In the `finance` array in `AppShell.vue` (around **line 83**):

```ts
{ label: 'Reports', href: '/finance/reports', icon: FileBarChart },
```

If registrars/admin are allowed, add the same item to their arrays.

### 3.4 Available reports

- Enrollment summary
- Collections (with date range)
- Class list
- Student ledger
- Payroll register

The payroll register requires the payroll migration and pay-period data.

---

## 4. Messaging / Inbox

Students can message **administrators, registrars, and faculty**. Finance is intentionally excluded.

### Files

| Purpose | File |
|---|---|
| Migration | `database/migrations/deferred/2026_10_24_200003_create_messaging_tables.php` |
| Models | `app/Features/Messaging/Models/Conversation.php`, `Message.php` |
| Service | `app/Features/Messaging/Services/ConversationService.php` |
| Controller | `app/Features/Messaging/Http/Controllers/MessageController.php` |
| UI | `resources/js/Pages/Deferred/Messaging/Index.vue`, `Show.vue` |

### 4.1 Activate the migration

```powershell
Move-Item "database/migrations/deferred/2026_10_24_200003_create_messaging_tables.php" "database/migrations/"
..\.tools\php\php.exe artisan migrate
```

### 4.2 Add the import

In `routes/web.php`:

```php
use App\Features\Messaging\Http\Controllers\MessageController;
```

### 4.3 Add the routes

Add a new group after the existing authenticated `/help` and `/settings` group, around **line 62**:

```php
Route::middleware(['auth', 'role:admin,registrar,faculty,student'])
    ->prefix('messages')
    ->name('deferred.messages.')
    ->group(function (): void {
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/{conversation}', [MessageController::class, 'show'])->name('show');
        Route::post('/', [MessageController::class, 'store'])->name('store');
        Route::post('/{conversation}/reply', [MessageController::class, 'reply'])->name('reply');
    });
```

Do **not** add `finance` to the role list.

### 4.4 Add navigation

Add `MessageSquare` to the `@lucide/vue` import in `AppShell.vue`.

Add this to the `student`, `admin`, `registrar`, and `faculty` arrays, before `Help & FAQ`:

```ts
{ label: 'Messages', href: '/messages', icon: MessageSquare },
```

Do **not** add it to the `finance` array.

### 4.5 Behavior

- A student starts a conversation by selecting an admin, registrar, or faculty member.
- The service rejects any staff role outside that list.
- Both participants can reply.
- Messages are marked read when the conversation is opened.
- The current UI does not yet support attachments; the migration includes attachment columns for a later extension.

---

## 5. Automated Student Account Provisioning

> The active application now supports **manual** student account creation from **Admin → Accounts & Activation**, using a fixed `@gstc.edu.ph` domain and name-based addresses. This dormant module is only for automated or bulk provisioning; align its email generator with the active `StudentAccountService` before enabling it.

The deferred module's planned format is:

```text
surname + account number + @gstc.edu.ph
```

This format is **not finalized**. The service normalizes the surname and student number, checks for collisions, and adds a numeric suffix if the address already exists.

### Files

| Purpose | File |
|---|---|
| Migration | `database/migrations/deferred/2026_10_24_200004_create_account_provisionings_table.php` |
| Model | `app/Features/Provisioning/Models/AccountProvisioning.php` |
| Service | `app/Features/Provisioning/Services/StudentAccountProvisioningService.php` |
| Controller | `app/Features/Provisioning/Http/Controllers/ProvisioningController.php` |
| UI | `resources/js/Pages/Deferred/Provisioning/Index.vue` |

### 5.1 Activate the migration

```powershell
Move-Item "database/migrations/deferred/2026_10_24_200004_create_account_provisionings_table.php" "database/migrations/"
..\.tools\php\php.exe artisan migrate
```

### 5.2 Add the import

In `routes/web.php`:

```php
use App\Features\Provisioning\Http\Controllers\ProvisioningController;
```

### 5.3 Add the routes

Inside the admin group at **line 74**:

```php
Route::prefix('provisioning')->name('deferred.provisioning.')->group(function (): void {
    Route::get('/', [ProvisioningController::class, 'index'])->name('index');
    Route::post('/', [ProvisioningController::class, 'store'])->name('store');
});
```

### 5.4 Add navigation

Inside the `admin` array in `AppShell.vue` (around **line 49**), before `Help & FAQ`:

```ts
{ label: 'Student Accounts', href: '/admin/provisioning', icon: UserPlus },
```

`UserPlus` is already imported for the Accounts item.

### 5.5 Behavior

- Lists students without linked user accounts.
- Shows the generated email preview.
- Creates a `pending` student user, links it to the student record, and generates an activation token.
- In demo mode the activation URL is flashed and shown on the page; in production it is sent through the existing OTP/activation mail flow.
- Existing students with accounts are skipped with a validation error.
- The email format should be confirmed by the school before production use.

---

## 6. Migrations: what not to do

- Do not move all deferred migrations at once unless you intend to enable all five modules.
- Do not run `php artisan migrate` in production with the runtime `hiraya_app` account. Use the migration account described in the main README.
- Do not add routes before the corresponding tables exist; the page will crash on its first query.
- Do not add finance to the messaging role group.
- Do not seed demo compensation rules or demo student accounts into production.

## 7. Tests to add when enabling

The current test suite covers the live CP3 application only. After wiring a module, add feature tests for:

- **Payroll** — draft totals, no rule, submit, distinct approver, return, post.
- **Reversal** — verified-only, pending workflow, ledger debit, balance increase, duplicate prevention.
- **Reports** — each report type returns the expected rows and CSV export.
- **Messaging** — student can message admin/registrar/faculty, cannot message finance, participant authorization.
- **Provisioning** — email format, collision suffix, pending account, activation token, duplicate prevention.

## 8. Rollback

To remove a module before enabling it:

1. Remove its `use` import and route group from `routes/web.php`.
2. Remove its navigation item from `resources/js/Components/AppShell.vue`.
3. If its migration was already run, run `php artisan migrate:rollback` for that migration (or drop the feature tables).
4. Delete the feature folder and Vue pages if they are no longer needed.

The rest of the application is unaffected.
