<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSprintRequest;
use App\Http\Requests\UpdateSprintRequest;
use App\Models\Project;
use App\Models\Sprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProjectSprintController extends Controller
{
    /**
     * Store a newly created sprint for the project (owner of an active project).
     */
    public function store(StoreSprintRequest $request, Project $project): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        $this->ensureProjectIsActive($project);

        $this->authorize('create', [Sprint::class, $project]);

        $project->sprints()->create($request->validated());

        return redirect()->route('projects.show', $project);
    }

    /**
     * Update the sprint's name and dates (owner of an active project).
     */
    public function update(UpdateSprintRequest $request, Project $project, Sprint $sprint): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        abort_unless($sprint->project_id === $project->id, 404);

        $this->ensureProjectIsActive($project);

        $this->authorize('update', [Sprint::class, $project]);

        $sprint->update($request->validated());

        return redirect()->route('projects.show', $project);
    }

    /**
     * Remove the sprint from the project (owner of an active project).
     */
    public function destroy(Request $request, Project $project, Sprint $sprint): RedirectResponse
    {
        abort_unless($project->isOwnedBy($request->user()), 404);

        abort_unless($sprint->project_id === $project->id, 404);

        $this->ensureProjectIsActive($project);

        $this->authorize('delete', [Sprint::class, $project]);

        $sprint->delete();

        return redirect()->route('projects.show', $project);
    }

    /**
     * Block sprint management when the project is not active, with a clear message.
     */
    private function ensureProjectIsActive(Project $project): void
    {
        if (! $project->status->isActive()) {
            back()->withErrors([
                'project' => 'El proyecto no admite cambios en su estado actual.',
            ])->throwResponse();
        }
    }
}
