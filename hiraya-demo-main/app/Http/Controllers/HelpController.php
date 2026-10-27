<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HelpController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $role = $request->user()?->role?->slug;
        $search = trim((string) $request->query('q', ''));

        $categories = FaqCategory::query()
            ->with(['faqs' => function ($query) use ($role, $search): void {
                $query->published()
                    ->forRole($role)
                    ->when($search !== '', function ($query) use ($search): void {
                        $query->where(function ($query) use ($search): void {
                            $query->where('question', 'like', "%{$search}%")
                                ->orWhere('answer', 'like', "%{$search}%");
                        });
                    })
                    ->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (FaqCategory $category): bool => $category->faqs->isNotEmpty())
            ->map(fn (FaqCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'faqs' => $category->faqs->map(fn (Faq $faq): array => [
                    'id' => $faq->id,
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'role_visibility' => $faq->role_visibility,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Help/Index', [
            'categories' => $categories,
            'search' => $search,
            'role' => $role,
        ]);
    }
}
