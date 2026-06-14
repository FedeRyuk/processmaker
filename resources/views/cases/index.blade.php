@extends('layouts.app')
@section('title', 'Casi · ' . $project->name)
@section('ai_page', 'flow')

@section('content')
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-plus-circle"></i> Avvia un nuovo caso</div>
            <div class="card-body">
                <form method="POST" action="{{ route('cases.store', $project) }}">
                    @csrf
                    <div class="mb-2"><input name="name" class="form-control" placeholder="Nome del caso" required></div>
                    <button class="btn btn-primary w-100">Avvia caso</button>
                </form>
                <p class="small text-muted mt-2 mb-0">Viene creato il task iniziale del processo.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <h5 class="mb-3"><i class="bi bi-folder2-open"></i> Casi del processo</h5>
        @forelse ($project->cases as $case)
            <div class="card mb-2">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><strong>{{ $case->name }}</strong></span>
                    <span class="badge bg-{{ $case->status === 'closed' ? 'secondary' : 'success' }}">{{ $case->status === 'closed' ? 'chiuso' : 'aperto' }}</span>
                </div>
                <ul class="list-group list-group-flush">
                    @foreach ($case->caseTasks as $ct)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <span>
                                <i class="bi bi-{{ $ct->status === 'completed' ? 'lock-fill text-secondary' : 'pencil-square text-primary' }}"></i>
                                {{ $ct->task->name }}
                                <span class="badge bg-light text-dark border ms-1">{{ $ct->status }}</span>
                            </span>
                            <a href="{{ route('cases.task', [$project, $case, $ct]) }}" class="btn btn-sm btn-outline-primary">
                                {{ $ct->status === 'completed' ? 'Vedi' : 'Apri' }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="card"><div class="card-body text-muted text-center py-4">Nessun caso avviato.</div></div>
        @endforelse
    </div>
</div>
@endsection
