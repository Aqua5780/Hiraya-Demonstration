<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user) {
            $user->loadMissing('role');
        }

        $activeYear = null;

        if ($user) {
            $activeYear = AcademicYear::query()
                ->where('is_active', true)
                ->first(['id', 'name', 'start_date', 'end_date', 'enrollment_start', 'enrollment_end', 'status']);
        }

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'school' => config('hiraya.school'),
            'demoMode' => config('hiraya.demo_mode'),
            'activeYear' => $activeYear,
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->slug,
                    'role_label' => $user->role?->name,
                    'status' => $user->status,
                    'initials' => $user->initials(),
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'demo_otp' => fn () => $request->session()->get('demo_otp'),
                'demo_activation_url' => fn () => $request->session()->get('demo_activation_url'),
                'import_errors' => fn () => $request->session()->get('import_errors'),
            ],
            'quote' => Inspiring::quote(),
        ];
    }
}
