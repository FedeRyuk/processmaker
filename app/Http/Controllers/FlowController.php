<?php

namespace App\Http\Controllers;

use App\Models\ProcessTask;
use App\Models\Project;
use App\Models\Transition;
use Illuminate\Http\Request;

class FlowController extends Controller
{
    public function index(Project $project)
    {
        $project->load(['tasks.fields', 'transitions']);

        $tasksJson = $project->tasks->map(fn ($t) => [
            'id' => $t->id, 'name' => $t->name, 'is_initial' => $t->is_initial,
            'pos_x' => $t->pos_x, 'pos_y' => $t->pos_y,
        ])->toJson();

        $transitionsJson = $project->transitions->map(fn ($t) => [
            'id' => $t->id, 'from' => $t->from_task_id, 'to' => $t->to_task_id,
            'label' => $t->label, 'conditions' => $t->conditions,
        ])->toJson();

        $fieldsByTaskJson = $project->tasks->mapWithKeys(fn ($t) => [
            $t->id => $t->fields->map(fn ($f) => ['name' => $f->name, 'label' => $f->label]),
        ])->toJson();

        return view('flow.index', compact('project', 'tasksJson', 'transitionsJson', 'fieldsByTaskJson'));
    }

    /**
     * Add a new task node to the flow.
     */
    public function storeTask(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'pos_x' => ['nullable', 'integer'],
            'pos_y' => ['nullable', 'integer'],
            'is_initial' => ['nullable', 'boolean'],
        ]);

        $task = $project->tasks()->create([
            'name' => $data['name'],
            'pos_x' => $data['pos_x'] ?? 60,
            'pos_y' => $data['pos_y'] ?? 60,
            'is_initial' => $request->boolean('is_initial'),
        ]);

        return response()->json($task);
    }

    /**
     * Persist node positions after dragging.
     */
    public function updatePositions(Request $request, Project $project)
    {
        $positions = $request->validate([
            'positions' => ['required', 'array'],
            'positions.*.id' => ['required', 'integer'],
            'positions.*.x' => ['required', 'integer'],
            'positions.*.y' => ['required', 'integer'],
        ])['positions'];

        foreach ($positions as $pos) {
            ProcessTask::where('project_id', $project->id)
                ->where('id', $pos['id'])
                ->update(['pos_x' => $pos['x'], 'pos_y' => $pos['y']]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroyTask(Project $project, ProcessTask $task)
    {
        abort_unless($task->project_id === $project->id, 404);
        $task->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Create an arrow between two tasks.
     */
    public function storeTransition(Request $request, Project $project)
    {
        $data = $request->validate([
            'from_task_id' => ['required', 'integer', 'exists:process_tasks,id'],
            'to_task_id' => ['required', 'integer', 'exists:process_tasks,id', 'different:from_task_id'],
        ]);

        $transition = $project->transitions()->firstOrCreate([
            'from_task_id' => $data['from_task_id'],
            'to_task_id' => $data['to_task_id'],
        ]);

        return response()->json($transition);
    }

    /**
     * Save the conditions attached to an arrow.
     */
    public function updateTransition(Request $request, Project $project, Transition $transition)
    {
        abort_unless($transition->project_id === $project->id, 404);

        $data = $request->validate([
            'label' => ['nullable', 'string', 'max:255'],
            'conditions' => ['nullable', 'array'],
            'conditions.logic' => ['nullable', 'in:AND,OR'],
            'conditions.rules' => ['nullable', 'array'],
            'conditions.rules.*.field' => ['nullable', 'string'],
            'conditions.rules.*.operator' => ['nullable', 'string'],
            'conditions.rules.*.value' => ['nullable'],
        ]);

        $transition->update([
            'label' => $data['label'] ?? null,
            'conditions' => $data['conditions'] ?? null,
        ]);

        return response()->json($transition);
    }

    public function destroyTransition(Project $project, Transition $transition)
    {
        abort_unless($transition->project_id === $project->id, 404);
        $transition->delete();
        return response()->json(['ok' => true]);
    }
}
