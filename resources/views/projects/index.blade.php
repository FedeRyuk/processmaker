@extends('layouts.app')
@section('title', 'Progetti')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="mb-0"><i class="bi bi-collection"></i> Progetti di processo</h3>
    <a href="{{ route('projects.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nuovo progetto</a>
</div>

@if ($projects->isEmpty())
    <div class="card"><div class="card-body text-center text-muted py-5">
        Nessun progetto presente. Crea il primo processo aziendale.
    </div></div>
@else
<div class="row g-3">
    @foreach ($projects as $project)
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body">
                <h5 class="card-title">{{ $project->name }}</h5>
                <p class="card-text text-muted small">{{ $project->description ?: 'Nessuna descrizione' }}</p>
                <div class="d-flex gap-3 small text-muted mb-3">
                    <span><i class="bi bi-list-task"></i> {{ $project->tasks_count }} task</span>
                    <span><i class="bi bi-folder2-open"></i> {{ $project->cases_count }} casi</span>
                </div>
                <p class="small text-muted mb-0"><i class="bi bi-database"></i>
                    {{ $project->db_database ?: 'db non impostato' }} @ {{ $project->db_host }}</p>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="{{ route('projects.show', $project) }}" class="btn btn-sm btn-outline-primary">Apri</a>
                <form method="POST" action="{{ route('projects.destroy', $project) }}"
                      onsubmit="return confirm('Eliminare il progetto e tutte le sue configurazioni?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
