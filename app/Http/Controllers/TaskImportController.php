<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\ImportTasksRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class TaskImportController extends Controller
{
    /**
     * Preview a standalone import (tasks without a project).
     */
    public function preview(ImportTasksRequest $request): RedirectResponse
    {
        return back()->with('importPreview', $this->forClient($request->analyze(null, $request->user())));
    }

    /**
     * Import the valid tasks as standalone backlog tasks.
     */
    public function store(ImportTasksRequest $request): RedirectResponse
    {
        return $this->import($request, null);
    }

    /**
     * Preview an import into a project's backlog (members of an active project).
     */
    public function projectPreview(ImportTasksRequest $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($request->user(), $project);

        return back()->with('importPreview', $this->forClient($request->analyze($project, $request->user())));
    }

    /**
     * Import the valid tasks into a project's backlog.
     */
    public function projectStore(ImportTasksRequest $request, Project $project): RedirectResponse
    {
        $this->authorizeProject($request->user(), $project);

        return $this->import($request, $project);
    }

    /**
     * Drop full descriptions from the preview sent to the browser; confirming re-analyzes the content.
     *
     * @param  array<string, mixed>  $preview
     * @return array<string, mixed>
     */
    private function forClient(array $preview): array
    {
        $preview['tasks'] = array_map(
            fn (array $task) => collect($task)->except('description')->all(),
            $preview['tasks'],
        );

        return $preview;
    }

    private function authorizeProject(User $user, Project $project): void
    {
        abort_unless($project->isMember($user), 404);

        $this->authorize('create', [Task::class, $project]);
    }

    /**
     * Re-analyze the content and create every importable task in one transaction.
     */
    private function import(ImportTasksRequest $request, ?Project $project): RedirectResponse
    {
        $user = $request->user();
        $preview = $request->analyze($project, $user);

        if ($preview['counts']['importable'] === 0) {
            return back()->withErrors(['content' => 'No hay tareas válidas para importar.']);
        }

        $created = DB::transaction(function () use ($preview, $project, $user): int {
            $count = 0;

            foreach ($preview['tasks'] as $task) {
                if (! in_array($task['state'], ['ok', 'warning'], true)) {
                    continue;
                }

                Task::query()->create([
                    'title' => $task['title'],
                    'type' => $task['type'],
                    'priority' => $task['priority'],
                    'status' => TaskStatus::Backlog,
                    'assignee_id' => $task['assignee']['id'] ?? null,
                    'description' => ImportTasksRequest::toTiptap($task['description']),
                    'project_id' => $project?->id,
                    'sprint_id' => null,
                    'author_id' => $user->id,
                ]);

                $count++;
            }

            return $count;
        });

        return back()->with('success', $created === 1 ? 'Se importó 1 tarea.' : "Se importaron {$created} tareas.");
    }
}
