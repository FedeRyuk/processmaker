<?php

namespace App\Http\Controllers;

use App\Models\ProcessTask;
use App\Models\Project;
use App\Models\TaskField;
use Illuminate\Http\Request;

class TaskDesignerController extends Controller
{
    public function index(Project $project, ProcessTask $task)
    {
        abort_unless($task->project_id === $project->id, 404);
        $task->load('fields');

        $fieldsJson = $task->fields->map(fn ($f) => [
            'id' => $f->id, 'name' => $f->name, 'label' => $f->label, 'type' => $f->type,
            'options' => $f->options, 'config' => $f->config,
            'read_db' => $f->read_db, 'read_table' => $f->read_table, 'read_column' => $f->read_column,
            'default_value' => $f->default_value,
            'write_db' => $f->write_db, 'write_table' => $f->write_table, 'write_column' => $f->write_column,
        ])->toJson();

        return view('tasks.designer', compact('project', 'task', 'fieldsJson'));
    }

    public function storeField(Request $request, Project $project, ProcessTask $task)
    {
        abort_unless($task->project_id === $project->id, 404);
        $data = $this->validateField($request);
        $data['sort_order'] = (int) $task->fields()->max('sort_order') + 1;
        $field = $task->fields()->create($data);
        return response()->json($field);
    }

    public function updateField(Request $request, Project $project, ProcessTask $task, TaskField $field)
    {
        abort_unless($task->project_id === $project->id && $field->task_id === $task->id, 404);
        $field->update($this->validateField($request));
        return response()->json($field);
    }

    public function destroyField(Project $project, ProcessTask $task, TaskField $field)
    {
        abort_unless($task->project_id === $project->id && $field->task_id === $task->id, 404);
        $field->delete();
        return response()->json(['ok' => true]);
    }

    private function validateField(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:text,textarea,number,date,select,checkbox,table'],
            'options' => ['nullable', 'array'],
            'config' => ['nullable', 'array'],
            'read_db' => ['nullable', 'string', 'max:255'],
            'read_table' => ['nullable', 'string', 'max:255'],
            'read_column' => ['nullable', 'string', 'max:255'],
            'default_value' => ['nullable', 'string'],
            'write_db' => ['nullable', 'string', 'max:255'],
            'write_table' => ['nullable', 'string', 'max:255'],
            'write_column' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
