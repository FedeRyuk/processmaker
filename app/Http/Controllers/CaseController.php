<?php

namespace App\Http\Controllers;

use App\Models\CaseTask;
use App\Models\ProcessCase;
use App\Models\Project;
use App\Services\ConditionEvaluator;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CaseController extends Controller
{
    public function index(Project $project)
    {
        $project->load(['cases.caseTasks.task']);
        return view('cases.index', compact('project'));
    }

    /**
     * Start a new case: create it and instantiate the initial task.
     */
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $initial = $project->tasks()->where('is_initial', true)->first()
            ?? $project->tasks()->first();

        if (!$initial) {
            return back()->withErrors(['name' => 'Definisci almeno un task nel flusso prima di avviare un caso.']);
        }

        $case = $project->cases()->create(['name' => $data['name'], 'status' => 'open']);
        $case->caseTasks()->create(['task_id' => $initial->id, 'status' => 'pending']);

        return redirect()->route('cases.task', [$project, $case, $case->caseTasks()->first()]);
    }

    public function showTask(Project $project, ProcessCase $case, CaseTask $caseTask)
    {
        abort_unless($case->project_id === $project->id && $caseTask->case_id === $case->id, 404);
        $caseTask->load('task.fields');
        $case->load('caseTasks.task');
        return view('cases.task', compact('project', 'case', 'caseTask'));
    }

    /**
     * Temporary save (draft) - data kept but task stays editable.
     */
    public function saveDraft(Request $request, Project $project, ProcessCase $case, CaseTask $caseTask)
    {
        $this->guard($project, $case, $caseTask);

        if ($caseTask->isFrozen()) {
            return back()->withErrors(['form' => 'Questo task è già stato congelato.']);
        }

        $caseTask->update([
            'data' => $request->input('fields', []),
            'status' => 'draft',
        ]);

        return back()->with('status', 'Salvataggio temporaneo eseguito.');
    }

    /**
     * Final save: validate, freeze the task and generate next tasks from the flow.
     */
    public function complete(Request $request, Project $project, ProcessCase $case, CaseTask $caseTask, ConditionEvaluator $evaluator)
    {
        $this->guard($project, $case, $caseTask);
        $caseTask->load('task.fields');

        if ($caseTask->isFrozen()) {
            return back()->withErrors(['form' => 'Questo task è già stato congelato.']);
        }

        $values = $request->input('fields', []);
        $this->validateFields($caseTask, $values);

        $caseTask->update([
            'data' => $values,
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Evaluate outgoing transitions and spawn the next tasks.
        $project->load('transitions');
        $spawned = 0;
        foreach ($project->transitions->where('from_task_id', $caseTask->task_id) as $transition) {
            if ($evaluator->passes($transition->conditions, $values)) {
                $case->caseTasks()->create(['task_id' => $transition->to_task_id, 'status' => 'pending']);
                $spawned++;
            }
        }

        // Leaf task with no spawned successors closes the case.
        if ($spawned === 0 && $case->caseTasks()->whereIn('status', ['pending', 'draft'])->count() === 0) {
            $case->update(['status' => 'closed']);
        }

        return redirect()->route('cases.index', $project)
            ->with('status', $spawned > 0
                ? "Task completato. Generati {$spawned} task successivi."
                : 'Task completato. Il caso è stato chiuso.');
    }

    private function validateFields(CaseTask $caseTask, array $values): void
    {
        $errors = [];
        foreach ($caseTask->task->fields as $field) {
            $required = data_get($field->config, 'required', false);
            $value = $values[$field->name] ?? null;
            if ($required && ($value === null || $value === '')) {
                $errors["fields.{$field->name}"] = "Il campo «{$field->label}» è obbligatorio.";
            }
            if ($field->type === 'number' && $value !== null && $value !== '' && !is_numeric($value)) {
                $errors["fields.{$field->name}"] = "Il campo «{$field->label}» deve essere numerico.";
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function guard(Project $project, ProcessCase $case, CaseTask $caseTask): void
    {
        abort_unless($case->project_id === $project->id && $caseTask->case_id === $case->id, 404);
    }
}
