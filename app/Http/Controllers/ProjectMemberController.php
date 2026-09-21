<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectMemberController extends Controller
{
    /**
     * Add an existing user as a project collaborator (owner only).
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $this->authorize('manageMembers', $project);

        $validated = $request->validate([
            'email' => [
                'required',
                'string',
                'email',
                Rule::exists('users', 'email'),
            ],
        ]);

        $user = User::query()->where('email', $validated['email'])->firstOrFail();

        if ($project->isMember($user)) {
            return back()->withErrors([
                'email' => 'Este usuario ya es miembro del proyecto.',
            ]);
        }

        $project->members()->attach($user);

        return redirect()->route('projects.show', $project);
    }

    /**
     * Remove a collaborator from the project (owner only, never the owner).
     */
    public function destroy(Request $request, Project $project, User $user): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $this->authorize('manageMembers', $project);

        abort_unless($project->members()->whereKey($user->id)->exists(), 404);

        $project->members()->detach($user);

        return redirect()->route('projects.show', $project);
    }
}
