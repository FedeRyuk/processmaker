<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::withCount(['tasks', 'cases'])->latest()->get();
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create', ['project' => new Project()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $project = Project::create($data);
        return redirect()->route('projects.show', $project)
            ->with('status', 'Progetto creato.');
    }

    public function show(Project $project)
    {
        $project->loadCount(['tasks', 'transitions', 'dbTables', 'cases']);
        return view('projects.show', compact('project'));
    }

    public function edit(Project $project)
    {
        return view('projects.create', compact('project'));
    }

    public function update(Request $request, Project $project)
    {
        $project->update($this->validateData($request));
        return redirect()->route('projects.show', $project)
            ->with('status', 'Progetto aggiornato.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')
            ->with('status', 'Progetto eliminato.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer'],
            'db_database' => ['nullable', 'string', 'max:255'],
            'db_username' => ['required', 'string', 'max:255'],
            'db_password' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
