@extends('layouts.app')
@section('title', $project->exists ? 'Modifica progetto' : 'Nuovo progetto')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
    <h3 class="mb-4">{{ $project->exists ? 'Modifica progetto' : 'Nuovo progetto di processo' }}</h3>
    <form method="POST" action="{{ $project->exists ? route('projects.update', $project) : route('projects.store') }}">
        @csrf
        @if ($project->exists) @method('PUT') @endif
        <div class="card mb-3"><div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nome del processo</label>
                <input name="name" class="form-control" value="{{ old('name', $project->name) }}" required>
            </div>
            <div class="mb-0">
                <label class="form-label">Descrizione</label>
                <textarea name="description" class="form-control" rows="3">{{ old('description', $project->description) }}</textarea>
            </div>
        </div></div>

        <div class="card mb-3"><div class="card-header">
            <i class="bi bi-database"></i> Database MySQL del progetto
            <small class="text-muted d-block">Le configurazioni e i dati del processo vengono memorizzati in questo database.</small>
        </div><div class="card-body">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Host</label>
                    <input name="db_host" class="form-control" value="{{ old('db_host', $project->db_host ?? '127.0.0.1') }}" required></div>
                <div class="col-md-2"><label class="form-label">Porta</label>
                    <input name="db_port" type="number" class="form-control" value="{{ old('db_port', $project->db_port ?? 3306) }}" required></div>
                <div class="col-md-4"><label class="form-label">Database</label>
                    <input name="db_database" class="form-control" value="{{ old('db_database', $project->db_database) }}"></div>
                <div class="col-md-6"><label class="form-label">Utente</label>
                    <input name="db_username" class="form-control" value="{{ old('db_username', $project->db_username ?? 'root') }}" required></div>
                <div class="col-md-6"><label class="form-label">Password</label>
                    <input name="db_password" type="password" class="form-control" value="{{ old('db_password', $project->db_password) }}"></div>
            </div>
        </div></div>

        <div class="d-flex gap-2">
            <button class="btn btn-primary">Salva</button>
            <a href="{{ route('projects.index') }}" class="btn btn-outline-secondary">Annulla</a>
        </div>
    </form>
</div>
</div>
@endsection
