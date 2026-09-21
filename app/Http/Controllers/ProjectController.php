<?php

namespace App\Http\Controllers;

use App\Enums\ProjectStatus;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the projects the user is a member of.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $projects = Project::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($query) => $query->whereKey($user->id))
            ->latest('updated_at')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'status' => $project->status->value,
                'statusLabel' => $project->status->label(),
                'progress' => $project->progressPercentage(),
            ]);

        return Inertia::render('Projects/Index', [
            'projects' => $projects,
        ]);
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(): Response
    {
        return Inertia::render('Projects/Create');
    }

    /**
     * Store a newly created project with the authenticated user as owner.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::query()->create([
            'owner_id' => $request->user()->id,
            'title' => $request->string('title'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('projects.show', $project);
    }

    /**
     * Display the project for any of its members.
     */
    public function show(Request $request, Project $project): Response
    {
        abort_unless($project->isMember($request->user()), 404);

        return Inertia::render('Projects/Show', [
            'project' => $this->projectPayload($request, $project),
        ]);
    }

    /**
     * Show the form for editing the project.
     */
    public function edit(Request $request, Project $project): Response
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        return Inertia::render('Projects/Edit', [
            'project' => $this->projectPayload($request, $project),
        ]);
    }

    /**
     * Update the project's title and description (owner of an active project).
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $this->authorize('update', $project);

        $project->update([
            'title' => $request->string('title'),
            'description' => $request->input('description'),
        ]);

        return redirect()->route('projects.show', $project);
    }

    /**
     * Transition the project to an allowed status (owner).
     */
    public function status(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $this->authorize('transition', $project);

        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $target = ProjectStatus::tryFrom($validated['status']);

        abort_if($target === null, 422);

        if (! in_array($target, $project->status->transitions(), true)) {
            return back()->withErrors([
                'status' => "No se puede pasar de {$project->status->label()} a {$target->label()}.",
            ]);
        }

        $project->update(['status' => $target]);

        return redirect()->route('projects.show', $project);
    }

    /**
     * Remove the project and dissolve its memberships (owner).
     */
    public function destroy(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $this->authorize('delete', $project);

        $project->delete();

        return redirect()->route('projects.index');
    }

    /**
     * Build the project payload shared by show and edit.
     *
     * @return array<string, mixed>
     */
    private function projectPayload(Request $request, Project $project): array
    {
        $project->loadMissing(['owner', 'members']);

        return [
            'id' => $project->id,
            'title' => $project->title,
            'description' => $project->description,
            'status' => $project->status->value,
            'statusLabel' => $project->status->label(),
            'progress' => $project->progressPercentage(),
            'owner' => [
                'id' => $project->owner->id,
                'name' => $project->owner->name,
                'email' => $project->owner->email,
            ],
            'members' => $project->members->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
            ])->values(),
            'allowedTransitions' => array_map(
                fn (ProjectStatus $status) => $status->value,
                $project->status->transitions(),
            ),
            'isOwner' => $project->isOwnedBy($request->user()),
        ];
    }
}
