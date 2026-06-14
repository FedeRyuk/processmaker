@extends('layouts.app')
@section('title', 'Progettazione task · ' . $task->name)
@section('ai_page', 'task')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-input-cursor-text"></i> Progettazione task: {{ $task->name }}</h4>
    <a href="{{ route('flow.index', $project) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Torna al flusso</a>
</div>

@include('tasks.partials.designer-panel', ['fieldsJson' => $fieldsJson])
@endsection
