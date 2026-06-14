@extends('layouts.app')
@section('title', 'Progettazione task')
@section('ai_page', 'task')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-input-cursor-text"></i> Progettazione task</h4>
    <a href="{{ route('flow.index', $project) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-diagram-3"></i> Vai al flusso</a>
</div>

@forelse ($tasks as $task)
    <section class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0"><i class="bi bi-input-cursor-text"></i> {{ $task->name }}</h5>
            <a href="{{ route('tasks.designer', [$project, $task]) }}" class="btn btn-outline-secondary btn-sm">Apri singolo task</a>
        </div>
        @include('tasks.partials.designer-panel', ['fieldsJson' => $task->fields_json])
    </section>
@empty
    <div class="alert alert-info">Nessun task definito. Creane uno dalla pagina del flusso.</div>
@endforelse
@endsection