@extends('layouts.app')
@section('title', 'Struttura DB · ' . $project->name)
@section('ai_page', 'db')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0"><i class="bi bi-database"></i> Struttura del database</h4>
    <div class="d-flex gap-2">
        <button id="add-table" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nuova tabella</button>
        <button id="gen-all" class="btn btn-outline-dark btn-sm"><i class="bi bi-filetype-sql"></i> Script completo</button>
    </div>
</div>

<div id="tables-wrap" class="row g-3"></div>

<!-- SQL modal -->
<div class="modal fade" id="sqlModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Script MySQL</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <textarea id="sql-out" class="form-control font-monospace" rows="14" readonly></textarea>
    </div>
    <div class="modal-footer">
        <button class="btn btn-outline-primary" id="copy-sql"><i class="bi bi-clipboard"></i> Copia</button>
        <button class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
    </div>
</div></div></div>
@endsection

@push('scripts')
<script>
const P = {{ $project->id }};
const ROUTES = {
    storeTable: "{{ route('db.tables.store', $project) }}",
    tableBase: "{{ url('projects/'.$project->id.'/db-structure/tables') }}",
    scriptBase: "{{ url('projects/'.$project->id.'/db-structure/script') }}",
};
const CSRF = document.querySelector('meta[name=csrf-token]').content;
let tables = {!! $tablesJson !!};
const TYPES = ['INT','BIGINT','VARCHAR','TEXT','DATE','DATETIME','DECIMAL','BOOLEAN'];

async function api(url, method, body) {
    const res = await fetch(url, { method, headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'}, body: body?JSON.stringify(body):undefined });
    if (!res.ok) throw new Error('Errore');
    return res.status === 204 ? null : res.json();
}

function render() {
    const wrap = document.getElementById('tables-wrap');
    wrap.innerHTML = '';
    if (!tables.length) { wrap.innerHTML = '<div class="col-12 text-center text-muted py-5">Nessuna tabella definita.</div>'; return; }
    tables.forEach(t => wrap.appendChild(tableCard(t)));
}

function tableCard(t) {
    const col = document.createElement('div');
    col.className = 'col-lg-6';
    col.innerHTML = `
    <div class="card h-100">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-table"></i> <strong>${esc(t.name)}</strong> <small class="text-muted">${esc(t.comment||'')}</small></span>
            <div class="d-flex gap-1">
                <button class="btn btn-sm btn-outline-dark gen">SQL</button>
                <button class="btn btn-sm btn-outline-danger delt">×</button>
            </div>
        </div>
        <div class="table-responsive"><table class="table table-sm mb-0 align-middle">
            <thead><tr><th>Nome</th><th>Tipo</th><th>Len</th><th>Null</th><th>PK</th><th>AI</th><th>Default</th><th></th></tr></thead>
            <tbody></tbody>
        </table></div>
        <div class="card-footer"><button class="btn btn-sm btn-outline-primary addc"><i class="bi bi-plus"></i> Colonna</button></div>
    </div>`;
    const tbody = col.querySelector('tbody');
    t.columns.forEach(c => tbody.appendChild(colRow(t, c)));
    col.querySelector('.gen').addEventListener('click', () => showScript(t.id));
    col.querySelector('.delt').addEventListener('click', async () => {
        if (!confirm('Eliminare la tabella?')) return;
        await api(ROUTES.tableBase + '/' + t.id, 'DELETE');
        tables = tables.filter(x => x.id !== t.id); render();
    });
    col.querySelector('.addc').addEventListener('click', async () => {
        const c = await api(ROUTES.tableBase + '/' + t.id + '/columns', 'POST', { name: 'colonna', type: 'VARCHAR', length: '255', nullable: true });
        t.columns.push(c); render();
    });
    return col;
}

function colRow(t, c) {
    const tr = document.createElement('tr');
    const typeOpts = TYPES.map(x => `<option ${x===c.type?'selected':''}>${x}</option>`).join('');
    tr.innerHTML = `
        <td><input class="form-control form-control-sm cn" value="${esc(c.name)}"></td>
        <td><select class="form-select form-select-sm ct">${typeOpts}</select></td>
        <td><input class="form-control form-control-sm cl" style="width:64px" value="${esc(c.length||'')}"></td>
        <td class="text-center"><input type="checkbox" class="cnull" ${c.nullable?'checked':''}></td>
        <td class="text-center"><input type="checkbox" class="cpk" ${c.is_primary?'checked':''}></td>
        <td class="text-center"><input type="checkbox" class="cai" ${c.auto_increment?'checked':''}></td>
        <td><input class="form-control form-control-sm cd" style="width:80px" value="${esc(c.default||'')}"></td>
        <td><button class="btn btn-sm btn-outline-danger cdel">×</button></td>`;
    const save = async () => {
        const payload = {
            name: tr.querySelector('.cn').value, type: tr.querySelector('.ct').value,
            length: tr.querySelector('.cl').value, nullable: tr.querySelector('.cnull').checked,
            is_primary: tr.querySelector('.cpk').checked, auto_increment: tr.querySelector('.cai').checked,
            default: tr.querySelector('.cd').value,
        };
        const updated = await api(ROUTES.tableBase + '/' + t.id + '/columns/' + c.id, 'PUT', payload);
        Object.assign(c, updated);
    };
    tr.querySelectorAll('input,select').forEach(el => el.addEventListener('change', save));
    tr.querySelector('.cdel').addEventListener('click', async () => {
        await api(ROUTES.tableBase + '/' + t.id + '/columns/' + c.id, 'DELETE');
        t.columns = t.columns.filter(x => x.id !== c.id); render();
    });
    return tr;
}

async function showScript(tableId) {
    const url = tableId ? ROUTES.scriptBase + '/' + tableId : ROUTES.scriptBase;
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    document.getElementById('sql-out').value = data.sql || '-- nessuna tabella --';
    new bootstrap.Modal(document.getElementById('sqlModal')).show();
}

document.getElementById('add-table').addEventListener('click', async () => {
    const name = prompt('Nome della tabella:');
    if (!name) return;
    const t = await api(ROUTES.storeTable, 'POST', { name });
    tables.push({ ...t, columns: t.columns || [] }); render();
});
document.getElementById('gen-all').addEventListener('click', () => showScript(null));
document.getElementById('copy-sql').addEventListener('click', () => {
    const ta = document.getElementById('sql-out'); ta.select(); document.execCommand('copy');
});

function esc(s){return String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
render();
</script>
@endpush
