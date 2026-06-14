@extends('layouts.app')
@section('title', $caseTask->task->name . ' · ' . $case->name)
@section('ai_page', 'task')

@php $frozen = $caseTask->status === 'completed'; $data = $caseTask->data ?? []; @endphp

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-pencil-square"></i> {{ $caseTask->task->name }}
        <small class="text-muted">· caso «{{ $case->name }}»</small></h4>
    <a href="{{ route('cases.index', $project) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Torna ai casi</a>
</div>

@if ($frozen)
    <div class="alert alert-secondary"><i class="bi bi-lock-fill"></i> Questo task è stato salvato in via definitiva e i suoi dati sono congelati (sola lettura).</div>
@endif

<div class="row">
<div class="col-lg-8">
<form method="POST" id="task-form" action="{{ route('cases.task.complete', [$project, $case, $caseTask]) }}">
    @csrf
    <div class="card"><div class="card-body">
        @forelse ($caseTask->task->fields as $field)
            @php $val = $data[$field->name] ?? $field->default_value; $ro = $frozen || data_get($field->config, 'readonly'); @endphp
            <div class="mb-3">
                <label class="form-label">{{ $field->label }}
                    @if(data_get($field->config,'required'))<span class="text-danger">*</span>@endif
                </label>
                @switch($field->type)
                    @case('textarea')
                        <textarea name="fields[{{ $field->name }}]" class="form-control" rows="3" {{ $ro?'readonly':'' }}>{{ $val }}</textarea>
                        @break
                    @case('number')
                        <input type="number" step="any" name="fields[{{ $field->name }}]" class="form-control" value="{{ $val }}" {{ $ro?'readonly':'' }}>
                        @break
                    @case('date')
                        <input type="date" name="fields[{{ $field->name }}]" class="form-control" value="{{ $val }}" {{ $ro?'readonly':'' }}>
                        @break
                    @case('checkbox')
                        <div class="form-check">
                            <input type="hidden" name="fields[{{ $field->name }}]" value="0">
                            <input type="checkbox" class="form-check-input" name="fields[{{ $field->name }}]" value="1" {{ $val?'checked':'' }} {{ $ro?'disabled':'' }}>
                        </div>
                        @break
                    @case('select')
                        <select name="fields[{{ $field->name }}]" class="form-select" {{ $ro?'disabled':'' }}>
                            <option value="">—</option>
                            @foreach (($field->options ?? []) as $opt)
                                @php $ov = $opt['value'] ?? $opt; $ol = $opt['label'] ?? $ov; @endphp
                                <option value="{{ $ov }}" {{ (string)$val === (string)$ov ? 'selected':'' }}>{{ $ol }}</option>
                            @endforeach
                        </select>
                        @break
                    @case('table')
                        <div class="table-responsive border rounded p-2">
                            <table class="table table-sm mb-0">
                                <thead><tr>@foreach (($field->options ?? []) as $colDef)<th>{{ $colDef['name'] ?? $colDef }}</th>@endforeach</tr></thead>
                                <tbody>
                                    @php $rows = is_array($val) ? $val : []; $rows[] = []; @endphp
                                    @foreach ($rows as $ri => $row)
                                        <tr>@foreach (($field->options ?? []) as $colDef)
                                            @php $cn = $colDef['name'] ?? $colDef; @endphp
                                            <td><input class="form-control form-control-sm" name="fields[{{ $field->name }}][{{ $ri }}][{{ $cn }}]" value="{{ $row[$cn] ?? '' }}" {{ $ro?'readonly':'' }}></td>
                                        @endforeach</tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <small class="text-muted">L'ultima riga vuota serve per aggiungere nuovi dati.</small>
                        </div>
                        @break
                    @default
                        <input type="text" name="fields[{{ $field->name }}]" class="form-control" value="{{ $val }}" {{ $ro?'readonly':'' }}>
                @endswitch
                @if ($field->read_db || $field->read_table)
                    <small class="text-muted">origine: {{ $field->read_db }}.{{ $field->read_table }}.{{ $field->read_column }}</small>
                @endif
            </div>
        @empty
            <p class="text-muted">Questo task non ha campi. Progettalo dalla pagina del flusso (doppio click sul task).</p>
        @endforelse
    </div></div>

    @unless ($frozen)
    <div class="d-flex gap-2 mt-3">
        <button type="submit" formaction="{{ route('cases.task.draft', [$project, $case, $caseTask]) }}" formnovalidate
                class="btn btn-outline-primary"><i class="bi bi-save"></i> Salvataggio temporaneo</button>
        <button type="submit" class="btn btn-success"
                onclick="return confirm('Salvataggio definitivo: i dati verranno validati e congelati. Procedere?')">
            <i class="bi bi-lock"></i> Salva e chiudi task</button>
    </div>
    @endunless
</form>
</div>

<div class="col-lg-4">
    <div class="card">
        <div class="card-header"><i class="bi bi-diagram-2"></i> Avanzamento del caso</div>
        <ul class="list-group list-group-flush">
            @foreach ($case->caseTasks as $ct)
                <li class="list-group-item d-flex justify-content-between align-items-center {{ $ct->id === $caseTask->id ? 'active' : '' }}">
                    <span>{{ $ct->task->name }}</span>
                    <span class="badge bg-{{ $ct->status === 'completed' ? 'secondary' : 'primary' }}">{{ $ct->status }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
</div>
@endsection
