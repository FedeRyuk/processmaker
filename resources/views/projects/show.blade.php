@extends('layouts.app')
@section('title', $project->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-1">{{ $project->name }}</h3>
        <p class="text-muted mb-0">{{ $project->description }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline-secondary"><i class="bi bi-gear"></i> Impostazioni</a>
        <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">Tutti i progetti</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-3"><a class="text-decoration-none" href="{{ route('flow.index', $project) }}">
        <div class="card h-100 shadow-sm"><div class="card-body text-center py-4">
            <i class="bi bi-diagram-3 fs-1 text-primary"></i>
            <h5 class="mt-2">Flusso</h5>
            <p class="text-muted small mb-0">{{ $project->transitions_count }} transizioni, {{ $project->tasks_count }} task</p>
        </div></div></a>
    </div>
    <div class="col-md-3"><a class="text-decoration-none" href="{{ route('flow.index', $project) }}">
        <div class="card h-100 shadow-sm"><div class="card-body text-center py-4">
            <i class="bi bi-input-cursor-text fs-1 text-primary"></i>
            <h5 class="mt-2">Progettazione task</h5>
            <p class="text-muted small mb-0">Apri un task dal flusso per gestirne i campi</p>
        </div></div></a>
    </div>
    <div class="col-md-3"><a class="text-decoration-none" href="{{ route('db.index', $project) }}">
        <div class="card h-100 shadow-sm"><div class="card-body text-center py-4">
            <i class="bi bi-database fs-1 text-primary"></i>
            <h5 class="mt-2">Struttura DB</h5>
            <p class="text-muted small mb-0">{{ $project->db_tables_count }} tabelle</p>
        </div></div></a>
    </div>
    <div class="col-md-3"><a class="text-decoration-none" href="{{ route('cases.index', $project) }}">
        <div class="card h-100 shadow-sm"><div class="card-body text-center py-4">
            <i class="bi bi-folder2-open fs-1 text-primary"></i>
            <h5 class="mt-2">Casi</h5>
            <p class="text-muted small mb-0">{{ $project->cases_count }} casi avviati</p>
        </div></div></a>
    </div>
</div>
@endsection
