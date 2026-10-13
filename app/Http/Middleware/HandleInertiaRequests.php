<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
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

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'importPreview' => $request->session()->get('importPreview'),
            ],
            ...($user !== null ? [
                'projects' => $this->projectSummaries($user),
                'taskFormProjects' => Inertia::optional(fn () => Project::taskFormOptionsFor($user)),
            ] : []),
        ];
    }

    /**
     * Lightweight project list for the workspace context switcher.
     *
     * @return array<int, array{id: int, title: string, color: string|null, icon: string|null}>
     */
    private function projectSummaries(User $user): array
    {
        return Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->whereKey($user->id))
            ->orderBy('title')
            ->limit(50)
            ->get(['id', 'title', 'color', 'icon'])
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'color' => $project->color?->value,
                'icon' => $project->icon?->value,
            ])
            ->all();
    }
}
