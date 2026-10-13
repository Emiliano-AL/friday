<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    /**
     * Add a registered user as collaborator, or invite an unregistered email by link (owner only).
     */
    public function store(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $this->authorize('manageMembers', $project);

        $validated = $request->validate(
            ['email' => ['required', 'string', 'email', 'max:255']],
            [
                'email.required' => 'Escribe el correo del colaborador.',
                'email.email' => 'Escribe un correo válido.',
            ]
        );

        $user = User::query()->whereRaw('lower(email) = ?', [mb_strtolower($validated['email'])])->first();

        if ($user !== null) {
            if ($project->isMember($user)) {
                return back()->withErrors([
                    'email' => 'Este usuario ya es miembro del proyecto.',
                ]);
            }

            $project->members()->attach($user);

            return redirect()->route('projects.show', $project);
        }

        if (! $project->status->isActive()) {
            return back()->withErrors([
                'email' => 'No puedes invitar en un proyecto archivado o completado.',
            ]);
        }

        [$invitation, $token] = ProjectInvitation::issue($project, $validated['email'], $request->user());

        return $invitation->send($token)
            ? redirect()->route('projects.show', $project)->with('success', "Invitación enviada a {$invitation->email}.")
            : redirect()->route('projects.show', $project)->with('error', 'No pudimos enviar el correo. Puedes reenviar la invitación.');
    }

    /**
     * Remove a collaborator from the project (owner only, never the owner).
     */
    public function destroy(Request $request, Project $project, User $user): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $this->authorize('manageMembers', $project);

        abort_unless($project->members()->whereKey($user->id)->exists(), 404);

        $project->tasks()
            ->where('assignee_id', $user->id)
            ->update(['assignee_id' => null]);

        $project->members()->detach($user);

        return redirect()->route('projects.show', $project);
    }
}
