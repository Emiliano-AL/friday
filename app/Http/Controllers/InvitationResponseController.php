<?php

namespace App\Http\Controllers;

use App\Enums\ProjectInvitationStatus;
use App\Models\ProjectInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InvitationResponseController extends Controller
{
    /**
     * Show the invitation to guests (remembering it through sign-up/sign-in) and to the invited user.
     */
    public function show(Request $request, string $token): Response
    {
        $invitation = ProjectInvitation::findByToken($token);

        if ($invitation === null) {
            return Inertia::render('Invitations/Show', ['state' => 'unavailable', 'reason' => 'not_found']);
        }

        $invitation->load(['project', 'invitedBy']);

        $reason = $this->unavailableReason($invitation);
        $user = $request->user();

        if ($reason === null && $user === null) {
            redirect()->setIntendedUrl($request->fullUrl());
            $request->session()->put('invitation_email', $invitation->email);
        }

        return Inertia::render('Invitations/Show', [
            'state' => match (true) {
                $reason !== null => 'unavailable',
                $user === null => 'guest',
                ! $invitation->matches($user) => 'mismatch',
                default => 'valid',
            },
            'reason' => $reason,
            'currentEmail' => $user?->email,
            'token' => $token,
            'invitation' => [
                'email' => $invitation->email,
                'project' => [
                    'title' => $invitation->project->title,
                    'color' => $invitation->project->color?->value,
                    'icon' => $invitation->project->icon?->value,
                ],
                'invitedBy' => [
                    'name' => $invitation->invitedBy->name,
                    'avatar' => $invitation->invitedBy->avatar,
                ],
                'expiresAt' => $invitation->expires_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Accept the invitation and join the project (invited email only).
     */
    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->resolveForResponse($request, $token);

        if (! $invitation->isUsable()) {
            return redirect()->route('invitations.show', $token);
        }

        $user = $request->user();
        $project = $invitation->project;

        DB::transaction(function () use ($invitation, $project, $user): void {
            if (! $project->isMember($user)) {
                $project->members()->attach($user);
            }

            $invitation->update([
                'status' => ProjectInvitationStatus::Accepted,
                'responded_at' => now(),
            ]);
        });

        $request->session()->forget('invitation_email');

        return redirect()->route('projects.show', $project)
            ->with('success', "Te uniste a {$project->title}.");
    }

    /**
     * Decline the invitation without joining the project (invited email only).
     */
    public function decline(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->resolveForResponse($request, $token);

        if (! $invitation->isUsable()) {
            return redirect()->route('invitations.show', $token);
        }

        $invitation->update([
            'status' => ProjectInvitationStatus::Declined,
            'responded_at' => now(),
        ]);

        $request->session()->forget('invitation_email');

        return redirect()->route('home')
            ->with('success', "Rechazaste la invitación a {$invitation->project->title}.");
    }

    private function resolveForResponse(Request $request, string $token): ProjectInvitation
    {
        $invitation = ProjectInvitation::findByToken($token);

        abort_if($invitation === null, 404);
        abort_unless($invitation->matches($request->user()), 403);

        return $invitation->load('project');
    }

    /**
     * @return 'expired'|'cancelled'|'used'|'inactive_project'|null
     */
    private function unavailableReason(ProjectInvitation $invitation): ?string
    {
        return match (true) {
            $invitation->status === ProjectInvitationStatus::Cancelled => 'cancelled',
            $invitation->status !== ProjectInvitationStatus::Pending => 'used',
            $invitation->isExpired() => 'expired',
            ! $invitation->project->status->isActive() => 'inactive_project',
            default => null,
        };
    }
}
