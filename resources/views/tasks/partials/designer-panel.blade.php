@php $panelId = 'task-designer-' . $task->id; @endphp

<div class="row g-3 task-designer-panel" id="{{ $panelId }}">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-ul"></i> Campi del task</span>
                <button type="button" class="btn btn-primary btn-sm" data-role="new-field"><i class="bi bi-plus-lg"></i> Nuovo campo</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead><tr><th>Etichetta</th><th>Nome</th><th>Tipo</th><th>Lettura</th><th>Scrittura</th><th></th></tr></thead>
                    <tbody data-role="fields-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card" data-role="field-editor" style="display:none;">
            <div class="card-header"><i class="bi bi-pencil"></i> <span data-role="editor-title">Campo</span></div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6"><label class="form-label small">Etichetta</label>
                        <input data-role="f-label" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="form-label small">Nome (chiave)</label>
                        <input data-role="f-name" class="form-control form-control-sm"></div>
                    <div class="col-6"><label class="form-label small">Tipo</label>
                        <select data-role="f-type" class="form-select form-select-sm">
                            <option value="text">Testo</option>
                            <option value="textarea">Testo lungo</option>
                            <option value="number">Numero</option>
                            <option value="date">Data</option>
                            <option value="select">Select</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="table">Tabella</option>
                        </select></div>
                    <div class="col-6 d-flex align-items-end gap-3">
                        <div class="form-check"><input data-role="f-required" class="form-check-input" type="checkbox" id="{{ $panelId }}-required">
                            <label class="form-check-label small" for="{{ $panelId }}-required">Obbligatorio</label></div>
                        <div class="form-check"><input data-role="f-readonly" class="form-check-input" type="checkbox" id="{{ $panelId }}-readonly">
                            <label class="form-check-label small" for="{{ $panelId }}-readonly">Sola lettura</label></div>
                    </div>
                    <div class="col-12" data-role="options-wrap" style="display:none;">
                        <label class="form-label small" data-role="options-label">Opzioni (una per riga: valore|etichetta)</label>
                        <textarea data-role="f-options" class="form-control form-control-sm" rows="3"></textarea>
                    </div>
                </div>

                <hr>
                <h6 class="small text-muted">Mappatura di LETTURA (o valore di default se assente)</h6>
                <div class="row g-2">
                    <div class="col-4"><input data-role="f-read-db" class="form-control form-control-sm" placeholder="database"></div>
                    <div class="col-4"><input data-role="f-read-table" class="form-control form-control-sm" placeholder="tabella"></div>
                    <div class="col-4"><input data-role="f-read-col" class="form-control form-control-sm" placeholder="colonna"></div>
                    <div class="col-12"><input data-role="f-default" class="form-control form-control-sm" placeholder="valore di default"></div>
                </div>

                <hr>
                <h6 class="small text-muted">Mappatura di SCRITTURA (salvataggio)</h6>
                <div class="row g-2">
                    <div class="col-4"><input data-role="f-write-db" class="form-control form-control-sm" placeholder="database"></div>
                    <div class="col-4"><input data-role="f-write-table" class="form-control form-control-sm" placeholder="tabella"></div>
                    <div class="col-4"><input data-role="f-write-col" class="form-control form-control-sm" placeholder="colonna"></div>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="button" data-role="save-field" class="btn btn-primary btn-sm">Salva campo</button>
                    <button type="button" data-role="cancel-field" class="btn btn-outline-secondary btn-sm">Annulla</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(() => {
    const root = document.getElementById(@json($panelId));
    const routes = {
        store: @json(route('tasks.fields.store', [$project, $task])),
        base: @json(url('projects/'.$project->id.'/tasks/'.$task->id.'/fields')),
    };
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    let fields = {!! $fieldsJson !!};
    let editingId = null;

    const find = role => root.querySelector(`[data-role="${role}"]`);
    const editor = find('field-editor');

    async function api(url, method, body) {
        const res = await fetch(url, { method, headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body: body?JSON.stringify(body):undefined });
        if (!res.ok) { const e = await res.json().catch(()=>({})); throw new Error(Object.values(e.errors||{message:['Errore']}).flat().join('\n')); }
        return res.status === 204 ? null : res.json();
    }

    function renderTable() {
        const body = find('fields-body');
        body.innerHTML = '';
        if (!fields.length) { body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Nessun campo definito</td></tr>'; return; }
        fields.forEach(f => {
            const tr = document.createElement('tr');
            const read = [f.read_db,f.read_table,f.read_column].filter(Boolean).join('.') || (f.default_value?('def: '+f.default_value):'—');
            const write = [f.write_db,f.write_table,f.write_column].filter(Boolean).join('.') || '—';
            tr.innerHTML = `<td>${esc(f.label)}</td><td><code>${esc(f.name)}</code></td><td><span class="badge bg-secondary">${esc(f.type)}</span></td>
                <td class="small">${esc(read)}</td><td class="small">${esc(write)}</td>
                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-primary me-1 ed">✎</button><button type="button" class="btn btn-sm btn-outline-danger del">×</button></td>`;
            tr.querySelector('.ed').addEventListener('click', () => openEditor(f));
            tr.querySelector('.del').addEventListener('click', async () => {
                if (!confirm('Eliminare il campo?')) return;
                await api(routes.base + '/' + f.id, 'DELETE');
                fields = fields.filter(x => x.id !== f.id);
                renderTable();
            });
            body.appendChild(tr);
        });
    }

    find('f-type').addEventListener('change', toggleOptions);
    function toggleOptions() {
        const type = find('f-type').value;
        find('options-wrap').style.display = (type === 'select' || type === 'table') ? 'block' : 'none';
        find('options-label').textContent = type === 'table'
            ? 'Colonne della tabella (una per riga: nome|tipo)'
            : 'Opzioni (una per riga: valore|etichetta)';
    }

    function openEditor(field) {
        editingId = field ? field.id : null;
        editor.style.display = 'block';
        find('editor-title').textContent = field ? 'Modifica campo' : 'Nuovo campo';
        find('f-label').value = field?.label || '';
        find('f-name').value = field?.name || '';
        find('f-type').value = field?.type || 'text';
        find('f-required').checked = !!(field?.config?.required);
        find('f-readonly').checked = !!(field?.config?.readonly);
        find('f-options').value = optionsToText(field);
        find('f-read-db').value = field?.read_db || '';
        find('f-read-table').value = field?.read_table || '';
        find('f-read-col').value = field?.read_column || '';
        find('f-default').value = field?.default_value || '';
        find('f-write-db').value = field?.write_db || '';
        find('f-write-table').value = field?.write_table || '';
        find('f-write-col').value = field?.write_column || '';
        toggleOptions();
    }

    function optionsToText(field) {
        if (!field || !field.options) return '';
        return (field.options || []).map(o => typeof o === 'object' ? `${o.value ?? o.name}|${o.label ?? o.type ?? ''}` : o).join('\n');
    }

    function textToOptions() {
        const type = find('f-type').value;
        const raw = find('f-options').value.trim();
        if (!raw || (type !== 'select' && type !== 'table')) return null;
        return raw.split('\n').map(line => {
            const [firstValue, secondValue] = line.split('|');
            return type === 'table'
                ? { name: firstValue.trim(), type: (secondValue||'text').trim() }
                : { value: firstValue.trim(), label: (secondValue||firstValue).trim() };
        });
    }

    find('new-field').addEventListener('click', () => openEditor(null));
    find('cancel-field').addEventListener('click', () => editor.style.display = 'none');

    find('save-field').addEventListener('click', async () => {
        const payload = {
            label: find('f-label').value,
            name: find('f-name').value,
            type: find('f-type').value,
            options: textToOptions(),
            config: { required: find('f-required').checked, readonly: find('f-readonly').checked },
            read_db: find('f-read-db').value,
            read_table: find('f-read-table').value,
            read_column: find('f-read-col').value,
            default_value: find('f-default').value,
            write_db: find('f-write-db').value,
            write_table: find('f-write-table').value,
            write_column: find('f-write-col').value,
        };
        try {
            let field;
            if (editingId) { field = await api(routes.base + '/' + editingId, 'PUT', payload); fields = fields.map(x => x.id === editingId ? field : x); }
            else { field = await api(routes.store, 'POST', payload); fields.push(field); }
            editor.style.display = 'none';
            renderTable();
        } catch (e) { alert(e.message); }
    });

    function esc(value){return String(value??'').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));}
    renderTable();
})();
</script>
@endpush