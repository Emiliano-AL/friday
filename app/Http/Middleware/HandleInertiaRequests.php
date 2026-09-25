<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\User;
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

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            ...($user !== null ? ['projects' => $this->projectSummaries($user)] : []),
        ];
    }

    /**
     * Lightweight project list for the workspace context switcher.
     *
     * @return array<int, array{id: int, title: string}>
     */
    private function projectSummaries(User $user): array
    {
        return Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->whereKey($user->id))
            ->orderBy('title')
            ->limit(50)
            ->get(['id', 'title'])
            ->all();
    }
}
