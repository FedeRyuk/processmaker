<?php

namespace App\Http\Controllers;

use App\Models\DbColumn;
use App\Models\DbTable;
use App\Models\Project;
use App\Services\MysqlScriptGenerator;
use Illuminate\Http\Request;

class DbStructureController extends Controller
{
    public function index(Project $project)
    {
        $project->load('dbTables.columns');

        $tablesJson = $project->dbTables->map(fn ($t) => [
            'id' => $t->id, 'name' => $t->name, 'comment' => $t->comment,
            'columns' => $t->columns->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'type' => $c->type, 'length' => $c->length,
                'nullable' => $c->nullable, 'default' => $c->default,
                'is_primary' => $c->is_primary, 'auto_increment' => $c->auto_increment,
            ]),
        ])->toJson();

        return view('dbstructure.index', compact('project', 'tablesJson'));
    }

    public function storeTable(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]);
        $table = $project->dbTables()->create($data);
        return response()->json($table->load('columns'));
    }

    public function updateTable(Request $request, Project $project, DbTable $table)
    {
        abort_unless($table->project_id === $project->id, 404);
        $table->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:255'],
        ]));
        return response()->json($table);
    }

    public function destroyTable(Project $project, DbTable $table)
    {
        abort_unless($table->project_id === $project->id, 404);
        $table->delete();
        return response()->json(['ok' => true]);
    }

    public function storeColumn(Request $request, Project $project, DbTable $table)
    {
        abort_unless($table->project_id === $project->id, 404);
        $data = $this->validateColumn($request);
        $data['sort_order'] = (int) $table->columns()->max('sort_order') + 1;
        $column = $table->columns()->create($data);
        return response()->json($column);
    }

    public function updateColumn(Request $request, Project $project, DbTable $table, DbColumn $column)
    {
        abort_unless($table->project_id === $project->id && $column->db_table_id === $table->id, 404);
        $column->update($this->validateColumn($request));
        return response()->json($column);
    }

    public function destroyColumn(Project $project, DbTable $table, DbColumn $column)
    {
        abort_unless($table->project_id === $project->id && $column->db_table_id === $table->id, 404);
        $column->delete();
        return response()->json(['ok' => true]);
    }

    /**
     * Return the MySQL script for one table or for the whole project.
     */
    public function script(Project $project, MysqlScriptGenerator $generator, ?DbTable $table = null)
    {
        if ($table) {
            abort_unless($table->project_id === $project->id, 404);
            $sql = $generator->createTable($table->load('columns'));
        } else {
            $sql = $generator->script($project->dbTables()->with('columns')->get());
        }
        return response()->json(['sql' => $sql]);
    }

    private function validateColumn(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:50'],
            'length' => ['nullable', 'string', 'max:50'],
            'nullable' => ['nullable', 'boolean'],
            'default' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['nullable', 'boolean'],
            'auto_increment' => ['nullable', 'boolean'],
        ]);
    }
}
