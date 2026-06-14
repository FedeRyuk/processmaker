@extends('layouts.app')
@section('title', 'Progettazione task · ' . $task->name)
@section('ai_page', 'task')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-input-cursor-text"></i> Progettazione task: {{ $task->name }}</h4>
    <a href="{{ route('flow.index', $project) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Torna al flusso</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul"></i> Campi del task</span>
                <button id="new-field" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuovo campo</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead><tr><th>Etichetta</th><th>Nome</th><th>Tipo</th><th>Lettura</th><th>Scrittura</th><th></th></tr></thead>
                    <tbody id="fields-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card" id="field-editor" style="display:none;">
            <div class="card-header"><i class="bi bi-pencil"></i> <span id="editor-title">Campo</span></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6"><label class="form-label small">Etichetta</label>
                        <input id="f-label" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="form-label small">Nome (chiave)</label>
                        <input id="f-name" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="form-label small">Tipo</label>
                        <select id="f-type" class="form-select form-select-sm">
                            <option value="text">Testo</option>
                            <option value="textarea">Testo lungo</option>
                            <option value="number">Numero</option>
                            <option value="date">Data</option>
                            <option value="select">Select</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="table">Tabella</option>
                        </select></div>
                    <div class="col-6 d-flex align-items-end gap-3">
                        <div class="form-check"><input id="f-required" class="form-check-input" type="checkbox">
                            <label class="form-check-label small" for="f-required">Obbligatorio</label></div>
                        <div class="form-check"><input id="f-readonly" class="form-check-input" type="checkbox">
                            <label class="form-check-label small" for="f-readonly">Sola lettura</label></div>
                    </div>
                    <div class="col-12" id="options-wrap" style="display:none;">
                        <label class="form-label small" id="options-label">Opzioni (una per riga: valore|etichetta)</label>
                        <textarea id="f-options" class="form-control form-control-sm" rows="3"></textarea>
                    </div>
                </div>

                <hr>
                <h6 class="small text-muted">Mappatura di LETTURA (o valore di default se assente)</h6>
                <div class="row g-2">
                    <div class="col-4"><input id="f-read-db" class="form-control form-control-sm" placeholder="database"></div>
                    <div class="col-4"><input id="f-read-table" class="form-control form-control-sm" placeholder="tabella"></div>
                    <div class="col-4"><input id="f-read-col" class="form-control form-control-sm" placeholder="colonna"></div>
                    <div class="col-12"><input id="f-default" class="form-control form-control-sm" placeholder="valore di default"></div>
                </div>

                <hr>
                <h6 class="small text-muted">Mappatura di SCRITTURA (salvataggio)</h6>
                <div class="row g-2">
                    <div class="col-4"><input id="f-write-db" class="form-control form-control-sm" placeholder="database"></div>
                    <div class="col-4"><input id="f-write-table" class="form-control form-control-sm" placeholder="tabella"></div>
                    <div class="col-4"><input id="f-write-col" class="form-control form-control-sm" placeholder="colonna"></div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button id="save-field" class="btn btn-primary btn-sm">Salva campo</button>
                    <button id="cancel-field" class="btn btn-outline-secondary btn-sm">Annulla</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const ROUTES = {
    store: "{{ route('tasks.fields.store', [$project, $task]) }}",
    base: "{{ url('projects/'.$project->id.'/tasks/'.$task->id.'/fields') }}",
};
const CSRF = document.querySelector('meta[name=csrf-token]').content;
let fields = {!! $fieldsJson !!};
let editingId = null;

async function api(url, method, body) {
    const res = await fetch(url, { method, headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body: body?JSON.stringify(body):undefined });
    if (!res.ok) { const e = await res.json().catch(()=>({})); throw new Error(Object.values(e.errors||{message:['Errore']}).flat().join('\n')); }
    return res.status === 204 ? null : res.json();
}

function renderTable() {
    const body = document.getElementById('fields-body');
    body.innerHTML = '';
    if (!fields.length) { body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Nessun campo definito</td></tr>'; return; }
    fields.forEach(f => {
        const tr = document.createElement('tr');
        const read = [f.read_db,f.read_table,f.read_column].filter(Boolean).join('.') || (f.default_value?('def: '+f.default_value):'—');
        const write = [f.write_db,f.write_table,f.write_column].filter(Boolean).join('.') || '—';
        tr.innerHTML = `<td>${esc(f.label)}</td><td><code>${esc(f.name)}</code></td><td><span class="badge bg-secondary">${esc(f.type)}</span></td>
            <td class="small">${esc(read)}</td><td class="small">${esc(write)}</td>
            <td class="text-end"><button class="btn btn-sm btn-outline-primary me-1 ed">✎</button><button class="btn btn-sm btn-outline-danger del">×</button></td>`;
        tr.querySelector('.ed').addEventListener('click', () => openEditor(f));
        tr.querySelector('.del').addEventListener('click', async () => {
            if (!confirm('Eliminare il campo?')) return;
            await api(ROUTES.base + '/' + f.id, 'DELETE');
            fields = fields.filter(x => x.id !== f.id);
            renderTable();
        });
        body.appendChild(tr);
    });
}

const editor = document.getElementById('field-editor');
document.getElementById('f-type').addEventListener('change', toggleOptions);
function toggleOptions() {
    const t = document.getElementById('f-type').value;
    const wrap = document.getElementById('options-wrap');
    wrap.style.display = (t === 'select' || t === 'table') ? 'block' : 'none';
    document.getElementById('options-label').textContent = t === 'table'
        ? 'Colonne della tabella (una per riga: nome|tipo)'
        : 'Opzioni (una per riga: valore|etichetta)';
}

function openEditor(f) {
    editingId = f ? f.id : null;
    editor.style.display = 'block';
    document.getElementById('editor-title').textContent = f ? 'Modifica campo' : 'Nuovo campo';
    document.getElementById('f-label').value = f?.label || '';
    document.getElementById('f-name').value = f?.name || '';
    document.getElementById('f-type').value = f?.type || 'text';
    document.getElementById('f-required').checked = !!(f?.config?.required);
    document.getElementById('f-readonly').checked = !!(f?.config?.readonly);
    document.getElementById('f-options').value = optionsToText(f);
    document.getElementById('f-read-db').value = f?.read_db || '';
    document.getElementById('f-read-table').value = f?.read_table || '';
    document.getElementById('f-read-col').value = f?.read_column || '';
    document.getElementById('f-default').value = f?.default_value || '';
    document.getElementById('f-write-db').value = f?.write_db || '';
    document.getElementById('f-write-table').value = f?.write_table || '';
    document.getElementById('f-write-col').value = f?.write_column || '';
    toggleOptions();
}

function optionsToText(f) {
    if (!f || !f.options) return '';
    return (f.options || []).map(o => typeof o === 'object' ? `${o.value ?? o.name}|${o.label ?? o.type ?? ''}` : o).join('\n');
}
function textToOptions() {
    const t = document.getElementById('f-type').value;
    const raw = document.getElementById('f-options').value.trim();
    if (!raw || (t !== 'select' && t !== 'table')) return null;
    return raw.split('\n').map(line => {
        const [a, b] = line.split('|');
        return t === 'table' ? { name: a.trim(), type: (b||'text').trim() } : { value: a.trim(), label: (b||a).trim() };
    });
}

document.getElementById('new-field').addEventListener('click', () => openEditor(null));
document.getElementById('cancel-field').addEventListener('click', () => editor.style.display = 'none');

document.getElementById('save-field').addEventListener('click', async () => {
    const payload = {
        label: document.getElementById('f-label').value,
        name: document.getElementById('f-name').value,
        type: document.getElementById('f-type').value,
        options: textToOptions(),
        config: { required: document.getElementById('f-required').checked, readonly: document.getElementById('f-readonly').checked },
        read_db: document.getElementById('f-read-db').value,
        read_table: document.getElementById('f-read-table').value,
        read_column: document.getElementById('f-read-col').value,
        default_value: document.getElementById('f-default').value,
        write_db: document.getElementById('f-write-db').value,
        write_table: document.getElementById('f-write-table').value,
        write_column: document.getElementById('f-write-col').value,
    };
    try {
        let f;
        if (editingId) { f = await api(ROUTES.base + '/' + editingId, 'PUT', payload); fields = fields.map(x => x.id === editingId ? f : x); }
        else { f = await api(ROUTES.store, 'POST', payload); fields.push(f); }
        editor.style.display = 'none';
        renderTable();
    } catch (e) { alert(e.message); }
});

function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
renderTable();
</script>
@endpush
