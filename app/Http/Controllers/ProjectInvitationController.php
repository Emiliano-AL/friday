<?php

namespace App\Http\Controllers;

use App\Enums\ProjectInvitationStatus;
use App\Models\Project;
use App\Models\ProjectInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectInvitationController extends Controller
{
    /**
     * Resend a pending invitation with a fresh link and validity period (owner only).
     */
    public function resend(Request $request, Project $project, ProjectInvitation $invitation): RedirectResponse
    {
        $this->authorizePending($request, $project, $invitation);

        if (! $project->status->isActive()) {
            return redirect()->route('projects.show', $project)
                ->with('error', 'No puedes invitar en un proyecto archivado o completado.');
        }

        [$invitation, $token] = ProjectInvitation::issue($project, $invitation->email, $request->user());

        return $invitation->send($token)
            ? redirect()->route('projects.show', $project)->with('success', "Invitación reenviada a {$invitation->email}.")
            : redirect()->route('projects.show', $project)->with('error', 'No pudimos enviar el correo. Puedes reenviar la invitación.');
    }

    /**
     * Cancel a pending invitation so its link stops working (owner only).
     */
    public function destroy(Request $request, Project $project, ProjectInvitation $invitation): RedirectResponse
    {
        $this->authorizePending($request, $project, $invitation);

        $invitation->update([
            'status' => ProjectInvitationStatus::Cancelled,
            'responded_at' => now(),
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', "Invitación a {$invitation->email} cancelada.");
    }

    private function authorizePending(Request $request, Project $project, ProjectInvitation $invitation): void
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $this->authorize('manageMembers', $project);

        abort_unless(
            $invitation->project_id === $project->id && $invitation->status === ProjectInvitationStatus::Pending,
            404,
        );
    }
}
